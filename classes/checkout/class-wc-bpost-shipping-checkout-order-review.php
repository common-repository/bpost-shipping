<?php

namespace WC_BPost_Shipping\Checkout;


use Exception;
use WC_BPost_Shipping\Adapter\WC_BPost_Shipping_Adapter_Woocommerce;
use WC_BPost_Shipping\Street\WC_BPost_Shipping_Street_Builder;
use WC_BPost_Shipping\Street\WC_BPost_Shipping_Street_Solver;
use WC_BPost_Shipping\WC_Bpost_Shipping_Container as Container;
use WC_BPost_Shipping_Cart;
use WC_BPost_Shipping_Limitations;
use WC_BPost_Shipping_Posted;

class WC_BPost_Shipping_Checkout_Order_Review {

	private WC_BPost_Shipping_Adapter_Woocommerce $adapter_woocommerce;
	private WC_BPost_Shipping_Limitations $limitations;

	public function __construct(
		WC_BPost_Shipping_Adapter_Woocommerce $adapter_woocommerce,
		WC_BPost_Shipping_Limitations $limitations
	) {
		$this->adapter_woocommerce = $adapter_woocommerce;
		$this->limitations         = $limitations;
	}

	public function review_order( WC_BPost_Shipping_Posted $posted ): bool {
		$street_builder = new WC_BPost_Shipping_Street_Builder( new WC_BPost_Shipping_Street_Solver() );

		$street = $street_builder
			->get_street_items( WC()->customer->get_shipping_address(), WC()->customer->get_shipping_address_2() )
			->get_street();

		$limitation_are_ok = $this->limitations->validate_limitations(
			$posted->get_payment_method(),
			$street,
			WC_BPost_Shipping_Cart::get_weight_in_kg()
		);

		foreach ( $this->limitations->get_errors() as $error ) {
			Container::get_logger()->warning( $error );
			throw new Exception( $error ); // throw an exception will provide a wp_notice(..., 'error')

//			$this->adapter_woocommerce->add_notice( $error, 'error' );
		}

		return $limitation_are_ok;
	}
}
