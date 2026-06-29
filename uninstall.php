<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}cawp_issues" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}cawp_scans" );

delete_option( 'cawp_settings' );
delete_option( 'cawp_db_version' );
