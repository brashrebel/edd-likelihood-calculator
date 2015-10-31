<?php
/*
Plugin Name: Easy Digital Downloads - Likelihood Calculator
Plugin URI: http://realbigplugins.com
Description: Helps calculate the likelihood of certain purchases taking place based on store history.
Version: 0.1
Author: Kyle Maurer
Author URI: http://kyleblog.net
License: GPL2
Text Domain: edd-likelihood-calculator
Domain Path: languages
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! class_exists( 'EDD_Likelihood_Calculator' ) ) {
	/**
	 * Main EDD_Likelihood_Calculator class
	 *
	 * @since       0.1
	 */
	class EDD_Likelihood_Calculator {
		/**
		 * @var         EDD_Likelihood_Calculator $instance The one true EDD_Likelihood_Calculator
		 * @since       0.1
		 */
		private static $instance;

		/**
		 * Get active instance
		 *
		 * @access      public
		 * @since       0.1
		 * @return      object self::$instance The one true EDD_Likelihood_Calculator
		 */
		public static function instance() {
			if ( ! self::$instance ) {
				self::$instance = new EDD_Likelihood_Calculator();
				self::$instance->setup_constants();
				self::$instance->includes();
				self::$instance->load_textdomain();
				self::$instance->hooks();
			}

			return self::$instance;
		}

		/**
		 * Setup plugin constants
		 *
		 * @access      private
		 * @since       0.1
		 * @return      void
		 */
		private function setup_constants() {
			// Plugin version
			define( 'EDD_LIKELIHOOD_CALCULATOR_VER', '0.1' );
			// Plugin path
			define( 'EDD_LIKELIHOOD_CALCULATOR_DIR', plugin_dir_path( __FILE__ ) );
			// Plugin URL
			define( 'EDD_LIKELIHOOD_CALCULATOR_URL', plugin_dir_url( __FILE__ ) );
		}

		/**
		 * Include necessary files
		 *
		 * @access      private
		 * @since       0.1
		 * @return      void
		 */
		private function includes() {
			// Include scripts
			require_once EDD_LIKELIHOOD_CALCULATOR_DIR . 'includes/functions.php';
		}

		/**
		 * Run action and filter hooks
		 *
		 * @access      private
		 * @since       0.1
		 * @return      void
		 */
		private function hooks() {
			add_action('edd_reports_tabs', 'edd_likelihood_calculator_tab');
			add_action('edd_reports_tab_likelihood_calculator', 'edd_likelihood_calculator_page');
			// Handle licensing
//			if ( class_exists( 'EDD_License' ) ) {
//				$license = new EDD_License( __FILE__, 'Likelihood Calculator', EDD_LIKELIHOOD_CALCULATOR_VER, 'Kyle Maurer' );
//			}
		}

		/**
		 * Internationalization
		 *
		 * @access      public
		 * @since       0.1
		 * @return      void
		 */
		public function load_textdomain() {
			// Set filter for language directory
			$lang_dir = EDD_LIKELIHOOD_CALCULATOR_DIR . '/languages/';
			$lang_dir = apply_filters( 'edd_likelihood_calculator_languages_directory', $lang_dir );
			// Traditional WordPress plugin locale filter
			$locale = apply_filters( 'plugin_locale', get_locale(), 'edd-likelihood-calculator' );
			$mofile = sprintf( '%1$s-%2$s.mo', 'edd-likelihood-calculator', $locale );
			// Setup paths to current locale file
			$mofile_local  = $lang_dir . $mofile;
			$mofile_global = WP_LANG_DIR . '/edd-likelihood-calculator/' . $mofile;
			if ( file_exists( $mofile_global ) ) {
				// Look in global /wp-content/languages/edd-likelihood-calculator/ folder
				load_textdomain( 'edd-likelihood-calculator', $mofile_global );
			} elseif ( file_exists( $mofile_local ) ) {
				// Look in local /wp-content/plugins/edd-likelihood-calculator/languages/ folder
				load_textdomain( 'edd-likelihood-calculator', $mofile_local );
			} else {
				// Load the default language files
				load_plugin_textdomain( 'edd-likelihood-calculator', false, $lang_dir );
			}
		}


	}
} // End if class_exists check
/**
 * The main function responsible for returning the one true EDD_Likelihood_Calculator
 * instance to functions everywhere
 *
 * @since       0.1
 * @return      \EDD_Likelihood_Calculator The one true EDD_Likelihood_Calculator
 */
function EDD_Likelihood_Calculator_load() {
	if ( ! class_exists( 'Easy_Digital_Downloads' ) ) {
		if ( ! class_exists( 'EDD_Extension_Activation' ) ) {
			require_once 'includes/class.extension-activation.php';
		}
		$activation = new EDD_Extension_Activation( plugin_dir_path( __FILE__ ), basename( __FILE__ ) );
		$activation = $activation->run();

		return EDD_Likelihood_Calculator::instance();
	} else {
		return EDD_Likelihood_Calculator::instance();
	}
}

add_action( 'plugins_loaded', 'EDD_Likelihood_Calculator_load' );

