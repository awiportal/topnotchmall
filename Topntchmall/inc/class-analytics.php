<?php
/**
 * GA4 configuration tag.
 *
 * Hooks wp_head at priority 2, AFTER Cookie_Consent writes the Consent Mode v2
 * denied defaults at priority 1, so this tag inherits the consent state.
 * Do not move this earlier.
 *
 * DOUBLE-COUNTING GUARD: if Site Kit (or any plugin) is connected to Analytics
 * it injects its own GA4 tag. Two tags double every session and event and
 * nothing warns you. This module stands itself down when a plugin owns GA4.
 * Exactly one owner per tag, never both.
 *
 * @package TopnotchMall
 */

declare( strict_types = 1 );

namespace TopnotchMall;

defined( 'ABSPATH' ) || exit;

/**
 * Emits the GA4 config tag.
 */
final class Analytics {

	public function hooks(): void {
		add_action( 'wp_head', array( $this, 'tag' ), 2 );
	}

	/**
	 * GA4 measurement ID. Returning an empty string disables the feature.
	 */
	public function measurement_id(): string {
		$id = (string) get_theme_mod( 'topnotch_ga4_id', 'G-WD7ZVQZ0SD' );
		return trim( (string) apply_filters( 'topnotch_ga4_id', $id ) );
	}

	/**
	 * True when a plugin already owns the GA4 tag on this site.
	 */
	public function plugin_owns_ga4(): bool {
		$settings = get_option( 'googlesitekit_analytics-4_settings' );
		if ( is_array( $settings ) ) {
			$mid = isset( $settings['measurementID'] ) ? (string) $settings['measurementID'] : '';
			if ( '' === trim( $mid ) ) {
				$mid = isset( $settings['webDataStreamID'] ) ? (string) $settings['webDataStreamID'] : '';
			}
			if ( '' === trim( $mid ) ) {
				$owns = false;
			} else {
				$owns = true;
			}
			if ( true === $owns ) {
				return (bool) apply_filters( 'topnotch_plugin_owns_ga4', true );
			}
		}
		return (bool) apply_filters( 'topnotch_plugin_owns_ga4', false );
	}

	/**
	 * True when the current visitor should not be measured.
	 *
	 * TRAP: with this on, a logged-in administrator sees nothing in GA4
	 * Realtime and concludes the tag is broken. It is not. Test the tag
	 * logged out, in a private window.
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
	 * Render the GA4 config tag.
	 */
	public function tag(): void {
		$id = $this->measurement_id();
		if ( '' === $id ) {
			return;
		}
		if ( true === $this->plugin_owns_ga4() ) {
			return;
		}
		if ( true === $this->excluded() ) {
			return;
		}
		$ads = trim( (string) apply_filters( 'topnotch_ads_id', (string) get_theme_mod( 'topnotch_ads_id', '' ) ) );
		?>
<script id="topnotch-ga4-src" async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr( $id ); ?>"></script>
<script id="topnotch-ga4">
window.dataLayer = window.dataLayer || [];
if (typeof window.gtag === 'undefined') { window.gtag = function(){dataLayer.push(arguments);}; }
gtag('js', new Date());
gtag('config', <?php echo wp_json_encode( $id ); ?>, { 'anonymize_ip': true });
<?php if ( '' === $ads ) : ?>
<?php else : ?>
gtag('config', <?php echo wp_json_encode( $ads ); ?>);
<?php endif; ?>
</script>
		<?php
	}
}
