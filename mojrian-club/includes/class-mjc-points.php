<?php
/**
 * موتور امتیاز (گیتا) و دفتر کل.
 *
 * @package mojrian-club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * کلاس امتیاز.
 */
class MJC_Points {

	/**
	 * هوک‌ها.
	 */
	public static function hooks() {
		// پاداش تکمیل پروفایل هنگام ذخیره‌ی پروفایل از فرانت.
		add_action( 'mjc_profile_updated', array( __CLASS__, 'maybe_award_profile' ) );
	}

	/**
	 * ثبت تراکنش امتیاز.
	 *
	 * @param int    $user_id شناسه کاربر.
	 * @param int    $amount مقدار (مثبت/منفی).
	 * @param string $action کلید اقدام.
	 * @param string $description توضیح.
	 * @param string $ref_type نوع مرجع.
	 * @param int    $ref_id شناسه مرجع.
	 * @return bool
	 */
	public static function log_transaction( $user_id, $amount, $action, $description = '', $ref_type = '', $ref_id = 0 ) {
		global $wpdb;
		$user_id = (int) $user_id;
		$amount  = (int) $amount;
		if ( ! $user_id || 0 === $amount ) {
			return false;
		}

		$member = MJC_Members::get_by_user( $user_id );
		if ( ! $member ) {
			return false;
		}

		$balance = max( 0, (int) $member->points_balance + $amount );
		MJC_DB::update( 'members', array(
			'points_balance' => $balance,
			'updated_at'     => current_time( 'mysql' ),
		), array( 'user_id' => $user_id ) );

		MJC_DB::insert( 'points_ledger', array(
			'user_id'        => $user_id,
			'amount'         => $amount,
			'balance_after'  => $balance,
			'action'         => sanitize_key( $action ),
			'description'    => sanitize_text_field( $description ),
			'ref_type'       => sanitize_key( $ref_type ),
			'ref_id'         => (int) $ref_id,
			'created_by'     => get_current_user_id(),
			'created_at'     => current_time( 'mysql' ),
		) );
		return true;
	}

	/**
	 * اعطای امتیاز بر اساس قانون تنظیمات.
	 *
	 * @param int    $user_id شناسه کاربر.
	 * @param string $rule_key کلید قانون.
	 * @param string $description توضیح.
	 * @param string $ref_type نوع مرجع.
	 * @param int    $ref_id شناسه مرجع.
	 * @return int مقدار اعطاشده.
	 */
	public static function award_by_rule( $user_id, $rule_key, $description = '', $ref_type = '', $ref_id = 0 ) {
		$rule = MJC_Settings::rule( $rule_key );
		if ( ! $rule['enabled'] || $rule['points'] <= 0 ) {
			return 0;
		}
		$ok = self::log_transaction( $user_id, $rule['points'], $rule_key, $description ? $description : $rule['label'], $ref_type, $ref_id );
		if ( $ok ) {
			MJC_Notify::push( $user_id, 'کسب ' . mjc_get_setting( 'points_label', 'امتیاز' ), sprintf( '%s امتیاز برای «%s» به حساب شما اضافه شد.', mjc_num( $rule['points'] ), $description ? $description : $rule['label'] ), mjc_page_url( 'points' ) );
		}
		return $ok ? $rule['points'] : 0;
	}

	/**
	 * امتیاز دستی (ادمین).
	 *
	 * @param int    $user_id شناسه کاربر.
	 * @param int    $amount مقدار.
	 * @param string $description توضیح.
	 * @return bool
	 */
	public static function manual_adjust( $user_id, $amount, $description ) {
		$ok = self::log_transaction( $user_id, $amount, 'manual_adjust', $description );
		if ( $ok ) {
			MJC_Audit::log( 'points_adjust', 'user', (int) $user_id, sprintf( '%+d — %s', (int) $amount, $description ) );
			MJC_Notify::push( (int) $user_id, 'تغییر امتیاز', sprintf( 'امتیاز شما %s شد (%s). دلیل: %s', ( $amount >= 0 ? 'افزایش' : 'کاهش' ), mjc_num( abs( $amount ) ), $description ) );
		}
		return $ok;
	}

	/**
	 * تاریخچه‌ی امتیاز کاربر.
	 *
	 * @param int $user_id شناسه کاربر.
	 * @param int $paged صفحه.
	 * @param int $per_page تعداد.
	 * @return array [rows, total]
	 */
	public static function history( $user_id, $paged = 1, $per_page = 20 ) {
		global $wpdb;
		$offset = max( 0, ( $paged - 1 ) * $per_page );
		$rows   = MJC_DB::results(
			'SELECT * FROM ' . mjc_table( 'points_ledger' ) . ' WHERE user_id = %d ORDER BY id DESC LIMIT %d OFFSET %d', // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			array( (int) $user_id, $per_page, $offset )
		);
		$total = (int) MJC_DB::var( 'SELECT COUNT(*) FROM ' . mjc_table( 'points_ledger' ) . ' WHERE user_id = %d', array( (int) $user_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return array( $rows ? $rows : array(), $total );
	}

	/**
	 * پاداش تکمیل پروفایل (یک‌بار).
	 *
	 * @param int $user_id شناسه کاربر.
	 */
	public static function maybe_award_profile( $user_id ) {
		$flag = get_user_meta( (int) $user_id, 'mjc_profile_awarded', true );
		if ( $flag ) {
			return;
		}
		$member = MJC_Members::get_by_user( $user_id );
		if ( ! $member || empty( $member->full_name ) || empty( $member->national_code ) || empty( $member->province ) || empty( $member->job_type ) ) {
			return;
		}
		$points = self::award_by_rule( (int) $user_id, 'profile_complete', 'تکمیل پروفایل', 'profile', (int) $user_id );
		if ( $points ) {
			update_user_meta( (int) $user_id, 'mjc_profile_awarded', 1 );
		}
	}
}
