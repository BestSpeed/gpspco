<?php
/**
 * ماژول معرفی دوستان.
 *
 * @package mojrian-club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * کلاس معرفی.
 */
class MJC_Referrals {

	/**
	 * هوک‌ها.
	 */
	public static function hooks() {}

	/**
	 * کد معرف کاربر.
	 *
	 * @param int $user_id شناسه کاربر.
	 * @return string
	 */
	public static function code( $user_id ) {
		$member = MJC_Members::get_by_user( $user_id );
		return $member ? (string) $member->referral_code : '';
	}

	/**
	 * نشانی دعوت.
	 *
	 * @param int $user_id شناسه کاربر.
	 * @return string
	 */
	public static function link( $user_id ) {
		$code = self::code( $user_id );
		if ( ! $code ) {
			return '';
		}
		return add_query_arg( 'ref', rawurlencode( $code ), mjc_page_url( 'register' ) );
	}

	/**
	 * زیرمجموعه‌های کاربر.
	 *
	 * @param int $user_id شناسه کاربر.
	 * @return array
	 */
	public static function referrals( $user_id ) {
		$member = MJC_Members::get_by_user( $user_id );
		if ( ! $member ) {
			return array();
		}
		return MJC_DB::results(
			'SELECT * FROM ' . mjc_table( 'members' ) . ' WHERE referred_by = %d ORDER BY id DESC LIMIT 100', // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			array( (int) $member->id )
		);
	}
}
