<?php
/**
 * Alma cart
 *
 * @package Alma_WooCommerce_Gateway
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Alma_WC_Model_Cart
 */
class Alma_WC_Model_Cart {
	/**
	 * Legacy
	 *
	 * @var bool
	 */
	private $legacy;

	/**
	 * Cart
	 *
	 * @var WC_Cart|null
	 */
	private $cart;

	/**
	 * __construct
	 *
	 * @return void
	 */
	public function __construct() {
		$this->legacy = version_compare( wc()->version, '3.2.0', '<' );
		$this->cart   = WC()->cart;
	}

	/**
	 * Get cart total.
	 *
	 * @return float
	 */
	public function get_total() {
		if ( ! $this->cart ) {
			return 0;
		}
		if ( $this->legacy ) {
			return alma_wc_price_to_cents( $this->cart->total );
		} else {
			return alma_wc_price_to_cents( $this->cart->get_total( null ) );
		}
	}
}
