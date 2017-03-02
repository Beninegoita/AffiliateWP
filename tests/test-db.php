<?php
namespace AffWP\Database;

use AffWP\Tests\UnitTestCase;

/**
 * Tests for Affiliate_WP_DB_Affiliates class
 *
 * @covers Affiliate_WP_DB
 * @group database
 */
class Tests extends UnitTestCase {

	/**
	 * Affiliate fixture.
	 *
	 * @access protected
	 * @var int
	 * @static
	 */
	protected static $affiliate_id = 0;

	/**
	 * Referral fixture.
	 *
	 * @access protected
	 * @var int
	 * @static
	 */
	protected static $referral_id = 0;

	/**
	 * Set up fixtures once.
	 */
	public static function wpSetUpBeforeClass() {
		self::$affiliate_id = parent::affwp()->affiliate->create();

		self::$referral_id = parent::affwp()->referral->create( array(
			'affiliate_id' => self::$affiliate_id
		) );
	}

	/**
	 * @covers Affiliate_WP_DB::insert()
	 */
	public function test_insert_should_unslash_data_before_inserting_into_db() {
		$description = addslashes( "Couldn't be simpler" );

		// Confirm the incoming value is slashed. (Simulating $_POST, which is slashed by core).
		$this->assertSame( "Couldn\'t be simpler", $description );

		// Fire ->add() which fires ->insert().
		$referral_id = $this->factory->referral->create( array(
			'affiliate_id' => self::$affiliate_id,
			'description'  => $description
		) );

		$stored = affiliate_wp()->referrals->get_column( 'description', $referral_id );

		$this->assertSame( wp_unslash( $description ), $stored );

		// Clean up.
		affwp_delete_referral( $referral_id );
	}

	/**
	 * @covers Affiliate_WP_DB::update()
	 */
	public function test_update_should_unslash_data_before_inserting_into_db() {
		$description = addslashes( "Couldn't be simpler" );

		// Confirm the incoming value is slashed. (Simulating $_POST, which is slashed by core).
		$this->assertSame( "Couldn\'t be simpler", $description );

		// Fire ->update_referral() which fires ->update()
		$this->factory->referral->update_object( self::$referral_id, array(
			'description' => $description
		) );

		$stored = affiliate_wp()->referrals->get_column( 'description', self::$referral_id );

		$this->assertSame( wp_unslash( $description ), $stored );
	}

	/**
	 * @covers \Affiliate_WP_DB::get_date_sql()
	 */
	public function test_get_date_sql_with_date_query_args_array_start_only_should_form_sql_for_items_after_given_date() {
		$timestamp = time() - WEEK_IN_SECONDS;
		$args      = array(
			'date' => array(
				'start' => date( 'm/d/Y', $timestamp )
			)
		);

		$expected_sql = sprintf( "date_registered > '%s'", date( 'Y-m-d 00:00:00', $timestamp ) );
		$result       = affiliate_wp()->affiliates->get_date_sql( $args, '', 'date_registered' );

		$this->assertContains( $expected_sql, $result );
	}

	/**
	 * @covers \Affiliate_WP_DB::get_date_sql()
	 */
	public function test_get_date_sql_with_date_query_args_array_end_only_should_form_sql_for_items_before_given_date() {
		$timestamp = time() + WEEK_IN_SECONDS;
		$args      = array(
			'date' => array(
				'end' => date( 'm/d/Y', $timestamp )
			)
		);

		$expected_sql = sprintf( "date_registered < '%s'", date( 'Y-m-d 00:00:00', $timestamp ) );
		$result       = affiliate_wp()->affiliates->get_date_sql( $args, '', 'date_registered' );

		$this->assertContains( $expected_sql, $result );
	}

	/**
	 * @covers \Affiliate_WP_DB::get_date_sql()
	 */
	public function test_get_date_sql_with_date_query_args_array_should_form_sql_for_items_between_dates() {
		$start_timestampe = time() - WEEK_IN_SECONDS;
		$end_timestamp    = time() + WEEK_IN_SECONDS;

		$args = array(
			'date' => array(
				'start' => date( 'm/d/Y', $start_timestampe ),
				'end'   => date( 'm/d/Y', $end_timestamp )
			)
		);

		$expected_sql = sprintf( "date_registered >= '%1s' AND date_registered <= '%2s'",
			date( 'Y-m-d 00:00:00', $start_timestampe ),
			date( 'Y-m-d 00:00:00', $end_timestamp )
		);

		$result = affiliate_wp()->affiliates->get_date_sql( $args, '', 'date_registered' );

		$this->assertContains( $expected_sql, $result );
	}

	/**
	 * @covers \Affiliate_WP_DB::get_date_sql()
	 */
	public function test_get_date_sql_with_date_query_args_string_should_form_sql_for_specific_date() {

	}


}
