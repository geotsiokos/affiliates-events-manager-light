<?php
/**
 * affiliates-events-manager-light.php
 *
 * Copyright (c) 2016 www.itthinx.com
 *
 * This code is released under the GNU General Public License.
 * See COPYRIGHT.txt and LICENSE.txt.
 *
 * This code is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This header and all notices must be kept intact.
 *
 * @author itthinx
 * @package affiliates-events-manager-light
 * @since 1.0.0
 *
 * Plugin Name: Affiliates Events Manager Light
 * Plugin URI: http://www.itthinx.com/plugins/affiliates-events-manager-light/
 * Description: Integrates Affiliates with Events Manager so that affiliates can earn commissions on referred bookings.
 * Author: itthinx
 * Author URI: http://www.itthinx.com/
 * Donate-Link: http://www.itthinx.com
 * Text Domain: affiliates-events-manager-light
 * Domain Path: /languages
 * License: GPLv3
 * Version: 2.0.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AFFILIATES_EM_PLUGIN_URL', WP_PLUGIN_URL . '/affiliates-events-manager-light' );
define( 'AFF_EVENTS_MANAGER_PLUGIN_DOMAIN', 'affiliates-events-manager-light' );

if ( !defined( 'AFF_EVENTS_MANAGER_DIR' ) ) {
	define( 'AFF_EVENTS_MANAGER_DIR', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
}
if ( !defined( 'AFF_EVENTS_MANAGER_LIB' ) ) {
	define( 'AFF_EVENTS_MANAGER_LIB', AFF_EVENTS_MANAGER_DIR . '/lib' );
}
require_once AFF_EVENTS_MANAGER_LIB . '/class-affiliates-events-manager-light.php';
