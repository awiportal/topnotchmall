<?php
/**
 * Theme Customizer: brand colours + contact/support details.
 *
 * @package TopnotchMall
 */

declare( strict_types = 1 );

namespace TopnotchMall;

defined( 'ABSPATH' ) || exit;

/**
 * Registers Customizer settings and outputs brand CSS variables.
 */
final class Customizer {

	public function hooks(): void {
		add_action( 'customize_register', array( $this, 'register' ) );
		add_action( 'wp_head', array( $this, 'output_css_vars' ), 20 );
	}

	/**
	 * @param \WP_Customize_Manager $wp_customize Customizer manager.
	 */
	public function register( $wp_customize ): void {
		$wp_customize->add_panel( 'topnotch_panel', array( 'title' => __( 'Topnotch Mall', 'topnotch-mall' ), 'priority' => 20 ) );

		// Colours.
		$wp_customize->add_section( 'topnotch_colors', array( 'title' => __( 'Brand Colours', 'topnotch-mall' ), 'panel' => 'topnotch_panel' ) );
		$this->color( $wp_customize, 'topnotch_primary', '#0F8A44', __( 'Primary (Green)', 'topnotch-mall' ) );
		$this->color( $wp_customize, 'topnotch_navy', '#0B2A1D', __( 'Secondary (Dark Green)', 'topnotch-mall' ) );

		// Contact + support.
		$wp_customize->add_section( 'topnotch_contact', array( 'title' => __( 'Contact & Support', 'topnotch-mall' ), 'panel' => 'topnotch_panel' ) );
		$this->text( $wp_customize, 'topnotch_phone', '+254 708 777192', __( 'Phone / WhatsApp', 'topnotch-mall' ) );
		$this->text( $wp_customize, 'topnotch_email', 'info@topnotchmall.co.ke', __( 'Email', 'topnotch-mall' ) );
		$this->text( $wp_customize, 'topnotch_hours', 'Mon - Sat, 9AM - 5PM', __( 'Support Hours', 'topnotch-mall' ) );
		$this->text( $wp_customize, 'topnotch_address', 'Magomano House, Tom Mboya Street, Nairobi, Kenya', __( 'Business Address', 'topnotch-mall' ) );
		$this->text( $wp_customize, 'topnotch_whatsapp', '254708777192', __( 'WhatsApp number (intl, no +)', 'topnotch-mall' ) );
		$this->text( $wp_customize, 'topnotch_cutoff', '5:00pm', __( 'Same-day order cut-off time', 'topnotch-mall' ) );
	}

	private function color( $wp, string $id, string $default, string $label ): void {
		$wp->add_setting( $id, array( 'default' => $default, 'sanitize_callback' => 'sanitize_hex_color', 'transport' => 'postMessage' ) );
		$wp->add_control( new \WP_Customize_Color_Control( $wp, $id, array( 'label' => $label, 'section' => 'topnotch_colors' ) ) );
	}

	private function text( $wp, string $id, string $default, string $label ): void {
		$wp->add_setting( $id, array( 'default' => $default, 'sanitize_callback' => 'sanitize_text_field' ) );
		$wp->add_control( $id, array( 'label' => $label, 'section' => 'topnotch_contact', 'type' => 'text' ) );
	}

	/**
	 * Print brand colours as CSS custom properties.
	 */
	public function output_css_vars(): void {
		$green = sanitize_hex_color( (string) get_theme_mod( 'topnotch_primary', '#0F8A44' ) );
		$dark  = sanitize_hex_color( (string) get_theme_mod( 'topnotch_navy', '#0B2A1D' ) );
		printf(
			'<style id="topnotch-brand">:root{--rk-primary:%s;--rk-navy:%s}</style>' . "\n",
			esc_html( $green ),
			esc_html( $dark )
		);
	}
}
