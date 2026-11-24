<?php
/**
 * Plugin Name:       Electric Duo EV Charging Matrix
 * Plugin URI:        https://theelectricduo.com/
 * Description:       Interactive, SEO-friendly matrix showing which EV charging apps and car brands work with which US charging networks (Tesla Supercharger, Electrify America, IONNA, EVgo, ChargePoint, Walmart and more). Dark theme matched to TheElectricDuo.com. Use the [ev_charging_matrix] shortcode.
 * Version:           1.1.6
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            The Electric Duo
 * Author URI:        https://theelectricduo.com/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ed-charging-matrix
 *
 * @package ElectricDuoChargingMatrix
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'EDCM_VERSION', '1.1.6' );
define( 'EDCM_FILE', __FILE__ );
define( 'EDCM_DIR', plugin_dir_path( __FILE__ ) );
define( 'EDCM_URL', plugin_dir_url( __FILE__ ) );

require_once EDCM_DIR . 'includes/class-edcm-data.php';
require_once EDCM_DIR . 'includes/class-edcm-render.php';
require_once EDCM_DIR . 'includes/class-edcm-admin.php';

/**
 * Boot the plugin.
 */
function edcm_boot() {
	load_plugin_textdomain( 'ed-charging-matrix', false, dirname( plugin_basename( EDCM_FILE ) ) . '/languages' );
	EDCM_Render::init();
	if ( is_admin() ) {
		EDCM_Admin::init();
	}
}
add_action( 'plugins_loaded', 'edcm_boot' );

/**
 * Settings link on the Plugins screen.
 *
 * @param array $links Existing action links.
 * @return array
 */
function edcm_action_links( $links ) {
	$url = admin_url( 'options-general.php?page=edcm' );
	array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'ed-charging-matrix' ) . '</a>' );
	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'edcm_action_links' );
