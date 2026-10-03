<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class APR_Ajax {

	const MAX_IMAGES  = 3;
	const MAX_SIZE_MB = 2;

	public function __construct() {
		add_action( 'wp_ajax_apr_submit_review', array( $this, 'handle_submit_review' ) );
		add_action( 'wp_ajax_nopriv_apr_submit_review', array( $this, 'handle_submit_review' ) );
	}

	public function handle_submit_review() {
		check_ajax_referer( 'apr_submit_review', 'nonce' );

		// Honeypot: bots tend to fill every field, real users never see this one.
		if ( ! empty( $_POST['apr_hp'] ) ) {
			wp_send_json_success( array( 'message' => 'Thank you for your review!' ) ); // Fail silently.
		}

		$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		$rating     = isset( $_POST['rating'] ) ? absint( $_POST['rating'] ) : 0;
		$review_text = isset( $_POST['review_text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['review_text'] ) ) : '';

		if ( ! $product_id || 'product' !== get_post_type( $product_id ) ) {
			wp_send_json_error( array( 'message' => 'Invalid product.' ) );
		}

		if ( $rating < 1 || $rating > 5 ) {
			wp_send_json_error( array( 'message' => 'Please select a rating.' ) );
		}

		if ( empty( $review_text ) ) {
			wp_send_json_error( array( 'message' => 'Please write your review.' ) );
		}

		// Author identity: logged-in users use their account, guests must supply name + email.
		if ( is_user_logged_in() ) {
			$user         = wp_get_current_user();
			$author_name  = $user->display_name;
			$author_email = $user->user_email;
		} else {
			$author_name  = isset( $_POST['author_name'] ) ? sanitize_text_field( wp_unslash( $_POST['author_name'] ) ) : '';
			$author_email = isset( $_POST['author_email'] ) ? sanitize_email( wp_unslash( $_POST['author_email'] ) ) : '';

			if ( empty( $author_name ) ) {
				wp_send_json_error( array( 'message' => 'Please enter your name.' ) );
			}

			if ( empty( $author_email ) || ! is_email( $author_email ) ) {
				wp_send_json_error( array( 'message' => 'Please enter a valid email address.' ) );
			}
		}

		$image_urls = array();

		if ( ! empty( $_FILES['review_images'] ) && ! empty( $_FILES['review_images']['name'][0] ) ) {
			$image_result = $this->handle_image_uploads( $_FILES['review_images'] );
			if ( is_wp_error( $image_result ) ) {
				wp_send_json_error( array( 'message' => $image_result->get_error_message() ) );
			}
			$image_urls = $image_result;
		}

		$insert_id = APR_DB::insert_review(
			array(
				'product_id'   => $product_id,
				'author_name'  => $author_name,
				'author_email' => $author_email,
				'rating'       => $rating,
				'review_text'  => $review_text,
				'images'       => $image_urls,
				'ip_address'   => $this->get_client_ip(),
			)
		);

		if ( ! $insert_id ) {
			wp_send_json_error( array( 'message' => 'Could not save your review. Please try again.' ) );
		}

		wp_send_json_success(
			array(
				'message' => 'Thanks! Your review has been submitted and is awaiting approval.',
			)
		);
	}

	/**
	 * Validate and move uploaded images into the uploads directory.
	 *
	 * @param array $files The $_FILES['review_images'] array (multi-file format).
	 * @return array|WP_Error Array of image URLs, or WP_Error on validation failure.
	 */
	private function handle_image_uploads( $files ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';

		$allowed_types = array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' );
		$max_bytes     = self::MAX_SIZE_MB * 1024 * 1024;
		$count         = count( $files['name'] );

		if ( $count > self::MAX_IMAGES ) {
			return new WP_Error( 'too_many_images', 'You can upload a maximum of ' . self::MAX_IMAGES . ' images.' );
		}

		$urls = array();

		for ( $i = 0; $i < $count; $i++ ) {
			if ( empty( $files['name'][ $i ] ) ) {
				continue;
			}

			if ( $files['error'][ $i ] !== UPLOAD_ERR_OK ) {
				return new WP_Error( 'upload_error', 'There was a problem uploading one of your images.' );
			}

			if ( $files['size'][ $i ] > $max_bytes ) {
				return new WP_Error( 'file_too_large', 'Each image must be smaller than ' . self::MAX_SIZE_MB . 'MB.' );
			}

			$file_type = wp_check_filetype( $files['name'][ $i ] );
			if ( empty( $file_type['type'] ) || ! in_array( $file_type['type'], $allowed_types, true ) ) {
				return new WP_Error( 'invalid_type', 'Only JPG, PNG, GIF, or WEBP images are allowed.' );
			}

			$single_file = array(
				'name'     => $files['name'][ $i ],
				'type'     => $files['type'][ $i ],
				'tmp_name' => $files['tmp_name'][ $i ],
				'error'    => $files['error'][ $i ],
				'size'     => $files['size'][ $i ],
			);

			$upload = wp_handle_upload( $single_file, array( 'test_form' => false ) );

			if ( isset( $upload['error'] ) ) {
				return new WP_Error( 'upload_failed', 'One of your images could not be uploaded.' );
			}

			$urls[] = $upload['url'];
		}

		return $urls;
	}

	private function get_client_ip() {
		if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ips = explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );
			return trim( $ips[0] );
		}
		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}
		return '';
	}
}
