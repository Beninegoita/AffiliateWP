<?php
/**
 * Coupon functions
 *
 * @since 2.1
 * @package Affiliate_WP
 */

/**
 * Retrieves a coupon object.
 *
 * @param  int|AffWP\Affiliate\Coupon $coupon Coupon ID or object.
 * @return AffWP\Affiliate\Coupon|false Coupon object if found, otherwise false.
 * @since  2.1
 */
function affwp_get_coupon( $coupon = 0 ) {

	if ( is_object( $coupon ) && isset( $coupon->affwp_coupon_id ) ) {
		$by = $coupon->affwp_coupon_id;
	} elseif ( is_numeric( $coupon ) ) {
		$by = absint( $coupon );
	} elseif ( isset( $coupon->coupon_id ) ) {
		$by = $coupon->coupon_id;
	} else {
		return false;
	}

	return affiliate_wp()->affiliates->coupons->get_object( $by );
}

/**
 * Adds a coupon record.
 *
 * @since 2.1
 *
 * @param array $args {
 *     Optional. Arguments for adding a new coupon record. Default empty array.
 *
 *     @type int          $affiliate_id    Affiliate ID.
 *     @type int|array    $referrals       Referral ID or array of IDs.
 *     @type string       $integration     Coupon integration.
 *     @type string       $status          Coupon status. Default 'active'.
 *     @type string|array $expiration_date Coupon expiration date.
 * }
 * @return int|false The ID for the newly-added coupon, otherwise false.
 */
function affwp_add_coupon( $args = array() ) {

	if ( empty( $args['integration'] ) || empty( $args['affiliate_id'] ) || empty( $args['coupon_id'] ) ) {
		affiliate_wp()->utils->log( 'Unable to add new coupon object. Please ensure that the integration name, the affiliate ID, and the coupon ID from the integration are specified.' );
		return false;
	}

	if ( $coupon = affiliate_wp()->affiliates->coupons->add( $args ) ) {
		/**
		 * Fires immediately after a coupon has been added.
		 *
		 * @since 2.1
		 *
		 * @param int    $affwp_coupon_id  AffiliateWP coupon ID.
		 * @param object $coupon           AffiliateWP coupon object.
		 */
		do_action( 'affwp_add_coupon', $coupon->affwp_coupon_id, $coupon );
		return $coupon;
	}

	return false;
}

/**
 * Deletes a coupon.
 *
 * @param  int|\AffWP\Affiliate\Coupon $affwp_coupon_id  AffiliateWP coupon ID or object.
 * @return bool True if the coupon was successfully deleted, otherwise false.
 * @since  2.1
 */
function affwp_delete_coupon( $coupon ) {
	if ( ! $coupon = affwp_get_coupon( $coupon ) ) {
		return false;
	}

	if ( affiliate_wp()->affiliates->coupons->delete( $coupon->affwp_coupon_id, 'coupon' ) ) {
		/**
		 * Fires immediately after a coupon has been deleted.
		 *
		 * @since 2.1
		 *
		 * @param int    $affwp_coupon_id  AffiliateWP coupon ID.
	     * @param object $coupon           AffiliateWP coupon object.
		 */
		do_action( 'affwp_delete_coupon', $coupon->affwp_coupon_id, $coupon );

		return true;
	}

	return false;
}

/**
 * Retrieves all coupons associated with a specified affiliate.
 *
 * @since  2.1
 *
 * @param  integer $affiliate_id Affiliate ID.
 *
 * @return object  $coupons      An array of coupon objects associated with the affiliate.
 */
function affwp_get_affiliate_coupons( $affiliate_id = 0 ) {
	$args = array(
		'affiliate_id' => $affiliate_id
		);

	// return affiliate_wp()->coupons->get( $args );
	return false;
}

/**
 * Retrieves the referrals associated with a coupon.
 *
 * @param  int|AffWP\Affiliate\Coupon $coupon Coupon ID or object.
 * @return array|false                        List of referral objects associated with the coupon,
 *                                            otherwise false.
 * @since  2.1
 */
function affwp_get_coupon_referrals( $coupon = 0 ) {
	if ( ! $coupon = affwp_get_coupon( $coupon ) ) {
		return false;
	}

	$referrals = affiliate_wp()->affiliates->coupons->get_referral_ids( $coupon );

	return array_map( 'affwp_get_referral', $referrals );
}

/**
 * Retrieves the status label for a coupon.
 *
 * @param int|AffWP\Affiliate\Coupon $coupon Coupon ID or object.
 * @return string|false The localized version of the coupon status label, otherwise false.
 * @since 2.1
 */
function affwp_get_coupon_status_label( $coupon ) {

	if ( ! $coupon = affwp_get_coupon( $coupon ) ) {
		return false;
	}

	$statuses = array(
		'active'   => _x( 'Active', 'coupon', 'affiliate-wp' ),
		'inactive' => __( 'Inactive', 'affiliate-wp' ),
	);

	$label = array_key_exists( $coupon->status, $statuses ) ? $statuses[ $coupon->status ] : _x( 'Active', 'coupon', 'affiliate-wp' );
}

/**
 * Returns an array of coupon IDs based on the specified AffiliateWP integration.
 *
 * @since  2.1
 * @param  array              $integration  The AffiliateWP integration for which coupons should be retrieved.
 * @return mixed  bool|array  $coupons      Array of coupons based on the specified AffiliateWP integration.
 */
function affwp_get_coupons_by_integration( $integration = '' ) {

	if ( ! isset( $integration ) ) {
		affiliate_wp()->utils->log( 'Unable to determine integration when querying coupons.' );
		return false;
	}

	$coupons    = array();
	$coupon_ids = array();


	// Todo - cycle through active integrations, show variable UI depending on the integrations enabled,
	// to allow all supported concurrently-active integrations to auto-generate coupons.
	switch ( $integration ) {
		case 'edd':
			// Only retrieve active EDD discounts.
			$args = array(
				'post_status' => 'active'
			);
			$coupons = edd_get_discounts( $args );
			break;

		default:
			affiliate_wp()->utils->log( 'Unable to determine integration when querying coupons in core function affwp_get_coupons_by_integration.' );
			return false;
			break;
	}

	if ( $coupons ) {
		foreach ( $coupons as $coupon ) {
			$coupon_id = ( isset( $coupon->ID ) && is_int( $coupon->ID ) ) ? $coupon_id : false;
			if ( $coupon_id ) {
				$coupon_ids[] = $coupon_id;
			}
		}
	} else {
		affiliate_wp()->utils->log( 'Unable to locate coupons for this integration.' );
	}

	return $coupon_ids;
}

/**
 * Checks whether the specified integration has support for coupons in AffiliateWP.
 *
 * @param  string  $integration The integration to check.
 * @return bool                 Returns true if the integration is supported, otherwise false.
 * @since  2.1
 */
function affwp_has_coupon_support( $integration ) {

	// $integrations = affiliate_wp()->integrations->get_enabled_integrations();
	// $is_enabled   = in_array( $integration, $integrations );

	if ( empty( $integration ) ) {
		affiliate_wp()->utils->log( 'An integration must be provided when querying via affwp_has_coupon_support.' );
		return false;
	}

	$supported = array(
		'woocommerce',
		'edd',
		'exchange',
		'rcp',
		'pmp',
		'pms',
		'memberpress',
		'jigoshop',
		'lifterlms',
		'gravityforms'
	);

	return in_array( $integration, $supported );
}

function affwp_get_coupon_templates() {

	$templates    = array();
	$integrations = affiliate_wp()->integrations->get_enabled_integrations();

	if ( ! empty( $integrations ) ) {

		$output = array();

		foreach ( $integrations as $key => $value ) {

			$template_id  = affiliate_wp()->coupons->get_coupon_template_id( $key );
			$template_url = affiliate_wp()->coupons->get_coupon_template_url( $template_id, $key );

			if ( affwp_has_coupon_support( $key ) && $template_id ) {
				$output[ $key ] = $value . ' : ' . $template_url;
			}
		}

		return ! empty( $output ) ? $output : false;
	}
}
