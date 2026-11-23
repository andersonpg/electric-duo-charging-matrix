<?php
/**
 * Removes the plugin's options when it is deleted from the Plugins screen.
 *
 * @package ElectricDuoChargingMatrix
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'edcm_settings' );
delete_option( 'edcm_custom_data' );

if ( is_multisite() ) {
	delete_site_option( 'edcm_settings' );
	delete_site_option( 'edcm_custom_data' );
}
