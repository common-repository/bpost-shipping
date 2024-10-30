<?php

namespace WC_BPost_Shipping;

use LogicException;
use WC_BPost_Shipping\Adapter\WC_BPost_Shipping_Adapter_Woocommerce as Adapter;
use WC_BPost_Shipping\Api\WC_BPost_Shipping_Api_Connector;
use WC_BPost_Shipping\Api\WC_BPost_Shipping_Api_Factory;
use WC_BPost_Shipping\Api\WC_BPost_Shipping_Api_Product_Configuration;
use WC_BPost_Shipping\Assets\WC_BPost_Shipping_Assets_Detector;
use WC_BPost_Shipping\Assets\WC_BPost_Shipping_Assets_Management;
use WC_BPost_Shipping\Assets\WC_BPost_Shipping_Assets_Resources;
use WC_BPost_Shipping\Checkout\WC_BPost_Shipping_Checkout_Order_Review;
use WC_BPost_Shipping\Label\WC_BPost_Shipping_Label_Path_Resolver;
use WC_BPost_Shipping\Label\WC_BPost_Shipping_Label_Retriever;
use WC_BPost_Shipping\Label\WC_BPost_Shipping_Label_Url_Generator;
use WC_BPost_Shipping\Options\WC_BPost_Shipping_Options_Base;
use WC_BPost_Shipping\Options\WC_BPost_Shipping_Options_Label;
use WC_BPost_Shipping_Limitations;
use WC_BPost_Shipping_Logger;
use WC_BPost_Shipping_Meta_Type;

class WC_Bpost_Shipping_Container {
	private static array $objects = [];

	private static function get( $class_name ) {
		if ( ! array_key_exists( $class_name, self::$objects ) ) {
			self::$objects[ $class_name ] = self::get_class_instance( $class_name );
		}

		return self::$objects[ $class_name ];
	}

	private static function get_class_instance( $class_name ) {
		switch ( $class_name ) {
			case Adapter::class:
				return Adapter::get_instance();

			case WC_BPost_Shipping_Options_Label::class:
				return new WC_BPost_Shipping_Options_Label( self::get_adapter() );

			case WC_BPost_Shipping_Options_Base::class:
				return new WC_BPost_Shipping_Options_Base();

			case WC_BPost_Shipping_Label_Path_Resolver::class:
				return new WC_BPost_Shipping_Label_Path_Resolver( self::get_options_label() );

			case WC_BPost_Shipping_Label_Url_Generator::class:
				return new WC_BPost_Shipping_Label_Url_Generator(
					self::get_adapter(),
					WC()
				);

			case WC_BPost_Shipping_Logger::class:
				return new WC_BPost_Shipping_Logger();

			case WC_BPost_Shipping_Api_Factory::class:
				return new WC_BPost_Shipping_Api_Factory(
					self::get_logger()
				);

			case WC_BPost_Shipping_Label_Retriever::class:
				return new WC_BPost_Shipping_Label_Retriever(
					self::get_api_factory(),
					self::get_label_url_generator(),
					self::get_label_resolver_path(),
					self::get_options_label()
				);

			case WC_BPost_Shipping_Assets_Management::class:
				return new WC_BPost_Shipping_Assets_Management(
					new WC_BPost_Shipping_Assets_Detector( self::get_adapter() ),
					new WC_BPost_Shipping_Assets_Resources()
				);

			case WC_BPost_Shipping_Meta_Type::class:
				return new WC_BPost_Shipping_Meta_Type( self::get_adapter() );

			case WC_BPost_Shipping_Api_Connector::class:
				$options   = self::get( WC_BPost_Shipping_Options_Base::class );
				$connector = new WC_BPost_Shipping_Api_Connector(
					$options->get_account_id(), $options->get_passphrase(), $options->get_api_url()
				);
				$connector->setLogger( self::get_logger() );

				return $connector;

			case WC_BPost_Shipping_Limitations::class:
				return new WC_BPost_Shipping_Limitations(
					new WC_BPost_Shipping_Api_Product_Configuration(
						self::get( WC_BPost_Shipping_Api_Connector::class ), self::get_logger()
					),
					self::get( WC_BPost_Shipping_Api_Connector::class )
				);

			case WC_BPost_Shipping_Checkout_Order_Review::class:
				return new WC_BPost_Shipping_Checkout_Order_Review(
					self::get_adapter(),
					self::get( WC_BPost_Shipping_Limitations::class ),
				);
		}

		throw new LogicException( sprintf( 'Class to load not found: "%s"', $class_name ) );
	}

	public static function get_adapter(): Adapter {
		return self::get( Adapter::class );
	}

	public static function get_options_label(): WC_BPost_Shipping_Options_Label {
		return self::get( WC_BPost_Shipping_Options_Label::class );
	}

	public static function get_label_resolver_path(): WC_BPost_Shipping_Label_Path_Resolver {
		return self::get( WC_BPost_Shipping_Label_Path_Resolver::class );
	}

	public static function get_label_url_generator(): WC_BPost_Shipping_Label_Url_Generator {
		return self::get( WC_BPost_Shipping_Label_Url_Generator::class );
	}

	public static function get_logger(): WC_BPost_Shipping_Logger {
		return self::get( WC_BPost_Shipping_Logger::class );
	}

	public static function get_api_factory(): WC_BPost_Shipping_Api_Factory {
		return self::get( WC_BPost_Shipping_Api_Factory::class );
	}

	public static function get_label_retriever(): WC_BPost_Shipping_Label_Retriever {
		return self::get( WC_BPost_Shipping_Label_Retriever::class );
	}

	public static function get_assets_management(): WC_BPost_Shipping_Assets_Management {
		return self::get( WC_BPost_Shipping_Assets_Management::class );
	}

	public static function get_meta_type(): WC_BPost_Shipping_Meta_Type {
		return self::get( WC_BPost_Shipping_Meta_Type::class );
	}

	public static function get_api_connector(): WC_BPost_Shipping_Api_Connector {
		return self::get( WC_BPost_Shipping_Api_Connector::class );
	}

	public static function get_order_review(): WC_BPost_Shipping_Checkout_Order_Review {
		return self::get( WC_BPost_Shipping_Checkout_Order_Review::class );
	}

}
