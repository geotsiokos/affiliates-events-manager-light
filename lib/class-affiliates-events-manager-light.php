<?php
/**
 * class-affiliates-events-manager-light.php
 *
 * Copyright (c) "kento" Karim Rahimpur www.itthinx.com
 *
 * This code is provided subject to the license granted.
 * Unauthorized use and distribution is prohibited.
 * See COPYRIGHT.txt and LICENSE.txt
 *
 * This code is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * This header and all notices must be kept intact.
 *
 * @author Karim Rahimpur
 * @package affiliates-events-manager-light
 * @since affiliates-events-manager-light 2.0.0
 */

/**
 * Affliates Events Manager Light class
 */
class Affiliates_Events_Manager_Light {

	const PLUGIN_OPTIONS        = 'affiliates_events_manager_light';
	const REFERRAL_TYPE         = 'booking';
	const REFERRAL_RATE         = 'referral-rate';
	const REFERRAL_RATE_DEFAULT = '0';
	const NONCE                 = 'aff_em_light_admin_nonce';
	const SET_ADMIN_OPTIONS     = 'set_admin_options';

	// Events Manager uses magic numbers
	const BOOKING_STATUS_UNAPPROVED              = 0;
	const BOOKING_STATUS_APPROVED                = 1;
	const BOOKING_STATUS_REJECTED                = 2;
	const BOOKING_STATUS_CANCELLED               = 3;
	const BOOKING_STATUS_AWAITING_ONLINE_PAYMENT = 4;
	const BOOKING_STATUS_AWAITING_PAYMENT        = 5;

	/**
	 * Admin messages.
	 *
	 * @static
	 * @access private
	 *
	 * @var array
	 */
	private static $admin_messages = array();

	/**
	 * Prints admin notices.
	 */
	public static function admin_notices() {
		if ( !empty( self::$admin_messages ) ) {
			foreach ( self::$admin_messages as $msg ) {
				echo wp_kses(
					$msg,
					array(
						'a' => array(
							'href'   => array(),
							'target' => array( '_blank' )
						),
						'div' => array(
							'class' => array()
						),
						'strong' => array()
					)
				);
			}
		}
	}

	/**
	 * Checks dependencies and sets up actions and filters.
	 */
	public static function init() {

		add_action( 'admin_notices', array( __CLASS__, 'admin_notices' ) );

		$verified = true;
		$disable = false;
		$active_plugins = get_option( 'active_plugins', array() );
		$affiliates_is_active = in_array( 'affiliates/affiliates.php', $active_plugins ) || in_array( 'affiliates-pro/affiliates-pro.php', $active_plugins ) || in_array( 'affiliates-enterprise/affiliates-enterprise.php', $active_plugins );
		$events_manager_is_active = in_array( 'events-manager/events-manager.php', $active_plugins );
		$affiliates_events_manager_is_active = in_array( 'affiliates-events-manager/affiliates-events-manager.php', $active_plugins );

		if ( !$affiliates_is_active ) {
			self::$admin_messages[] = '<div class="error">' . __( 'The <strong>Affiliates Events Manager Integration Light</strong> plugin requires the <a href="http://wordpress.org/plugins/affiliates/">Affiliates</a> plugin.', 'affiliates-events-manager-light' ) . '</div>';
		}
		if ( !$events_manager_is_active ) {
			self::$admin_messages[] = '<div class="error">' . __( 'The <strong>Affiliates Events Manager Integration Light</strong> plugin requires the <a href="http://wordpress.org/plugins/woocommerce/">Events Manager</a> plugin to be activated.', 'affiliates-events-manager-light' ) . '</div>';
		}
		if ( $affiliates_events_manager_is_active ) {
			self::$admin_messages[] = '<div class="error">' . __( 'You do not need to use the <strong>Affiliates Events Manager Integration Light</strong> plugin because you are already using the advanced Affiliates Events Manager Integration plugin. Please deactivate the <strong>Affiliates Events Manager Integration Light</strong> plugin now.', 'affiliates-events-manager-light' ) . '</div>';
		}
		if ( !$affiliates_is_active || !$events_manager_is_active || $affiliates_events_manager_is_active ) {
			if ( $disable ) {
				include_once  ABSPATH . 'wp-admin/includes/plugin.php' ;
				deactivate_plugins( array( __FILE__ ) );
			}
			$verified = false;
		}

		if ( $verified ) {
			add_action( 'affiliates_admin_menu', array( __CLASS__, 'affiliates_admin_menu' ) );
			add_action( 'em_bookings_added', array( __CLASS__, 'em_bookings_added' ) );
			add_filter( 'em_booking_set_status', array( __CLASS__, 'em_booking_set_status' ), 10, 2 );
			add_filter( 'em_booking_delete', array( __CLASS__, 'em_booking_delete' ), 10, 2 );
		}
	}

	/**
	 * Adds a submenu item to the Affiliates menu for the Events Manager integration options.
	 */
	public static function affiliates_admin_menu() {
		$page = add_submenu_page(
			'affiliates-admin',
			__( 'Affiliates Events Manager Integration Light', 'affiliates-events-manager-light' ),
			__( 'Events Manager Integration Light', 'affiliates-events-manager-light' ),
			AFFILIATES_ADMINISTER_OPTIONS,
			'affiliates-events-manager-light',
			array( __CLASS__, 'affiliates_admin_em_light' )
		);
		$pages[] = $page;
		add_action( 'admin_print_styles-' . $page, 'affiliates_admin_print_styles' );
		add_action( 'admin_print_scripts-' . $page, 'affiliates_admin_print_scripts' );
	}

	/**
	 * Affiliates Events Manager Integration Light : admin section.
	 */
	public static function affiliates_admin_em_light() {
		$output = '';
		if ( !current_user_can( AFFILIATES_ADMINISTER_OPTIONS ) ) {
			wp_die( esc_html__( 'Access denied.', 'affiliates-events-manager-light' ) );
		}
		$options = get_option( self::PLUGIN_OPTIONS , array() );
		if ( isset( $_POST['submit'] ) ) {
			if ( wp_verify_nonce( $_POST[self::NONCE], self::SET_ADMIN_OPTIONS ) ) {
				$options[self::REFERRAL_RATE]  = floatval( $_POST[self::REFERRAL_RATE] );
				if ( $options[self::REFERRAL_RATE] > 1.0 ) {
					$options[self::REFERRAL_RATE] = 1.0;
				} else if ( $options[self::REFERRAL_RATE] < 0 ) {
					$options[self::REFERRAL_RATE] = 0.0;
				}
			}
			update_option( self::PLUGIN_OPTIONS, $options );
		}

		$referral_rate = isset( $options[self::REFERRAL_RATE] ) ? $options[self::REFERRAL_RATE] : self::REFERRAL_RATE_DEFAULT;

		$output .= '<div>';
		$output .= '<h2>';
		$output .= esc_html__( 'Affiliates Events Manager Integration Light', 'affiliates-events-manager-light' );
		$output .= '</h2>';
		$output .= '</div>';

		$output .= '<p class="manage" style="padding:1em;margin-right:1em;font-weight:bold;font-size:1em;line-height:1.62em">';
		$output .=
			wp_kses(
				__( 'You can support the development of the Affiliates plugin and get additional features with <a href="http://www.itthinx.com/shop/affiliates-pro/" target="_blank">Affiliates Pro</a> and <a href="http://www.itthinx.com/shop/affiliates-enterprise/" target="_blank">Affiliates Enterprise</a>.', 'affiliates-events-manager-light' ),
				array(
					'a' => array(
						'href'   => array(),
						'target' => array( '_blank' )
					),
					'strong' => array()
				)
			);
		$output .= '</p>';

		$output .= '<div class="manage" style="padding:2em;margin-right:1em;">';
		$output .= '<form action="" name="options" method="post">';
		$output .= '<div>';
		$output .= '<h3>' . esc_html__( 'Referral Rate', 'affiliates-events-manager-light' ) . '</h3>';
		$output .= '<p>';
		$output .= '<label for="' . esc_attr( self::REFERRAL_RATE ) . '">' . esc_html__( 'Referral rate', 'affiliates-events-manager-light' ) . '</label>';
		$output .= '&nbsp;';
		$output .= '<input name="' . esc_attr( self::REFERRAL_RATE ) . '" type="text" value="' . esc_attr( $referral_rate ) . '"/>';
		$output .= '</p>';
		$output .= '<p>';
		$output .= esc_html__( 'The referral rate determines the referral amount based on the net sale made.', 'affiliates-events-manager-light' );
		$output .= '</p>';
		$output .= '<p class="description">';
		$output .=
			wp_kses(
				__( 'Example: Set the referral rate to <strong>0.1</strong> if you want your affiliates to get a <strong>10%</strong> commission on each booking.', 'affiliates-events-manager-light' ),
				array(
					'strong' => array()
				)
			);
		$output .= '</p>';

		$output .= '<p>';
		$output .= wp_nonce_field( self::SET_ADMIN_OPTIONS, self::NONCE, true, false );
		$output .= '<input class="button-primary" type="submit" name="submit" value="' . __( 'Save', 'affiliates-events-manager-light' ) . '"/>';
		$output .= '</p>';

		$output .= '</div>';
		$output .= '</form>';
		$output .= '</div>';

		// @codingStandardsIgnoreStart
		echo $output;
		// @codingStandardsIgnoreEnd

		affiliates_footer();
	}

	/**
	 * Load translations.
	 */
	public static function wp_init() {
		load_plugin_textdomain( AFF_EVENTS_MANAGER_PLUGIN_DOMAIN, false, 'affiliates-events-manager/languages' );
	}

	/**
	 * Registers script.
	 * Currently not used.
	 */
	public static function wp_enqueue_scripts() {
		wp_register_script( 'affiliates-events-manager', AFFILIATES_EM_PLUGIN_URL . '/js/affiliates-events-manager.js', array( 'jquery' ), AFFILIATES_EM_VERSION, true );
	}

	/**
	 * Retrieve the current URL.
	 *
	 * @return string
	 */
	public static function get_url() {
		return ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	}

	/**
	 * Record a referral for a new booking.
	 *
	 * @param EM_Booking $em_booking
	 */
	public static function em_bookings_added( $em_booking ) {

		// booking price excluding taxes and with discounts applied
		$price = $em_booking->get_price_pre_taxes();

		// There is a single currency in Events Manager and there is no API function to obtain
		// the currency id so we have to use the option directly (EM 5.5.5).
		$currency = get_option( 'dbem_bookings_currency' );
		$em_event = $em_booking->get_event();

		$data = array(
			'booking_id' => array(
				'title' => 'Booking #',
				'domain' => AFF_EVENTS_MANAGER_PLUGIN_DOMAIN,
				'value' => esc_sql( $em_booking->booking_id )
			),
			'booking_total' => array(
				'title' => 'Total',
				'domain' => AFF_EVENTS_MANAGER_PLUGIN_DOMAIN,
				'value' => esc_sql( $price )
			),
			'booking_currency' => array(
				'title' => 'Currency',
				'domain' => AFF_EVENTS_MANAGER_PLUGIN_DOMAIN,
				'value' => esc_sql( $currency )
			),
			'booking_link' => array(
				'title' => 'Booking',
				'domain' => AFF_EVENTS_MANAGER_PLUGIN_DOMAIN,
				'value' => esc_sql(
					sprintf(
						'<a href="%s">%s</a>',
						$em_booking->get_admin_url(),
						__( 'View', 'affiliates-events-manager-light' )
					)
				)
			)
		);

		$options       = get_option( self::PLUGIN_OPTIONS , array() );
		$post_id       = $em_event->post_id;
		$referral_rate = isset( $options[self::REFERRAL_RATE] ) ? $options[self::REFERRAL_RATE] : self::REFERRAL_RATE_DEFAULT;
		$amount        = bcmul( $referral_rate, $price, AFFILIATES_REFERRAL_AMOUNT_DECIMALS );
		$description   = sprintf( __( 'Booking %d', 'affiliates-events-manager-light' ), $em_booking->booking_id );
		$status        = self::get_referral_status( $em_booking );
		$type          = self::REFERRAL_TYPE;
		$reference     = $em_booking->booking_id;
		$aff_id        = affiliates_suggest_referral( $post_id, $description, $data, $amount, $currency, $status, $type, $reference );
	}

	/**
	 * Hooked on the status filter to update the related referral(s) based on the
	 * booking status.
	 *
	 * @param int $result
	 * @param EM_Booking $em_booking
	 * @return int
	 */
	public static function em_booking_set_status( $result, $em_booking ) {
		global $wpdb;
		$status = self::get_referral_status( $em_booking );
		$referrals_table = _affiliates_get_tablename( 'referrals' );
		if ( $referrals = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DISTINCT referral_id FROM $referrals_table WHERE reference = %s AND type = %s AND status != %s AND status != %s",
				$em_booking->booking_id,
				self::REFERRAL_TYPE,
				$status,
				AFFILIATES_REFERRAL_STATUS_CLOSED
			)
		) ) {
			foreach ( $referrals as $referral ) {
				affiliates_update_referral(
					$referral->referral_id,
					array( 'status' => $status )
				);
			}
		}
		return $result;
	}

	/**
	 * Reject referrals for deleted bookings.
	 *
	 * @param boolean $result true if the booking has been deleted
	 * @param EM_Booking $em_booking
	 * @return boolean
	 */
	public static function em_booking_delete( $result, $em_booking ) {
		global $wpdb;
		if ( $result !== false ) {
			$status = AFFILIATES_REFERRAL_STATUS_REJECTED;
			$referrals_table = _affiliates_get_tablename( 'referrals' );
			if ( $referrals = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT DISTINCT referral_id FROM $referrals_table WHERE reference = %s AND type = %s AND status != %s",
					$em_booking->booking_id,
					self::REFERRAL_TYPE,
					$status
				)
			) ) {
				foreach ( $referrals as $referral ) {
					affiliates_update_referral(
						$referral->referral_id,
						array( 'status' => $status )
					);
				}
			}
		}
		return $result;
	}

	/**
	 * Returns the corresponding referral status for a booking, based
	 * on the booking's current status.
	 *
	 * @param EM_Booking $em_booking
	 * @return string
	 */
	private static function get_referral_status( $em_booking ) {
		if ( isset( $em_booking->booking_status ) ) {
			switch ( $em_booking->booking_status ) {
				case self::BOOKING_STATUS_UNAPPROVED :
				case self::BOOKING_STATUS_AWAITING_PAYMENT :
				case self::BOOKING_STATUS_AWAITING_ONLINE_PAYMENT :
					$status = AFFILIATES_REFERRAL_STATUS_PENDING;
					break;
				case self::BOOKING_STATUS_APPROVED :
					$status = AFFILIATES_REFERRAL_STATUS_ACCEPTED;
					break;
				case self::BOOKING_STATUS_REJECTED :
				case self::BOOKING_STATUS_CANCELLED :
					$status = AFFILIATES_REFERRAL_STATUS_REJECTED;
					break;
				default :
					$status = get_option( 'aff_default_referral_status', AFFILIATES_REFERRAL_STATUS_ACCEPTED );
			}
		}
		return $status;
	}

}
Affiliates_Events_Manager_Light::init();
