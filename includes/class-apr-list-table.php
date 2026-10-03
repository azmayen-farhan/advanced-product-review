<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class APR_List_Table extends WP_List_Table {

	public $status_filter = '';

	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'review',
				'plural'   => 'reviews',
				'ajax'     => false,
			)
		);
	}

	public function get_columns() {
		return array(
			'cb'          => '<input type="checkbox" />',
			'product'     => 'Product',
			'author'      => 'Author',
			'rating'      => 'Rating',
			'review_text' => 'Review',
			'images'      => 'Images',
			'created_at'  => 'Date',
			'status'      => 'Status',
		);
	}

	protected function get_sortable_columns() {
		return array(
			'created_at' => array( 'created_at', true ),
			'rating'     => array( 'rating', false ),
		);
	}

	protected function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="review_ids[]" value="%d" />', $item->id );
	}

	protected function column_default( $item, $column_name ) {
		return isset( $item->$column_name ) ? esc_html( $item->$column_name ) : '';
	}

	protected function get_default_primary_column_name() {
		return 'product';
	}

	protected function column_product( $item ) {
		$product = function_exists( 'wc_get_product' ) ? wc_get_product( $item->product_id ) : false;
		$title   = $product ? $product->get_name() : '(deleted product #' . $item->product_id . ')';
		$edit_link = get_edit_post_link( $item->product_id );

		$name_html = $edit_link ? '<a href="' . esc_url( $edit_link ) . '" target="_blank">' . esc_html( $title ) . '</a>' : esc_html( $title );

		return '<strong>' . $name_html . '</strong>' . $this->row_actions( $this->build_row_actions( $item ) );
	}

	private function build_row_actions( $item ) {
		$base    = admin_url( 'admin.php?page=apr-reviews' );
		$actions = array();

		if ( 'approved' !== $item->status ) {
			$url = wp_nonce_url( add_query_arg( array( 'apr_action' => 'approve', 'id' => $item->id ), $base ), 'apr_row_action_' . $item->id );
			$actions['approve'] = '<a href="' . esc_url( $url ) . '" style="color:#1a7a3c;">Approve</a>';
		}

		if ( 'rejected' !== $item->status ) {
			$url = wp_nonce_url( add_query_arg( array( 'apr_action' => 'reject', 'id' => $item->id ), $base ), 'apr_row_action_' . $item->id );
			$actions['reject'] = '<a href="' . esc_url( $url ) . '" style="color:#a02222;">Reject</a>';
		}

		$delete_url = wp_nonce_url( add_query_arg( array( 'apr_action' => 'delete', 'id' => $item->id ), $base ), 'apr_row_action_' . $item->id );
		$actions['delete'] = '<a href="' . esc_url( $delete_url ) . '" onclick="return confirm(\'Delete this review permanently?\');" style="color:#a02222;">Delete</a>';

		return $actions;
	}

	protected function column_author( $item ) {
		return esc_html( $item->author_name ) . '<br><span style="color:#777;">' . esc_html( $item->author_email ) . '</span>';
	}

	protected function column_rating( $item ) {
		$labels = apr_rating_labels();
		$label  = isset( $labels[ $item->rating ] ) ? $labels[ $item->rating ] : '';
		$stars  = str_repeat( '&#9733;', (int) $item->rating ) . str_repeat( '&#9734;', 5 - (int) $item->rating );
		return '<span style="color:#f2a33d;letter-spacing:1px;">' . $stars . '</span><br><span style="font-size:12px;color:#777;">' . esc_html( $label ) . '</span>';
	}

	protected function column_review_text( $item ) {
		$text = $item->review_text;
		if ( strlen( $text ) > 160 ) {
			$text = substr( $text, 0, 160 ) . '&hellip;';
		} else {
			$text = esc_html( $text );
		}
		return '<div style="max-width:320px;">' . $text . '</div>';
	}

	protected function column_images( $item ) {
		$images = json_decode( $item->images, true );
		if ( empty( $images ) || ! is_array( $images ) ) {
			return '&mdash;';
		}

		$out = '<div style="display:flex;gap:4px;flex-wrap:wrap;">';
		foreach ( $images as $url ) {
			$out .= '<a href="' . esc_url( $url ) . '" target="_blank"><img src="' . esc_url( $url ) . '" style="width:40px;height:40px;object-fit:cover;border-radius:3px;border:1px solid #ddd;"></a>';
		}
		$out .= '</div>';
		return $out;
	}

	protected function column_created_at( $item ) {
		return esc_html( mysql2date( 'M j, Y g:ia', $item->created_at ) );
	}

	protected function column_status( $item ) {
		$map = array(
			'pending'  => array( '#8a6d1f', '#fff6df', 'Pending' ),
			'approved' => array( '#1a7a3c', '#e7f6ec', 'Approved' ),
			'rejected' => array( '#a02222', '#fbe9e9', 'Rejected' ),
		);
		$info = isset( $map[ $item->status ] ) ? $map[ $item->status ] : array( '#555', '#eee', ucfirst( $item->status ) );

		return '<span style="display:inline-block;padding:3px 10px;border-radius:12px;font-size:11px;font-weight:600;color:' . esc_attr( $info[0] ) . ';background:' . esc_attr( $info[1] ) . ';">' . esc_html( $info[2] ) . '</span>';
	}

	protected function get_bulk_actions() {
		return array(
			'approve' => 'Approve',
			'reject'  => 'Reject',
			'delete'  => 'Delete',
		);
	}

	protected function extra_tablenav( $which ) {
		if ( 'top' !== $which ) {
			return;
		}

		$current = isset( $_GET['review_status'] ) ? sanitize_key( $_GET['review_status'] ) : '';
		$base_url = admin_url( 'admin.php?page=apr-reviews' );

		$tabs = array(
			''         => 'All',
			'pending'  => 'Pending',
			'approved' => 'Approved',
			'rejected' => 'Rejected',
		);

		echo '<div class="alignleft actions apr-status-tabs">';
		foreach ( $tabs as $key => $label ) {
			$url = $key ? add_query_arg( 'review_status', $key, $base_url ) : $base_url;
			$count = APR_DB::count_reviews( array( 'status' => $key ) );
			$class = ( $current === $key ) ? 'button button-primary' : 'button';
			echo '<a href="' . esc_url( $url ) . '" class="' . esc_attr( $class ) . '" style="margin-right:6px;">' . esc_html( $label ) . ' (' . esc_html( $count ) . ')</a>';
		}
		echo '</div>';
	}

	public function prepare_items() {
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->status_filter = isset( $_GET['review_status'] ) ? sanitize_key( $_GET['review_status'] ) : '';

		$per_page     = 20;
		$current_page = $this->get_pagenum();

		$orderby = isset( $_GET['orderby'] ) ? sanitize_key( $_GET['orderby'] ) : 'created_at';
		$order   = isset( $_GET['order'] ) ? sanitize_key( $_GET['order'] ) : 'desc';

		$total_items = APR_DB::count_reviews( array( 'status' => $this->status_filter ) );

		$this->items = APR_DB::get_reviews(
			array(
				'status'   => $this->status_filter,
				'per_page' => $per_page,
				'paged'    => $current_page,
				'orderby'  => $orderby,
				'order'    => $order,
			)
		);

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			)
		);
	}
}
