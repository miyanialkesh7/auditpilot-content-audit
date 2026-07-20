<?php
/**
 * Constants normally defined at runtime in the plugin's main file,
 * declared here so PHPStan can resolve them during static analysis.
 *
 * @package AuditPilot_Content_Audit
 */

if ( ! defined( 'APCA_VERSION' ) ) {
	define( 'APCA_VERSION', '1.0.0' );
}
if ( ! defined( 'APCA_FILE' ) ) {
	define( 'APCA_FILE', __DIR__ . '/auditpilot-content-audit.php' );
}
if ( ! defined( 'APCA_PATH' ) ) {
	define( 'APCA_PATH', __DIR__ . '/' );
}
if ( ! defined( 'APCA_URL' ) ) {
	define( 'APCA_URL', '' );
}
if ( ! defined( 'APCA_BASENAME' ) ) {
	define( 'APCA_BASENAME', 'auditpilot-content-audit/auditpilot-content-audit.php' );
}
if ( ! defined( 'APCA_MIN_WP' ) ) {
	define( 'APCA_MIN_WP', '6.8' );
}
if ( ! defined( 'APCA_MIN_PHP' ) ) {
	define( 'APCA_MIN_PHP', '7.4' );
}
