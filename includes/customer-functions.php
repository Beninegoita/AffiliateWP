<?php
/**
 * Customer functions
 *
 * @since 2.2
 * @package Affiliate_WP
 */

/**
 * Retrieves the customer object
 *
 * @since 2.2
 *
 * @param int|AffWP\Customer $creative Customer ID or object.
 * @return AffWP\Customer|false Customer object, otherwise false.
 */
function affwp_get_customer( $customer = null ) {

	if ( is_object( $customer ) && isset( $customer->customer_id ) ) {
		$customer_id = $customer->customer_id;
	} elseif( is_numeric( $customer ) ) {
		$customer_id = absint( $customer );
	} elseif( is_email( $customer ) ) {
		$customer_id = affiliate_wp()->customers->get_column_by( 'customer_id', 'email', $customer );
		if( ! $customer_id ) {
			return false;
		}
	} else {
		return false;
	}

	return affiliate_wp()->customers->get_object( $customer_id );
}

/**
 * Adds a new customer to the database.
 *
 * @since 2.2
 * @param array $data {
 *     Arguments for setting up the customer record.
 *
 *     @type string       $first_name     First name for the customer.
 *     @type string       $last_name      Last  anme for the customer.
 *     @type string       $email          Email address for the customer.
 *     @type int          $affiliate_id   ID of the affiliate that generated this customer.
 * }
 * @return int|false ID of the newly-created customer, otherwise false.
 */
function affwp_add_customer( $data = array() ) {

	$args = array(
		'first_name'   => ! empty( $data['first_name'] ) ? sanitize_text_field( $data['first_name'] ) : '',
		'last_name'    => ! empty( $data['last_name'] ) ? sanitize_text_field( $data['last_name'] ) : '',
		'email'        => ! empty( $data['email'] ) ? sanitize_text_field( $data['email'] ) : '',
		'affiliate_id' => ! empty( $data['affiliate_id'] ) ? absint( $data['affiliate_id'] ) : '',
	);

	if ( $customer_id = affiliate_wp()->customers->add( $args ) ) {
		return $customer_id;
	}

	return false;

}

/**
 * Updates a customer
 *
 * @since 2.2
 * @return bool
 */
function affwp_update_customer( $data = array() ) {

	if ( empty( $data['customer_id'] )
		|| ( ! $customer = affwp_get_customer( $data['customer_id'] ) )
	) {
		return false;
	}

	$args = array(
		'first_name'   => ! empty( $data['first_name'] ) ? sanitize_text_field( $data['first_name'] ) : '',
		'last_name'    => ! empty( $data['last_name'] ) ? sanitize_text_field( $data['last_name'] ) : '',
		'email'        => ! empty( $data['email'] ) ? sanitize_text_field( $data['email'] ) : '',
		'affiliate_id' => ! empty( $data['affiliate_id'] ) ? absint( $data['affiliate_id'] ) : '',
	);

	if ( affiliate_wp()->customers->update( $customer->ID, $args, '', 'customer' ) ) {
		return true;
	}

	return false;

}

/**
 * Deletes a customer
 *
 * @since 2.2
 * @param $delete_data bool
 * @return bool
 */
function affwp_delete_customer( $customer ) {

	if ( ! $customer = affwp_get_customer( $customer ) ) {
		return false;
	}

	return affiliate_wp()->customers->delete( $customer->ID, 'customer' );
}