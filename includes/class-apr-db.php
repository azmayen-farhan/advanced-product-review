<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class APR_DB {

	public static function table_name() {
		global $wpdb;
		return $wpdb->prefix . APR_TABLE;
	}

	public static function create_table() {
		global $wpdb;

		$table_name      = self::table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			product_id BIGINT(20) UNSIGNED NOT NULL,
			author_name VARCHAR(150) NOT NULL,
			author_email VARCHAR(150) NOT NULL,
			rating TINYINT(1) UNSIGNED NOT NULL,
			review_text TEXT NOT NULL,
			images LONGTEXT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'pending',
			ip_address VARCHAR(45) NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY product_id (product_id),
			KEY status (status)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Insert a new review. Always starts as 'pending'.
	 *
	 * @param array $data
	 * @return int|false Insert ID or false on failure.
	 */
	public static function insert_review( $data ) {
		global $wpdb;

		$now = current_time( 'mysql' );

		$inserted = $wpdb->insert(
			self::table_name(),
			array(
				'product_id'   => absint( $data['product_id'] ),
				'author_name'  => sanitize_text_field( $data['author_name'] ),
				'author_email' => sanitize_email( $data['author_email'] ),
				'rating'       => absint( $data['rating'] ),
				'review_text'  => sanitize_textarea_field( $data['review_text'] ),
				'images'       => isset( $data['images'] ) ? wp_json_encode( $data['images'] ) : '',
				'status'       => 'pending',
				'ip_address'   => isset( $data['ip_address'] ) ? sanitize_text_field( $data['ip_address'] ) : '',
				'created_at'   => $now,
				'updated_at'   => $now,
			),
			array( '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		return $inserted ? $wpdb->insert_id : false;
	}

	public static function get_review( $id ) {
		global $wpdb;
		$table = self::table_name();
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", absint( $id ) ) );
	}

	/**
	 * Fetch reviews with optional filters + pagination. Used by both the
	 * admin list table and the frontend (approved only).
	 */
	public static function get_reviews( $args = array() ) {
		global $wpdb;
		$table = self::table_name();

		$defaults = array(
			'product_id' => 0,
			'status'     => '',
			'per_page'   => 20,
			'paged'      => 1,
			'orderby'    => 'created_at',
			'order'      => 'DESC',
		);
		$args = wp_parse_args( $args, $defaults );

		$where  = array( '1=1' );
		$prepared_args = array();

		if ( ! empty( $args['product_id'] ) ) {
			$where[]        = 'product_id = %d';
			$prepared_args[] = absint( $args['product_id'] );
		}

		if ( ! empty( $args['status'] ) ) {
			$where[]        = 'status = %s';
			$prepared_args[] = sanitize_key( $args['status'] );
		}

		$where_sql = implode( ' AND ', $where );

		$allowed_orderby = array( 'created_at', 'rating', 'product_id', 'status' );
		$orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'created_at';
		$order           = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

		$per_page = max( 1, absint( $args['per_page'] ) );
		$offset   = ( max( 1, absint( $args['paged'] ) ) - 1 ) * $per_page;

		$sql = "SELECT * FROM $table WHERE $where_sql ORDER BY $orderby $order LIMIT %d OFFSET %d";
		$prepared_args[] = $per_page;
		$prepared_args[] = $offset;

		if ( ! empty( $prepared_args ) ) {
			$sql = $wpdb->prepare( $sql, $prepared_args );
		}

		return $wpdb->get_results( $sql );
	}

	public static function count_reviews( $args = array() ) {
		global $wpdb;
		$table = self::table_name();

		$defaults = array(
			'product_id' => 0,
			'status'     => '',
		);
		$args = wp_parse_args( $args, $defaults );

		$where          = array( '1=1' );
		$prepared_args  = array();

		if ( ! empty( $args['product_id'] ) ) {
			$where[]         = 'product_id = %d';
			$prepared_args[] = absint( $args['product_id'] );
		}

		if ( ! empty( $args['status'] ) ) {
			$where[]         = 'status = %s';
			$prepared_args[] = sanitize_key( $args['status'] );
		}

		$where_sql = implode( ' AND ', $where );
		$sql       = "SELECT COUNT(id) FROM $table WHERE $where_sql";

		if ( ! empty( $prepared_args ) ) {
			$sql = $wpdb->prepare( $sql, $prepared_args );
		}

		return (int) $wpdb->get_var( $sql );
	}

	public static function update_status( $id, $status ) {
		global $wpdb;

		$allowed = array( 'pending', 'approved', 'rejected' );
		if ( ! in_array( $status, $allowed, true ) ) {
			return false;
		}

		return $wpdb->update(
			self::table_name(),
			array(
				'status'     => $status,
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'id' => absint( $id ) ),
			array( '%s', '%s' ),
			array( '%d' )
		);
	}

	public static function delete_review( $id ) {
		global $wpdb;

		$review = self::get_review( $id );
		if ( $review && ! empty( $review->images ) ) {
			$images = json_decode( $review->images, true );
			if ( is_array( $images ) ) {
				foreach ( $images as $image_url ) {
					self::delete_uploaded_file( $image_url );
				}
			}
		}

		return $wpdb->delete( self::table_name(), array( 'id' => absint( $id ) ), array( '%d' ) );
	}

	private static function delete_uploaded_file( $url ) {
		$upload_dir = wp_upload_dir();
		if ( strpos( $url, $upload_dir['baseurl'] ) === 0 ) {
			$relative_path = str_replace( $upload_dir['baseurl'], '', $url );
			$file_path     = $upload_dir['basedir'] . $relative_path;
			if ( file_exists( $file_path ) ) {
				@unlink( $file_path );
			}
		}
	}

	/**
	 * Build the full stats block used by the frontend widget: average
	 * rating, total count, per-star breakdown, and "recommended" percentage.
	 *
	 * Recommended = reviews rated 4 or 5 stars.
	 */
	public static function get_stats( $product_id ) {
		global $wpdb;
		$table = self::table_name();

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT rating, COUNT(id) as cnt FROM $table WHERE product_id = %d AND status = 'approved' GROUP BY rating",
				absint( $product_id )
			)
		);

		$breakdown = array( 5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0 );
		$total     = 0;
		$sum       = 0;

		foreach ( $rows as $row ) {
			$rating = absint( $row->rating );
			$count  = absint( $row->cnt );
			if ( isset( $breakdown[ $rating ] ) ) {
				$breakdown[ $rating ] = $count;
			}
			$total += $count;
			$sum   += ( $rating * $count );
		}

		$average     = $total > 0 ? round( $sum / $total, 1 ) : 0;
		$recommended = $total > 0 ? ( $breakdown[5] + $breakdown[4] ) : 0;
		$recommended_pct = $total > 0 ? round( ( $recommended / $total ) * 100, 2 ) : 0;

		$breakdown_pct = array();
		foreach ( $breakdown as $stars => $count ) {
			$breakdown_pct[ $stars ] = array(
				'count'   => $count,
				'percent' => $total > 0 ? round( ( $count / $total ) * 100 ) : 0,
			);
		}

		return array(
			'average'          => $average,
			'total'            => $total,
			'breakdown'        => $breakdown_pct,
			'recommended'      => $recommended,
			'recommended_pct'  => $recommended_pct,
		);
	}
}
