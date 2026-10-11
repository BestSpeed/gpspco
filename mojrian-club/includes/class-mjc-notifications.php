<?php
/**
 * اعلان‌های درون‌سایت.
 *
 * @package mojrian-club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * کلاس اعلان‌ها.
 */
class MJC_Notify {

	/**
	 * ارسال اعلان به یک کاربر.
	 *
	 * @param int    $user_id شناسه کاربر.
	 * @param string $title عنوان.
	 * @param string $body متن.
	 * @param string $url نشانی (اختیاری).
	 */
	public static function push( $user_id, $title, $body, $url = '' ) {
		if ( ! $user_id ) {
			return;
		}
		MJC_DB::insert( 'notifications', array(
			'user_id'    => (int) $user_id,
			'title'      => sanitize_text_field( $title ),
			'body'       => wp_kses_post( $body ),
			'url'        => esc_url_raw( $url ),
			'is_read'    => 0,
			'created_at' => current_time( 'mysql' ),
		) );
	}

	/**
	 * اعلان همگانی به همه‌ی اعضا (یا گروهی از وضعیت‌ها).
	 *
	 * @param string $title عنوان.
	 * @param string $body متن.
	 * @param string $url نشانی.
	 * @param string $status وضعیت عضو (approved برای همه‌ی اعضا).
	 * @return int تعداد.
	 */
	public static function broadcast( $title, $body, $url = '', $status = 'approved' ) {
		global $wpdb;
		$user_ids = $wpdb->get_col( $wpdb->prepare( 'SELECT user_id FROM ' . mjc_table( 'members' ) . ' WHERE status = %s', $status ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		foreach ( (array) $user_ids as $uid ) {
			self::push( (int) $uid, $title, $body, $url );
		}
		return count( (array) $user_ids );
	}

	/**
	 * تعداد خوانده‌نشده‌ها.
	 *
	 * @param int $user_id شناسه کاربر.
	 * @return int
	 */
	public static function unread_count( $user_id ) {
		return (int) MJC_DB::var( 'SELECT COUNT(*) FROM ' . mjc_table( 'notifications' ) . ' WHERE user_id = %d AND is_read = 0', array( (int) $user_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * فهرست اعلان‌ها.
	 *
	 * @param int $user_id شناسه کاربر.
	 * @param int $paged صفحه.
	 * @return array [rows, total]
	 */
	public static function list_for_user( $user_id, $paged = 1, $per_page = 20 ) {
		$offset = max( 0, ( $paged - 1 ) * $per_page );
		$rows   = MJC_DB::results(
			'SELECT * FROM ' . mjc_table( 'notifications' ) . ' WHERE user_id = %d ORDER BY id DESC LIMIT %d OFFSET %d', // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			array( (int) $user_id, $per_page, $offset )
		);
		$total = (int) MJC_DB::var( 'SELECT COUNT(*) FROM ' . mjc_table( 'notifications' ) . ' WHERE user_id = %d', array( (int) $user_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return array( $rows ? $rows : array(), $total );
	}

	/**
	 * خواندن همه.
	 *
	 * @param int $user_id شناسه کاربر.
	 */
	public static function mark_all_read( $user_id ) {
		MJC_DB::update( 'notifications', array( 'is_read' => 1 ), array( 'user_id' => (int) $user_id ) );
	}
}
