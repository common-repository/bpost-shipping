<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class WC_BPost_Shipping_Cart allows to pass some data through classes.
 * TODO: should be removed or split into more coherent structure
 */
class WC_BPost_Shipping_Cart {
	public static function get_weight_in_g(): float {
		return self::get_weight_in_kg() * 1000;
	}

	public static function get_weight_in_kg(): float {
		return (float) wc_get_weight( WC()->cart->get_cart_contents_weight(), 'kg' );
	}

	public static function get_discounted_subtotal(): float {
		return WC()->cart->get_displayed_subtotal() - WC()->cart->get_discount_total();
	}

	public static function get_used_coupons(): array {
		return WC()->cart->get_applied_coupons();
	}
}
