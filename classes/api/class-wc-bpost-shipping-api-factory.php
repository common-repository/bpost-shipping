<?php

namespace WC_BPost_Shipping\Api;

use WC_BPost_Shipping\WC_Bpost_Shipping_Container;
use WC_BPost_Shipping_Logger;

/**
 * Class WC_BPost_Shipping_Product_Configuration_Factory creates an instance of WC_BPost_Shipping_Product_Configuration ^^
 */
class WC_BPost_Shipping_Api_Factory {

	private WC_BPost_Shipping_Logger $logger;

	public function __construct( WC_BPost_Shipping_Logger $logger ) {
		$this->logger = $logger;
	}

	public function get_product_configuration(): WC_BPost_Shipping_Api_Product_Configuration {
		return new WC_BPost_Shipping_Api_Product_Configuration(
			WC_Bpost_Shipping_Container::get_api_connector(),
			$this->logger
		);
	}

	public function get_label(): WC_BPost_Shipping_Api_Label {
		return new WC_BPost_Shipping_Api_Label( WC_Bpost_Shipping_Container::get_api_connector(), $this->logger );
	}

	public function get_geo6_search(): WC_BPost_Shipping_Api_Geo6_Search {
		return new WC_BPost_Shipping_Api_Geo6_Search( $this->get_api_geo6_connector() );
	}

	public function get_api_status(): WC_BPost_Shipping_Api_Status {
		return new WC_BPost_Shipping_Api_Status( WC_Bpost_Shipping_Container::get_api_connector(), $this->logger );
	}

	public function get_api_geo6_connector(): WC_BPost_Shipping_Api_Geo6_Connector {
		return new WC_BPost_Shipping_Api_Geo6_Connector( '999999', 'A001' );
	}
}
