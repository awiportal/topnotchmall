<?php
/**
 * Consent Mode v2 defaults, region-scoped, plus the cookie banner.
 *
 * ORDERING IS A COMPLIANCE CONTROL, NOT A COSMETIC CHOICE.
 * This module hooks wp_head at priority 1 so the defaults reach the
 * dataLayer BEFORE any Google tag loads. Analytics runs at 2, Tag Manager at 3.
 * Google's own GTM install text says to paste the container "as high in the head
 * as possible"; followed literally on a site with a consent layer that is
 * actively harmful, because the container would begin firing as though consent
 * were granted. We honour that instruction as "as high as is CORRECT".
 * Do not helpfully move this later or move GTM earlier.
 *
 * REGION SCOPING IS ALSO A COMPLIANCE CONTROL.
 * The denied defaults are scoped to the EEA, the UK and Switzerland, where
 * denied-by-default is required. A second, unscoped default grants storage
 * everywhere else, including Kenya, which is this shop's actual market.
 * Google's documented precedence: a region-specific default wins for the
 * regions it lists, and the unscoped default is the fallback for all other
 * users. The region-specific call MUST be emitted first. Do not "simplify"
 * these two calls into one, and do not remove the region key: doing so
 * denies ads and analytics storage for every Kenyan visitor who has not
 * pressed Accept, which silently disables conversion measurement, holds
 * Performance Max at Eligible (Limited) and reports a 0% consent rate.
 *
 * Relevant regime: Kenya Data Protection Act 2019, which requires notice and
 * a genuine ability to object. The banner below provides both, and the Reject
 * button is honoured on this and every subsequent page load. The Act does not
 * require denied-by-default telemetry for Kenyan visitors.
 *
 * @package TopnotchMall
 */

declare( strict_types = 1 );

namespace TopnotchMall;

defined( 'ABSPATH' ) || exit;

/**
 * Writes Consent Mode v2 defaults and renders the consent banner.
 */
final class Cookie_Consent {

	/**
	 * Regions that must default to denied: EEA member states, plus the UK and
	 * Switzerland. Kenya is deliberately absent.
	 */
	private const DENIED_REGIONS = array(
		'AT',
		'BE',
		'BG',
		'HR',
		'CY',
		'CZ',
		'DK',
		'EE',
		'FI',
		'FR',
		'DE',
		'GR',
		'HU',
		'IS',
		'IE',
		'IT',
		'LV',
		'LI',
		'LT',
		'LU',
		'MT',
		'NL',
		'NO',
		'PL',
		'PT',
		'RO',
		'SK',
		'SI',
		'ES',
		'SE',
		'GB',
		'CH',
	);

	public function hooks(): void {
		add_action( 'wp_head', array( $this, 'defaults' ), 1 );
		add_action( 'wp_footer', array( $this, 'banner' ), 5 );
	}

	/**
	 * True when the consent layer is switched on. Returning false from the
	 * filter disables the banner AND the defaults together.
	 */
	public function enabled(): bool {
		return (bool) apply_filters( 'topnotch_consent_enabled', true );
	}

	/**
	 * Region codes that default to denied.
	 *
	 * @return array<int,string>
	 */
	private function denied_regions(): array {
		$regions = (array) apply_filters( 'topnotch_consent_denied_regions', self::DENIED_REGIONS );

		return array_values( array_filter( array_map( 'strval', $regions ) ) );
	}

	/**
	 * Consent Mode v2 defaults. Must be the first Google-related code
	 * in the document. Marker id is used by live-HTML byte-offset verification.
	 */
	public function defaults(): void {
		if ( false === $this->enabled() ) {
			return;
		}
		$wait    = (int) apply_filters( 'topnotch_consent_wait_for_update', 500 );
		$regions = wp_json_encode( $this->denied_regions() );
		if ( false === $regions ) {
			$regions = '[]';
		}
		?>
<script id="topnotch-consent-default">
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
/* EEA, UK and Switzerland: denied until the visitor opts in. */
gtag('consent', 'default', {
	'ad_storage': 'denied',
	'ad_user_data': 'denied',
	'ad_personalization': 'denied',
	'analytics_storage': 'denied',
	'functionality_storage': 'denied',
	'personalization_storage': 'denied',
	'security_storage': 'granted',
	'region': <?php echo $regions; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_json_encode output. ?>,
	'wait_for_update': <?php echo (int) $wait; ?>

});
/* Everywhere else, including Kenya: granted, with Reject honoured below. */
gtag('consent', 'default', {
	'ad_storage': 'granted',
	'ad_user_data': 'granted',
	'ad_personalization': 'granted',
	'analytics_storage': 'granted',
	'functionality_storage': 'granted',
	'personalization_storage': 'granted',
	'security_storage': 'granted'
});
gtag('set', 'ads_data_redaction', true);
gtag('set', 'url_passthrough', true);
(function(){
	try {
		var v = window.localStorage.getItem('topnotch_consent');
		if (v === 'granted') {
			gtag('consent', 'update', {
				'ad_storage': 'granted',
				'ad_user_data': 'granted',
				'ad_personalization': 'granted',
				'analytics_storage': 'granted',
				'functionality_storage': 'granted',
				'personalization_storage': 'granted'
			});
		} else if (v === 'denied') {
			/* Required: outside the denied regions the default is granted, so a
			   stored Reject must be reapplied on every subsequent page load. */
			gtag('consent', 'update', {
				'ad_storage': 'denied',
				'ad_user_data': 'denied',
				'ad_personalization': 'denied',
				'analytics_storage': 'denied',
				'functionality_storage': 'denied',
				'personalization_storage': 'denied'
			});
		}
	} catch (e) {}
})();
</script>
		<?php
	}

	/**
	 * Banner markup. Rendered only when the visitor has made no choice yet.
	 */
	public function banner(): void {
		if ( false === $this->enabled() ) {
			return;
		}
		$policy = $this->policy_url();
		?>
<div id="topnotch-consent" class="topnotch-consent" hidden role="dialog" aria-live="polite" aria-label="<?php esc_attr_e( 'Cookie choices', 'topnotch-mall' ); ?>">
	<div class="topnotch-consent__inner">
		<p class="topnotch-consent__text">
			<?php esc_html_e( 'We use cookies to run this shop, measure how it is used and improve our advertising. You can accept or reject cookies that are not needed to run the site.', 'topnotch-mall' ); ?>
			<?php if ( '' === $policy ) : ?>
			<?php else : ?>
				<a href="<?php echo esc_url( $policy ); ?>"><?php esc_html_e( 'Read our Cookie Policy', 'topnotch-mall' ); ?></a>
			<?php endif; ?>
		</p>
		<div class="topnotch-consent__actions">
			<button type="button" class="rk-btn rk-btn--ghost" data-topnotch-consent="denied"><?php esc_html_e( 'Reject', 'topnotch-mall' ); ?></button>
			<button type="button" class="rk-btn rk-btn--primary" data-topnotch-consent="granted"><?php esc_html_e( 'Accept', 'topnotch-mall' ); ?></button>
		</div>
	</div>
</div>
<script id="topnotch-consent-ui">
(function(){
	var box = document.getElementById('topnotch-consent');
	if (box === null) { return; }
	var stored = null;
	try { stored = window.localStorage.getItem('topnotch_consent'); } catch (e) {}
	if (stored === null) { box.hidden = false; }
	function choose(state){
		try { window.localStorage.setItem('topnotch_consent', state); } catch (e) {}
		if (typeof window.gtag === 'function') {
			var granted = (state === 'granted');
			window.gtag('consent', 'update', {
				'ad_storage': granted ? 'granted' : 'denied',
				'ad_user_data': granted ? 'granted' : 'denied',
				'ad_personalization': granted ? 'granted' : 'denied',
				'analytics_storage': granted ? 'granted' : 'denied',
				'functionality_storage': granted ? 'granted' : 'denied',
				'personalization_storage': granted ? 'granted' : 'denied'
			});
		}
		box.hidden = true;
	}
	box.addEventListener('click', function(ev){
		var btn = ev.target.closest('[data-topnotch-consent]');
		if (btn === null) { return; }
		choose(btn.getAttribute('data-topnotch-consent'));
	});
})();
</script>
		<?php
	}

	/**
	 * Resolve the cookie policy page URL, empty string when none exists.
	 */
	private function policy_url(): string {
		$page = get_page_by_path( 'cookie-policy' );
		if ( $page instanceof \WP_Post ) {
			return (string) get_permalink( $page );
		}
		return (string) apply_filters( 'topnotch_consent_policy_url', '' );
	}
}
