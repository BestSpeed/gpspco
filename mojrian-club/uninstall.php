<?php
/**
 * حذف کامل افزونه هنگام Uninstall.
 *
 * @package mojrian-club
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// حذف تنظیمات فقط در صورت فعال بودن گزینه‌ی حذف داده‌ها.
$mjc_settings = get_option( 'mjc_settings', array() );
$mjc_delete   = isset( $mjc_settings['delete_data_on_uninstall'] ) ? $mjc_settings['delete_data_on_uninstall'] : false;

if ( $mjc_delete ) {
	global $wpdb;

	// حذف جداول.
	$tables = array( 'members', 'points_ledger', 'projects', 'grade_requests', 'reward_requests', 'wallet_transactions', 'enrollments', 'survey_responses', 'lottery_entries', 'notifications', 'tickets', 'audit_log', 'sms_log' );
	foreach ( $tables as $table ) {
		$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'mjc_' . $table ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	// حذف تنظیمات.
	delete_option( 'mjc_settings' );
	delete_option( 'mjc_guide_seeded' );

	// حذف نقش‌ها.
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-mjc-roles.php';
	MJC_Roles::uninstall();

	// حذف صفحات ساخته‌شده.
	$pages = isset( $mjc_settings['pages'] ) ? (array) $mjc_settings['pages'] : array();
	foreach ( $pages as $page_id ) {
		wp_delete_post( (int) $page_id, true );
	}
}
