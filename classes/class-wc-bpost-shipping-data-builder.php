<?php

use WC_BPost_Shipping\Locale\WC_BPost_Shipping_Locale_Locale;
use WC_BPost_Shipping\Options\WC_BPost_Shipping_Options_Base;
use WC_BPost_Shipping\WC_Bpost_Shipping_Container as Container;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class WC_BPost_Shipping_Data_Builder provides data to inject to js shm.
 * TODO: some restructuring can be clean this class
 */
class WC_BPost_Shipping_Data_Builder {
	private WC_BPost_Shipping_Options_Base $shipping_options;
	private WC_BPost_Shipping_Address $shipping_address;
	private WC_BPost_Shipping_Delivery_Methods $delivery_methods;

	private array $shm_supported_languages = array(
		WC_BPost_Shipping_Locale_Locale::LANGUAGE_EN,
		WC_BPost_Shipping_Locale_Locale::LANGUAGE_FR,
		WC_BPost_Shipping_Locale_Locale::LANGUAGE_NL,
	);
	private WC_BPost_Shipping_Logger $logger;

	public function __construct(
		WC_BPost_Shipping_Address $shipping_address,
		WC_BPost_Shipping_Options_Base $shipping_options,
		WC_BPost_Shipping_Delivery_Methods $delivery_methods
	) {
		$this->shipping_options = $shipping_options;
		$this->shipping_address = $shipping_address;
		$this->delivery_methods = $delivery_methods;
		$this->logger           = Container::get_logger();
	}

	/**
	 * Various bpost data needed
	 * @return string[]
	 */
	public function get_bpost_data(): array {

		// Build data to inject
		$order_reference = uniqid();

		$callback_url  = WC()->api_request_url( 'shm-callback' );
		$callback_url .= strpos( $callback_url, '?' ) === false ? '?' : '&';
		$callback_url .= 'result=';

		$bpost_data = array(
			'account_id'                => $this->shipping_options->get_account_id(),
			'order_reference'           => $order_reference,
			'callback_url'              => $callback_url,
			// Euro-cents
			'sub_total'                 => round( WC()->cart->get_subtotal() * 100 ),
			// In grams, if 0, then we set 1kg (1000g)
			'sub_weight'                => ceil( WC_BPost_Shipping_Cart::get_weight_in_g() ?: 1000 ),
			'language'                  => $this->get_language_for_shm(),
			'additional_customer_ref'   => 'WORDPRESS ' . get_bloginfo( 'version' ) . ' / WOOCOMMERCE ' . WC()->version,
			'delivery_method_overrides' => $this->shipping_options->get_delivery_method_overrides(
				$this->shipping_address,
				$this->delivery_methods
			),
			'extra'                     => $this->get_extra_json(),
		);

		$bpost_data['hash'] = $this->shipping_options->get_hash(
			$bpost_data,
			$this->shipping_address->get_shipping_country()
		);

		return $bpost_data;
	}

	private function get_extra_json(): string {
		if ( $this->shipping_address->get_shipping_state() ) {
			return json_encode(
				array( 'customerState' => $this->shipping_address->get_shipping_state() )
			);
		}

		return '';
	}

	private function get_language_for_shm(): string {
		$locale = new WC_BPost_Shipping_Locale_Locale( Container::get_adapter() );

		$language = $locale->get_language();

		if ( in_array( $language, $this->shm_supported_languages, true ) ) {
			return strtoupper( $language );
		} else {
			$this->logger->warning( "Unsupported language '$language', falling back to '" . WC_BPost_Shipping_Locale_Locale::LANGUAGE_DEFAULT . "'" );
		}

		return WC_BPost_Shipping_Locale_Locale::LANGUAGE_DEFAULT;
	}

	/**
	 * Shipping address to pre-fill shm form
	 * @return string[]
	 */
	public function get_shipping_address(): array {
		$shipping_address = array(
			'first_name'   => $this->shipping_address->get_first_name(),
			'last_name'    => $this->shipping_address->get_last_name(),
			'company'      => $this->shipping_address->get_company(),
			'post_code'    => $this->shipping_address->get_shipping_postcode(),
			'city'         => $this->shipping_address->get_shipping_city(),
			'country_code' => $this->shipping_address->get_shipping_country(),
			'email'        => $this->shipping_address->get_email(),
			'phone_number' => $this->shipping_address->get_phone(),
		);

		$street_data = $this->shipping_address->get_street_items();

		$shipping_address['address']       = $street_data->get_street();
		$shipping_address['street_number'] = $street_data->get_number();
		$shipping_address['street_box']    = $street_data->get_box();

		return $shipping_address;
	}
}
