<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}si_issues" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}si_scans" );

delete_option( 'site_inspector_settings' );
delete_option( 'site_inspector_db_version' );
