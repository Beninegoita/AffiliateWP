<?php
namespace AffWP\Integrations\Opt_In;

/**
 * Core abstract class extended to implement opt-in platform integrations.
 *
 * @since 2.2
 * @abstract
 */
abstract class Platform {

	/**
	 * ID of the platform, i.e. "mailchimp".
	 *
	 * @access public
	 * @since  2.2
	 * @var    string
	 */
	public $platform_id = '';
	
	/**
	 * Contact details to be subscribed to the platform.
	 * @param array  $contact {
	 *     Arguments for the contact.
	 *
	 *     @email      The email address of the contact
	 *     @first_name The first namee of the contact
	 *     @last_name  The last name of the contact
	 *
	 * @access public
	 * @since  2.2
	 * @var    array
	 */
	public $contact = array();

	/**
	 * ID of the list on the  platform to subscribe contacts.
	 *
	 * @access public
	 * @since  2.2
	 * @var    string
	 */
	public $list_id = '';

	/**
	 * Storage for API request errors.
	 *
	 * @access public
	 * @since  2.2
	 * @var    array
	 */
	public $errors = array();

	/**
	 * API key for authentication with the platform.
	 *
	 * @access protected
	 * @since  2.2
	 * @var    string
	 */
	protected $api_key = '';
	
	/**
	 * API request URL for the  platform.
	 *
	 * @access protected
	 * @since  2.2
	 * @var    string
	 */
	protected $api_url = '';

	public function __construct() {

		add_filter( 'affwp_settings_opt_in_forms', array( $this, 'settings' ) );

		$this->init();
	}

	public function init() {}

	public function subscribe_contact() {}
	public function settings( $settings ) {
		return $settings;
	}

	protected function call_api( $url = '', $body = array(), $headers = array() ) {

		if( empty( $url ) ) {
			$this->add_error( 'no_api_url', __( 'Please provide a platform API URL', 'affiliate-wp' ) );
			return;
		}

		if( empty( $body ) ) {
			$this->add_error( 'no_api_body', __( 'Please provide platform API body parameters', 'affiliate-wp' ) );
			return;
		}

		$args = array(
			'timeout'     => 45,
			'sslverify'   => false,
			'httpversion' => '1.1',
			'headers'     => $headers,
			'body'        => apply_filters( 'affwp_opt_in_platform_subscribe_args', $body, $this ),
		);

		$request = wp_remote_post( $url, $args );

		if( is_wp_error( $request ) ) {

			$this->add_error( $request->get_error_code(), $request->get_error_message() );

		}

		if( 200 !== wp_remote_retrieve_response_code( $request ) ) {

			$this->add_error( wp_remote_retrieve_response_code( $request ), wp_remote_retrieve_response_message( $request ) );
		}

		return $request;

	}

	/**
	 * Register a submission error
	 *
	 * @since 2.2
	 */
	public function add_error( $error_id, $message = '' ) {
		$this->errors[ $error_id ] = $message;
	}

}	