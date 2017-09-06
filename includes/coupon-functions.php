<?php
/**
 * Coupon functions
 *
 * @since 2.2
 * @package Affiliate_WP
 */

/**
 * Retrieves a coupon object.
 *
 * @since 2.2
 *
 * @param int|AffWP\Affiliate\Coupon $coupon Coupon ID, integration coupon ID, AffiliateWP coupon object,
 * @param bool $by_integration_id True to delete the coupon by the supplied integration coupon ID
 * @param string $integration The name of the integration the supplied ID belongs too. Only used if $by_integration_id is true
 * @return AffWP\Affiliate\Coupon|false Coupon object if found, otherwise false.
 */
function affwp_get_coupon( $coupon = 0, $by_integration_id = false, $integration = '' ) {

	global $wpdb;

	if( $by_integration_id ) {

		if( empty( $integration ) || ! is_numeric( $coupon ) ) {
			return false;
		}

		$table_name = affiliate_wp()->coupons->table_name;
		$coupon_id  = $wpdb->get_var( $wpdb->prepare( "SELECT coupon_id FROM {$table_name} WHERE integration = '%s' AND integration_coupon_id = %d LIMIT 0, 1;", $integration, $coupon ) );

	} else {

		if ( is_object( $coupon ) && isset( $coupon->coupon_id ) ) {

			$coupon_id = $coupon->coupon_id;

		} elseif ( is_numeric( $coupon ) ) {

			$coupon_id = absint( $coupon );

		} else {

			return false;

		}

	}

	return affiliate_wp()->affiliates->coupons->get_object( $coupon_id );
}

/**
 * Adds a coupon object. This function calls the `add()` method in `Affiliate_WP_Coupons_DB`,
 * and does not itself generate a coupon for an integration.
 *
 * For methods which generate coupons for integrations,
 * see `affwp_add_integration_coupon`.
 *
 * @since 2.2
 *
 * @param array $args {
 *     Arguments for adding a new coupon record. Default empty array.
 *
 *     @type int          $affiliate_id    Affiliate ID.
 *     @type int|array    $referrals       Referral ID or array of IDs.
 *     @type string       $integration     Coupon integration.
 *     @type string       $status          Coupon status. Default 'active'.
 *     @type string|array $expiration_date Coupon expiration date.
 * }
 * @return int|false $coupon The newly-added coupon object, otherwise false.
 */
function affwp_add_coupon( $args = array() ) {

	if ( empty( $args ) ) {
		affiliate_wp()->utils->log( 'affwp_add_coupon: No arguments provided.' );
		return false;
	}

	if ( $coupon = affiliate_wp()->affiliates->coupons->add( $args ) ) {
		/**
		 * Fires immediately after a coupon has been added.
		 *
		 * @since 2.2
		 *
		 * @param int $coupon ID of the newly-added coupon.
		 */
		do_action( 'affwp_add_coupon', $coupon );

		return $coupon;
	}

	return false;
}

/**
 * Deletes a coupon.
 *
 * @since 2.2
 *
 * @param int|\AffWP\Affiliate\Coupon $coupon  AffiliateWP coupon ID or object.
 * @param bool $by_integration_id True to delete the coupon by the supplied integration coupon ID
 * @param string $integration The name of the integration the supplied ID belongs too. Only used if $by_integration_id is true 
 * @return bool True if the coupon was successfully deleted, otherwise false.
 */
function affwp_delete_coupon( $coupon, $by_integration_id = false, $integration = '' ) {
	if ( ! $coupon = affwp_get_coupon( $coupon, $by_integration_id, $integration ) ) {
		return false;
	}

	if ( affiliate_wp()->affiliates->coupons->delete( $coupon->ID, 'coupon' ) ) {
		/**
		 * Fires immediately after a coupon has been deleted.
		 *
		 * @since 2.2
		 *
		 * @param int $coupon_id Core coupon ID.
		 */
		do_action( 'affwp_delete_coupon', $coupon->ID );

		return true;
	}

	return false;
}

/**
 * Retrieves all coupons associated with a specified affiliate.
 *
 * @since 2.2
 *
 * @param int $affiliate_id Affiliate ID.
 * @return array An array of coupon objects associated with the affiliate.
 */
function affwp_get_affiliate_coupons( $affiliate_id ) {

	$args = array(
		'affiliate_id' => $affiliate_id,
		'number'       => -1
	);

	$coupons = affiliate_wp()->affiliates->coupons->get_coupons( $args );

	/**
	 * Returns coupon objects filtered by a provided affiliate ID.
	 *
	 * @since 2.2
	 *
	 * @param array $coupons      Affiliate coupons.
	 * @param int   $affiliate_id Affiliate ID.
	 */
	return apply_filters( 'affwp_get_affiliate_coupons', $coupons, $affiliate_id );
}


/**
 * Retrieves the status label for a coupon.
 *
 * @param int|AffWP\Affiliate\Coupon $coupon Coupon ID or object.
 * @return string|false The localized version of the coupon status label, otherwise false.
 * @since 2.2
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

	/**
	 * Filters the coupon status label.
	 *
	 * @since 2.2
	 *
	 * @param string                 $label  A localized version of the coupon status label.
	 * @param AffWP\Affiliate\Coupon $coupon Coupon object.
	 */
	return apply_filters( 'affwp_coupon_status_label', $label, $coupon );
}

/**
 * Retrieves the referrals associated with a coupon.
 *
 * @param  int         $integration_coupon_id  Integration coupon ID.
 * @return array|false                         List of referral objects associated with the coupon,
 *                                             otherwise false.
 * @since  2.2
 */
function affwp_get_coupon_referrals( $integration_coupon_id = 0, $integration = '' ) {

	$referrals = array();

	if ( empty( $integration ) || ! is_int( $integration_coupon_id ) ) {
		return false;
	}

	$referrals = affiliate_wp()->affiliates->coupons->get_referral_ids( $integration_coupon_id );

	return array_map( 'affwp_get_referral', $referrals );
}

/**
 * Retrieves an array of coupon IDs based on the specified AffiliateWP integration and affiliate ID.
 *
 * @since 2.2
 *
 * @param array $args {
 *     Arguments for retrieving coupons by integration.
 *
 *     @type int    $affiliate_id Affiliate ID
 *     @type string $integration  Integration.
 * }
 * @return array Array of coupons based on the specified AffiliateWP integration, otherwise empty array.
 */
function affwp_get_coupons_by_integration( $args ) {

	$coupons   = array();
	$coupon_id = 0;

	if ( isset( $args[ 'coupon_id' ] ) ) {

		if ( affwp_get_coupon( $args[ 'coupon_id' ] ) ) {
			$coupon_id = is_int( $args[ 'coupon_id' ] ) ? absint( $args[ 'coupon_id' ] ) : 0;
		}
	}

	if ( ! isset( $args[ 'integration' ] ) ) {
		affiliate_wp()->utils->log( 'affwp_get_coupons_by_integration: Unable to determine integration when querying coupons.' );
		return $coupons;
	}

	if ( ! isset( $args[ 'affiliate_id' ] ) ) {
		affiliate_wp()->utils->log( 'affwp_get_coupons_by_integration: Unable to determine affiliate ID when querying coupons.' );
		return $coupons;
	}

	if ( affwp_integration_has_coupon_support( $args['integration'] ) ) {
		// Cycle through active integrations, and gets all coupons for the given affiliate ID.
		switch ( $args[ 'integration' ] ) {
			case 'edd':
				// Only retrieve active EDD discounts.
				$discount_args = array(
					'post_status'              => 'active',
					'affwp_discount_affiliate' => $args[ 'affiliate_id' ]
				);

				// Returns an array of WP Post objects.
				$discounts = edd_get_discounts( $discount_args );

				if ( $discounts ) {
					foreach( $discounts as $discount ) {

						$referrals = affwp_get_coupon_referrals( $discount->ID, 'edd' );
						$referrals = implode( ', ', wp_list_pluck( $referrals, 'referral_id' ) );

						$coupons[ $discount->ID ] = array(
							'integration_coupon_id' => $discount->ID,
							'coupon_id'             => $coupon_id,
							'integration'           => 'edd',
							'coupon_code'           => get_post_meta( $discount->ID, '_edd_discount_code', true ),
							'referrals'             => $referrals

						);
					}
				}

				break;

			default:
				affiliate_wp()->utils->log( 'Unable to determine integration when querying coupons in affwp_get_coupons_by_integration.' );
				break;
		}
	}

	if ( empty( $coupons ) ) {
		affiliate_wp()->utils->log( 'Unable to locate coupons for this integration.' );
	}

	return $coupons;
}

/**
 * Returns an array of integrations which support coupons.
 *
 * @param boolean $active  Whether or not to only return active integrations. Default is false.
 * @return array Array of integrations.
 *
 * @since  2.2
 */
function affwp_has_coupon_support_list( $active = false ) {

	/**
	 * Array of integrations which support coupons.
	 *
	 * @param array $supported Array of integrations which support coupons.
	 * @since 2.2
	 */
	$supported_integrations = apply_filters( 'affwp_has_coupon_support_list', array(
			'woocommerce'  => 'WooCommerce',
			'edd'          => 'Easy Digital Downloads',
			'exchange'     => 'iThemes Exchange',
			'rcp'          => 'Restrict Content Pro',
			'pmp'          => 'Paid Memberships Pro',
			'pms'          => 'Paid Member Subscriptions',
			'memberpress'  => 'MemberPress',
			'jigoshop'     => 'Jigoshop',
			'lifterlms'    => 'LifterLMS',
			'gravityforms' => 'Gravity Forms'

		)
	);

	$active_supported = array();

	if ( $active ) {
		foreach ( $supported_integrations as $supported_integration => $label ) {

			$integrations = affiliate_wp()->integrations->get_enabled_integrations();

			$has_support  = array_key_exists( $supported_integration, $integrations );

			if ( $has_support ) {
				$active_supported[ $supported_integration ] = $label;
			}
		}
	}

	return $active ? $active_supported : $supported_integrations;
}

/**
 * Checks whether the specified integration has support for coupons in AffiliateWP.
 *
 * @param  string  $integration The integration to check.
 * @return bool                 Returns true if the integration is supported, otherwise false.
 *
 * @since  2.2
 */
function affwp_integration_has_coupon_support( $integration ) {

	if ( empty( $integration ) ) {
		affiliate_wp()->utils->log( 'An integration must be provided when querying via affwp_has_coupon_support.' );
		return false;
	}

	$integrations = affiliate_wp()->integrations->get_enabled_integrations();
	$supported    = affwp_has_coupon_support_list();
	$has_support  = array_key_exists( $integration, $integrations );

	/**
	 * Filters whether the given coupon integration is supported.
	 *
	 * To add support for an integration or additional third-party plugin,
	 * provide a unique name for your integration as the `$integration.`
	 *
	 * An array of coupon-supporting integrations in AffiliateWP core are provided by
	 * `$supported` for reference.
	 *
	 * @param bool   $has_support True if the given integration has support, otherwise false.
	 * @param string $integration Integration being checked.
	 * @param array  $supported   Supported integrations.
	 *
	 * @since 2.2
	 */
	return apply_filters( 'affwp_has_coupon_support', $integration, $supported );
}

/**
 * Retrieves the coupon template ID, if set.
 *
 * @param  string $integration The integration.
 * @return int                 The coupon template ID if set, otherwise returns 0.
 *
 * @since  2.2
 */
function affwp_get_coupon_template_id( $integration ) {
	return affiliate_wp()->affiliates->coupons->get_coupon_template_id( $integration );
}

/**
 * Retrieves the coupon template URL for the given integration coupon ID and integration.
 *
 * @param int     $integration_coupon_id The integration coupon ID.
 * @param string  $integration           Integration.
 * @return string                        The template edit URL for the integration coupon ID,
 *                                       otherwise an empty string.
 *
 * @since 2.2
 */
function affwp_get_coupon_edit_url( $integration_coupon_id, $integration_id ) {
	return affiliate_wp()->affiliates->coupons->get_coupon_edit_url( $integration_coupon_id, $integration_id );
}

/**
 * Gets the coupon template object for the given integration.
 * Returns a predictable array of object properties.
 *
 * @param  string $integration The integration.
 * @return array  $template    Coupon template properties array on success, an empty array otherwise.
 *
 * @since  2.2
 */
function affwp_get_coupon_template( $integration ) {

	if ( empty( $integration ) ) {
		affiliate_wp()->utils->log( 'affwp_get_coupon_template: Unable to locate coupon template; the integration must be specified.' );
		return false;
	}

	$template    = array();
	$template_id = 0;

	// Attempt to get the coupon template internally, prior to querying an integration.
	$args = array(
		'is_template' => true,
		'integration' => $integration,
		'number'      => 1
	);

	$template = affiliate_wp()->affiliates->coupons->get_coupons( $args );
	$template = reset( $template );

	// If the template isn't an internal AffiliateWP coupon object,
	// query the integration directly.
	if ( ! $template ) {
		$template_id = affwp_get_coupon_template_id( $integration );

		if ( $template_id ) {

			switch ( $integration ) {
				case 'edd':
					$template = edd_get_discount( $template_id ) ? edd_get_discount( $template_id ) : false;

					break;
				case 'woocommerce' :
					$template = get_post( $template_id );
					break;
				case 'exchange' :
					$template = '';
					break;
				case 'rcp' :
					$template = '';
					break;
				case 'pmp' :
					$template = '';
					break;
				case 'pms' :
					$template = '';
					break;
				case 'memberpress' :
					$template = '';
					break;
				case 'jigoshop' :
					$template = '';
					break;
				case 'lifterlms' :
					$template = '';
					break;
				case 'gravityforms' :
					$template = '';
					break;

				default:
					$template = get_post( $template_id );
					break;
			}
		} else {
			affiliate_wp()->utils->log( 'Unable to determine coupon template ID' );
		}

	}

	return (array) $template;
}

/**
 * Retrieves a list of active integrations with both coupon support and a selected coupon template.
 *
 * @return string $output Formatted list of integration coupon templates, otherwise a notice.
 * @since  2.2
 */
function affwp_get_coupon_templates() {

	$integrations        = affiliate_wp()->integrations->get_enabled_integrations();
	$integrations_output = array();
	$output              = false;

	if ( ! empty( $integrations ) ) {

		foreach ( $integrations as $integration_id => $integration_term ) {

			// Ensure that this integration has both coupon support,
			// and a coupon template has also been selected.
			if ( affwp_integration_has_coupon_support( $integration_id ) ) {

				$template_id = affwp_get_coupon_template_id( $integration_id );

				if ( ! $template_id ) {
					continue;
				} else {
					$template_url = affiliate_wp()->affiliates->coupons->get_coupon_edit_url( $template_id, $integration_id );

					$integrations_output[] = sprintf( '<li data-integration="%1$s">%2$s: %3$s</li>',
						esc_html( $integration_id ),
						esc_html( $integration_term ),
						sprintf( '<a href="%1$s">View coupon (ID %2$s)</a>',
						esc_url( $template_url ),
						esc_html( $template_id )
						)
					);
				}
			}
		}
	}

	if ( ! empty( $integrations_output ) ) {
		$output = '<ul class="affwp-coupon-template-list">';

		foreach ( $integrations_output as $integration_output ) {
			$output .= $integration_output;
		}

		$output .= '</ul>';
	}

	return $output ? $output : __( 'No coupon templates have been defined for any active AffiliateWP integrations.', 'affiliate-wp' );

}

/**
 * Gets the coupon-creation admin url for the specified integration.
 * Can output wither a raw admin url, or a formatted html anchor containing the link.
 *
 * The affiliate ID is used optionally in cases where data may be passed to the integration.
 *
 * @param  string  $integration   The integration.
 * @param  int     $affiliate_id  Affiliate ID.
 * @param  bool    $html          Whether or not to provide an html anchor tag in the return.
 *                                Specify true to output an anchor tag. Default is false.
 *
 * @return string|false         The coupon creation admin url, otherwise false.
 * @since  2.2
 */
function affwp_get_coupon_create_url( $integration, $affiliate_id = 0, $html = false ) {

	$url = false;

	if ( empty( $integration ) ) {
		return false;
	}

	if ( affwp_integration_has_coupon_support( $integration ) ) {

		$user_name = affwp_get_affiliate_username( $affiliate_id );

		switch ( $integration ) {
			case 'edd':
				$url = admin_url( 'edit.php?post_type=download&page=edd-discounts&edd-action=add_discount&user_name=' . $user_name);
				break;

			default:
				break;
		}

	} else {
		affiliate_wp()->utils->log( sprintf( 'affwp_get_coupon_create_url: The %s integration does not presently have AffiliateWP coupon support.', $integration ) );
		return false;
	}

	if ( $html ) {
		return '<a class="affwp-inline-link" href="' . esc_url( $url ) . '">' . esc_html__( 'Create Coupon', 'affiliate-wp' ) . '</a>';
	}

	return $url;
}

/**
 * Generates a unique coupon code string, used when generating an integration coupon.
 *
 * @param  integer             $affiliate_id       Affiliate ID.
 * @param  string              $integration        Integration.
 * @param  string              $base               The base coupon code string.
 *
 * @return mixed string|false  $coupon             Coupon code string if successful, otherwise returns false.
 * @since  2.2
 */
function affwp_generate_coupon_code( $affiliate_id = 0, $integration = '', $base = '' ) {

	$coupon_code = '';

	if ( ! $affiliate_id || empty( $integration ) ) {
		affiliate_wp()->utils->log( 'affwp_generate_coupon_code: Both the integration and the Affiliate ID  must be provided.' );
		return false;
	}

	// Define the coupon code base from the coupon template, if one is not provided.
	if ( empty( $base ) ) {

		// Generate a base coupon code from the existing coupon template, for the provided integration.
		$template = affwp_get_coupon_template( $integration );

		if ( ! $template ) {
			affiliate_wp()->utils->log( 'affwp_generate_coupon_code: Unable to determine coupon template.' );
			return false;
		}

		if ( isset( $template[ 'coupon_code' ] ) ) {
			$base = $template[ 'coupon_code' ];
		} elseif ( isset( $template[ 'code' ] ) ) {
			$base = $template[ 'code' ];
		} else {
			$base = '';
			affiliate_wp()->utils->log( 'affwp_generate_coupon_code: Unable to generate coupon code $base.' );
		}

	}

	/**
	 * Coupon code string.
	 *
	 * @param string $base          Base coupon code, to which a suffix is applied.
	 * @param int    $affiliate_id  Affiliate ID.
	 * @param string $hash          Eight-digit substring of md5 hash of affiliate ID (eight digits).
	 *
	 * The generated coupon code would be:
	 *
	 * base_example-1-c4ca4238
	 */


	// Define default values.
	$hash      = substr( md5( $affiliate_id, false ), 0, 8 );
	$separator = '-';

	/**
	 * Defines the coupon code base.
	 *
	 * @param string $base  Coupon code base.
	 * @since 2.2
	 */
	$base      = apply_filters( 'affwp_coupon_code_base', $base );

	/**
	 * Defines the separator used in the coupon code string.
	 * Specify a single-character separator.
	 *
	 * @param string $separator Separator character used in coupon code string generation.
	 *                          Defaults to a hyphen, '-'.
	 * @since 2.2
	 */
	$separator  = apply_filters( 'affwp_coupon_code_separator', $separator );

	/**
	 * Defines the affiliate ID used in the coupon code string.
	 *
	 * @param int $affiliate_id  Affiliate ID.
	 * @since 2.2
	 */
	$affiliate_id  = apply_filters( 'affwp_coupon_code_affiliate_id', $affiliate_id );

	/**
	 * Defines the hash suffix of the coupon code.
	 *
	 * @param string $hash  Coupon code hash suffix. Defaults to an eight-digit
	 *                      substring of an md5 hash of the provided affiliate ID.
	 * @since 2.2
	 */
	$hash       = apply_filters( 'affwp_coupon_code_hash', $hash );

	$coupon_code = sanitize_text_field(
		implode( $separator,
			array(
				$base,
				$affiliate_id,
				$hash
			)
		)
	);

	/**
	 * Sets the coupon code when generating a coupon for a supported integration.
	 *
	 * Specify a string to use for the coupon code,
	 * ensuring that the formatting is supported by the integrations coupon code sanitization.
	 *
	 * @param mixed string|false $coupon_code   The generated coupon code string,
	 *                                          otherwise returns false.
	 * @param string             $base          Coupon code base.
	 * @param string             $separator     Separator character used in the coupon code string.
	 * @param int                $affiliate_id  Affiliate ID.
	 * @param string             $hash          Coupon code hash suffix.
	 * @since 2.2
	 */
	return apply_filters( 'affwp_generate_coupon_code',
		$coupon_code,
		$base,
		$separator,
		$affiliate_id,
		$hash
	);
}

/**
 * Checks whether the affiliate already has an existing coupon for the given integration.
 *
 * @since  2.2
 *
 * @param  integer $affiliate_id  Affiliate ID.
 * @param  string  $integration   Integtation to query.
 *
 * @return bool                   True if the coupon exists, otherwise false.
 */
function affwp_coupon_exists( $affiliate_id = 0, $integration = '' ) {

	if ( ! $affiliate_id || empty( $integration ) ) {
		affiliate_wp()->utils->log( 'affwp_coupon_exists: The affiliate ID and integration must be specified.' );
		return false;
	}

	$args = array(
		'affiliate_id' => $affiliate_id,
		'number'       => 1,
		'integration'  => $integration
	);

	return affwp_get_coupon( $args );
}

/**
 * May generate one or more coupons for an affiliate, if the following conditions are met:
 * - Some coupon integrations are enabled for which the affiliate does not have coupons generated.
 * - The required parameters are provided.
 *
 * @since  2.2
 * @param  integer $row_id Affiliate ID.
 * @param  array   $data   Affiliate data.
 *
 * @return array   $added  Array of affiliate coupon objects.
 *                         Returns an empty array if no coupons are generated.
 */
function affwp_maybe_generate_coupons( $data, $affiliate_id ) {

	$args = array();

	// Bail if the auto-generate coupons setting is not active.
	if ( ! affiliate_wp()->settings->get( 'auto_generate_coupons_enabled' ) ) {

		return false;
	}

	// Automatic coupon generation is set to trigger when an affiliate has an 'active' status.
	if ( ! affwp_is_active_affiliate( $affiliate_id ) ) {
		return false;
	}

	// Get all coupons for this affiliate.
	$existing_coupons     = affwp_get_affiliate_coupons( $affiliate_id );
	$integrations_to_skip = array();

	// Build an array of integration coupons which exist for the given affiliate ID.

	if ( $existing_coupons ) {
		affiliate_wp()->utils->log( 'Existing coupons data: ' . print_r( $existing_coupons, true ) );
		foreach ( $existing_coupons as $existing_coupon ) {
			$integrations_to_skip[] = $existing_coupon->integration;
		}
	}

	/**
	 * Check active coupon integrations, and compare it against existing coupons for this affiliate.
	 * If the affiliate is missing any, generate a coupon for that integration.
	 */
	$to_generate = affiliate_wp()->settings->get( 'coupon_integrations' );
	$added       = array();

	affiliate_wp()->utils->log( 'coupon_integrations setting: ' . print_r( $to_generate, true ) );

	foreach ( $to_generate as $integration => $label ) {

		if( in_array( $integration, $integrations_to_skip ) ) {
			continue;
		}

		$args = array(
			'affiliate_id' => $affiliate_id,
			'coupon_code'  => affwp_generate_coupon_code( $affiliate_id, $integration, '' ),
			'integration'  => $integration,
			'status'       => 'active',
		);

		// Generate a coupon for the affiliate, since none exists.
		$coupon = affwp_generate_integration_coupon( $args );

		if ( $coupon ) {
			$added[] = $coupon;
		} else {
			affiliate_wp()->utils->log( 'affwp_maybe_generate_coupons: Unable to generate integration coupon for provided data: ' . print_r( $args, true ) );
		}
	}

	return $added;

}
add_action( 'affwp_post_update_affiliate', 'affwp_maybe_generate_coupons', 10, 2 );

/**
 * Generates a coupon within the specified integration.
 *
 * Each integration should return either the ID of the generated coupon on success, or an array containing
 * the ID as `integration_coupon_id` or `id`.
 *
 * @param  array         $args   Integration coupon arguments.
 * @return object|false  $coupon AffiliateWP coupon object on success, otherwise false.
 * @since  2.2
 */
function affwp_generate_integration_coupon( $args = array() ) {

	if ( ! isset( $args[ 'integration' ] ) || empty( $args[ 'integration'] ) ) {

		affiliate_wp()->utils->log( 'affwp_generate_integration_coupon: The integration must be specified when attempting to generate a coupon for an integration.' );
		return false;
	}

	if ( ! affwp_integration_has_coupon_support( $args[ 'integration' ] ) ) {
		affiliate_wp()->utils->log( 'affwp_generate_integration_coupon: The provided integration does not have coupon support in AffiliateWP at this time. Please see affwp_has_coupon_support_list for a list of compatible integrations.' );
		return false;
	}


	$args[ 'affiliate_id' ] = is_int( $args[ 'affiliate_id' ] ) ? $args[ 'affiliate_id' ] : false;
	$args[ 'template_id' ]  = affwp_get_coupon_template_id( $args[ 'integration' ] );

	// Bail if no affiliate ID or coupon template is provided.
	if ( ! $args[ 'affiliate_id' ] ) {
		affiliate_wp()->utils->log( 'affwp_generate_integration_coupon: The affiliate ID must be set when generating a coupon for an integration.' );
		return false;
	}

	if ( ! $args[ 'template_id' ] ) {
		affiliate_wp()->utils->log( 'affwp_generate_integration_coupon: The ID of the coupon template must be specified.' );
		return false;
	}

	$template = affwp_get_coupon_template( $args[ 'integration' ] );
	$template = (array) $template;
	$base     = ! empty( $template[ 'coupon_code' ] ) ? $template[ 'coupon_code' ] : sanitize_text_field( strtolower( get_the_title( $args[ 'template_id' ] ) ) );
	$args[ 'coupon_code' ] = affwp_generate_coupon_code( $args[ 'affiliate_id' ], $args[ 'integration' ], $base );

	/**
	 * Dynamically calls the necessary integration coupon generator function, named per integration.
	 *
	 * The name of each integration function has the integration ID appended
	 * to the name of this caller, `affwp_generate_integration_coupon`.
	 *
	 * For example, the function which creates an integration coupon in EDD
	 * is named `affwp_generate_integration_coupon_edd`.
	 *
	 */
	$function_name    = 'affwp_generate_integration_coupon_' . $args[ 'integration' ];
	$integration_data = function_exists( $function_name ) ? $function_name( $args ) : false;

	affiliate_wp()->utils->log( 'Integration coupon data: '. print_r( $integration_data, true ) );

	if ( ! $integration_data ) {
		affiliate_wp()->utils->log( 'affwp_generate_integration_coupon: Could not generate integration coupon via dynamic caller.' );
		return false;
	}

	$integration_data = is_object( $integration_data ) ? (array) $integration_data : $integration_data;


	/**
	 * Fires immediately after an integration coupon is generated.
	 *
	 * @param array $integration_data  The generated integration coupon data.
	 * @since 2.2
	 */
	do_action( 'affwp_post_generate_integration_coupon', $integration_data );

	$integration_coupon_id = false;

	// intval will return a 1 for non-empty arrays.
	if ( ! is_array( $integration_data ) && is_int( $integration_data ) ) {
		$integration_coupon_id = absint( $integration_data );
	} elseif ( isset( $integration_data[ 'ID' ] ) ) {
		$integration_coupon_id = absint( $integration_data[ 'ID' ] );
	} else {
		// The integration coupon ID is required to generate an internal AffiliateWP coupon object.
		affiliate_wp()->utils->log( 'affwp_generate_integration_coupon: Could not determine the ID of the integration coupon.' );
		return false;
	}

	// Update post meta to specify the affiliate ID.

	if ( get_post( $integration_coupon_id ) ) {
		update_post_meta( $integration_coupon_id, 'affwp_discount_affiliate', $args[ 'affiliate_id' ] );
	}

	// Build coupon arguments.
	$affwp_coupon_args = array(
		'affiliate_id'          => $args[ 'affiliate_id' ],
		'coupon_code'           => $integration_data[ 'coupon_code' ],
		'integration_coupon_id' => $integration_coupon_id,
		'integration'           => $args[ 'integration' ],
		'status'                => $integration_data[ 'status' ] ? $integration_data[ 'status' ]: 'active',
	);

	return $affwp_coupon_args;
}

/**
 * Generates an EDD coupon.
 *
 * @param  array              $args    Coupon arguments. The array should contain:
 *     `affiliate_id`
 *     `template_id`
 *     `name`
 *     `coupon_code`
 *     `amount`
 *     `type`
 *     `affwp_discount_affiliate`
 *
 * @return mixed array|false  $coupon  Array of coupon data if successful, otherwise returns false.
 * @see    affwp_generate_integration_coupon
 * @since  2.2
 */
function affwp_generate_integration_coupon_edd( $args = array() ) {

	$discount_args = array();
	$coupon_args   = array();
	$template      = false;

	// Ensure that a coupon template exists before proceeding.
	if ( edd_get_discount( $args[ 'template_id' ] ) ) {
		$template = edd_get_discount( $args[ 'template_id' ] );
	} else {
		affiliate_wp()->utils->log( 'affwp_generate_integration_coupon_edd: Unable to retrieve the EDD discount template.' );
		return false;
	}

	$template_code = $template->code ? $template->code: '';

	if ( empty( $template->code ) ) {
		if ( ! empty( get_post_meta( $template->ID, '_edd_discount_code', true ) ) ) {
			$template_code = get_post_meta( $template->ID, '_edd_discount_code', true );
		} elseif ( ! empty( get_post_meta( $template->ID, '_edd_discount_name', true ) ) ) {
			$template_code = get_post_meta( $template->ID, '_edd_discount_name', true );
		} else {
			$template_code = get_the_title( $template->ID );
		}
	}

	if ( empty( $template_code ) ) {
		affiliate_wp()->utils->log( 'Unable to locate EDD discount template code.' );
		return false;
	}

	/**
	 * If a coupon code is provided, use it.
	 * Otherwise, generate the coupon code string by using the following data:
	 * - Affiliate ID
	 * - Coupon template data
	 * - The date
	 */
	$discount_args = array(
		'code'              => $args[ 'coupon_code' ],
		'name'              => get_post_meta( $template->ID, '_edd_discount_name', true ),
		'status'            => isset( $args[ 'status' ] )            ? $args['status' ]             : get_post_meta( $template->ID, '_edd_discount_status', true ),
		'uses'              => isset( $template->uses )              ? $template->uses              : '',
		'max_uses'          => isset( $template->max )               ? $template->max               : '',
		'amount'            => isset( $template->amount )            ? $template->amount            : '',
		'start'             => isset( $template->start )             ? $template->start             : '',
		'expiration'        => isset( $template->expiration )        ? $template->expiration        : '',
		'type'              => isset( $template->type )              ? $template->type              : '',
		'min_price'         => isset( $template->min_price )         ? $template->min_price         : '',
		'product_reqs'      => isset( $template->products )          ? $template->products          : array(),
		'product_condition' => isset( $template->product_condition ) ? $template->product_condition : '',
		'excluded_products' => isset( $template->excluded_products ) ? $template->excluded_products : array(),
		'is_not_global'     => isset( $template->not_global )        ? $template->not_global        : false,
		'is_single_use'     => isset( $template->use_once )          ? $template->use_once          : false,
		'affwp_discount_affiliate' => $args[ 'affiliate_id' ]
	);

	$discount_id = edd_store_discount( $discount_args, null );

	if ( $discount_id ) {
		$discount = edd_get_discount( $discount_id );

		/**
		 * @see EDD/#5974
		 * @link https://github.com/easydigitaldownloads/easy-digital-downloads/issues/5974
		 */
		if ( empty( $discount->code ) ) {
			affiliate_wp()->utils->log( 'No coupon code generated for discount ID ' . $discount_id );
			return false;
		}

		$coupon_args = array(
			'coupon_code'           => $discount->code,
			'integration'           => 'edd',
			'status'                => $discount->status,
			'integration_coupon_id' => $discount->ID,
			'affiliate_id'          => $args[ 'affiliate_id' ],
		);

		return $coupon_args;
	}

	return false;
}

/**
 * Generates a WooCommerce coupon.
 *
 * @param  array              $args    Coupon arguments. The array should contain:
 *     `affiliate_id`
 *     `template_id`
 *     `name`
 *     `coupon_code`
 *     `amount`
 *     `type`
 *     `affwp_discount_affiliate`
 *
 * @return mixed array|false  $coupon  Array of coupon data if successful, otherwise returns false.
 * @see    affwp_generate_integration_coupon
 * @since  2.2
 */
function affwp_generate_integration_coupon_woocommerce( $args = array() ) {
	$coupon_args = array();
	$template    = false;

	// Ensure that a coupon template exists before proceeding.
	if ( is_int( $args[ 'template_id' ] ) ) {
		$template = get_post( $args[ 'template_id' ] );
	} else {
		affiliate_wp()->utils->log( 'affwp_generate_integration_coupon_woocommerce: Unable to retrieve WooCommerce coupon template.' );
		return false;
	}

	if ( ! $template ) {
		affiliate_wp()->utils->log( 'affwp_generate_integration_coupon_woocommerce: Unable to retrieve WooCommerce coupon template.' );
		return false;
	}

	if ( ! $args[ 'affiliate_id' ] ) {
		affiliate_wp()->utils->log( 'affwp_generate_integration_coupon_woocommerce: Unable to retrieve affiliate ID.' );
		return false;
	}

	if ( ! $args[ 'coupon_code' ] ) {
		affiliate_wp()->utils->log( 'affwp_generate_integration_coupon_woocommerce: Unable to retrieve template coupon code.' );
		return false;
	}

	$template = is_object( $template ) ? (array) $template : $template;

	$coupon_code = $args[ 'coupon_code' ];

	$coupon_args = array (
		'post_title' => $coupon_code,
		'post_type' => 'shop_coupon',
		'meta_input' => array (
			'code'                         => $coupon_code,
			'type'                         => $template[ 'type' ] ? $template[ 'type' ] : 'fixed_cart',
			'amount'                       => $template[ 'amount' ] ? $template[ 'amount' ] : 0,
			'individual_use'               => $template[ 'individual_use' ] ? $template[ 'individual_use' ] : false,
			'product_ids'                  => $template[ 'product_ids' ] ? $template[ 'product_ids' ] : array(),
			'exclude_product_ids'          => $template[ 'exclude_product_ids' ] ? $template[ 'exclude_product_ids' ] : array(),
			'usage_limit'                  => $template[ 'usage_limit' ] ? $template[ 'usage_limit' ] : '',
			'usage_limit_per_user'         => $template[ 'usage_limit_per_user' ] ? $template[ 'usage_limit_per_user' ] : '',
			'limit_usage_to_x_items'       => $template[ 'limit_usage_to_x_items' ] ? $template[ 'limit_usage_to_x_items' ] : '',
			'usage_count'                  => $template[ 'usage_count' ] ? $template[ 'usage_count' ] : '',
			'expiry_date'                  => $template[ 'expiry_date' ] ? $template[ 'expiry_date' ] : '',
			'enable_free_shipping'         => $template[ 'enable_free_shipping' ] ? $template[ 'enable_free_shipping' ] : false,
			'product_category_ids'         => $template[ 'product_category_ids' ] ? $template[ 'product_category_ids' ] : array(),
			'exclude_product_category_ids' => $template[ 'exclude_product_category_ids' ] ? $template[ 'exclude_product_category_ids' ] : array(),
			'exclude_sale_items'           => $template[ 'exclude_sale_items' ] ? $template[ 'exclude_sale_items' ] : false,
			'minimum_amount'               => $template[ 'minimum_amount' ] ? $template[ 'minimum_amount' ] : '',
			'maximum_amount'               => $template[ 'maximum_amount' ] ? $template[ 'maximum_amount' ] : '',
			'customer_emails'              => $template[ 'customer_emails' ] ? $template[ 'customer_emails' ] : array(),
			'description'                  => $template[ 'description' ] ? $template[ 'description' ] : ''

		)
	);

	$wc_coupon_id = wp_insert_post( $coupon_args );

	if ( $wc_coupon_id ) {
		$wc_coupon = get_post( $wc_coupon_id );

		/**
		 * @see EDD/#5974
		 * @link https://github.com/easydigitaldownloads/easy-digital-downloads/issues/5974
		 */
		if ( empty( get_post_meta( $wc_coupon_id, 'code', true ) ) ) {
			affiliate_wp()->utils->log( 'No coupon code generated for discount ID ' . $discount_id );
			return false;
		}

		$coupon_args = array(
			'coupon_code'           => get_post_meta( $wc_coupon_id, 'code', true ),
			'integration'           => 'woocommerce',
			'status'                => ! empty( $template[ 'status' ] ) ? $template[ 'status' ] : 'active',
			'integration_coupon_id' => $wc_coupon->ID,
			'affiliate_id'          => $args[ 'affiliate_id' ],
		);

		return $coupon_args;
	}

	return false;
}
