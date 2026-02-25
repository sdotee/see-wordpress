<?php
/**
 * Main plugin class (singleton).
 *
 * @package SDOTEE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SDOTEE_Plugin
 *
 * Central plugin class that initializes all modules.
 */
class SDOTEE_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var SDOTEE_Plugin|null
	 */
	private static ?SDOTEE_Plugin $instance = null;

	/**
	 * Settings module.
	 *
	 * @var SDOTEE_Settings
	 */
	public SDOTEE_Settings $settings;

	/**
	 * Admin module.
	 *
	 * @var SDOTEE_Admin
	 */
	public SDOTEE_Admin $admin;

	/**
	 * Short URL module.
	 *
	 * @var SDOTEE_ShortUrl
	 */
	public SDOTEE_ShortUrl $shorturl;

	/**
	 * File module.
	 *
	 * @var SDOTEE_File
	 */
	public SDOTEE_File $file;

	/**
	 * Text module.
	 *
	 * @var SDOTEE_Text
	 */
	public SDOTEE_Text $text;

	/**
	 * Get singleton instance.
	 *
	 * @return SDOTEE_Plugin
	 */
	public static function get_instance(): SDOTEE_Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor - private for singleton.
	 */
	private function __construct() {
		$this->init_modules();
	}

	/**
	 * Initialize all sub-modules.
	 */
	private function init_modules(): void {
		$this->settings = new SDOTEE_Settings();
		$this->admin    = new SDOTEE_Admin();
		$this->shorturl = new SDOTEE_ShortUrl();
		$this->file     = new SDOTEE_File();
		$this->text     = new SDOTEE_Text();
	}
}
