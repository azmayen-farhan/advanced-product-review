<?php
/**
 * Frontend review widget markup.
 *
 * Expects $product_id (int) to be set by the including code.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $product_id ) ) {
	return;
}

$stats  = APR_DB::get_stats( $product_id );
$labels = apr_rating_labels();

// $settings comes from the Elementor widget's render(). Fall back to
// sensible defaults if this template is ever included another way.
$settings = isset( $settings ) ? $settings : array();
$heading_text     = ! empty( $settings['apr_heading_text'] ) ? $settings['apr_heading_text'] : 'Submit Your Review';
$note_text        = ! empty( $settings['apr_note_text'] ) ? $settings['apr_note_text'] : 'Your email address will not be published. Required fields are marked *';
$button_text      = ! empty( $settings['apr_button_text'] ) ? $settings['apr_button_text'] : 'SUBMIT REVIEW';
$show_breakdown   = ! isset( $settings['apr_show_breakdown'] ) || 'yes' === $settings['apr_show_breakdown'];
$show_recommended = ! isset( $settings['apr_show_recommended'] ) || 'yes' === $settings['apr_show_recommended'];
$show_images      = ! isset( $settings['apr_show_images'] ) || 'yes' === $settings['apr_show_images'];

/**
 * Renders a row of 5 stars, filled up to $filled.
 */
if ( ! function_exists( 'apr_render_stars' ) ) {
	function apr_render_stars( $filled, $size = 16 ) {
		$out = '<span class="apr-stars" aria-hidden="true">';
		for ( $i = 1; $i <= 5; $i++ ) {
			$class = ( $i <= $filled ) ? 'apr-star apr-star--filled' : 'apr-star apr-star--empty';
			$out  .= '<svg class="' . esc_attr( $class ) . '" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24"><path d="M12 2.5l2.9 6.28 6.6.62-5 4.53 1.5 6.57L12 17.3l-5.99 3.2L7.5 14l-5-4.53 6.6-.62L12 2.5z"/></svg>';
		}
		$out .= '</span>';
		return $out;
	}
}
?>
<div class="apr-review-widget" id="apr-widget-<?php echo esc_attr( $product_id ); ?>">

	<div class="apr-summary">

		<div class="apr-summary-score">
			<div class="apr-avg-number"><?php echo esc_html( number_format( (float) $stats['average'], 1 ) ); ?></div>
			<div class="apr-avg-meta">
				<div class="apr-avg-label">Average Rating</div>
				<div class="apr-avg-stars-row">
					<?php echo apr_render_stars( round( $stats['average'] ) ); ?>
					<span class="apr-avg-count">(<?php echo esc_html( $stats['total'] ); ?> Review<?php echo 1 === (int) $stats['total'] ? '' : 's'; ?>)</span>
				</div>
			</div>
		</div>

		<?php if ( $show_recommended ) : ?>
		<div class="apr-recommend">
			<div class="apr-recommend-percent"><?php echo esc_html( number_format( (float) $stats['recommended_pct'], 2 ) ); ?>%</div>
			<div class="apr-recommend-label">Recommended <span>(<?php echo esc_html( $stats['recommended'] ); ?> of <?php echo esc_html( $stats['total'] ); ?>)</span></div>
		</div>
		<?php endif; ?>

		<?php if ( $show_breakdown ) : ?>
		<div class="apr-breakdown">
			<?php for ( $star = 5; $star >= 1; $star-- ) : ?>
				<div class="apr-breakdown-row">
					<div class="apr-breakdown-stars"><?php echo apr_render_stars( $star, 13 ); ?></div>
					<div class="apr-breakdown-bar">
						<div class="apr-breakdown-bar-fill" style="width:<?php echo esc_attr( $stats['breakdown'][ $star ]['percent'] ); ?>%;"></div>
					</div>
					<div class="apr-breakdown-percent"><?php echo esc_html( $stats['breakdown'][ $star ]['percent'] ); ?>%</div>
				</div>
			<?php endfor; ?>
		</div>
		<?php endif; ?>

	</div>

	<div class="apr-form-wrap">

		<h3 class="apr-form-title"><?php echo esc_html( $heading_text ); ?></h3>
		<p class="apr-form-note"><?php echo esc_html( $note_text ); ?></p>

		<form id="apr-review-form-<?php echo esc_attr( $product_id ); ?>" class="apr-review-form" novalidate>

			<input type="hidden" name="product_id" value="<?php echo esc_attr( $product_id ); ?>">
			<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'apr_submit_review' ) ); ?>">
			<!-- Honeypot, kept off-screen for real users -->
			<div class="apr-hp-field" aria-hidden="true">
				<label>Leave this field empty</label>
				<input type="text" name="apr_hp" tabindex="-1" autocomplete="off">
			</div>

			<?php if ( ! is_user_logged_in() ) : ?>
				<div class="apr-field-row apr-field-row--split">
					<div class="apr-field">
						<label for="apr-name-<?php echo esc_attr( $product_id ); ?>">Name <span class="apr-required">*</span></label>
						<input type="text" id="apr-name-<?php echo esc_attr( $product_id ); ?>" name="author_name" required>
					</div>
					<div class="apr-field">
						<label for="apr-email-<?php echo esc_attr( $product_id ); ?>">Email <span class="apr-required">*</span></label>
						<input type="email" id="apr-email-<?php echo esc_attr( $product_id ); ?>" name="author_email" required>
					</div>
				</div>
			<?php endif; ?>

			<div class="apr-field">
				<label for="apr-text-<?php echo esc_attr( $product_id ); ?>">Write your opinion about the product</label>
				<textarea id="apr-text-<?php echo esc_attr( $product_id ); ?>" name="review_text" rows="5" placeholder="Write Your Review Here..." required></textarea>
			</div>

			<?php if ( $show_images ) : ?>
			<div class="apr-field">
				<label>Upload Images (Optional)</label>
				<div class="apr-dropzone" tabindex="0" role="button" aria-label="Upload images">
					<input type="file" class="apr-file-input" name="review_images[]" accept="image/png,image/jpeg,image/gif,image/webp" multiple hidden>
					<span class="apr-dropzone-icon-badge">
						<svg width="20" height="20" viewBox="0 0 24 24"><path d="M19.35 10.04A7.49 7.49 0 0012 4C9.11 4 6.6 5.64 5.35 8.04A5.994 5.994 0 000 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM14 13v4h-4v-4H7l5-5 5 5h-3z"/></svg>
					</span>
					<div class="apr-dropzone-text">Drag &amp; Drop Images Here</div>
					<div class="apr-dropzone-subtext">or click to browse files ( 3 max )</div>
				</div>
				<div class="apr-preview-list"></div>
			</div>
			<?php endif; ?>

			<div class="apr-field-row apr-rating-row">
				<label for="apr-rating-<?php echo esc_attr( $product_id ); ?>">Your Rating:</label>
				<select id="apr-rating-<?php echo esc_attr( $product_id ); ?>" name="rating" required>
					<option value="">Select One</option>
					<?php foreach ( $labels as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="apr-form-footer">
				<div class="apr-form-message" role="status"></div>
				<button type="submit" class="apr-submit-btn"><?php echo esc_html( $button_text ); ?></button>
			</div>

		</form>
	</div>

</div>
