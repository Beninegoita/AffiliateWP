<?php
namespace AffWP\Integrations\Opt_In;

use AffWP\Integrations\Opt_In;

/**
 * MailChimp opt-in platform integration.
 *
 * @since 2.2
 * @abstract
 */
class MailChimp extends Opt_In\Platform {

	public function init() {

		$this->platform_id = 'mailchimp';
		$this->api_key     = affiliate_wp()->settings->get( 'mailchimp_api_key' );
		$this->list_id     = affiliate_wp()->settings->get( 'mailchimp_list_id' );
		$data_center       = 'us4';

		if( ! empty( $this->api_key ) ) {
			$data_center   = substr( $this->api_key, strpos( $this->api_key, '-' ) + 1 );
		}

		$this->api_url     = 'https://' . $data_center . '.api.mailchimp.com/3.0/lists/' . $this->list_id . '/members/';
	}

	public function subscribe_contact() {

		$headers = array(
			'Authorization' => 'Basic ' . base64_encode( 'user:' . $this->api_key )
		);

		$body = array(
			'email_address' => $this->contact['email'],
			'status'        => 'subscribed',
			'merge_fields'  => array(
		    	'FNAME'     => $this->contact['first_name'],
		    	'LNAME'     => $this->contact['last_name']
			)
		);

		return $this->call_api( $this->api_url, $body, $headers );

	}

	public function settings( $settings ) {

		$settings['mailchimp_api_key'] = array(
			'name' => __( 'MailChimp API Key', 'affiliate-wp' ),
			'type' => 'text',
			'desc' => __( 'Enter your MailChimp API key.', 'affiliate-wp' ),
		);

		$settings['mailchimp_list_id'] = array(
			'name' => __( 'MailChimp List ID', 'affiliate-wp' ),
			'type' => 'text',
			'desc' => __( 'Enter the ID of the list you wish to subscribe contacts to.', 'affiliate-wp' ),
		);

		return $settings;
	}

}