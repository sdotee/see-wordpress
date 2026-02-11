<?php
/**
 * Main plugin class (singleton).
 *
 * @package SEE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SEE_Plugin
 *
 * Central plugin class that initializes all modules.
 */
class SEE_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var SEE_Plugin|null
	 */
	private static ?SEE_Plugin $instance = null;

	/**
	 * Settings module.
	 *
	 * @var SEE_Settings
	 */
	public SEE_Settings $settings;

	/**
	 * Admin module.
	 *
	 * @var SEE_Admin
	 */
	public SEE_Admin $admin;

	/**
	 * Short URL module.
	 *
	 * @var SEE_ShortUrl
	 */
	public SEE_ShortUrl $shorturl;

	/**
	 * File module.
	 *
	 * @var SEE_File
	 */
	public SEE_File $file;

	/**
	 * Text module.
	 *
	 * @var SEE_Text
	 */
	public SEE_Text $text;

	/**
	 * Get singleton instance.
	 *
	 * @return SEE_Plugin
	 */
	public static function get_instance(): SEE_Plugin {
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
		$this->settings = new SEE_Settings();
		$this->admin    = new SEE_Admin();
		$this->shorturl = new SEE_ShortUrl();
		$this->file     = new SEE_File();
		$this->text     = new SEE_Text();
	}
}
