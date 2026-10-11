<?php
/**
 * مدیریت اعضا (پروفایل باشگاه).
 *
 * @package mojrian-club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * کلاس اعضا.
 */
class MJC_Members {

	/**
	 * هوک‌ها.
	 */
	public static function hooks() {
		add_action( 'profile_update', array( __CLASS__, 'sync_display_name' ), 10, 2 );
	}

	/**
	 * دریافت عضو با شناسه کاربر.
	 *
	 * @param int $user_id شناسه کاربر.
	 * @return object|null
	 */
	public static function get_by_user( $user_id ) {
		global $wpdb;
		if ( ! $user_id ) {
			return null;
		}
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . mjc_table( 'members' ) . ' WHERE user_id = %d', $user_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $row ? $row : null;
	}

	/**
	 * دریافت عضو با شناسه‌ی عضو.
	 *
	 * @param int $id شناسه.
	 * @return object|null
	 */
	public static function get( $id ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . mjc_table( 'members' ) . ' WHERE id = %d', (int) $id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $row ? $row : null;
	}

	/**
	 * ساخت/به‌روزرسانی عضو.
	 *
	 * @param int   $user_id شناسه کاربر.
	 * @param array $data داده‌ها.
	 * @return int شناسه عضو.
	 */
	public static function upsert( $user_id, $data ) {
		$existing = self::get_by_user( $user_id );
		$data     = array_merge( array( 'updated_at' => current_time( 'mysql' ) ), $data );
		if ( $existing ) {
			MJC_DB::update( 'members', $data, array( 'user_id' => (int) $user_id ) );
			return (int) $existing->id;
		}
		$data = array_merge( array(
			'user_id'    => (int) $user_id,
			'created_at' => current_time( 'mysql' ),
			'status'     => 'pending',
		), $data );
		return MJC_DB::insert( 'members', $data );
	}

	/**
	 * ساخت ردیف عضو برای کاربر (در صورت نبود) — الگوی create.
	 *
	 * @param int $user_id شناسه کاربر وردپرس.
	 * @return object|null ردیف عضو.
	 */
	public static function create( $user_id ) {
		$user_id = (int) $user_id;
		if ( ! $user_id ) {
			return null;
		}
		$existing = self::get_by_user( $user_id );
		if ( $existing ) {
			return $existing;
		}
		self::upsert( $user_id, array( 'referral_code' => self::generate_referral_code() ) );
		return self::get_by_user( $user_id );
	}

	/**
	 * ساخت کد معرف یکتا.
	 *
	 * @return string
	 */
	public static function generate_referral_code() {
		do {
			$code = strtoupper( wp_generate_password( 8, false, false ) );
			$exists = MJC_DB::var( 'SELECT id FROM ' . mjc_table( 'members' ) . ' WHERE referral_code = %s', array( $code ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		} while ( $exists );
		return $code;
	}

	/**
	 * تغییر وضعیت عضو.
	 *
	 * @param int    $member_id شناسه عضو.
	 * @param string $status وضعیت جدید.
	 * @param string $reason دلیل.
	 * @return bool
	 */
	public static function set_status( $member_id, $status, $reason = '' ) {
		$member = self::get( $member_id );
		if ( ! $member ) {
			return false;
		}
		MJC_DB::update( 'members', array(
			'status'        => sanitize_key( $status ),
			'reject_reason' => sanitize_text_field( $reason ),
			'updated_at'    => current_time( 'mysql' ),
		), array( 'id' => (int) $member_id ) );

		// همگام‌سازی نقش وردپرس.
		$user = get_userdata( (int) $member->user_id );
		if ( $user ) {
			if ( 'approved' === $status ) {
				$user->set_role( MJC_Roles::MEMBER );
			} elseif ( 'suspended' === $status ) {
				$user->set_role( 'subscriber' );
			}
		}

		MJC_Audit::log( 'member_status_' . $status, 'member', $member_id, $reason );

		// پیام به کاربر.
		if ( 'approved' === $status ) {
			MJC_Notify::push( (int) $member->user_id, 'عضویت شما تأیید شد', 'به باشگاه مجریان خوش آمدید! اکنون می‌توانید از همه‌ی امکانات باشگاه استفاده کنید.', mjc_page_url( 'dashboard' ) );
			// امتیاز خوش‌آمدگویی و پاداش معرف.
			MJC_Points::award_by_rule( (int) $member->user_id, 'welcome', 'خوش‌آمدگویی عضویت در باشگاه' );
			if ( ! empty( $member->referred_by ) ) {
				MJC_Points::award_by_rule( (int) $member->referred_by, 'referral_joined', 'معرفی عضو جدید (' . mjc_pretty_mobile( $member->mobile ) . ')' );
			}
		} elseif ( 'rejected' === $status ) {
			MJC_Notify::push( (int) $member->user_id, 'ثبت‌نام شما تأیید نشد', 'متأسفانه درخواست عضویت شما پذیرفته نشد. دلیل: ' . $reason );
		}
		return true;
	}

	/**
	 * همگام‌سازی نام نمایشی.
	 *
	 * @param int      $user_id شناسه کاربر.
	 * @param WP_User  $old_user کاربر قدیمی.
	 */
	public static function sync_display_name( $user_id, $old_user ) {
		$user = get_userdata( $user_id );
		if ( $user ) {
			MJC_DB::update( 'members', array(
				'full_name'  => trim( $user->first_name . ' ' . $user->last_name ) ? trim( $user->first_name . ' ' . $user->last_name ) : $user->display_name,
				'updated_at' => current_time( 'mysql' ),
			), array( 'user_id' => (int) $user_id ) );
		}
	}

	/**
	 * نام نمایشی عضو.
	 *
	 * @param object $member ردیف عضو.
	 * @return string
	 */
	public static function display_name( $member ) {
		if ( ! $member ) {
			return '—';
		}
		if ( ! empty( $member->full_name ) ) {
			return $member->full_name;
		}
		$user = get_userdata( (int) $member->user_id );
		return $user ? $user->display_name : '—';
	}

	/**
	 * آمار اعضا برای داشبورد ادمین.
	 *
	 * @return array
	 */
	public static function stats() {
		return array(
			'total'    => (int) MJC_DB::var( 'SELECT COUNT(*) FROM ' . mjc_table( 'members' ) ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'pending'  => (int) MJC_DB::var( "SELECT COUNT(*) FROM " . mjc_table( 'members' ) . " WHERE status = 'pending'" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'approved' => (int) MJC_DB::var( "SELECT COUNT(*) FROM " . mjc_table( 'members' ) . " WHERE status = 'approved'" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'month'    => (int) MJC_DB::var( "SELECT COUNT(*) FROM " . mjc_table( 'members' ) . " WHERE created_at >= %s", array( date( 'Y-m-01 00:00:00' ) ) ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
	}
}
