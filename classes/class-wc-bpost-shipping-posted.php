<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class WC_BPost_Shipping_Address retrieves specific data from posted var
 */
class WC_BPost_Shipping_Posted {
	/** @var string[] */
	private array $posted;

	private function __construct( array $posted ) {
		$this->posted = $posted;
	}

	public static function create( array $posted ): self {
		// Merge with an empty (about values) array to avoid a notice if the key is not in $posted
		$posted = array_merge(
			array(
				'billing_first_name'        => '',
				'billing_last_name'         => '',
				'billing_company'           => '',
				'shipping_first_name'       => '',
				'shipping_last_name'        => '',
				'shipping_company'          => '',
				'billing_email'             => '',
				'billing_phone'             => '',
				'payment_method'            => '',
				'ship_to_different_address' => false,
			),
			$posted
		);

		return new self( $posted );
	}

	public function get_payment_method(): string {
		return $this->posted['payment_method'];
	}

	public function get_first_name(): string {
		return $this->posted[ $this->get_address_type() . '_first_name' ];
	}

	/**
	 * @return string return 'shipping' or 'billing' depending on ship_to_different_address flag
	 */
	private function get_address_type(): string {
		return $this->is_ship_to_different_address() ? 'shipping' : 'billing';
	}

	public function get_last_name(): string {
		return $this->posted[ $this->get_address_type() . '_last_name' ];
	}

	public function get_company(): string {
		return $this->posted[ $this->get_address_type() . '_company' ];
	}

	public function get_email(): string {
		return $this->posted['billing_email'];
	}

	public function get_phone(): string {
		return $this->posted['billing_phone'];
	}

	public function get_shipping_method(): string {
		if ( ! is_array( $this->posted['shipping_method'] ) ) {
			return '';
		}

		return join( '', $this->posted['shipping_method'] );
	}

	public function is_ship_to_different_address(): bool {
		return (bool) $this->posted['ship_to_different_address'];
	}
}
