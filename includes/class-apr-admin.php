<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class APR_Admin {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'handle_actions' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	public function register_menu() {
		$pending_count = APR_DB::count_reviews( array( 'status' => 'pending' ) );
		$badge         = $pending_count > 0 ? ' <span class="awaiting-mod count-' . absint( $pending_count ) . '"><span class="pending-count">' . absint( $pending_count ) . '</span></span>' : '';

		add_menu_page(
			'Product Reviews',
			'Product Reviews' . $badge,
			'manage_woocommerce',
			'apr-reviews',
			array( $this, 'render_page' ),
			'dashicons-star-half',
			56
		);
	}

	public function enqueue_assets( $hook ) {
		if ( strpos( $hook, 'apr-reviews' ) === false ) {
			return;
		}
		wp_enqueue_style( 'apr-admin', APR_URL . 'assets/css/admin.css', array(), APR_VERSION );
	}

	/**
	 * Handles single row actions (?apr_action=approve&id=X) and bulk actions
	 * submitted from the list table form. Runs on admin_init so we can
	 * redirect cleanly before any output is sent.
	 */
	public function handle_actions() {
		if ( ! isset( $_GET['page'] ) || 'apr-reviews' !== $_GET['page'] ) {
			return;
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		// Single row action.
		if ( isset( $_GET['apr_action'], $_GET['id'] ) ) {
			$id     = absint( $_GET['id'] );
			$action = sanitize_key( $_GET['apr_action'] );

			check_admin_referer( 'apr_row_action_' . $id );

			$this->run_action( $action, array( $id ) );

			wp_safe_redirect( remove_query_arg( array( 'apr_action', 'id', '_wpnonce' ) ) );
			exit;
		}

		// Bulk action (top or bottom submit button).
		$bulk_action = false;
		if ( isset( $_REQUEST['action'] ) && '-1' !== $_REQUEST['action'] ) {
			$bulk_action = sanitize_key( $_REQUEST['action'] );
		} elseif ( isset( $_REQUEST['action2'] ) && '-1' !== $_REQUEST['action2'] ) {
			$bulk_action = sanitize_key( $_REQUEST['action2'] );
		}

		if ( $bulk_action && ! empty( $_REQUEST['review_ids'] ) && is_array( $_REQUEST['review_ids'] ) ) {
			check_admin_referer( 'bulk-reviews' );

			$ids = array_map( 'absint', $_REQUEST['review_ids'] );
			$this->run_action( $bulk_action, $ids );

			wp_safe_redirect( remove_query_arg( array( '_wpnonce', '_wp_http_referer', 'action', 'action2', 'review_ids' ) ) );
			exit;
		}
	}

	private function run_action( $action, $ids ) {
		foreach ( $ids as $id ) {
			switch ( $action ) {
				case 'approve':
					APR_DB::update_status( $id, 'approved' );
					break;
				case 'reject':
					APR_DB::update_status( $id, 'rejected' );
					break;
				case 'delete':
					APR_DB::delete_review( $id );
					break;
			}
		}
	}

	public function render_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( 'You do not have permission to access this page.' );
		}

		$list_table = new APR_List_Table();
		$list_table->prepare_items();

		$total_all      = APR_DB::count_reviews();
		$total_pending  = APR_DB::count_reviews( array( 'status' => 'pending' ) );
		$total_approved = APR_DB::count_reviews( array( 'status' => 'approved' ) );
		?>
		<div class="wrap apr-admin-wrap">
			<h1 class="wp-heading-inline">Product Reviews</h1>
			<hr class="wp-header-end">

			<div class="apr-stat-cards">
				<div class="apr-stat-card">
					<div class="apr-stat-number"><?php echo esc_html( $total_all ); ?></div>
					<div class="apr-stat-label">Total Reviews</div>
				</div>
				<div class="apr-stat-card apr-stat-card--pending">
					<div class="apr-stat-number"><?php echo esc_html( $total_pending ); ?></div>
					<div class="apr-stat-label">Awaiting Approval</div>
				</div>
				<div class="apr-stat-card apr-stat-card--approved">
					<div class="apr-stat-number"><?php echo esc_html( $total_approved ); ?></div>
					<div class="apr-stat-label">Live on Site</div>
				</div>
			</div>

			<form method="post">
				<?php $list_table->display(); ?>
			</form>
		</div>
		<?php
	}
}
