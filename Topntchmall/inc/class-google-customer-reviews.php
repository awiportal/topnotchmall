<?php
/**
 * Google Customer Reviews: opt-in survey and seller badge.
 *
 * The opt-in survey renders on the order-received page ONLY. The seller
 * badge renders sitewide. A reputation signal is one of the five items on
 * Google's Misrepresentation checklist, which is why this exists.
 *
 * NOTE for testing: an empty cart redirects checkout back to the cart, so an
 * is_checkout() guard can look untested. Verify on a real order-received URL.
 *
 * @package TopnotchMall
 */

declare( strict_types = 1 );

namespace TopnotchMall;

defined( 'ABSPATH' ) || exit;

/**
 * Emits the Google Customer Reviews opt-in and badge.
 */
final class Google_Customer_Reviews {

	public function hooks(): void {
		add_action( 'woocommerce_thankyou', array( $this, 'opt_in' ), 30 );
		add_action( 'wp_footer', array( $this, 'badge' ), 30 );
	}

	/**
	 * Merchant Center account ID. Returning an empty string disables the feature.
	 */
	public function merchant_id(): string {
		$id = (string) get_theme_mod( 'topnotch_merchant_id', '' );
		return trim( (string) apply_filters( 'topnotch_gcr_merchant_id', $id ) );
	}

	/**
	 * Working days to add for the delivery estimate. Counted against the real
	 * fulfilment window: dispatch 0 to 1 working day, delivery up to 7.
	 */
	public function delivery_days(): int {
		return (int) apply_filters( 'topnotch_gcr_delivery_days', 7 );
	}

	/**
	 * Add working days to a timestamp, skipping Sundays.
	 *
	 * @param int $start Unix timestamp to count from.
	 * @param int $days  Working days to add.
	 */
	private function add_working_days( int $start, int $days ): int {
		$cursor = $start;
		$added  = 0;
		while ( $added < $days ) {
			$cursor += DAY_IN_SECONDS;
			if ( 'Sun' === gmdate( 'D', $cursor ) ) {
				continue;
			}
			$added++;
		}
		return $cursor;
	}

	/**
	 * Opt-in survey, order-received page only.
	 *
	 * @param int $order_id Received order ID.
	 */
	public function opt_in( $order_id ): void {
		$merchant = $this->merchant_id();
		if ( '' === $merchant ) {
			return;
		}
		$order = $order_id ? wc_get_order( $order_id ) : false;
		if ( false === ( $order instanceof \WC_Order ) ) {
			return;
		}
		$email    = (string) $order->get_billing_email();
		$country  = (string) $order->get_shipping_country();
		if ( '' === $country ) {
			$country = (string) $order->get_billing_country();
		}
		if ( '' === $country ) {
			$country = 'KE';
		}
		$estimate = gmdate( 'Y-m-d', $this->add_working_days( time(), $this->delivery_days() ) );
		?>
<script src="https://apis.google.com/js/platform.js?onload=renderOptIn" async defer></script>
<script id="topnotch-gcr-optin">
window.renderOptIn = function() {
	window.gapi.load('surveyoptin', function() {
		window.gapi.surveyoptin.render({
			"merchant_id": <?php echo wp_json_encode( $merchant ); ?>,
			"order_id": <?php echo wp_json_encode( (string) $order->get_order_number() ); ?>,
			"email": <?php echo wp_json_encode( $email ); ?>,
			"delivery_country": <?php echo wp_json_encode( $country ); ?>,
			"estimated_delivery_date": <?php echo wp_json_encode( $estimate ); ?>
		});
	});
};
</script>
		<?php
	}

	/**
	 * Seller badge, sitewide.
	 */
	public function badge(): void {
		$merchant = $this->merchant_id();
		if ( '' === $merchant ) {
			return;
		}
		if ( is_order_received_page() ) {
			return;
		}
		$position = (string) apply_filters( 'topnotch_gcr_badge_position', 'BOTTOM_RIGHT' );
		?>
<div id="topnotch-gcr-badge"></div>
<script src="https://apis.google.com/js/platform.js?onload=renderBadge" async defer></script>
<script id="topnotch-gcr-badge-js">
window.renderBadge = function() {
	var container = document.getElementById('topnotch-gcr-badge');
	if (container === null) { return; }
	window.gapi.load('ratingbadge', function() {
		window.gapi.ratingbadge.render(container, {
			"merchant_id": <?php echo wp_json_encode( $merchant ); ?>,
			"position": <?php echo wp_json_encode( $position ); ?>
		});
	});
};
</script>
		<?php
	}
}
