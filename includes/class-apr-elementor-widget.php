<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class APR_Elementor_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'advanced-product-review';
	}

	public function get_title() {
		return 'Product Reviews';
	}

	public function get_icon() {
		return 'eicon-star';
	}

	public function get_categories() {
		return array( 'advanced-product-review' );
	}

	public function get_keywords() {
		return array( 'review', 'rating', 'woocommerce', 'product' );
	}

	/**
	 * Tells Elementor which registered style/script handles this widget
	 * needs. Elementor enqueues these automatically wherever the widget is
	 * used — normal pages, Theme Builder templates, and inside the editor
	 * preview iframe — so we don't have to guess with is_singular() checks.
	 */
	public function get_style_depends() {
		return array( 'apr-frontend' );
	}

	public function get_script_depends() {
		return array( 'apr-frontend' );
	}

	protected function register_controls() {
		$this->register_content_controls();
		$this->register_summary_style_controls();
		$this->register_form_heading_style_controls();
		$this->register_form_fields_style_controls();
		$this->register_dropzone_style_controls();
		$this->register_button_style_controls();
	}

	/* ==================== CONTENT TAB ==================== */

	private function register_content_controls() {

		$this->start_controls_section(
			'apr_section_source',
			array(
				'label' => 'Settings',
			)
		);

		$this->add_control(
			'apr_source',
			array(
				'label'       => 'Product',
				'type'        => \Elementor\Controls_Manager::SELECT,
				'default'     => 'current',
				'options'     => array(
					'current' => 'Current Product (dynamic)',
				),
				'description' => 'Drop this widget into your Single Product template — it automatically shows reviews for whichever product is being viewed.',
			)
		);

		$this->add_control(
			'apr_heading_text',
			array(
				'label'   => 'Form Heading',
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'Submit Your Review',
			)
		);

		$this->add_control(
			'apr_note_text',
			array(
				'label'   => 'Note Text',
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'Your email address will not be published. Required fields are marked *',
			)
		);

		$this->add_control(
			'apr_button_text',
			array(
				'label'   => 'Button Text',
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'SUBMIT REVIEW',
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'apr_section_visibility',
			array(
				'label' => 'Visibility',
			)
		);

		$this->add_control(
			'apr_show_breakdown',
			array(
				'label'     => 'Show Star Breakdown',
				'type'      => \Elementor\Controls_Manager::SWITCHER,
				'label_on'  => 'Show',
				'label_off' => 'Hide',
				'default'   => 'yes',
			)
		);

		$this->add_control(
			'apr_show_recommended',
			array(
				'label'     => 'Show Recommended %',
				'type'      => \Elementor\Controls_Manager::SWITCHER,
				'label_on'  => 'Show',
				'label_off' => 'Hide',
				'default'   => 'yes',
			)
		);

		$this->add_control(
			'apr_show_images',
			array(
				'label'     => 'Show Image Upload',
				'type'      => \Elementor\Controls_Manager::SWITCHER,
				'label_on'  => 'Show',
				'label_off' => 'Hide',
				'default'   => 'yes',
			)
		);

		$this->end_controls_section();
	}

	/* ==================== STYLE: SUMMARY (left column) ==================== */

	private function register_summary_style_controls() {

		$this->start_controls_section(
			'apr_section_style_summary',
			array(
				'label' => 'Summary',
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'apr_star_filled_color',
			array(
				'label'     => 'Star Color (Filled)',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#f2a33d',
				'selectors' => array(
					'{{WRAPPER}} .apr-review-widget' => '--apr-accent: {{VALUE}}; --apr-star-filled: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'apr_star_empty_color',
			array(
				'label'     => 'Star Color (Empty)',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#d9d9d9',
				'selectors' => array(
					'{{WRAPPER}} .apr-review-widget' => '--apr-star-empty: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'apr_avg_number_typography',
				'label'    => 'Average Number Typography',
				'selector' => '{{WRAPPER}} .apr-avg-number',
			)
		);

		$this->add_control(
			'apr_avg_number_color',
			array(
				'label'     => 'Average Number Color',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#222222',
				'selectors' => array(
					'{{WRAPPER}} .apr-review-widget' => '--apr-summary-number: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'apr_summary_text_color',
			array(
				'label'       => 'Summary Text Color',
				'type'        => \Elementor\Controls_Manager::COLOR,
				'default'     => '#6b6b6b',
				'description' => 'Applies to "Average Rating", review count, and star breakdown percentages.',
				'selectors'   => array(
					'{{WRAPPER}} .apr-review-widget' => '--apr-summary-text: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'apr_recommend_color',
			array(
				'label'     => 'Recommended % Color',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#222222',
				'selectors' => array(
					'{{WRAPPER}} .apr-review-widget' => '--apr-recommend-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'apr_heading_bar_track',
			array(
				'label'     => 'Progress Bar Track Color',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ececec',
				'selectors' => array(
					'{{WRAPPER}} .apr-review-widget' => '--apr-bar-track: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'apr_heading_bar_fill',
			array(
				'label'     => 'Progress Bar Fill Color',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#f2a33d',
				'selectors' => array(
					'{{WRAPPER}} .apr-review-widget' => '--apr-bar-fill: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/* ==================== STYLE: FORM HEADING ==================== */

	private function register_form_heading_style_controls() {

		$this->start_controls_section(
			'apr_section_style_heading',
			array(
				'label' => 'Form Heading',
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'apr_heading_typography',
				'label'    => 'Heading Typography',
				'selector' => '{{WRAPPER}} .apr-form-title',
			)
		);

		$this->add_control(
			'apr_heading_color',
			array(
				'label'     => 'Heading Color',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#222222',
				'selectors' => array(
					'{{WRAPPER}} .apr-form-title' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'apr_underline_color',
			array(
				'label'     => 'Underline Color',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#f2a33d',
				'selectors' => array(
					'{{WRAPPER}} .apr-form-title::after' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'apr_underline_width',
			array(
				'label'     => 'Underline Width',
				'type'      => \Elementor\Controls_Manager::SLIDER,
				'range'     => array(
					'px' => array(
						'min' => 10,
						'max' => 200,
					),
				),
				'default'   => array(
					'unit' => 'px',
					'size' => 46,
				),
				'selectors' => array(
					'{{WRAPPER}} .apr-form-title::after' => 'width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'apr_note_color',
			array(
				'label'     => 'Note Text Color',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#6b6b6b',
				'selectors' => array(
					'{{WRAPPER}} .apr-form-note' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/* ==================== STYLE: FORM FIELDS ==================== */

	private function register_form_fields_style_controls() {

		$this->start_controls_section(
			'apr_section_style_fields',
			array(
				'label' => 'Form Fields',
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'apr_label_typography',
				'label'    => 'Label Typography',
				'selector' => '{{WRAPPER}} .apr-field label, {{WRAPPER}} .apr-rating-row label',
			)
		);

		$this->add_control(
			'apr_label_color',
			array(
				'label'     => 'Label Color',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#222222',
				'selectors' => array(
					'{{WRAPPER}} .apr-field label, {{WRAPPER}} .apr-rating-row label' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'apr_input_text_color',
			array(
				'label'     => 'Input Text Color',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#222222',
				'selectors' => array(
					'{{WRAPPER}} .apr-field input, {{WRAPPER}} .apr-field textarea, {{WRAPPER}} .apr-rating-row select' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'apr_input_bg_color',
			array(
				'label'     => 'Input Background Color',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .apr-field input, {{WRAPPER}} .apr-field textarea, {{WRAPPER}} .apr-rating-row select' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'apr_input_border_color',
			array(
				'label'     => 'Input Border Color',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#e2e2e2',
				'selectors' => array(
					'{{WRAPPER}} .apr-field input, {{WRAPPER}} .apr-field textarea, {{WRAPPER}} .apr-rating-row select' => 'border-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'apr_input_focus_border_color',
			array(
				'label'     => 'Input Focus Border Color',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#f2a33d',
				'selectors' => array(
					'{{WRAPPER}} .apr-field input:focus, {{WRAPPER}} .apr-field textarea:focus, {{WRAPPER}} .apr-rating-row select:focus' => 'border-color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'apr_input_radius',
			array(
				'label'     => 'Input Border Radius',
				'type'      => \Elementor\Controls_Manager::SLIDER,
				'range'     => array(
					'px' => array(
						'min' => 0,
						'max' => 30,
					),
				),
				'default'   => array(
					'unit' => 'px',
					'size' => 4,
				),
				'selectors' => array(
					'{{WRAPPER}} .apr-field input, {{WRAPPER}} .apr-field textarea, {{WRAPPER}} .apr-rating-row select' => 'border-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/* ==================== STYLE: UPLOAD BOX ==================== */

	private function register_dropzone_style_controls() {

		$this->start_controls_section(
			'apr_section_style_dropzone',
			array(
				'label' => 'Upload Box',
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'apr_dropzone_bg',
			array(
				'label'     => 'Background Color',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#fafafa',
				'selectors' => array(
					'{{WRAPPER}} .apr-dropzone' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'apr_dropzone_border',
			array(
				'label'     => 'Border Color',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#e2e2e2',
				'selectors' => array(
					'{{WRAPPER}} .apr-dropzone' => 'border-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'apr_dropzone_icon_bg',
			array(
				'label'     => 'Icon Background Color',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#2f8fe0',
				'selectors' => array(
					'{{WRAPPER}} .apr-dropzone-icon-badge' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'apr_dropzone_icon_color',
			array(
				'label'     => 'Icon Color',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .apr-dropzone-icon-badge svg' => 'fill: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'apr_dropzone_text_color',
			array(
				'label'     => 'Text Color',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#222222',
				'selectors' => array(
					'{{WRAPPER}} .apr-dropzone-text' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'apr_dropzone_subtext_color',
			array(
				'label'     => 'Subtext Color',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#6b6b6b',
				'selectors' => array(
					'{{WRAPPER}} .apr-dropzone-subtext' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/* ==================== STYLE: SUBMIT BUTTON ==================== */

	private function register_button_style_controls() {

		$this->start_controls_section(
			'apr_section_style_button',
			array(
				'label' => 'Submit Button',
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'apr_button_typography',
				'label'    => 'Typography',
				'selector' => '{{WRAPPER}} .apr-submit-btn',
			)
		);

		$this->add_responsive_control(
			'apr_button_radius',
			array(
				'label'     => 'Border Radius',
				'type'      => \Elementor\Controls_Manager::SLIDER,
				'range'     => array(
					'px' => array(
						'min' => 0,
						'max' => 50,
					),
				),
				'default'   => array(
					'unit' => 'px',
					'size' => 2,
				),
				'selectors' => array(
					'{{WRAPPER}} .apr-submit-btn' => 'border-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'apr_button_padding',
			array(
				'label'      => 'Padding',
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'default'    => array(
					'top'      => 15,
					'right'    => 32,
					'bottom'   => 15,
					'left'     => 32,
					'unit'     => 'px',
					'isLinked' => false,
				),
				'selectors'  => array(
					'{{WRAPPER}} .apr-submit-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->start_controls_tabs( 'apr_button_tabs' );

		$this->start_controls_tab(
			'apr_button_tab_normal',
			array( 'label' => 'Normal' )
		);

		$this->add_control(
			'apr_button_text_color',
			array(
				'label'     => 'Text Color',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .apr-submit-btn' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'apr_button_bg_color',
			array(
				'label'     => 'Background Color',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#282828',
				'selectors' => array(
					'{{WRAPPER}} .apr-submit-btn' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'apr_button_tab_hover',
			array( 'label' => 'Hover' )
		);

		$this->add_control(
			'apr_button_text_color_hover',
			array(
				'label'     => 'Text Color',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .apr-submit-btn:hover' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'apr_button_bg_color_hover',
			array(
				'label'     => 'Background Color',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#000000',
				'selectors' => array(
					'{{WRAPPER}} .apr-submit-btn:hover' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	protected function render() {
		global $product;

		$settings   = $this->get_settings_for_display();
		$product_id = 0;

		if ( $product instanceof WC_Product ) {
			$product_id = $product->get_id();
		} elseif ( is_singular( 'product' ) ) {
			$product_id = get_the_ID();
		}

		if ( ! $product_id && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			// Editor preview fallback: grab the most recent product so the widget isn't empty in the builder.
			$recent = get_posts(
				array(
					'post_type'      => 'product',
					'posts_per_page' => 1,
					'fields'         => 'ids',
				)
			);
			if ( ! empty( $recent ) ) {
				$product_id = $recent[0];
			}
		}

		if ( ! $product_id ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div style="padding:20px;text-align:center;background:#f5f5f5;color:#666;">Product Reviews widget — add at least one WooCommerce product to preview this widget.</div>';
			}
			return;
		}

		include APR_PATH . 'templates/widget-template.php';
	}
}
