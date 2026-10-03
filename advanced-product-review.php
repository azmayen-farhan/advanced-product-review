<?php
/**
 * Plugin Name: Advanced Product Review
 * Plugin URI:  https://azmayenfarhan.com
 * Description: Custom product review widget for Elementor + WooCommerce single product pages, with an admin moderation queue (pending / approved / rejected).
 * Version:     1.0.0
 * Author:      Azmayen Farhan
 * Text Domain: advanced-product-review
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

define( 'APR_VERSION', '1.0.0' );
define( 'APR_PATH', plugin_dir_path( __FILE__ ) );
define( 'APR_URL', plugin_dir_url( __FILE__ ) );
define( 'APR_TABLE', 'apr_reviews' );

/**
 * Rating value => label map. Shared by frontend form, admin list, and stats.
 */
function apr_rating_labels() {
	return array(
		5 => 'Perfect',
		4 => 'Good',
		3 => 'Average',
		2 => 'Not that bad',
		1 => 'Very poor',
	);
}

require_once APR_PATH . 'includes/class-apr-db.php';
require_once APR_PATH . 'includes/class-apr-ajax.php';
require_once APR_PATH . 'includes/class-apr-admin.php';
require_once APR_PATH . 'includes/class-apr-list-table.php';
// Note: class-apr-elementor-widget.php is intentionally NOT required here.
// It extends \Elementor\Widget_Base, so it must only be loaded once we know
// Elementor is fully booted — see apr_register_elementor_widget() below.

register_activation_hook( __FILE__, array( 'APR_DB', 'create_table' ) );

/**
 * Boot everything once plugins are loaded (so WooCommerce/Elementor are available).
 */
function apr_init() {
	new APR_Ajax();
	new APR_Admin();

	// Elementor widget registration.
	add_action( 'elementor/widgets/register', 'apr_register_elementor_widget' );
	add_action( 'elementor/elements/categories_registered', 'apr_register_elementor_category' );
}
add_action( 'plugins_loaded', 'apr_init' );

/**
 * Friendly heads-up in wp-admin if a required plugin is missing. Never
 * fatals the site — just lets the user know the widget won't show up.
 */
function apr_dependency_notice() {
	$missing = array();

	if ( ! class_exists( 'WooCommerce' ) ) {
		$missing[] = 'WooCommerce';
	}
	if ( ! did_action( 'elementor/loaded' ) ) {
		$missing[] = 'Elementor';
	}

	if ( empty( $missing ) ) {
		return;
	}

	echo '<div class="notice notice-warning"><p><strong>Advanced Product Review</strong> requires ' . esc_html( implode( ' and ', $missing ) ) . ' to be installed and active. The admin review queue will still work, but the Elementor widget won\'t be available until then.</p></div>';
}
add_action( 'admin_notices', 'apr_dependency_notice' );

function apr_register_elementor_category( $elements_manager ) {
	$elements_manager->add_category(
		'advanced-product-review',
		array(
			'title' => 'Advanced Product Review',
			'icon'  => 'fa fa-plug',
		)
	);
}

function apr_register_elementor_widget( $widgets_manager ) {
	// This hook only fires once Elementor itself is fully loaded, so it's
	// safe to require the widget class (which extends \Elementor\Widget_Base)
	// here rather than at plugin bootstrap time.
	require_once APR_PATH . 'includes/class-apr-elementor-widget.php';
	$widgets_manager->register( new APR_Elementor_Widget() );
}

/**
 * Register (not enqueue) frontend assets. We only register them here —
 * the Elementor widget declares them via get_style_depends() /
 * get_script_depends(), which makes Elementor enqueue them wherever the
 * widget actually appears: normal pages, Theme Builder templates, AND
 * inside the Elementor editor preview iframe. This is more reliable than
 * an is_singular('product') check, which fails when editing a Single
 * Product *template* (not a literal product post).
 */
function apr_register_assets() {
	wp_register_style( 'apr-frontend', APR_URL . 'assets/css/frontend.css', array(), APR_VERSION );
	wp_register_script( 'apr-frontend', APR_URL . 'assets/js/frontend.js', array(), APR_VERSION, true );

	wp_localize_script(
		'apr-frontend',
		'aprData',
		array(
			'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
			'maxImages' => 3,
			'maxSizeMB' => 2,
			'i18n'      => array(
				'selectRating'  => 'Please select a rating.',
				'writeReview'   => 'Please write your review.',
				'tooManyImages' => 'You can upload a maximum of 3 images.',
				'imageTooBig'   => 'Each image must be smaller than 2MB.',
				'invalidType'   => 'Only JPG, PNG, GIF, or WEBP images are allowed.',
				'submitting'    => 'Submitting...',
				'genericError'  => 'Something went wrong. Please try again.',
			),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'apr_register_assets' );
