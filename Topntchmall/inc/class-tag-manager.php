<?php
/**
 * Google Tag Manager container and noscript fallback.
 *
 * ORDERING: hooks wp_head at priority 3, AFTER Cookie_Consent (1) and
 * Analytics (2). Google's install instructions say to paste the container
 * "as high in the head as possible". On a site with a consent layer that is
 * wrong: the container would load before the Consent Mode v2 denied defaults
 * and start firing as though consent were granted, which is the exact leak the
 * banner exists to prevent. The deviation is deliberate. Do not revert it.
 *
 * TAG OWNERSHIP: never re-create inside this container a tag the theme already
 * fires. The theme owns GA4 (see class-analytics.php) and the WhatsApp click
 * conversion (see class-whatsapp-tracking.php). Either the theme owns a tag or
 * the container does, never both, or every hit is counted twice.
 *
 * @package TopnotchMall
 */

declare( strict_types = 1 );

namespace TopnotchMall;

defined( 'ABSPATH' ) || exit;

/**
 * Emits the GTM container and its noscript iframe.
 */
final class Tag_Manager {

	public function hooks(): void {
		add_action( 'wp_head', array( $this, 'container' ), 3 );
		add_action( 'wp_body_open', array( $this, 'noscript' ), 1 );
	}

	/**
	 * GTM container ID. Returning an empty string disables the feature.
	 */
	public function container_id(): string {
		$id = (string) get_theme_mod( 'topnotch_gtm_id', 'GTM-THGMLKN2' );
		return trim( (string) apply_filters( 'topnotch_gtm_id', $id ) );
	}

	/**
	 * True when the current visitor should not be measured.
	 */
	public function excluded(): bool {
		$exclude_admins = (bool) apply_filters( 'topnotch_analytics_exclude_admins', true );
		if ( true === $exclude_admins ) {
			if ( current_user_can( 'manage_options' ) ) {
				return true;
			}
		}
		return (bool) apply_filters( 'topnotch_analytics_excluded', false );
	}

	/**
	 * Container snippet.
	 */
	public function container(): void {
		$id = $this->container_id();
		if ( '' === $id ) {
			return;
		}
		if ( true === $this->excluded() ) {
			return;
		}
		?>
<script id="topnotch-gtm">
(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});
var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=(l==='dataLayer')?'':'&l='+l;
j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer',<?php echo wp_json_encode( $id ); ?>);
</script>
		<?php
	}

	/**
	 * The noscript iframe must come immediately after the opening body tag,
	 * which is why this hangs off wp_body_open at priority 1.
	 */
	public function noscript(): void {
		$id = $this->container_id();
		if ( '' === $id ) {
			return;
		}
		if ( true === $this->excluded() ) {
			return;
		}
		printf(
			'<noscript id="topnotch-gtm-noscript"><iframe src="https://www.googletagmanager.com/ns.html?id=%s" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>' . "\n",
			esc_attr( $id )
		);
	}
}
