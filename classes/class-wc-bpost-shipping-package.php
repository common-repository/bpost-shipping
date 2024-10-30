<?php

use WC_BPost_Shipping\Api\Exception\WC_BPost_Shipping_Api_Exception_Country_Not_Allowed;
use WC_BPost_Shipping\Api\Exception\WC_BPost_Shipping_Api_Exception_Weight_Not_Allowed;
use WC_BPost_Shipping\Api\WC_BPost_Shipping_Api_Product_Configuration;
use WC_BPost_Shipping\Options\WC_BPost_Shipping_Options_Base;

/**
 * Class WC_BPost_Shipping_Package computes the shipping price for a given package
 */
class WC_BPost_Shipping_Package {

	private WC_BPost_Shipping_Api_Product_Configuration $product_configuration;
	private WC_BPost_Shipping_Options_Base $options;
	private array $package;


	public function __construct(
		WC_BPost_Shipping_Api_Product_Configuration $product_configuration,
		WC_BPost_Shipping_Options_Base $options,
		array $package
	) {
		$this->product_configuration = $product_configuration;
		$this->options               = $options;
		$this->package               = $package;
	}

	/**
	 * @return int Euro-cents
	 * @throws WC_BPost_Shipping_Api_Exception_Country_Not_Allowed
	 */
	public function calculate_shipping() {
		$country_iso_2 = $this->package['destination']['country'];

		if ( ! $this->product_configuration->is_bpost_country( $country_iso_2 ) ) {
			throw new WC_BPost_Shipping_Api_Exception_Country_Not_Allowed();
		}

		// original unit to grams: provides data to \Bpost\BpostApiClient\Bpost\ProductConfiguration\Price::getPriceByWeight;
		$cart_weight_grams = (int) wc_get_weight( $this->get_weight_from_package(), 'g' );

		if ( ! $this->product_configuration->is_valid_weight( $cart_weight_grams ) ) {
			throw new WC_BPost_Shipping_Api_Exception_Weight_Not_Allowed();
		}

		if ( $this->options->is_free_shipping(
			$country_iso_2,
			WC_BPost_Shipping_Cart::get_discounted_subtotal(),
			WC_BPost_Shipping_Cart::get_used_coupons()
		) ) {
			return 0;
		}

		return $this->get_minimum_shipping_cost_from_weight( $country_iso_2, $cart_weight_grams );
	}

	/**
	 * @param string $country_iso_2
	 *
	 * @return int Euro-cents
	 * @throws \Bpost\BpostApiClient\Exception
	 */
	private function get_minimum_shipping_cost_from_weight( $country_iso_2, $cart_weight_grams ) {
		return $this->product_configuration->get_minimum_shipping_cost_from_weight( $country_iso_2, $cart_weight_grams );
	}

	/**
	 * @return float
	 */
	private function get_weight_from_package() {
		$cart_weight = 0;

		if ( isset( $this->package['contents'] ) ) {
			/** @var array $content */
			foreach ( $this->package['contents'] as $content ) {
				$cart_weight += $this->get_weight_from_product( $content );
			}
		}

		return $cart_weight;
	}

	/**
	 * @param $content
	 *
	 * @return float
	 */
	private function get_weight_from_product( $content ) {
		if ( isset( $content['data'] ) ) {
			/** @var WC_Product_Simple $data */
			$data = $content['data'];

			$weight = (float) $data->get_weight();

			if ( isset( $content['quantity'] ) ) {
				$weight *= $content['quantity'];
			}

			return $weight;
		}

		return 0.0;
	}
}
