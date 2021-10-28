/**
 * Inject payment plan widget.
 *
 * @package Alma_WooCommerce_Gateway
 */

(function ($) {
	var paymentPlansContainerId = "#alma-payment-plans";
	var settings                = $( paymentPlansContainerId ).data( 'settings' )
	if ( ! settings ) {
		return;
	}
	var jqueryUpdateEvent = settings.jqueryUpdateEvent;
	var firstRender       = settings.firstRender;

	/**
	 * Check visibility
	 *
	 * @param $elem jQuery
	 * @return {boolean}
	 */
	function isVisible( $elem ) {
		if ( ! $elem || $elem.length === 0 ) {
			return false;
		}
		var elem = $elem.get()[0];
		return ! ! ( elem && ( elem.offsetWidth || elem.offsetHeight || elem.getClientRects().length ) );
	}

	/**
	 * Retrieve amount element if found
	 *
	 * @return {null|*|jQuery|HTMLElement}
	 */
	function getAmountElement() {
		var amountQuerySelector = settings.amountQuerySelector;

		if ( ! amountQuerySelector ) {

			return null;
		}
		var $amountElement = jQuery( amountQuerySelector );
		if ( ! $amountElement.length > 0 ) {

			return null
		}

		return $amountElement;
	}

	window.AlmaInitWidget = function () {
		// Make sure settings are up-to-date after a potential cart_totals refresh.
		var settings = $( paymentPlansContainerId ).data( 'settings' )
		if ( ! settings ) {
			return;
		}

		if (settings.hasExcludedProducts) {
			return;
		}

		var merchantId = settings.merchantId;
		var apiMode    = settings.apiMode;
		var amount     = parseInt( settings.amount );

		var $amountElement = getAmountElement()
		if ($amountElement) {
			if (isVisible( $amountElement )) {
				var child = $amountElement.get()[0].firstChild;
				while (child) {
					if (child.nodeType === ( Node.TEXT_NODE || 3 )) {
						var strAmount = child.data
							.replace( settings.thousandSeparator, '' )
							.replace( settings.decimalSeparator, '.' )
							.replace( /[^\d.]/g, '' )

						amount = Alma.Utils.priceToCents( parseFloat( strAmount ) );
						break;
					}
					child = child.nextSibling;
				}
			} else {
				amount = 0
			}
		}

		var almaWidgets = Alma.Widgets.initialize( merchantId, apiMode );
		almaWidgets.add(
			Alma.Widgets.PaymentPlans,
			{
				container: paymentPlansContainerId,
				purchaseAmount: amount,
				plans: settings.enabledPlans.map(
					function ( plan ) {
						return {
							installmentsCount: plan.installments_count,
							minAmount: plan.min_amount,
							maxAmount: plan.max_amount
						}
					}
				)
			}
		);

		almaWidgets.render();
	};

	if ( firstRender ) {
		window.AlmaInitWidget();
	}

	if ( jqueryUpdateEvent ) {
		$( document.body ).on(
			jqueryUpdateEvent,
			function () {
				// WooCommerce animates the appearing of the product's price when necessary options have been selected,
				// or its disappearing when some choices are missing. We first try to find an ongoing animation to
				// update our widget *after* the animation has taken place, so that it uses up-to-date information/DOM
				// in AlmaInitWidget.
				var $amountElement = getAmountElement()
				var timer          = $.timers.find(
					function ( t ) {
						return t.elem === jQuery( $amountElement ).closest( '.woocommerce-variation' ).get( 0 )
					}
				)

				if ( timer ) {
					window.setTimeout( window.AlmaInitWidget, timer.anim.duration )
				} else if ( isVisible( $amountElement ) || ! settings.amountQuerySelector ) {
					window.AlmaInitWidget()
				}
			}
		);
	}
} )( jQuery );
