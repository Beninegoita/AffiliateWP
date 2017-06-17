<?php
namespace AffWP;

use \Carbon\Carbon;

/**
 * Implements date formatting helpers for AffiliateWP.
 *
 * @since 2.2
 * @final
 */
final class Date {

	/**
	 * The current WordPress timezone.
	 *
	 * @access public
	 * @since  2.2
	 * @var    string
	 */
	public $timezone;

	/**
	 * The current WordPress date format.
	 *
	 * @access public
	 * @since  2.2
	 * @var    string
	 */
	public $date_format;

	/**
	 * The current WordPress time format.
	 *
	 * @access public
	 * @since  2.2
	 * @var    string
	 */
	public $time_format;

	/**
	 * Sets up the class.
	 *
	 * @access public
	 * @since  2.2
	 */
	public function __construct() {
		$this->timezone    = get_option( 'timezone_string' );
		$this->date_format = get_option( 'date_format' );
		$this->time_format = get_option( 'time_format' );

		$this->includes();
	}

	/**
	 * Includes needed files.
	 *
	 * @access public
	 * @since  2.2
	 */
	private function includes() {
		require_once AFFILIATEWP_PLUGIN_DIR . 'includes/libraries/Carbon/Carbon.php';
	}

	/**
	 * Formats a given date string according to WP date and time formats and timezone.
	 *
	 * @access public
	 * @since  2.2
	 *
	 * @param string $date_string Date string to format.
	 * @param string $type        Optional. Type of formatting. Accepts 'date', 'time',
	 *                            'datetime', or 'utc'. Default 'datetime'.
	 * @return string Formatted date string.
	 */
	public function format( $date_string, $type = 'datetime' ) {
		$timezone = 'utc' === $type ? 'UTC' : $this->timezone;
		$date     = Carbon::parse( $date_string, $timezone );

		if ( empty( $type ) || 'utc' === $type ) {
			$type = 'datetime';
		}

		switch( $type ) {
			case 'date':
				$formatted = $date->format( $this->date_format );
				break;

			case 'time':
				$formatted = $date->format( $this->time_format );
				break;

			case 'datetime':
			default:
				$formatted = $date->format( $this->date_format . ' ' . $this->time_format );
				break;

		}

		return $formatted;
	}


}