<?php
/**
 * Instantiate theme modules on load (each guarded).
 *
 * @package TopnotchMall
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

require TOPNOTCH_DIR . 'inc/helpers.php';

$topnotch_modules = array(
	'TopnotchMall\\Setup',
	'TopnotchMall\\Assets',
	'TopnotchMall\\Security',
	'TopnotchMall\\Brand_Guard',
	'TopnotchMall\\Brand_Migration',
	'TopnotchMall\\SEO',
	'TopnotchMall\\WooCommerce_Support',
	'TopnotchMall\\Ajax',
	'TopnotchMall\\Customizer',
	'TopnotchMall\\Schema',
	'TopnotchMall\\Content_Installer',
	'TopnotchMall\\Demo_Import',
	'TopnotchMall\\Single_Product',
	'TopnotchMall\\Merchant_Inspector',
	// Tracking stack. wp_head priority ordering is a compliance control:
	// Cookie_Consent writes Consent Mode v2 denied defaults at priority 1,
	// Analytics at 2 and Tag_Manager at 3 both inherit that consent state.
	// See class-cookie-consent.php before changing any of this.
	'TopnotchMall\\Cookie_Consent',
	'TopnotchMall\\Analytics',
	'TopnotchMall\\Tag_Manager',
	'TopnotchMall\\Whatsapp_Tracking',
	'TopnotchMall\\Google_Customer_Reviews',
);

foreach ( $topnotch_modules as $topnotch_class ) {
	try {
		if ( class_exists( $topnotch_class ) ) {
			( new $topnotch_class() )->hooks();
		}
	} catch ( \Throwable $e ) {
		error_log( 'Topnotch Mall module ' . $topnotch_class . ' failed: ' . $e->getMessage() );
	}
}

require TOPNOTCH_DIR . 'inc/required-plugins.php';
