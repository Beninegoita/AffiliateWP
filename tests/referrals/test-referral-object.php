<?php
namespace AffWP\Referral\Object;

use AffWP\Tests\UnitTestCase;
use AffWP\Referral;

/**
 * Tests for AffWP\Referral
 *
 * @covers AffWP\Referral
 * @covers AffWP\Base_Object
 *
 * @group referrals
 * @group objects
 */
class Tests extends UnitTestCase {

	/**
	 * @covers AffWP\Base_Object::get_instance()
	 */
	public function test_get_instance_with_invalid_referral_id_should_return_false() {
		$this->assertFalse( Referral::get_instance( 0 ) );
	}

	/**
	 * @covers AffWP\Base_Object::get_instance()
	 */
	public function test_get_instance_with_referral_id_should_return_Referral_object() {
		$user_id = $this->factory->user->create();

		$affiliate_id = affiliate_wp()->affiliates->add( array(
			'user_id' => $user_id
		) );

		$referral_id = affiliate_wp()->referrals->add( array(
			'affiliate_id' => $affiliate_id
		) );

		$referral = Referral::get_instance( $referral_id );

		$this->assertInstanceOf( 'AffWP\Referral', $referral );
	}
}
