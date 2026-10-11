<?php
/**
 * گزارش رویدادهای حساس (Audit Log).
 *
 * @package mojrian-club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * کلاس Audit.
 */
class MJC_Audit {

	/**
	 * ثبت رویداد.
	 *
	 * @param string $action     کلید رویداد.
	 * @param string $object_type نوع شیء.
	 * @param int    $object_id  شناسه شیء.
	 * @param string $details    جزئیات.
	 */
	public static function log( $action, $object_type = '', $object_id = 0, $details = '' ) {
		MJC_DB::insert( 'audit_log', array(
			'actor_id'    => get_current_user_id(),
			'action'      => sanitize_key( $action ),
			'object_type' => sanitize_key( $object_type ),
			'object_id'   => (int) $object_id,
			'details'     => wp_kses_post( $details ),
			'created_at'  => current_time( 'mysql' ),
		) );
	}
}
