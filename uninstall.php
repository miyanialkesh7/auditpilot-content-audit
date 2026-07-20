<?php
/**
 * Uninstall routine: removes custom tables and options created by the plugin.
 *
 * @package AuditPilot_Content_Audit
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Uninstall must drop custom tables directly; no WordPress API handles schema removal.
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}apca_issues" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}apca_scans" );
// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange

delete_option( 'apca_settings' );
delete_option( 'apca_db_version' );
