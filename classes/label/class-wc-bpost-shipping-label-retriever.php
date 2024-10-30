<?php

namespace WC_BPost_Shipping\Label;

use Bpost\BpostApiClient\Bpost\Label;
use Bpost\BpostApiClient\Exception\BpostApiResponseException\BpostCurlException;
use Bpost\BpostApiClient\Exception\BpostApiResponseException\BpostInvalidResponseException;
use Bpost\BpostApiClient\Exception\BpostApiResponseException\BpostInvalidSelectionException;
use Bpost\BpostApiClient\Exception\XmlException\BpostXmlNoReferenceFoundException;
use WC_BPost_Shipping\Api\WC_BPost_Shipping_Api_Factory;
use WC_BPost_Shipping\Api\WC_BPost_Shipping_Api_Label;
use WC_BPost_Shipping\Label\Exception\WC_BPost_Shipping_Label_Exception_Not_Found;
use WC_BPost_Shipping\Options\WC_BPost_Shipping_Options_Label;
use WC_BPost_Shipping\WC_Bpost_Shipping_Container as Container;
use WC_BPost_Shipping_Logger;
use WC_Order;

use function wc_get_order;

class WC_BPost_Shipping_Label_Retriever {

	private WC_BPost_Shipping_Api_Label $api_label;
	private WC_BPost_Shipping_Label_Url_Generator $url_generator;
	private WC_BPost_Shipping_Label_Path_Resolver $label_path_resolver;
	private WC_BPost_Shipping_Options_Label $options_label;
	private WC_BPost_Shipping_Logger $logger;

	public function __construct(
		WC_BPost_Shipping_Api_Factory $api_factory,
		WC_BPost_Shipping_Label_Url_Generator $url_generator,
		WC_BPost_Shipping_Label_Path_Resolver $label_path_resolver,
		WC_BPost_Shipping_Options_Label $options_label
	) {
		$this->api_label           = $api_factory->get_label();
		$this->url_generator       = $url_generator;
		$this->label_path_resolver = $label_path_resolver;
		$this->options_label       = $options_label;

		$this->logger = Container::get_logger();
	}


	/**
	 * @throws BpostCurlException
	 * @throws BpostInvalidResponseException
	 * @throws BpostInvalidSelectionException
	 * @throws BpostXmlNoReferenceFoundException
	 * @throws WC_BPost_Shipping_Label_Exception_Not_Found
	 */
	public function get_label_as_file( string $filepath, WC_BPost_Shipping_Label_Post $post ) {
		if ( ! $post->get_order_reference() ) {
			$this->logger->warning( 'The order does not contain order reference',
				array( 'order_id' => $post->get_post_id() ) );

			return;
		}
		if ( file_exists( $filepath ) && filesize( $filepath ) > 0 ) {
			return;
		}
		$format             = $this->options_label->get_label_format();
		$with_return_labels = $this->options_label->is_return_label_enabled( $post );
		$label              = $this->api_label->get_label( $post->get_order_reference(), $format, $with_return_labels );

		if ( ! $label ) {
			throw new WC_BPost_Shipping_Label_Exception_Not_Found( bpost__( 'This label is not available for print.' ) );
		}
		$this->save_label( $filepath, $label );
	}

	/**
	 * Save into temp file (/tmp/xxx or whatever a label provided as attachment)
	 */
	private function save_label( string $filepath, Label $label_retrieved ) {
		$handle = fopen( $filepath, 'w' );
		fwrite( $handle, $label_retrieved->getBytes() );
		fclose( $handle );
		clearstatcache();
	}

	/**
	 * @throws \Exception
	 */
	public function get_labels_contents( array $post_ids ): array {
		$contents = array();

		$are_labels_as_files = $this->options_label->are_labels_as_files();

		foreach ( $post_ids as $post_id ) {
			$order = wc_get_order( $post_id );
			if ( $are_labels_as_files ) {
				$url = $this->get_label_file_url( $order );
			} else {
				$url = $this->get_label_attachment_url( $order );
			}

			$contents[ $this->label_path_resolver->get_basename( $url ) ] = $this->label_path_resolver->get_content( $url );
		}

		return $contents;
	}

	/**
	 * @param int $post_id
	 *
	 * @return false|string
	 * @throws \Exception
	 */
	private function get_label_attachment_url( WC_Order $order ) {
		$label_attach = new WC_BPost_Shipping_Label_Attachment(
			$this->url_generator,
			$this,
			$this->label_path_resolver,
			$this->get_label_post( $order )
		);

		return $label_attach->get_url( $order );
	}

	/**
	 * @param int $post_id
	 *
	 * @return false|string
	 * @throws \Exception
	 */
	private function get_label_file_url( WC_Order $order ) {
		$label_post = $this->get_label_post( $order );
		$url        = $this->label_path_resolver->get_storage_file_path( $label_post );
		$this->get_label_as_file( $url, $label_post );

		return $url;
	}

	private function get_label_post( WC_Order $order ) {
		$meta_handler = new \WC_BPost_Shipping_Meta_Handler( Container::get_meta_type(), $order );

		return new WC_BPost_Shipping_Label_Post( $meta_handler, $order );
	}
}
