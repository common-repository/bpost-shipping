<?php

namespace WC_BPost_Shipping\Label;

use Bpost\BpostApiClient\Exception\BpostApiResponseException\BpostCurlException;
use Bpost\BpostApiClient\Exception\BpostApiResponseException\BpostInvalidResponseException;
use Bpost\BpostApiClient\Exception\BpostApiResponseException\BpostInvalidSelectionException;
use Bpost\BpostApiClient\Exception\XmlException\BpostXmlNoReferenceFoundException;
use WC_BPost_Shipping\Adapter\WC_BPost_Shipping_Adapter_Woocommerce;
use WC_BPost_Shipping\Label\Exception\WC_BPost_Shipping_Label_Exception_Temporary_File;
use WC_BPost_Shipping\WC_Bpost_Shipping_Container as Container;
use WC_Order;

/**
 * Class WC_BPost_Shipping_Label_Attachment
 * @package WC_BPost_Shipping\Label
 */
class WC_BPost_Shipping_Label_Attachment {

	private WC_BPost_Shipping_Label_Post $label_post;
	private WC_BPost_Shipping_Label_Url_Generator $url_generator;
	private WC_BPost_Shipping_Label_Retriever $label_retriever;
	private WC_BPost_Shipping_Adapter_Woocommerce $adapter;
	private WC_BPost_Shipping_Label_Path_Resolver $label_path_resolver;

	public function __construct(
		WC_BPost_Shipping_Label_Url_Generator $url_generator,
		WC_BPost_Shipping_Label_Retriever $label_retriever,
		WC_BPost_Shipping_Label_Path_Resolver $label_path_resolver,
		WC_BPost_Shipping_Label_Post $label_post
	) {
		$this->adapter             = Container::get_adapter();
		$this->label_post          = $label_post;
		$this->label_retriever     = $label_retriever;
		$this->url_generator       = $url_generator;
		$this->label_path_resolver = $label_path_resolver;
	}

	/**
	 * @param $filepath
	 *
	 * @return int|\WP_Error
	 * @throws BpostCurlException
	 * @throws BpostInvalidResponseException
	 * @throws BpostInvalidSelectionException
	 * @throws BpostXmlNoReferenceFoundException
	 * @throws \Exception
	 */
	public function create_attachment( $filepath ) {
		$this->label_retriever->get_label_as_file( $filepath, $this->label_post );

		$desc       = $this->label_post->get_order_reference();
		$file_array = array();

		// Set variables for storage
		// fix file filename for query strings
		$file_array['name']     = $this->label_path_resolver->get_filename( $this->label_post );
		$file_array['tmp_name'] = $filepath;

		// do the validation and storage stuff
		$attach_id = $this->adapter->media_handle_sideload( $file_array, $desc );

		if ( is_wp_error( $attach_id ) ) {
			throw new \Exception( $attach_id->get_error_message() );
		}

		$this->adapter->wp_set_post_tags( $attach_id, array( 'bpost' ) );

		return $attach_id;
	}

	/**
	 * @return bool
	 */
	public function has_attachment() {
		return (bool) $this->get_post();
	}

	/**
	 * @return false|string
	 * @throws \Exception
	 */
	public function get_url( WC_Order $order ) {
		if ( $order->get_meta( 'label_attachment_id' ) ) {
			return wp_get_attachment_url( $order->get_meta( 'label_attachment_id' ) );
		}

		$temp_filename = $this->adapter->wp_tempnam();
		if ( ! $temp_filename ) {
			throw new WC_BPost_Shipping_Label_Exception_Temporary_File( bpost__( 'Could not create Temporary file.' ) );
		}

		$attach_id = $this->create_attachment( $temp_filename );
		$order->set_meta_data( [ 'label_attachment_id' => $attach_id ] );

		return wp_get_attachment_url( $attach_id );
	}

	/**
	 * @return string
	 */
	public function get_generate_url() {
		return $this->url_generator->get_generate_url( array( $this->label_post->get_post_id() ) );
	}

	/**
	 * @return array
	 */
	public function build_request_params() {
		return array(
			'post_type'   => 'attachment',
			'numberposts' => 1,
			'post_status' => 'any',
			'post_parent' => $this->label_post->get_post_id(),
		);
	}

	/**
	 * @return \WP_Post|null
	 */
	private function get_post() {
		$post_attachments = get_posts( $this->build_request_params() );

		if ( ! $post_attachments ) {
			return null;
		}

		return $post_attachments[0];
	}

	/**
	 * @return string
	 */
	public function get_order_reference() {
		return $this->label_post->get_order_reference();
	}

	/**
	 * @return \DateTime
	 * @throws \Exception
	 */
	public function get_retrieved_date() {
		$post = $this->get_post();

		return new \DateTime( $post->post_date );
	}

	/**
	 * @return string
	 */
	public function get_shipping_postal_code() {
		return $this->label_post->get_order()->get_shipping_postcode();
	}
}
