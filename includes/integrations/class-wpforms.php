<?php

class Affiliate_WP_WPForms extends Affiliate_WP_Base {

	/**
	 * Get things started
	 *
	 * @access  public
	 * @since   2.0
	*/
	public function init() {

		$this->context = 'wpforms';

        add_action( 'wpforms_process_complete', array( $this, 'add_pending_referral' ), 10, 4 );
        add_filter( 'affwp_referral_reference_column', array( $this, 'reference_link' ), 10, 2 );

	}

    /**
	 * Records a pending referral when a pending payment is created
	 *
	 * @access  public
	 * @since   2.0
	*/
	public function add_pending_referral( $fields, $entry, $form_data, $entry_id ) {

        if ( ! $this->was_referred() && empty( $this->affiliate_id ) ) {
			return;
		}

        // get referral total
        $total          = wpforms_get_total_payment( $fields );
        $referral_total = $this->calculate_referral_amount( $total, $entry_id );

        // get description
        $description = $form_data['settings']['form_title'];

        // insert a pending referral
        $referral_id = $this->insert_pending_referral( $referral_total, $entry_id, $description );

        // set the referral to "unpaid" if there's no total
        if ( empty( $referral_total ) ) {
			$this->mark_referral_complete( $entry_id );
		}

	}

    /**
	 * Sets a referral to unpaid when payment is completed
	 *
	 * @access  public
	 * @since   2.0
	*/
	public function mark_referral_complete( $entry_id = 0 ) {
		$this->complete_referral( $entry_id );
	}

    /**
	 * Sets up the reference link in the Referrals table
	 *
	 * @access  public
	 * @since   2.0
	*/
	public function reference_link( $reference = 0, $referral ) {

		if ( empty( $referral->context ) || 'wpforms' != $referral->context ) {
			return $reference;
		}

		$url = admin_url( 'admin.php?page=wpforms-entries&view=details&entry_id=' . $reference );

		return '<a href="' . esc_url( $url ) . '">' . $reference . '</a>';
        
	}

}
new Affiliate_WP_WPForms;
