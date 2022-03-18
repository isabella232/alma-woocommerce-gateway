<?php
/**
 * Alma Payment Upon Trigger
 *
 * @package Alma_WooCommerce_Gateway
 */

use Alma\API\RequestError;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Alma_WC_Payment_Upon_Trigger
 */
class Alma_WC_Payment_Upon_Trigger {

	const FOO = 'bar';

	/**
	 * Logger
	 *
	 * @var Alma_WC_Logger
	 */
	private $logger;

	/**
	 * __construct.
	 */
	public function __construct() {
		add_action( 'woocommerce_order_status_changed', array( $this, 'woocommerce_order_status_changed' ), 10, 3 );
	}

	/**
	 * Callback function for the event "order status changed".
	 *
	 * @param integer $order_id The order id.
	 * @param string  $previous_status Order status before it changes.
	 * @param string  $next_status Order status affected to the order.
	 * @return void
	 */
	public function woocommerce_order_status_changed( $order_id, $previous_status, $next_status ) {

		if ( 'yes' !== alma_wc_plugin()->settings->payment_upon_trigger_enabled ) {
			return;
		}

		if ( alma_wc_plugin()->settings->payment_upon_trigger_event === $next_status ) {

			// @todo check if order isn't flag as already paid.
			$this->launch_payment( $order_id );
		}
	}

	/**
	 * Launches the payment on trigger for an order.
	 *
	 * @param integer $order_id The order id.
	 * @return void
	 */
	private function launch_payment( $order_id ) {

		// @todo flag order as already paid.
		error_log( 'launch payment for the order_id = ' . $order_id );
	}

	/**
	 * Returns the lists of existing order statuses.
	 *
	 * @return array
	 */
	public static function get_order_statuses() {
		$get_order_statuses = wc_get_order_statuses();
		foreach ( $get_order_statuses as $status_key => $status_description ) {
			$get_order_statuses[ str_replace( 'wc-', '', $status_key ) ] = $status_description;
			unset( $get_order_statuses[ $status_key ] );
		}
		return $get_order_statuses;
	}

	/**
	 * Returns the list of texts proposed to be displayed on front-office.
	 *
	 * @return array
	 */
	public static function get_display_texts() {
		return array(
			'at_shipping' => __( 'At shipping', 'alma-woocommerce-gateway' ),
		);
	}

	/**
	 * Has the merchant the "payment upon trigger" enabled in his admin alma dashboard.
	 *
	 * @return bool
	 */
	public static function has_merchant_payment_upon_trigger_enabled() {
		$fee_plans = null;
		try {
			$fee_plans = alma_wc_plugin()->get_fee_plans();
		} catch ( RequestError $e ) {
			alma_wc_plugin()->handle_settings_exception( $e );
		}
		if ( ! $fee_plans ) {
			return false;
		}

		foreach ( $fee_plans as $fee_plan ) {
			if ( self::is_payment_upon_trigger_enabled_for_fee_plan( $fee_plan ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Tells if a fee plan is allowed to accept "payment upon trigger" on admin alma dashboard.
	 *
	 * @param FeePlan $fee_plan A fee plan.
	 * @return bool
	 */
	public static function is_payment_upon_trigger_enabled_for_fee_plan( $fee_plan ) {
		if ( isset( $fee_plan->deferred_trigger_limit_days ) ) {
			return true;
		}
		return false;
	}
}

new Alma_WC_Payment_Upon_Trigger();
