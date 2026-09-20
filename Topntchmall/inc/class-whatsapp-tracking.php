<?php
/**
 * WhatsApp click conversion.
 *
 * Fires a Google Ads conversion when a visitor clicks any wa.me link.
 * The listener is DELEGATED on document, so links inserted later by AJAX
 * (cart drawer, quick views, search suggestions) are covered too.
 *
 * TAG OWNERSHIP: the theme owns this conversion. Do not also build a
 * wa.me click trigger inside the GTM container, or every chat order is
 * counted twice.
 *
 * REPORTING: register this conversion action as SECONDARY in Google Ads.
 * Chat orders are then visible in reporting without steering bidding,
 * which must stay on completed purchases only.
 *
 * @package TopnotchMall
 */

declare( strict_types = 1 );

namespace TopnotchMall;

defined( 'ABSPATH' ) || exit;

/**
 * Emits the WhatsApp click conversion listener.
 */
final class Whatsapp_Tracking {

	public function hooks(): void {
		add_action( 'wp_footer', array( $this, 'listener' ), 20 );
	}

	/**
	 * The Google Ads send_to value, in AW-XXXXXXXXX/LabelHere form.
	 * Returning an empty string disables the feature.
	 */
	public function send_to(): string {
		$ads   = trim( (string) get_theme_mod( 'topnotch_ads_id', '' ) );
		$label = trim( (string) get_theme_mod( 'topnotch_ads_whatsapp_label', '' ) );  // Blank until the Ads conversion action exists.
		$value = '';
		if ( '' === $ads ) {
			$value = '';
		} elseif ( '' === $label ) {
			$value = '';
		} else {
			$value = $ads . '/' . $label;
		}
		return trim( (string) apply_filters( 'topnotch_whatsapp_conversion_send_to', $value ) );
	}

	/**
	 * Delegated click listener with a short dedupe window.
	 */
	public function listener(): void {
		$send_to = $this->send_to();
		if ( '' === $send_to ) {
			return;
		}
		$window = (int) apply_filters( 'topnotch_whatsapp_dedupe_ms', 2000 );
		?>
<script id="topnotch-wa-conversion">
(function(){
	var last = 0;
	var WINDOW_MS = <?php echo (int) $window; ?>;
	document.addEventListener('click', function(ev){
		var link = ev.target.closest('a[href*="wa.me"]');
		if (link === null) { return; }
		if (typeof window.gtag === 'undefined') { return; }
		var now = Date.now();
		if ((now - last) < WINDOW_MS) { return; }
		last = now;
		window.gtag('event', 'conversion', {
			'send_to': <?php echo wp_json_encode( $send_to ); ?>
		});
	}, true);
})();
</script>
		<?php
	}
}
