<?php
/**
 * Installation related functions and actions.
 *
 * @author   VaLeXaR
 * @category Admin
 * @package  WPMPlugin/Classes
 */

namespace WPM\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WPM_Install Class.
 */
class WPM_Install {

	/**
	 * Hook in tabs.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'check_version' ), 5 );
	}

	/**
	 * Check WPMPlugin version and run the updater is required.
	 *
	 * This check is done on all requests and runs if he versions do not match.
	 */
	public static function check_version() {
		if ( get_option( 'wpm_version' ) !== WPM()->version ) {
			self::install();
		}
	}

	/**
	 * Install GP.
	 */
	public static function install() {
		global $wpdb;

		if ( ! defined( 'WPM_INSTALLING' ) ) {
			define( 'WPM_INSTALLING', true );
		}

		self::create_options();
		WPM_Config::load_config_run();
		self::update_gp_version();

		/*
		 * Deletes all expired transients. The multi-table delete syntax is used
		 * to delete the transient record from table a, and the corresponding
		 * transient_timeout record from table b.
		 *
		 * Based on code inside core's upgrade_network() function.
		 */
		$sql = "DELETE a, b FROM $wpdb->options a, $wpdb->options b
			WHERE a.option_name LIKE %s
			AND a.option_name NOT LIKE %s
			AND b.option_name = CONCAT( '_transient_timeout_', SUBSTRING( a.option_name, 12 ) )
			AND b.option_value < %d";
		$wpdb->query( $wpdb->prepare( $sql, $wpdb->esc_like( '_transient_' ) . '%', $wpdb->esc_like( '_transient_timeout_' ) . '%', time() ) );

		// Trigger action
		do_action( 'wpm_installed' );
	}

	/**
	 * Update GP version to current.
	 */
	private static function update_gp_version() {
		delete_option( 'wpm_version' );
		add_option( 'wpm_version', WPM()->version );
	}

	/**
	 * Default options.
	 *
	 * Sets up the default options used on the settings page.
	 */
	private static function create_options() {

		$config = WPM_Setup::instance();
		$languages = array();
		$installed_languages = $config->get_installed_languages();
		$translations = $config->get_translations();

		foreach ($installed_languages as $language ) {
			$languages[ $language ] = array(
				'name' => $translations[ $language ]['native_name'],
				'slug' => current( $translations[ $language ]['iso'] ),
				'flag' => current( $translations[ $language ]['iso'] ),
				'enable' => 1
			);
		}

		add_option( 'wpm_languages', $languages, '', 'yes' );
	}
}