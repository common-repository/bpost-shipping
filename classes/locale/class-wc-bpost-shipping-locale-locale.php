<?php

namespace WC_BPost_Shipping\Locale;

use WC_BPost_Shipping\Adapter\WC_BPost_Shipping_Adapter_Woocommerce;

class WC_BPost_Shipping_Locale_Locale {

	const LANGUAGE_EN = 'EN';
	const LANGUAGE_FR = 'FR';
	const LANGUAGE_NL = 'NL';
	const LANGUAGE_DEFAULT = self::LANGUAGE_EN;

	private WC_BPost_Shipping_Adapter_Woocommerce $adapter;

	public function __construct( WC_BPost_Shipping_Adapter_Woocommerce $adapter ) {
		$this->adapter = $adapter;
	}

	public function get_language(): string {
		// hack because weglot does not use get_locale()
		if ( function_exists( 'weglot_get_current_language' ) ) {
			return weglot_get_current_language();
		}
		$split_locale = explode( '_', $this->adapter->get_locale() );

		if ( count( $split_locale ) === 2 ) {
			return strtoupper( $split_locale[0] );
		}

		return self::LANGUAGE_DEFAULT;
	}
}
