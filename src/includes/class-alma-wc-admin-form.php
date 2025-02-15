<?php
/**
 * Alma WooCommerce payment gateway
 *
 * @package Alma_WooCommerce_Gateway
 * @noinspection HtmlUnknownTarget
 */

use Alma\API\Entities\FeePlan;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Alma_WC_Admin_Form
 */
class Alma_WC_Admin_Form {

	/**
	 * Singleton static property
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Admin Form fields initialisation.
	 *
	 * @return array
	 */
	public static function init_form_fields() {
		$need_api_key     = alma_wc_plugin()->settings->need_api_key();
		$default_settings = Alma_WC_Settings::default_settings();

		if ( $need_api_key ) {
			return array_merge(
				self::get_instance()->init_enabled_field( $default_settings ),
				self::get_instance()->init_api_key_fields( __( '→ Start by filling in your API keys', 'alma-woocommerce-gateway' ), $default_settings ),
				self::get_instance()->init_debug_fields( $default_settings )
			);
		}

		return array_merge(
			self::get_instance()->init_enabled_field( $default_settings ),
			self::get_instance()->init_fee_plans_fields( $default_settings ),
			self::get_instance()->init_general_settings_fields( $default_settings ),
			self::get_instance()->init_api_key_fields( __( '→ API configuration', 'alma-woocommerce-gateway' ), $default_settings ),
			self::get_instance()->init_debug_fields( $default_settings )
		);
	}

	/**
	 * Inits a fee_plan's fields.
	 *
	 * @param FeePlan $fee_plan Fee plan definitions.
	 * @param array   $default_settings Default settings definitions.
	 * @param bool    $selected If this field is currently selected.
	 *
	 * @return array  as field_form definition
	 */
	private function init_fee_plan_fields( FeePlan $fee_plan, $default_settings, $selected ) {
		$key                   = $fee_plan->getPlanKey();
		$min_amount_key        = 'min_amount_' . $key;
		$section_key           = $key . '_section';
		$max_amount_key        = 'max_amount_' . $key;
		$toggle_key            = 'enabled_' . $key;
		$class                 = 'alma_fee_plan alma_fee_plan_' . $key;
		$css                   = $selected ? '' : 'display: none;';
		$default_min_amount    = alma_wc_price_from_cents( $fee_plan->min_purchase_amount );
		$default_max_amount    = alma_wc_price_from_cents( $fee_plan->max_purchase_amount );
		$merchant_fee_fixed    = alma_wc_price_from_cents( $fee_plan->merchant_fee_fixed );
		$merchant_fee_variable = $fee_plan->merchant_fee_variable / 100; // percent.
		$customer_fee_fixed    = alma_wc_price_from_cents( $fee_plan->customer_fee_fixed );
		$customer_fee_variable = $fee_plan->customer_fee_variable / 100; // percent.
		$customer_lending_rate = $fee_plan->customer_lending_rate / 100; // percent.
		$default_enabled       = $default_settings['selected_fee_plan'] === $key ? 'yes' : 'no';
		$custom_attributes     = array(
			'required' => 'required',
			'min'      => $default_min_amount,
			'max'      => $default_max_amount,
			'step'     => 0.01,
		);

		$section_title = '';
		$toggle_label  = '';
		if ( $fee_plan->isPnXOnly() ) {
			// translators: %d: number of installments.
			$section_title = sprintf( __( '→ %d-installment payment', 'alma-woocommerce-gateway' ), $fee_plan->getInstallmentsCount() );
			// translators: %d: number of installments.
			$toggle_label = sprintf( __( 'Enable %d-installment payments with Alma', 'alma-woocommerce-gateway' ), $fee_plan->getInstallmentsCount() );
		}
		if ( $fee_plan->isPayLaterOnly() ) {
			$deferred_days   = $fee_plan->getDeferredDays();
			$deferred_months = $fee_plan->getDeferredMonths();
			if ( $deferred_days ) {
				// translators: %d: number of deferred days.
				$section_title = sprintf( __( '→ D+%d-deferred payment', 'alma-woocommerce-gateway' ), $deferred_days );
				// translators: %d: number of deferred days.
				$toggle_label = sprintf( __( 'Enable D+%d-deferred payments with Alma', 'alma-woocommerce-gateway' ), $deferred_days );
			}
			if ( $deferred_months ) {
				// translators: %d: number of deferred months.
				$section_title = sprintf( __( '→ M+%d-deferred payment', 'alma-woocommerce-gateway' ), $deferred_months );
				// translators: %d: number of deferred months.
				$toggle_label = sprintf( __( 'Enable M+%d-deferred payments with Alma', 'alma-woocommerce-gateway' ), $deferred_months );
			}
		}

		return array(
			$section_key    => array(
				'title'             => $section_title,
				'type'              => 'title',
				'description'       => $this->generate_fee_plan_description( $fee_plan, $default_min_amount, $default_max_amount, $merchant_fee_fixed, $merchant_fee_variable, $customer_fee_fixed, $customer_fee_variable, $customer_lending_rate ),
				'class'             => $class,
				'description_class' => $class,
				'table_class'       => $class,
				'css'               => $css,
				'description_css'   => $css,
				'table_css'         => $css,
			),
			$toggle_key     => array(
				'title'   => __( 'Enable/Disable', 'alma-woocommerce-gateway' ),
				'type'    => 'checkbox',
				'label'   => $toggle_label,
				'default' => $default_enabled,
			),
			$min_amount_key => array(
				'title'             => __( 'Minimum amount', 'alma-woocommerce-gateway' ),
				'type'              => 'number',
				'css'               => 'width: 100px;',
				'custom_attributes' => $custom_attributes,
				'default'           => $default_min_amount,
			),
			$max_amount_key => array(
				'title'             => __( 'Maximum amount', 'alma-woocommerce-gateway' ),
				'type'              => 'number',
				'css'               => 'width: 100px;',
				'custom_attributes' => $custom_attributes,
				'default'           => $default_max_amount,
			),
		);
	}

	/**
	 * Inits enabled Admin field.
	 *
	 * @param array $default_settings as default settings.
	 *
	 * @return array[]
	 */
	private function init_enabled_field( $default_settings ) {
		return array(
			'enabled' => array(
				'title'   => __( 'Enable/Disable', 'alma-woocommerce-gateway' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable monthly payments with Alma', 'alma-woocommerce-gateway' ),
				'default' => $default_settings['enabled'],
			),
		);
	}

	/**
	 * Inits test & live api keys fields.
	 *
	 * @param string $keys_title as section title.
	 * @param array  $default_settings as default settings.
	 *
	 * @return array[]
	 */
	private function init_api_key_fields( $keys_title, $default_settings ) {

		return array(
			'keys_section' => array(
				'title'       => '<hr>' . $keys_title,
				'type'        => 'title',
				/* translators: %s Alma security URL */
				'description' => sprintf( __( 'You can find your API keys on <a href="%s" target="_blank">your Alma dashboard</a>', 'alma-woocommerce-gateway' ), alma_wc_plugin()->get_alma_dashboard_url( 'security' ) ),
			),
			'live_api_key' => array(
				'title' => __( 'Live API key', 'alma-woocommerce-gateway' ),
				'type'  => 'text',
			),
			'test_api_key' => array(
				'title' => __( 'Test API key', 'alma-woocommerce-gateway' ),
				'type'  => 'text',
			),
			'environment'  => array(
				'title'       => __( 'API Mode', 'alma-woocommerce-gateway' ),
				'type'        => 'select',
				'description' => __( 'Use <b>Test</b> mode until you are ready to take real orders with Alma<br>In Test mode, only admins can see Alma on cart/checkout pages.', 'alma-woocommerce-gateway' ),
				'default'     => $default_settings['environment'],
				'options'     => array(
					'test' => __( 'Test', 'alma-woocommerce-gateway' ),
					'live' => __( 'Live', 'alma-woocommerce-gateway' ),
				),
			),
		);
	}

	/**
	 * Inits all allowed fee plans admin field.
	 *
	 * @param array $default_settings Default settings.
	 *
	 * @return array|array[]
	 */
	private function init_fee_plans_fields( $default_settings ) {
		$fee_plans_fields = array();
		$title_field      = array(
			'fee_plan_section' => array(
				'title' => '<hr>' . __( '→ Fee plans configuration', 'alma-woocommerce-gateway' ),
				'type'  => 'title',
			),
		);
		$select_options   = $this->generate_select_options();
		if ( count( $select_options ) === 0 ) {
			/* translators: %s: Alma conditions URL */
			$title_field['fee_plan_section']['description'] = sprintf( __( '⚠ There is no fee plan allowed in your <a href="%s" target="_blank">Alma dashboard</a>.', 'alma-woocommerce-gateway' ), alma_wc_plugin()->get_alma_dashboard_url( 'conditions' ) );

			return $title_field;
		}
		$selected_fee_plan = $this->generate_selected_fee_plan_key( $select_options, $default_settings );
		foreach ( alma_wc_plugin()->settings->get_allowed_fee_plans() as $fee_plan ) {
			$fee_plans_fields = array_merge(
				$fee_plans_fields,
				$this->init_fee_plan_fields( $fee_plan, $default_settings, $selected_fee_plan === $fee_plan->getPlanKey() )
			);
		}

		return array_merge(
			$title_field,
			array(
				'selected_fee_plan' => array(
					'title'       => __( 'Select a fee plan to update', 'alma-woocommerce-gateway' ),
					'type'        => 'select_alma_fee_plan',
					/* translators: %s: Alma conditions URL */
					'description' => sprintf( __( 'Choose which fee plan you want to modify<br>(only your <a href="%s" target="_blank">Alma dashboard</a> available fee plans are shown here).', 'alma-woocommerce-gateway' ), alma_wc_plugin()->get_alma_dashboard_url( 'conditions' ) ),
					'default'     => $selected_fee_plan,
					'options'     => $select_options,
				),
			),
			$fee_plans_fields
		);
	}

	/**
	 * Inits default plugin fields.
	 *
	 * @param array $default_settings default settings.
	 *
	 * @return array
	 */
	private function init_general_settings_fields( array $default_settings ) {
		$general_settings_fields = array(
			'general_section' => array(
				'title' => '<hr>' . __( '→ General configuration', 'alma-woocommerce-gateway' ),
				'type'  => 'title',
			),
			'text_fields'     => array(
				'title' => '<p style="font-weight:normal;">' . __( 'Edit the text displayed when choosing the payment method in your checkout.', 'alma-woocommerce-gateway' ) . '</p>',
				'type'  => 'title',
			),
		);

		$fields_pnx = $this->get_custom_fields_payment_method( 'payment_method_pnx', __( 'Payments in 2, 3 and 4 installments:', 'alma-woocommerce-gateway' ), $default_settings );

		$fields_pay_later = array();
		if ( alma_wc_plugin()->settings->has_pay_later() ) {
			$fields_pay_later = $this->get_custom_fields_payment_method( 'payment_method_pay_later', __( 'Deferred Payments:', 'alma-woocommerce-gateway' ), $default_settings );
		}

		$fields_pnx_plus_4 = array();
		if ( alma_wc_plugin()->settings->has_pnx_plus_4() ) {
			$fields_pnx_plus_4 = $this->get_custom_fields_payment_method( 'payment_method_pnx_plus_4', __( 'Payments in more than 4 installments:', 'alma-woocommerce-gateway' ), $default_settings );
		}

		$general_settings_fields_end = array(
			'display_product_eligibility'           => array(
				'title'   => __( 'Product eligibility notice', 'alma-woocommerce-gateway' ),
				'type'    => 'checkbox',
				'label'   => __( 'Display a message about product eligibility for monthly payments', 'alma-woocommerce-gateway' ),
				'default' => $default_settings['display_product_eligibility'],
			),
			'variable_product_price_query_selector' => array(
				'title'       => __( 'Variable products price query selector', 'alma-woocommerce-gateway' ),
				'type'        => 'text',
				'description' => __( 'Query selector used to get the price of product with variations', 'alma-woocommerce-gateway' ),
				'desc_tip'    => true,
				'default'     => $default_settings['variable_product_price_query_selector'],
			),
			'display_cart_eligibility'              => array(
				'title'   => __( 'Cart eligibility notice', 'alma-woocommerce-gateway' ),
				'type'    => 'checkbox',
				'label'   => __( 'Display a message about cart eligibility for monthly payments', 'alma-woocommerce-gateway' ),
				'default' => $default_settings['display_cart_eligibility'],
			),
			'excluded_products_list'                => array(
				'title'       => __( 'Excluded product categories', 'alma-woocommerce-gateway' ),
				'type'        => 'multiselect',
				'description' => __( 'Exclude all virtual/downloadable product categories, as you cannot sell them with Alma', 'alma-woocommerce-gateway' ),
				'desc_tip'    => true,
				'css'         => 'height: 150px;',
				'options'     => $this->generate_categories_options(),
			),
		);

		$field_cart_not_eligible_message_gift_cards = $this->generate_i18n_field(
			'cart_not_eligible_message_gift_cards',
			array(
				'title'       => __( 'Non-eligibility message for excluded products', 'alma-woocommerce-gateway' ),
				'description' => __( 'Message displayed below the cart totals when it contains excluded products', 'alma-woocommerce-gateway' ),
				'desc_tip'    => true,
			),
			$default_settings['cart_not_eligible_message_gift_cards']
		);

		return array_merge(
			$general_settings_fields,
			$fields_pnx,
			$fields_pay_later,
			$fields_pnx_plus_4,
			$general_settings_fields_end,
			$field_cart_not_eligible_message_gift_cards
		);
	}

	/**
	 * Adds all the translated fields for one field.
	 *
	 * @param string $field_name The field name.
	 * @param array  $field_infos The information for this field.
	 * @param string $default The default value for the field.
	 *
	 * @return array
	 */
	private function generate_i18n_field( $field_name, $field_infos, $default ) {
		if ( Alma_WC_Internationalization::is_site_multilingual() ) {
			$new_fields = array();
			$lang_list  = Alma_WC_Internationalization::get_list_languages();
			foreach ( $lang_list as $code_lang => $label_lang ) {
				$new_file_key                 = $field_name . '_' . $code_lang;
				$new_field_infos              = $field_infos;
				$new_field_infos['type']      = 'text_alma_i18n';
				$new_field_infos['class']     = $code_lang;
				$new_field_infos['default']   = Alma_WC_Internationalization::get_translated_text( $default, $code_lang );
				$new_field_infos['lang_list'] = $lang_list;

				$new_fields[ $new_file_key ] = $new_field_infos;
			}

			return $new_fields;
		}

		$additional_infos = array(
			'type'    => 'text',
			'class'   => 'alma-i18n',
			'default' => $default,
		);
		return array( $field_name => array_merge( $field_infos, $additional_infos ) );
	}

	/**
	 * Inits debug fields.
	 *
	 * @param array $default_settings as default settings.
	 *
	 * @return array
	 */
	private function init_debug_fields( $default_settings ) {
		return array(
			'debug_section' => array(
				'title' => '<hr>' . __( '→ Debug options', 'alma-woocommerce-gateway' ),
				'type'  => 'title',
			),
			'debug'         => array(
				'title'       => __( 'Debug mode', 'alma-woocommerce-gateway' ),
				'type'        => 'checkbox',
				// translators: %s: Admin logs url.
				'label'       => __( 'Activate debug mode', 'alma-woocommerce-gateway' ) . sprintf( __( '(<a href="%s">Go to logs</a>)', 'alma-woocommerce-gateway' ), alma_wc_plugin()->get_admin_logs_url() ),
				'description' => __( 'Enable logging info and errors to help debug any issue with the plugin', 'alma-woocommerce-gateway' ),
				'desc_tip'    => true,
				'default'     => $default_settings['debug'],
			),
		);
	}

	/**
	 * Singleton static method.
	 *
	 * @return Alma_WC_Admin_Form
	 */
	private static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Product categories options.
	 *
	 * @return array
	 */
	private function generate_categories_options() {
		$product_categories = get_terms(
			'product_cat',
			array(
				'orderby'    => 'name',
				'order'      => 'asc',
				'hide_empty' => false,
			)
		);

		$options = array();
		foreach ( $product_categories as $category ) {
			$options[ $category->slug ] = $category->name;
		}

		return $options;
	}

	/**
	 * Gets fee plan description.
	 *
	 * @param FeePlan $fee_plan The fee plan do describe.
	 * @param float   $min_amount Min amount.
	 * @param float   $max_amount Max amount.
	 * @param float   $merchant_fee_fixed Merchant fee fixed.
	 * @param float   $merchant_fee_variable Merchant fee variable.
	 * @param float   $customer_fee_fixed Customer fee fixed.
	 * @param float   $customer_fee_variable Customer fee variable.
	 * @param float   $customer_lending_rate Customer lending rate.
	 *
	 * @return string
	 */
	private function generate_fee_plan_description(
		FeePlan $fee_plan,
		$min_amount,
		$max_amount,
		$merchant_fee_fixed,
		$merchant_fee_variable,
		$customer_fee_fixed,
		$customer_fee_variable,
		$customer_lending_rate
	) {
		$you_can_offer = '';
		if ( $fee_plan->isPnXOnly() ) {
			$you_can_offer = sprintf(
			// translators: %d: number of installments.
				__( 'You can offer %1$d-installment payments for amounts between <b>%2$d€</b> and <b>%3$d€</b>.', 'alma-woocommerce-gateway' ),
				$fee_plan->installments_count,
				$min_amount,
				$max_amount
			);
		}
		if ( $fee_plan->isPayLaterOnly() ) {
			$deferred_days   = $fee_plan->getDeferredDays();
			$deferred_months = $fee_plan->getDeferredMonths();
			if ( $deferred_days ) {
				$you_can_offer = sprintf(
					// translators: %d: number of deferred days.
					__( 'You can offer D+%1$d-deferred payments for amounts between <b>%2$d€</b> and <b>%3$d€</b>.', 'alma-woocommerce-gateway' ),
					$deferred_days,
					$min_amount,
					$max_amount
				);
			}
			if ( $deferred_months ) {
				$you_can_offer = sprintf(
					// translators: %d: number of deferred months.
					__( 'You can offer M+%1$d-deferred payments for amounts between <b>%2$d€</b> and <b>%3$d€</b>.', 'alma-woocommerce-gateway' ),
					$deferred_months,
					$min_amount,
					$max_amount
				);
			}
		}
		$fees_applied          = __( 'Fees applied to each transaction for this plan:', 'alma-woocommerce-gateway' );
		$you_pay               = $this->generate_fee_to_pay_description( __( 'You pay:', 'alma-woocommerce-gateway' ), $merchant_fee_variable, $merchant_fee_fixed );
		$customer_pays         = $this->generate_fee_to_pay_description( __( 'Customer pays:', 'alma-woocommerce-gateway' ), $customer_fee_variable, $customer_fee_fixed );
		$customer_lending_pays = $this->generate_fee_to_pay_description( __( 'Customer lending rate:', 'alma-woocommerce-gateway' ), $customer_lending_rate, 0 );

		return sprintf( '<p>%s<br>%s %s %s %s</p>', $you_can_offer, $fees_applied, $you_pay, $customer_pays, $customer_lending_pays );
	}

	/**
	 * Generates a string with % + € OR only % OR only € (depending on parameters given).
	 * If all fees are <= 0 : return an empty string.
	 *
	 * @param string $translation as description prefix.
	 * @param float  $fee_variable as variable amount (if any).
	 * @param float  $fee_fixed as fixed amount (if any).
	 *
	 * @return string
	 */
	private function generate_fee_to_pay_description( $translation, $fee_variable, $fee_fixed ) {
		if ( ! $fee_variable && ! $fee_fixed ) {
			return '';
		}

		$fees = '';
		if ( $fee_variable ) {
			$fees .= $fee_variable . '%';
		}

		if ( $fee_fixed ) {
			if ( $fee_variable ) {
				$fees .= ' + ';
			}
			$fees .= $fee_fixed . '€';
		}

		return sprintf( '<br><b>%s</b> %s', $translation, $fees );
	}

	/**
	 * Generates select options key values for allowed fee_plans.
	 *
	 * @return array
	 */
	private function generate_select_options() {
		$select_options = array();
		foreach ( alma_wc_plugin()->settings->get_allowed_fee_plans() as $fee_plan ) {
			$select_label = '';
			if ( $fee_plan->isPnXOnly() ) {
				// translators: %d: number of installments.
				$select_label = sprintf( __( '→ %d-installment payment', 'alma-woocommerce-gateway' ), $fee_plan->getInstallmentsCount() );
			}
			if ( $fee_plan->isPayLaterOnly() ) {
				$deferred_months = $fee_plan->getDeferredMonths();
				$deferred_days   = $fee_plan->getDeferredDays();
				if ( $deferred_days ) {
					// translators: %d: number of deferred days.
					$select_label = sprintf( __( '→ D+%d-deferred payment', 'alma-woocommerce-gateway' ), $deferred_days );
				}
				if ( $deferred_months ) {
					// translators: %d: number of deferred months.
					$select_label = sprintf( __( '→ M+%d-deferred payment', 'alma-woocommerce-gateway' ), $deferred_months );
				}
			}
			$select_options[ $fee_plan->getPlanKey() ] = $select_label;
		}

		return $select_options;
	}

	/**
	 * Generates the selected option for current fee_plan_keys options.
	 *
	 * @param array $select_options Key,value allowed fee_plan options.
	 * @param array $default_settings Default settings.
	 *
	 * @return string
	 */
	private function generate_selected_fee_plan_key( array $select_options, $default_settings ) {
		$selected_fee_plan   = alma_wc_plugin()->settings->selected_fee_plan ? alma_wc_plugin()->settings->selected_fee_plan : $default_settings['selected_fee_plan'];
		$select_options_keys = array_keys( $select_options );

		return in_array( $selected_fee_plan, $select_options_keys, true ) ? $selected_fee_plan : $select_options_keys[0];
	}

	/**
	 * Gets custom fields for a payment method.
	 *
	 * @param string $payment_method_name The payment method name.
	 * @param string $title The title.
	 * @param array  $default_settings The defaults settings.
	 *
	 * @return array[]
	 */
	private function get_custom_fields_payment_method( $payment_method_name, $title, array $default_settings ) {

		$fields = array(
			$payment_method_name => array(
				'title' => sprintf( '<h4 style="color:#777;font-size:1.15em;">%s</h4>', $title ),
				'type'  => 'title',
			),
		);

		$field_payment_method_title = $this->generate_i18n_field(
			'title_' . $payment_method_name,
			array(
				'title'       => __( 'Title', 'alma-woocommerce-gateway' ),
				'description' => __( 'This controls the payment method name which the user sees during checkout.', 'alma-woocommerce-gateway' ),
				'desc_tip'    => true,
			),
			$default_settings[ 'title_' . $payment_method_name ]
		);

		$field_payment_method_description = $this->generate_i18n_field(
			'description_' . $payment_method_name,
			array(
				'title'       => __( 'Description', 'alma-woocommerce-gateway' ),
				'desc_tip'    => true,
				'description' => __( 'This controls the payment method description which the user sees during checkout.', 'alma-woocommerce-gateway' ),
			),
			$default_settings[ 'description_' . $payment_method_name ]
		);

		return array_merge(
			$fields,
			$field_payment_method_title,
			$field_payment_method_description
		);
	}

}


