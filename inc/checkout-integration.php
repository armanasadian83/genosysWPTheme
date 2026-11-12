<?php
/**
 * WooCommerce data and processing for the Genosys classic checkout.
 *
 * Keep WooCommerce in charge of field validation, shipping selection, coupons,
 * order creation, stock and payment processing. The theme only renders its data.
 *
 * @package Genosys
 */

defined( 'ABSPATH' ) || exit;

/** Localize digits in HTML text without corrupting tags or entity codes. */
function genosys_checkout_localize_html( $html ) {
	if ( ! function_exists( 'genosys_persian_number' ) ) {
		return $html;
	}
	$parts = function_exists( 'wp_html_split' ) ? wp_html_split( $html ) : array( $html );
	foreach ( $parts as &$part ) {
		if ( '' !== $part && '<' !== $part[0] ) {
			$part = esc_html( genosys_persian_number( html_entity_decode( $part, ENT_QUOTES, 'UTF-8' ) ) );
		}
	}
	return implode( '', $parts );
}

/** A formatted WooCommerce amount with Persian digits in text nodes only. */
function genosys_checkout_price( $amount ) {
	return genosys_checkout_localize_html( wc_price( $amount ) );
}

/** Render an actual Woo checkout field, retaining its extensions and validation. */
function genosys_checkout_render_field( $checkout, $key, $overrides = array() ) {
	if ( ! $checkout || ! method_exists( $checkout, 'get_checkout_fields' ) ) {
		return;
	}
	$field = null;
	foreach ( $checkout->get_checkout_fields() as $fields ) {
		if ( isset( $fields[ $key ] ) ) {
			$field = $fields[ $key ];
			break;
		}
	}
	if ( ! is_array( $field ) ) {
		return;
	}
	$field = array_merge( $field, $overrides );
	$value = $checkout->get_value( $key );
	if ( '' === (string) $value && is_user_logged_in() ) {
		if ( 'billing_first_name' === $key ) {
			$value = (string) get_user_meta( get_current_user_id(), 'first_name', true );
		} elseif ( 'billing_last_name' === $key ) {
			$value = (string) get_user_meta( get_current_user_id(), 'last_name', true );
		} elseif ( 'billing_email' === $key ) {
			$value = (string) wp_get_current_user()->user_email;
		}
	}
	if ( 'billing_phone' === $key && '' === (string) $value && is_user_logged_in() ) {
		$value = (string) get_user_meta( get_current_user_id(), 'genosys_phone', true );
	}
	if ( 'billing_country' === $key && '' === (string) $value ) {
		$value = WC()->countries->get_base_country();
	}
	woocommerce_form_field( $key, $field, $value );
}

/** Do not lose checkout fields inserted by Woo extensions. */
function genosys_checkout_render_extra_fields( $checkout, $fieldset, $exclude = array() ) {
	if ( ! $checkout || ! method_exists( $checkout, 'get_checkout_fields' ) ) {
		return;
	}
	foreach ( $checkout->get_checkout_fields( $fieldset ) as $key => $field ) {
		if ( ! in_array( $key, $exclude, true ) ) {
			genosys_checkout_render_field( $checkout, $key );
		}
	}
}

/** Match the visible contact/address requirements without bypassing Woo validation. */
function genosys_checkout_optional_email( $fields ) {
	if ( isset( $fields['billing']['billing_email'] ) ) {
		$fields['billing']['billing_email']['required'] = ! is_user_logged_in() && (bool) apply_filters( 'woocommerce_checkout_registration_required', 'yes' !== get_option( 'woocommerce_enable_guest_checkout' ) );
	}
	foreach ( array( 'billing_first_name', 'billing_last_name', 'billing_phone', 'billing_state', 'billing_city', 'billing_address_1', 'billing_postcode' ) as $key ) {
		if ( isset( $fields['billing'][ $key ] ) ) {
			$fields['billing'][ $key ]['required'] = true;
		}
	}
	return $fields;
}
add_filter( 'woocommerce_checkout_fields', 'genosys_checkout_optional_email', 20 );

/** The custom checkout only ships within Iran, including older saved addresses. */
function genosys_checkout_iran_posted_data( $data ) {
	if ( ! empty( $data['genosys_checkout_layout'] ) || isset( $_POST['genosys_checkout_layout'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Woo verifies the checkout nonce before processing.
		$data['billing_country']  = 'IR';
		$data['shipping_country'] = 'IR';
	}
	return $data;
}
add_filter( 'woocommerce_checkout_posted_data', 'genosys_checkout_iran_posted_data', 20 );

/** Match the SMS sign-in number format when placing an order. */
function genosys_checkout_validate_mobile( $data, $errors ) {
	if ( ! isset( $_POST['genosys_checkout_layout'] ) || '1' !== (string) wp_unslash( $_POST['genosys_checkout_layout'] ) || empty( $data['billing_phone'] ) || ! function_exists( 'genosys_sms_normalize_phone' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Woo verifies its checkout nonce before this validation hook.
		return;
	}
	if ( '' === genosys_sms_normalize_phone( (string) $data['billing_phone'] ) && ! $errors->get_error_message( 'billing_phone_validation' ) ) {
		$errors->add( 'billing_phone_genosys_validation', 'شماره موبایل معتبر نیست.', array( 'id' => 'billing_phone' ) );
	}
}
add_action( 'woocommerce_after_checkout_validation', 'genosys_checkout_validate_mobile', 10, 2 );

/** Keep the replacement rates confined to this theme's checkout and Woo AJAX. */
function genosys_checkout_is_shipping_context() {
	if ( function_exists( 'is_checkout' ) && is_checkout() && ! ( function_exists( 'is_order_received_page' ) && is_order_received_page() ) && ! ( function_exists( 'is_checkout_pay_page' ) && is_checkout_pay_page() ) ) {
		return true;
	}
	if ( isset( $_POST['genosys_checkout_layout'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Context check only, no mutation.
		return true;
	}
	if ( isset( $_POST['post_data'] ) && is_string( $_POST['post_data'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Context check only.
		parse_str( wp_unslash( $_POST['post_data'] ), $posted );
		return ! empty( $posted['genosys_checkout_layout'] );
	}
	return false;
}

/** Ensure an older foreign customer address cannot select an overseas zone. */
function genosys_checkout_iran_shipping_packages( $packages ) {
	if ( ! genosys_checkout_is_shipping_context() || ! is_array( $packages ) ) {
		return $packages;
	}
	foreach ( $packages as &$package ) {
		if ( is_array( $package ) ) {
			if ( isset( $package['destination'] ) && is_array( $package['destination'] ) ) {
				$package['destination']['country'] = 'IR';
			}
			// Woo caches package rates; a new schema key refreshes previous sessions.
			$package['genosys_checkout_shipping_version'] = '2';
		}
	}
	unset( $package );
	return $packages;
}
add_filter( 'woocommerce_cart_shipping_packages', 'genosys_checkout_iran_shipping_packages', 20 );

/** Tipax is paid upon receipt; Post charges the amount saved in theme settings. */
function genosys_checkout_shipping_rates( $rates, $package ) {
	if ( ! genosys_checkout_is_shipping_context() || ! is_array( $rates ) || ! is_array( $package ) || ! class_exists( 'WC_Shipping_Rate' ) ) {
		return $rates;
	}
	if ( isset( $package['destination']['country'] ) && 'IR' !== strtoupper( (string) $package['destination']['country'] ) ) {
		return $rates;
	}

	$tipax = new WC_Shipping_Rate( 'genosys_tipax', 'تیپاکس', 0, array(), 'genosys_tipax' );
	if ( method_exists( $tipax, 'set_description' ) ) {
		$tipax->set_description( 'کرایه هنگام تحویل توسط مشتری پرداخت می‌شود' );
	}
	$tipax->add_meta_data( 'نحوه پرداخت کرایه', 'پس کرایه' );
	$checkout_rates = array( $tipax->get_id() => $tipax );

	$cost = get_option( 'genosys_shipping_cost', '' );
	if ( '' !== $cost && is_numeric( $cost ) && (float) $cost >= 0 ) {
		$taxes = array();
		if ( function_exists( 'wc_tax_enabled' ) && wc_tax_enabled() && class_exists( 'WC_Tax' ) && function_exists( 'WC' ) && WC()->customer && ! WC()->customer->get_is_vat_exempt() ) {
			$taxes = WC_Tax::calc_shipping_tax( (float) $cost, WC_Tax::get_shipping_tax_rates() );
		}
		$post = new WC_Shipping_Rate( 'genosys_post', 'پست', (float) $cost, $taxes, 'genosys_post' );
		if ( method_exists( $post, 'set_description' ) ) {
			$post->set_description( 'ارسال به سراسر کشور' );
		}
		$checkout_rates[ $post->get_id() ] = $post;
	}

	// Preserve coupon-provided free shipping as an additional valid Woo rate.
	if ( function_exists( 'WC' ) && WC()->cart ) {
		foreach ( WC()->cart->get_coupons() as $coupon ) {
			if ( is_object( $coupon ) && method_exists( $coupon, 'get_free_shipping' ) && $coupon->get_free_shipping() ) {
				foreach ( $rates as $rate ) {
					if ( $rate instanceof WC_Shipping_Rate && 'free_shipping' === $rate->get_method_id() ) {
						$checkout_rates[ $rate->get_id() ] = $rate;
						break 2;
					}
				}
			}
		}
	}
	return $checkout_rates;
}
add_filter( 'woocommerce_package_rates', 'genosys_checkout_shipping_rates', 20, 2 );

/** Available rates with Woo's real shipping_method[index] input names. */
function genosys_checkout_render_shipping_methods() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart || ! WC()->cart->needs_shipping() ) {
		echo '<p class="gco-shipping-options__message">این سفارش نیازی به ارسال ندارد.</p>';
		return;
	}

	$packages = WC()->shipping()->get_packages();
	$chosen   = WC()->session ? (array) WC()->session->get( 'chosen_shipping_methods', array() ) : array();
	if ( ! $packages ) {
		echo '<p class="gco-shipping-options__message">برای نمایش روش‌های ارسال، نشانی تحویل را کامل کنید.</p>';
		return;
	}

	foreach ( $packages as $index => $package ) {
		$rates = isset( $package['rates'] ) && is_array( $package['rates'] ) ? $package['rates'] : array();
		if ( count( $packages ) > 1 ) {
			printf( '<h3 class="gco-shipping-options__package">%s</h3>', esc_html( sprintf( 'بسته %s', function_exists( 'genosys_persian_number' ) ? genosys_persian_number( (string) ( $index + 1 ) ) : (string) ( $index + 1 ) ) ) );
		}
		if ( ! $rates ) {
			echo '<p class="gco-shipping-options__message">برای این نشانی، روش ارسالی در دسترس نیست.</p>';
			continue;
		}
		$selected = isset( $chosen[ $index ] ) && isset( $rates[ $chosen[ $index ] ] ) ? $chosen[ $index ] : key( $rates );
		foreach ( $rates as $rate ) {
			if ( ! $rate instanceof WC_Shipping_Rate ) {
				continue;
			}
			$id          = $rate->get_id();
			$input_id    = 'shipping_method_' . (int) $index . '_' . sanitize_title( $id );
			$is_selected = $selected === $id;
			$cost        = (float) $rate->get_cost();
			if ( WC()->cart->display_prices_including_tax() ) {
				$cost += (float) $rate->get_shipping_tax();
			}
			$price = 'genosys_tipax' === $rate->get_method_id() ? 'پس کرایه' : ( $cost > 0 ? genosys_checkout_price( $cost ) : ( 'free_shipping' === $rate->get_method_id() || 'genosys_post' === $rate->get_method_id() ? 'رایگان' : '' ) );
			$description = 'genosys_tipax' === $rate->get_method_id() ? 'کرایه هنگام تحویل توسط مشتری پرداخت می‌شود' : ( 'genosys_post' === $rate->get_method_id() ? 'ارسال به سراسر کشور' : ( method_exists( $rate, 'get_description' ) ? $rate->get_description() : '' ) );
			?>
			<label class="gco-shipping-option<?php echo $is_selected ? ' is-selected' : ''; ?>" for="<?php echo esc_attr( $input_id ); ?>">
				<input class="shipping_method gco-shipping-option__input" type="radio" name="shipping_method[<?php echo esc_attr( (string) $index ); ?>]" data-index="<?php echo esc_attr( (string) $index ); ?>" id="<?php echo esc_attr( $input_id ); ?>" value="<?php echo esc_attr( $id ); ?>" <?php checked( $is_selected ); ?> />
				<span class="gco-shipping-option__radio" aria-hidden="true"></span>
				<span class="gco-shipping-option__text"><strong class="gco-shipping-option__name"><?php echo esc_html( $rate->get_label() ); ?></strong><?php if ( $description ) : ?><small class="gco-shipping-option__description"><?php echo esc_html( $description ); ?></small><?php endif; ?></span>
				<?php if ( '' !== $price ) : ?><strong class="gco-shipping-option__price"><?php echo wp_kses_post( $price ); ?></strong><?php endif; ?>
			</label>
			<?php
		}
	}
}

/** Live cart and total summary. Must be wrapped in .woocommerce-checkout-review-order-table. */
function genosys_checkout_render_summary() {
	$cart = function_exists( 'WC' ) && WC() ? WC()->cart : null;
	if ( ! $cart ) {
		return;
	}
	$prices_include_tax = $cart->display_prices_including_tax();
	$sale_savings       = 0.0;
	foreach ( $cart->get_cart() as $item ) {
		$product = isset( $item['data'] ) ? $item['data'] : null;
		if ( ! $product || ! $product->exists() ) {
			continue;
		}
		$regular = $product->get_regular_price();
		if ( '' !== $regular && (float) $regular > (float) $product->get_price() ) {
			$sale_savings += max( 0, wc_get_price_to_display( $product, array( 'price' => (float) $regular, 'display_context' => 'cart' ) ) - wc_get_price_to_display( $product, array( 'display_context' => 'cart' ) ) ) * (int) $item['quantity'];
		}
	}
	$subtotal = (float) $cart->get_subtotal() + ( $prices_include_tax ? (float) $cart->get_subtotal_tax() : 0 );
	$discount = (float) $cart->get_discount_total() + ( $prices_include_tax ? (float) $cart->get_discount_tax() : 0 );
	$shipping = (float) $cart->get_shipping_total() + ( $prices_include_tax ? (float) $cart->get_shipping_tax() : 0 );
	?>
	<h2 class="gco-summary__title" id="gco-summary-heading">سفارش شما</h2>
	<div class="gco-summary__items">
		<?php foreach ( $cart->get_cart() as $item ) : ?>
			<?php
			$product = isset( $item['data'] ) ? $item['data'] : null;
			if ( ! $product || ! $product->exists() || empty( $item['quantity'] ) ) {
				continue;
			}
			$quantity = (int) $item['quantity'];
			?>
			<div class="gco-summary__item">
				<span class="gco-summary__thumb"><?php echo wp_kses_post( $product->get_image( 'woocommerce_thumbnail', array( 'loading' => 'lazy' ) ) ); ?><span class="gco-summary__quantity"><?php echo esc_html( function_exists( 'genosys_persian_number' ) ? genosys_persian_number( (string) $quantity ) : (string) $quantity ); ?></span></span>
			<span class="gco-summary__name"><?php echo esc_html( $product->get_name() ); ?><?php echo wp_kses_post( wc_get_formatted_cart_item_data( $item ) ); ?></span>
				<strong class="gco-summary__line-price"><?php echo wp_kses_post( genosys_checkout_localize_html( $cart->get_product_subtotal( $product, $quantity ) ) ); ?></strong>
			</div>
		<?php endforeach; ?>
	</div>
	<div class="gco-summary__totals">
		<div class="gco-summary__row"><span>جمع کالاها</span><strong><?php echo wp_kses_post( genosys_checkout_price( $subtotal + $sale_savings ) ); ?></strong></div>
		<?php if ( $sale_savings + $discount > 0 ) : ?><div class="gco-summary__row gco-summary__row--discount"><span>سود شما از تخفیف</span><strong><?php echo wp_kses_post( genosys_checkout_price( $sale_savings + $discount ) ); ?></strong></div><?php endif; ?>
		<div class="gco-summary__row"><span>هزینه ارسال</span><strong><?php
			if ( ! $cart->needs_shipping() ) {
				echo '—';
			} elseif ( $cart->has_calculated_shipping() && $cart->get_shipping_methods() ) {
				echo $shipping > 0 ? wp_kses_post( genosys_checkout_price( $shipping ) ) : esc_html( genosys_checkout_selected_shipping_label() );
			} else {
				echo 'پس از انتخاب نشانی';
			}
		?></strong></div>
		<?php foreach ( $cart->get_fees() as $fee ) : ?><div class="gco-summary__row"><span><?php echo esc_html( $fee->name ); ?></span><strong><?php echo wp_kses_post( genosys_checkout_price( $fee->total + ( $prices_include_tax ? $fee->tax : 0 ) ) ); ?></strong></div><?php endforeach; ?>
		<?php if ( ! $prices_include_tax && $cart->get_total_tax() > 0 ) : ?><div class="gco-summary__row"><span>مالیات</span><strong><?php echo wp_kses_post( genosys_checkout_price( $cart->get_total_tax() ) ); ?></strong></div><?php endif; ?>
	</div>
	<?php if ( function_exists( 'wc_coupons_enabled' ) && wc_coupons_enabled() ) : ?>
		<div class="gco-summary__coupon">
			<label class="screen-reader-text" for="gco-coupon-code">کد تخفیف</label>
			<input type="text" id="gco-coupon-code" name="gco_coupon_code" placeholder="کد تخفیف دارید؟" autocomplete="off" />
			<button type="button" data-gco-apply-coupon>اعمال</button>
		</div>
		<?php foreach ( $cart->get_applied_coupons() as $code ) : ?><p class="gco-summary__applied-coupon"><?php echo esc_html( $code ); ?> <a href="<?php echo esc_url( add_query_arg( 'remove_coupon', rawurlencode( $code ), wc_get_checkout_url() ) ); ?>" class="woocommerce-remove-coupon" data-coupon="<?php echo esc_attr( $code ); ?>">حذف</a></p><?php endforeach; ?>
	<?php endif; ?>
	<div class="gco-summary__total"><span>مبلغ قابل پرداخت</span><strong><?php echo wp_kses_post( genosys_checkout_price( $cart->get_total( 'edit' ) ) ); ?></strong></div>
	<?php $shipping_note = genosys_checkout_selected_shipping_description(); if ( $shipping_note ) : ?><p class="gco-summary__shipping-note"><?php echo esc_html( $shipping_note ); ?></p><?php endif; ?>
	<?php
}

/** Label for a zero-cost rate, without calling an unpaid method "free". */
function genosys_checkout_selected_shipping_label() {
	if ( ! function_exists( 'WC' ) || ! WC()->shipping() || ! WC()->session ) {
		return 'در زمان تحویل';
	}
	$chosen = (array) WC()->session->get( 'chosen_shipping_methods', array() );
	foreach ( WC()->shipping()->get_packages() as $index => $package ) {
		if ( isset( $chosen[ $index ], $package['rates'][ $chosen[ $index ] ] ) ) {
			$rate = $package['rates'][ $chosen[ $index ] ];
			return 'genosys_tipax' === $rate->get_method_id() ? 'پس کرایه' : ( 'free_shipping' === $rate->get_method_id() || 'genosys_post' === $rate->get_method_id() ? 'رایگان' : $rate->get_label() );
		}
	}
	return 'در زمان تحویل';
}

/** Show the selected Woo shipping rate's own explanation, if configured. */
function genosys_checkout_selected_shipping_description() {
	if ( ! function_exists( 'WC' ) || ! WC()->shipping() || ! WC()->session ) {
		return '';
	}
	$chosen = (array) WC()->session->get( 'chosen_shipping_methods', array() );
	foreach ( WC()->shipping()->get_packages() as $index => $package ) {
		if ( isset( $chosen[ $index ], $package['rates'][ $chosen[ $index ] ] ) ) {
			$rate = $package['rates'][ $chosen[ $index ] ];
			if ( 'genosys_tipax' === $rate->get_method_id() ) {
				return 'کرایه هنگام تحویل توسط مشتری پرداخت می‌شود';
			}
			if ( 'genosys_post' === $rate->get_method_id() ) {
				return 'ارسال به سراسر کشور';
			}
			if ( method_exists( $rate, 'get_description' ) ) {
				return trim( wp_strip_all_tags( $rate->get_description() ) );
			}
		}
	}
	return '';
}

/** Replace Woo's stock order table fragment and refresh shipping when address changes. */
function genosys_checkout_order_review_fragments( $fragments ) {
	$post_data = array();
	if ( isset( $_POST['post_data'] ) && is_string( $_POST['post_data'] ) ) {
		wp_parse_str( wp_unslash( $_POST['post_data'] ), $post_data );
	}
	if ( empty( $post_data['genosys_checkout_layout'] ) || ! function_exists( 'WC' ) || ! WC()->cart ) {
		return $fragments;
	}
	ob_start();
	echo '<div class="gco-summary-inner woocommerce-checkout-review-order-table">';
	genosys_checkout_render_summary();
	echo '</div>';
	$fragments['.woocommerce-checkout-review-order-table'] = ob_get_clean();
	ob_start();
	echo '<div class="gco-shipping-options">';
	genosys_checkout_render_shipping_methods();
	echo '</div>';
	$fragments['.gco-shipping-options'] = ob_get_clean();
	return $fragments;
}
add_filter( 'woocommerce_update_order_review_fragments', 'genosys_checkout_order_review_fragments', 20 );

/** The SMS login phone is the fallback for WP administrators without billing data. */
function genosys_checkout_phone_default( $value, $key ) {
	if ( 'billing_phone' !== $key || null !== $value || ! is_user_logged_in() ) {
		return $value;
	}
	$billing_phone = (string) get_user_meta( get_current_user_id(), 'billing_phone', true );
	if ( '' !== $billing_phone ) {
		return null;
	}
	$phone = (string) get_user_meta( get_current_user_id(), 'genosys_phone', true );
	return '' !== $phone ? $phone : null;
}
add_filter( 'woocommerce_checkout_get_value', 'genosys_checkout_phone_default', 10, 2 );

/** Expose the card-to-card gateway to Woo's native checkout processing. */
function genosys_checkout_add_card_gateway( $gateways ) {
	if ( class_exists( 'Genosys_WC_Gateway_Card_To_Card' ) ) {
		$gateways[] = 'Genosys_WC_Gateway_Card_To_Card';
	}
	return $gateways;
}
add_filter( 'woocommerce_payment_gateways', 'genosys_checkout_add_card_gateway' );

/** Normalize a configured card number for spacing and copy, including Persian digits. */
function genosys_checkout_card_digits( $raw ) {
	$ascii = strtr( (string) $raw, array(
		'۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
		'۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
		'٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
		'٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
	) );
	return preg_replace( '/\D+/', '', $ascii );
}

/** Register after Woo's payment gateway base is available. */
function genosys_checkout_define_card_gateway() {
	if ( ! class_exists( 'WC_Payment_Gateway' ) || class_exists( 'Genosys_WC_Gateway_Card_To_Card' ) ) {
		return;
	}
	class Genosys_WC_Gateway_Card_To_Card extends WC_Payment_Gateway {
		public function __construct() {
			$this->id                 = 'genosys_card_to_card';
			$this->method_title       = 'کارت به کارت ژنوسیس';
			$this->method_description = 'پرداخت دستی؛ سفارش تا تأیید رسید در حالت در انتظار بررسی می‌ماند.';
			$this->title              = 'کارت به کارت';
			$this->description        = 'اطلاعات کارت و ارسال رسید پس از ثبت سفارش نمایش داده می‌شود.';
			$this->has_fields         = false;
			$this->supports           = array( 'products' );
			$this->enabled            = 'yes';
		}

		public function is_available() {
			foreach ( array( 'genosys_checkout_card_number', 'genosys_checkout_card_holder', 'genosys_checkout_bank_name', 'genosys_checkout_whatsapp' ) as $key ) {
				if ( '' === trim( (string) get_option( $key, '' ) ) ) {
					return false;
				}
			}
			if ( 16 !== strlen( genosys_checkout_card_digits( get_option( 'genosys_checkout_card_number', '' ) ) ) || '' === genosys_checkout_whatsapp_receipt_url() ) {
				return false;
			}
			return parent::is_available();
		}

		public function process_payment( $order_id ) {
			$order = wc_get_order( $order_id );
			if ( ! $order ) {
				wc_add_notice( 'ثبت سفارش انجام نشد. لطفاً دوباره تلاش کنید.', 'error' );
				return array( 'result' => 'failure' );
			}
			// Preserve the payment instructions used for this order if settings change later.
			$order->update_meta_data( '_genosys_payment_bank', (string) get_option( 'genosys_checkout_bank_name', '' ) );
			$order->update_meta_data( '_genosys_payment_card', genosys_checkout_card_digits( get_option( 'genosys_checkout_card_number', '' ) ) );
			$order->update_meta_data( '_genosys_payment_holder', (string) get_option( 'genosys_checkout_card_holder', '' ) );
			$order->update_meta_data( '_genosys_payment_whatsapp', (string) get_option( 'genosys_checkout_whatsapp', '' ) );
			// WooCommerce reduces stock when the order enters on-hold. Do not mark paid.
			$order->update_status( 'on-hold', 'در انتظار واریز کارت‌به‌کارت و بررسی رسید پرداخت.' );
			if ( function_exists( 'genosys_package_order_has_packages' ) && genosys_package_order_has_packages( $order ) ) {
				$fresh = wc_get_order( $order_id ); $fresh->read_meta_data( true );
				$stock_error = $fresh->get_meta( '_genosys_package_stock_error', true );
				$error = is_array( $stock_error ) ? 'reduce' === ( $stock_error['operation'] ?? '' ) : (bool) $stock_error;
				$complete = $fresh->get_data_store()->get_stock_reduced( $order_id );
				if ( 'yes' === get_option( 'woocommerce_manage_stock' ) ) {
					foreach ( $fresh->get_items() as $item ) {
						if ( ! $item->get_meta( '_genosys_package_components', true ) ) { continue; }
						$ledger = $item->get_meta( '_genosys_package_stock_ledger', true );
						$complete = $complete && is_array( $ledger ) && isset( $ledger['remaining'], $ledger['refunded'] ) && (float) $ledger['remaining'] + (float) $ledger['refunded'] >= (float) $item->get_quantity();
					}
				}
				if ( $error || ! $complete ) {
					$fresh->update_status( 'failed', 'ثبت سفارش به‌علت عدم تأیید موجودی اجزای پکیج متوقف شد.' );
					wc_release_stock_for_order( $fresh );
					wc_add_notice( 'موجودی محصولات پکیج برای ثبت سفارش تأیید نشد. سبد خرید را بررسی کنید و دوباره تلاش کنید.', 'error' );
					return array( 'result' => 'failure' );
				}
			}
			WC()->cart->empty_cart();
			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url( $order ),
			);
		}
	}
}
add_action( 'after_setup_theme', 'genosys_checkout_define_card_gateway', 20 );
add_action( 'init', 'genosys_checkout_define_card_gateway', 5 );

/** A WhatsApp receipt link for the order-received screen. */
function genosys_checkout_whatsapp_receipt_url( $order_number = '', $saved_phone = null ) {
	$phone = genosys_checkout_card_digits( null === $saved_phone ? get_option( 'genosys_checkout_whatsapp', '' ) : $saved_phone );
	if ( 0 === strpos( $phone, '00' ) ) {
		$phone = substr( $phone, 2 );
	} elseif ( 0 === strpos( $phone, '0' ) ) {
		$phone = '98' . substr( $phone, 1 );
	} elseif ( 0 === strpos( $phone, '9' ) ) {
		$phone = '98' . $phone;
	}
	if ( '' === $phone ) {
		return '';
	}
	$message = '' !== (string) $order_number
		? 'سلام، تصویر رسید پرداخت سفارش شماره ' . $order_number . ' را ارسال می‌کنم.'
		: 'سلام، درباره ارسال رسید پرداخت سفارش ژنوسیس پیام می‌دهم.';
	return 'https://wa.me/' . rawurlencode( $phone ) . '?text=' . rawurlencode( $message );
}

/** Show the saved payment instructions only after the order has been placed. */
function genosys_checkout_card_thankyou( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( ! $order || 'genosys_card_to_card' !== $order->get_payment_method() ) {
		return;
	}
	$card        = genosys_checkout_card_digits( $order->get_meta( '_genosys_payment_card', true ) );
	$card_print = trim( chunk_split( $card, 4, ' ' ) );
	$holder      = (string) $order->get_meta( '_genosys_payment_holder', true );
	$bank        = (string) $order->get_meta( '_genosys_payment_bank', true );
	$saved_phone = (string) $order->get_meta( '_genosys_payment_whatsapp', true );
	$whatsapp   = genosys_checkout_whatsapp_receipt_url( $order->get_order_number(), $saved_phone );
	?>
	<section class="gco-receipt-instructions" dir="rtl">
		<h2>پرداخت کارت به کارت</h2>
		<?php if ( $order->is_paid() ) : ?>
			<p>پرداخت این سفارش ثبت شده است.</p>
		<?php elseif ( 16 === strlen( $card ) && '' !== $bank && '' !== $holder && '' !== $whatsapp ) : ?>
			<p class="gco-payment__intro">سفارش شما ثبت شد. مبلغ <?php echo wp_kses_post( $order->get_formatted_order_total() ); ?> را به کارت زیر واریز کنید و سپس تصویر فیش واریزی را در کادر زیر بارگذاری کنید.</p>
			<div class="gco-bank-card" aria-label="کارت بانکی برای پرداخت">
				<div class="gco-bank-card__top"><span class="gco-bank-card__bank"><small>نام بانک</small><strong><?php echo esc_html( $bank ); ?></strong></span><span class="gco-bank-card__chip" aria-hidden="true"></span></div>
				<div class="gco-bank-card__number" dir="ltr"><?php echo esc_html( $card_print ); ?></div>
				<div class="gco-bank-card__bottom"><span class="gco-bank-card__holder"><small>به نام</small><strong><?php echo esc_html( $holder ); ?></strong></span><span class="gco-bank-card__brand" aria-hidden="true">GENOSYS</span></div>
			</div>
			<button class="gco-copy-card" type="button" data-gco-copy-card data-card-number="<?php echo esc_attr( $card ); ?>"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="8" y="8" width="11" height="11" rx="2"/><path d="M16 8V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h3"/></svg><span>کپی شماره کارت</span></button>
			<?php get_template_part( 'woocommerce/checkout/receipt-uploader', null, array( 'receipt_order' => $order ) ); ?>
			<ol class="gco-payment__instructions"><li>مبلغ قابل پرداخت را به کارت بالا واریز کنید.</li><li>تصویر فیش واریزی را در کادر بالا انتخاب کنید.</li><li>پس از تأیید پرداخت، سفارش شما ارسال می‌شود.</li></ol>
		<?php else : ?>
			<p>سفارش شما ثبت شد، اما اطلاعات کارت این سفارش در دسترس نیست. لطفاً با پشتیبانی تماس بگیرید.</p>
		<?php endif; ?>
	</section>
	<?php
}
add_action( 'woocommerce_thankyou_genosys_card_to_card', 'genosys_checkout_card_thankyou' );
