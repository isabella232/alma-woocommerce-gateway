<?php
/**
 * Alma WooCommerce payment gateway
 *
 * @package Alma_WooCommerce_Gateway
 */

use Alma\API\Endpoints\Results\Eligibility;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
	return;
}

/**
 * The Alma
 */
class Alma_WC_Checkout_Renderer {

	const ALMA_PAYMENT_PLAN_TABLE_ID_TEMPLATE = 'alma-payment-plan-table-%s-installments';
	const ALMA_PAY_LATER_PLAN_TABLE_CSS_CLASS = 'js-alma-payment-pay-later-plan-table';
	const ALMA_PNX_PLAN_TABLE_CSS_CLASS       = 'js-alma-payment-pnx-plan-table';
	const ALMA_DEFAULT_INPUT_NAME             = 'alma_fee_plan';
	const ALMA_PNX_INPUT_NAME                 = 'alma_pnx_plan';
	const ALMA_PAY_LATER_INPUT_NAME           = 'alma_deferred_plan';
	const PAYMENT_METHOD_ID                   = 'alma_pay_later';

	/**
	 * Output HTML for a single payment field.
	 *
	 * @param Eligibility $plan             The plan to render as payment method field.
	 * @param boolean     $has_radio_button Include a radio button for plan selection.
	 * @param boolean     $is_checked       Should the radio button be checked.
	 */
	public function payment_field( $plan, $has_radio_button, $is_checked ) {
		$plan_class = '.' . $this->build_table_plan_class( $plan );
		$plan_id    = '#' . $this->build_table_plan_id( $plan );
		$input_name = $this->build_input_name( $plan );
		$plan_key   = $plan->getPlanKey();
		$logo_url   = alma_wc_plugin()->get_asset_url( sprintf( 'images/%s_logo.svg', $plan_key ) );
		?>
		<input
			type="<?php echo $has_radio_button ? 'radio' : 'hidden'; ?>"
			value="<?php echo esc_attr( $plan_key ); ?>"
			id="alma_fee_plan_<?php echo esc_attr( $plan_key ); ?>"
			name="<?php echo esc_attr( $input_name ); ?>"

			<?php if ( $has_radio_button ) : ?>
				style="margin-right: 5px;"
				<?php echo $is_checked ? 'checked' : ''; ?>
				onchange="if (this.checked) { jQuery( '<?php echo esc_js( $plan_class ); ?>' ).hide(); jQuery( '<?php echo esc_js( $plan_id ); ?>' ).show() }"
			<?php endif; ?>
		>
		<label
			class="checkbox"
			style="margin-right: 10px; display: inline;"
			for="alma_fee_plan_<?php echo esc_attr( $plan_key ); ?>"
		>
			<img src="<?php echo esc_attr( $logo_url ); ?>"
				style="float: unset !important; width: auto !important; height: 30px !important;  border: none !important; vertical-align: middle; display: inline-block;"
				alt="
					<?php
					// translators: %d: number of installments.
					echo sprintf( esc_html__( '%d installments', 'alma-woocommerce-gateway' ), esc_html( $plan_key ) );
					?>
					">
		</label>
		<?php
	}


	/**
	 * Render plan with dates.
	 *
	 * @param string $default_plan Plan key.
	 * @param array  $allowed_keys As allowed plan keys.
	 *
	 * @return void
	 */
	public function render_detailed_plans( $default_plan, $allowed_keys ) {
		$eligibilities = alma_wc_plugin()->get_cart_eligibilities();
		if ( $eligibilities ) {
			foreach ( $eligibilities as $key => $plan ) {
				if ( ! in_array( $key, $allowed_keys, true ) ) {
					continue;
				}
				?>
				<div
					id="<?php echo esc_attr( $this->build_table_plan_id( $plan ) ); ?>"
					class="<?php echo esc_attr( $this->build_table_plan_class( $plan ) ); ?>"
					style="
						margin: 0 auto;
					<?php if ( $key !== $default_plan && count( $allowed_keys ) > 1 ) { ?>
						display: none;
					<?php	} ?>
						"
				>
					<?php
					$plans_count = count( $plan->paymentPlan ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName
					$plan_index  = 0;
					foreach ( $plan->paymentPlan as $step ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName
						?>
						<!--suppress CssReplaceWithShorthandSafely -->
						<p style="
							display: flex;
							justify-content: space-between;
							padding: 4px 0;
							margin: 4px 0;
						<?php if ( ++$plan_index !== $plans_count ) { ?>
							border-bottom: 1px solid lightgrey;
						<?php	} else { ?>
							padding-bottom: 0;
							margin-bottom: 0;
						<?php	} ?>
							">
							<span><?php echo esc_html( date_i18n( get_option( 'date_format' ), $step['due_date'] ) ); ?></span>
							<span>€<?php echo esc_html( alma_wc_price_from_cents( $step['purchase_amount'] + $step['customer_fee'] ) ); ?></span>
						</p>
					<?php } ?>
				</div>
				<?php
			}
		}
	}

	/**
	 * Render select for given payment fields
	 *
	 * @param array  $eligible_plans As pre-filtered plans keys.
	 * @param string $multiple_description The description to display if there are multiple choices.
	 * @param string $default_plan As default plan key.
	 */
	public function render_payment_fields( array $eligible_plans, $multiple_description, $default_plan ) {
		$is_multiple_plans = count( $eligible_plans ) > 1;

		if ( $is_multiple_plans ) {
			?>
			<p><?php echo esc_html( $multiple_description ); ?><span
					class="required">*</span></p>
			<?php
		}
		?>
		<p>
			<?php
			foreach ( $eligible_plans as $plan_key ) {
				$this->payment_field( alma_wc_plugin()->get_cart_eligibility_by( $plan_key ), $is_multiple_plans, $plan_key === $default_plan );
			}

			$this->render_detailed_plans( $default_plan, $eligible_plans );
			?>
		</p>
		<?php
	}

	/**
	 * Add HTML forms for pay later.
	 *
	 * @param string $template_name The template name.
	 * @param string $template_path The template path.
	 * @param string $located The located template file.
	 * @param array  $args Filter arguments.
	 *
	 * @see Alma_WC_Payment_Gateway::render_pay_later_fields() Where pay later is rendered.
	 *
	 * @see woocommerce/templates/checkout/payment-method.php from where this template si duplicated.
	 */
	public function add_pay_later_payment_method( $template_name, $template_path, $located, $args ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
		if ( ! alma_wc_plugin()->has_eligible_pay_later_in_cart() ) {
			return;
		}
		if ( isset( $args['gateway'] ) && 'alma' === $args['gateway']->id ) {
			/**
			 * Our gateway.
			 *
			 * @var Alma_WC_Payment_Gateway $gateway
			 */
			$gateway = $args['gateway'];
			?>
			<li class="wc_payment_method payment_method_alma_pay_later">
				<input id="payment_method_alma_pay_later" type="radio" class="input-radio" name="payment_method" value="<?php echo esc_attr( self::PAYMENT_METHOD_ID ); ?>" data-order_button_text="<?php echo esc_attr( $gateway->order_button_text ); ?>" />

				<label for="payment_method_alma_pay_later">
					<?php echo esc_attr( $gateway->get_option( 'pay_later_title' ) ); ?> <?php echo wp_kses_post( $gateway->get_icon() ); ?>
				</label>
				<div class="payment_box payment_method_alma_pay_later">
					<?php $gateway->render_pay_later_fields(); ?>
				</div>
			</li>
			<?php
		}
	}

	/**
	 * Surround default available gateways array and add another payment method in checkout.
	 *
	 * @param array $available_gateways Alerady existing gateways.
	 *
	 * @return array
	 *
	 * @TODO add new pay_later gateway here and extract all front pay later fields & validation into new gateway (without admin part)
	 */
	public function set_pay_later_available( $available_gateways ) {
		$alma_gw                                       = $available_gateways[ Alma_WC_Payment_Gateway::GATEWAY_ID ];
		$available_gateways[ self::PAYMENT_METHOD_ID ] = $alma_gw;
		return $available_gateways;
	}

	/**
	 * Generate plan table DOM unique ID from given plan.
	 *
	 * @param Eligibility $plan Plan to check for build.
	 *
	 * @return string
	 */
	protected function build_table_plan_id( $plan ) {
		return sprintf( self::ALMA_PAYMENT_PLAN_TABLE_ID_TEMPLATE, $plan->getPlanKey() );
	}

	/**
	 * Generate plan table DOM adapted classname from given plan.
	 *
	 * @param Eligibility $plan Plan to check for build.
	 *
	 * @return string
	 */
	protected function build_table_plan_class( $plan ) {
		if ( $plan->isPnXOnly() ) {

			return self::ALMA_PNX_PLAN_TABLE_CSS_CLASS;
		}
		if ( $plan->isPayLaterOnly() ) {

			return self::ALMA_PAY_LATER_PLAN_TABLE_CSS_CLASS;
		}

		return '';
	}

	/**
	 * Generate input name from given plan.
	 *
	 * @param Eligibility $plan Plan to check for build.
	 *
	 * @return string
	 */
	private function build_input_name( Eligibility $plan ) {
		if ( $plan->isPnXOnly() ) {

			return self::ALMA_PNX_INPUT_NAME;
		}
		if ( $plan->isPayLaterOnly() ) {

			return self::ALMA_PAY_LATER_INPUT_NAME;
		}

		return self::ALMA_DEFAULT_INPUT_NAME;
	}
}
