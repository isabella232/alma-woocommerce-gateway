#!/bin/bash
set -Eeauo pipefail

wp_set_option_arr() {
  echo "$2" \
  | php -r "echo json_encode(unserialize(fgets(STDIN)));" \
  | /usr/local/bin/wp --format=json --path=/var/www/html --allow-root option set $1
}
wp_set_option() {
  /usr/local/bin/wp --path=/var/www/html --allow-root option set $1 "$2"
}
wp() {
  /usr/local/bin/wp --path=/var/www/html --allow-root $@
}

wp_set_option_arr woocommerce_alma_settings	'a:52:{s:7:"enabled";s:3:"yes";s:17:"selected_fee_plan";s:14:"general_10_0_0";s:21:"enabled_general_3_0_0";s:3:"yes";s:5:"title";s:39:"Monthly and Deferred Payments with Alma";s:11:"description";s:67:"Pay in deferred or multiple monthly payments with your credit card.";s:24:"display_cart_eligibility";s:3:"yes";s:27:"display_product_eligibility";s:3:"yes";s:37:"variable_product_price_query_selector";s:86:"form.variations_form div.woocommerce-variation-price span.woocommerce-Price-amount bdi";s:22:"excluded_products_list";s:0:"";s:36:"cart_not_eligible_message_gift_cards";s:66:"Some products cannot be paid with monthly or deferred installments";s:12:"live_api_key";s:32:"sk_test_w0asdHXEyOayGQwiSEeWB2Gi";s:12:"test_api_key";s:32:"sk_test_w0asdHXEyOayGQwiSEeWB2Gi";s:11:"environment";s:4:"test";s:5:"debug";s:3:"yes";s:16:"fully_configured";b:1;s:12:"keys_section";s:0:"";s:13:"debug_section";s:0:"";s:11:"merchant_id";s:43:"merchant_11lj0oR4qzR33WghMokIce4Oye4Kf4slU4";s:25:"min_amount_general_1_30_0";i:5000;s:25:"max_amount_general_1_30_0";i:500000;s:22:"enabled_general_1_30_0";s:3:"yes";s:30:"deferred_months_general_1_30_0";i:0;s:28:"deferred_days_general_1_30_0";i:30;s:33:"installments_count_general_1_30_0";i:1;s:24:"min_amount_general_3_0_0";i:5000;s:24:"max_amount_general_3_0_0";i:300000;s:29:"deferred_months_general_3_0_0";i:0;s:27:"deferred_days_general_3_0_0";i:0;s:32:"installments_count_general_3_0_0";i:3;s:24:"min_amount_general_4_0_0";i:10000;s:24:"max_amount_general_4_0_0";i:400000;s:21:"enabled_general_4_0_0";s:2:"no";s:29:"deferred_months_general_4_0_0";i:0;s:27:"deferred_days_general_4_0_0";i:0;s:32:"installments_count_general_4_0_0";i:4;s:24:"min_amount_general_7_0_0";i:5000;s:24:"max_amount_general_7_0_0";i:500000;s:21:"enabled_general_7_0_0";s:2:"no";s:29:"deferred_months_general_7_0_0";i:0;s:27:"deferred_days_general_7_0_0";i:0;s:32:"installments_count_general_7_0_0";i:7;s:25:"min_amount_general_10_0_0";i:20000;s:25:"max_amount_general_10_0_0";i:500000;s:22:"enabled_general_10_0_0";s:3:"yes";s:30:"deferred_months_general_10_0_0";i:0;s:28:"deferred_days_general_10_0_0";i:0;s:33:"installments_count_general_10_0_0";i:10;s:13:"enabled_3_0_0";s:2:"no";s:14:"enabled_1_30_0";s:2:"no";s:13:"enabled_4_0_0";s:2:"no";s:13:"enabled_7_0_0";s:2:"no";s:14:"enabled_10_0_0";s:2:"no";}'

wp post update 7 --page_template='template-fullwidth.php'
