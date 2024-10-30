<?php
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}
/**
 * @package CardlessPay by Cardless Paytech 
 * @version 1.0.0
 */
/**
 * Plugin Name: CardlessPay by Cardless Paytech
 * Description: CardlessPay gateway for WooCommerce
 * Author: Cardless Paytech 
 * Contributors: cardlessmoney
 * Version: 1.0.0
 * Author URI: 
 * Copyright: © 2019 Cardless Paytech 
 *
 * Tested up to: 5.2.2
 * WC requires at least: 3.0.0
 * WC tested up to: 3.7.0
 *Copyright © 2019 Cardless Paytech 
 *
 * License: Modified MIT license
 *
 *Permission is hereby granted, free of charge, to any person obtaining a copy
 *of this plugin software and associated documentation files (the "Software"),
 *to deal in the Software, including without limitation the rights to use, copy,
 *modify, merge, publish, and distribute the Software, and to permit persons to
 *whom the Software is furnished to do so, subject to the following conditions:
 *
 *The above copyright notice and this permission notice shall be included in all
 *copies or substantial portions of the Software.
 *
 *Distributed copies of the Software either in whole or in part must be provided
 *free of charge to the persons to whom the Software is furnished.
 *
 *It is wholly understood that the term "Software" applies solely to this code and
 *associated documentation files and not to any other property or softwares owned
 *by the copyright holders, including but not limited to any software this code may
 *interface with as a necessary component.
 *
 *THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 *AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 *SOFTWARE.
 */

if (!class_exists('Woocommerce_Gateway_Cardless_Pay')) {
	include_once('includes/cardless_extra_functions.php');
	include_once('includes/cardless_settings.php');
	/**
	 * class:   Woocommerce_Gateway_Cardless_Pay
	 * desc:    plugin class to Woocommerce Gateway CardlessPay by Cardless Paytech 
	 */
	class Woocommerce_Gateway_Cardless_Pay
	{

		private static $instance;

		public static function instance()
		{
			if (!self::$instance) {
				self::$instance = new Woocommerce_Gateway_Cardless_Pay();
				$networkFlag = False;
				if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')), true)) {
					$networkFlag = True;
				} else {
					$active_plugins_var = (array) get_network_option(null, 'active_sitewide_plugins');
					if (is_array($active_plugins_var) && count($active_plugins_var) != 0 && in_array('woocommerce/woocommerce.php', $active_plugins_var)) {
						$networkFlag = True;
					}
				}
				if ($networkFlag) {
					self::$instance->setup_constants();
					self::$instance->hooks();
					self::$instance->includes();
					self::$instance->load_textdomain();

					add_filter('woocommerce_payment_gateways', array(self::$instance, 'add_wc_gateway'));
				}
			}
			return self::$instance;
		}

		private function setup_constants()
		{
			// Plugin path
			define('WOO_CPCE_DIR', plugin_dir_path(__FILE__));

			// Plugin URL
			define('WOO_CPCE_URL', plugin_dir_url(__FILE__));
		}

		private function hooks()
		{
			register_activation_hook(__FILE__, array('Woocommerce_Gateway_Cardless_Pay', 'activate'));
			register_deactivation_hook(__FILE__, array('Woocommerce_Gateway_Cardless_Pay', 'deactivate'));
		}

		private function includes()
		{
			require_once WOO_CPCE_DIR . 'includes/gateway.php';
		}

		/**
		 * Add the gateway to WooCommerce.
		 *
		 * @access public
		 * @param  array  $methods
		 * @return array
		 */
		function add_wc_gateway($methods)
		{

			$methods[] = 'WC_Gateway_Cardless_Pay';
			return $methods;
		}

		public function load_textdomain()
		{
			// Set filter for language directory
			$lang_dir	 = WOO_CPCE_DIR . '/languages/';
			$lang_dir	 = apply_filters('woo_gateway_cardless_money_lang_dir', $lang_dir);

			// Traditional WordPress plugin locale filter
			$locale	 = apply_filters('plugin_locale', get_locale(), '');
			$mofile	 = sprintf('%1$s-%2$s.mo', 'woocommerce-gateway-cardless-money', $locale);

			// Setup paths to current locale file
			$mofile_local	 = $lang_dir . $mofile;
			$mofile_global	 = WP_LANG_DIR . '/woocommerce-gateway-cardless-money/' . $mofile;

			if (file_exists($mofile_global)) {
				// Look in global /wp-content/languages/woocommerce-gateway-cardless-money/ folder
				load_textdomain('woocommerce-gateway-cardless-money', $mofile_global);
			} elseif (file_exists($mofile_local)) {
				// Look in local /wp-content/plugins/woocommerce-gateway-cardless-money/languages/ folder
				load_textdomain('woocommerce-gateway-cardless-money', $mofile_local);
			}
			else {
				// Load the default language files
				load_plugin_textdomain('woocommerce-gateway-cardless-money', false, $lang_dir);
			}
		} // END public function __construct()

		public static function activate()
		{
			flush_rewrite_rules();
		}

		public static function deactivate()
		{
			flush_rewrite_rules();
		}
	} // END class Woocommerce_Gateway_Cardless_Pay

} // END if(!class_exists("Woocommerce_Gateway_Cardless_Pay"))

function woocommerce_gateway_cardless_money_load()
{
	if (!class_exists('WooCommerce')) {

		require_once(ABSPATH . 'wp-admin/includes/plugin.php');

		$plugins = get_plugins();

		foreach ($plugins as $plugin_path => $plugin) {
			if ('WooCommerce' === $plugin['Name']) {
				define('HAS_WOO', true);
				break;
			}
		}
		add_action('admin_notices', 'woocommerce_gateway_cardless_money_notice');
	} else { //else WooCommerce class exists
		return Woocommerce_Gateway_Cardless_Pay::instance();
	}
}
add_action('plugins_loaded', 'woocommerce_gateway_cardless_money_load');

function woocommerce_gateway_cardless_money_notice()
{
	if (HAS_WOO) {
		echo '<div class="error"><p>' . wp_kses(__('CardlessPay by Cardless Paytech  add-on requires WooCommerce! Please activate it to continue!', 'woocommerce-gateway-cardless-money'), $allowed_html_array) . '</p></div>';
	} else {
		echo '<div class="error"><p>' . wp_kses(__('CardlessPay by Cardless Paytech  add-on requires WooCommerce! Please install it to continue!', 'woocommerce-gateway-cardless-money'), $allowed_html_array) . '</p></div>';
	}
}
