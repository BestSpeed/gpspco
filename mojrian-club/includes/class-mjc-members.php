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
		add_action( 'init', array( __CLASS__, 'maintenance' ) );
		add_action( 'wp_login', array( __CLASS__, 'repair_admin_lockout' ) );
	}

	/**
	 * آیا کاربر «مدیر/کاربر ارشد» باشگاه است؟
	 * این کاربران هرگز در فهرست اعضا نمی‌آیند، نیاز به تأیید ندارند و نقش وردپرس‌شان دست نمی‌خورد.
	 *
	 * @param int $user_id شناسه کاربر.
	 * @return bool
	 */
	public static function is_staff( $user_id ) {
		$user_id = (int) $user_id;
		if ( ! $user_id ) {
			return false;
		}
		return user_can( $user_id, 'manage_options' )
			|| user_can( $user_id, 'mjc_manage_club' )
			|| user_can( $user_id, 'mjc_manage_members' );
	}

	/**
	 * شناسه‌ی کاربران مدیر/کاربر ارشد (برای حذف از فهرست‌ها و شمارش‌ها).
	 *
	 * @return array
	 */
	public static function staff_user_ids() {
		$ids = array();
		foreach ( array( 'manage_options', 'mjc_manage_members' ) as $cap ) {
			foreach ( (array) get_users( array( 'capability' => $cap, 'fields' => 'ID' ) ) as $uid ) {
				$ids[ (int) $uid ] = (int) $uid;
			}
		}
		return array_values( $ids );
	}

	/**
	 * قطعه‌ی SQL برای حذف کاربران مدیر از کوئری‌های جدول اعضا.
	 *
	 * @return string مثل " AND user_id NOT IN (1,2)" یا رشته‌ی خالی.
	 */
	public static function not_staff_sql() {
		$staff = self::staff_user_ids();
		if ( ! $staff ) {
			return '';
		}
		return ' AND user_id NOT IN (' . implode( ',', array_map( 'intval', $staff ) ) . ')';
	}

	/**
	 * نگهداری دوره‌ای (هر چند دقیقه): ترمیم قفل‌شدن مدیر و وضعیت ردیف‌های کاربران مدیر.
	 */
	public static function maintenance() {
		if ( (int) get_option( 'mjc_maintenance_ts', 0 ) > time() - 300 ) {
			return;
		}
		update_option( 'mjc_maintenance_ts', time(), false );
		self::repair_admin_lockout();
		self::normalize_staff_rows();
	}

	/**
	 * ترمیم خودکار حالتی که هیچ کاربری با manage_options باقی نمانده (مثلاً پس از تغییر نقش اشتباه ادمین).
	 * نخستین کاربری که نقشش فقط نقش‌های عضویتی است، دوباره administrator می‌شود.
	 */
	public static function repair_admin_lockout() {
		if ( is_multisite() ) {
			return;
		}
		$admins = get_users( array( 'capability' => 'manage_options', 'fields' => 'ID', 'number' => 1 ) );
		if ( $admins ) {
			return;
		}
		$candidates = get_users( array( 'orderby' => 'ID', 'order' => 'ASC', 'fields' => 'all' ) );
		foreach ( (array) $candidates as $u ) {
			$roles = array_values( array_filter( (array) $u->roles ) );
			$only_club = $roles && ! array_diff( $roles, array( MJC_Roles::MEMBER, 'subscriber' ) );
			if ( ! $only_club ) {
				continue;
			}
			$u->set_role( 'administrator' );
			MJC_Roles::install();
			MJC_Audit::log( 'admin_role_restored', 'user', (int) $u->ID, 'بازیابی خودکار نقش مدیر' );
			break;
		}
	}

	/**
	 * ردیف کاربران مدیر/کاربر ارشد همیشه «تأییدشده» می‌ماند (نیاز به تأیید ندارند).
	 */
	public static function normalize_staff_rows() {
		global $wpdb;
		$staff = self::staff_user_ids();
		if ( ! $staff ) {
			return;
		}
		$in = implode( ',', array_map( 'intval', $staff ) );
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( 'UPDATE ' . mjc_table( 'members' ) . " SET status = 'approved' WHERE user_id IN ({$in}) AND status <> 'approved'" );
		// phpcs:enable
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
		// کاربران مدیر همیشه تأییدشده می‌مانند؛ وضعیت ورودی نمی‌تواند آن را تغییر دهد.
		if ( self::is_staff( $user_id ) ) {
			$data['status'] = 'approved';
		}
		if ( $existing ) {
			MJC_DB::update( 'members', $data, array( 'user_id' => (int) $user_id ) );
			return (int) $existing->id;
		}
		$data = array_merge( array(
			'user_id'    => (int) $user_id,
			'created_at' => current_time( 'mysql' ),
			'status'     => self::is_staff( $user_id ) ? 'approved' : 'pending',
		), $data );
		if ( self::is_staff( $user_id ) ) {
			$data['status'] = 'approved';
		}
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
		// کاربران مدیر هرگز تغییر وضعیت داده نمی‌شوند.
		if ( self::is_staff( (int) $member->user_id ) ) {
			return false;
		}
		MJC_DB::update( 'members', array(
			'status'        => sanitize_key( $status ),
			'reject_reason' => sanitize_text_field( $reason ),
			'updated_at'    => current_time( 'mysql' ),
		), array( 'id' => (int) $member_id ) );

		// همگام‌سازی نقش وردپرس — بدون دست زدن به نقش کاربران مدیر و بدون پاک‌کردن نقش‌های دیگر.
		$user = get_userdata( (int) $member->user_id );
		if ( $user && ! self::is_staff( (int) $member->user_id ) ) {
			if ( 'approved' === $status ) {
				$user->add_role( MJC_Roles::MEMBER );
			} else {
				$user->remove_role( MJC_Roles::MEMBER );
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
		$ns = self::not_staff_sql();
		return array(
			'total'    => (int) MJC_DB::var( 'SELECT COUNT(*) FROM ' . mjc_table( 'members' ) . ' WHERE 1=1' . $ns ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'pending'  => (int) MJC_DB::var( "SELECT COUNT(*) FROM " . mjc_table( 'members' ) . " WHERE status = 'pending'" . $ns ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'approved' => (int) MJC_DB::var( "SELECT COUNT(*) FROM " . mjc_table( 'members' ) . " WHERE status = 'approved'" . $ns ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'month'    => (int) MJC_DB::var( "SELECT COUNT(*) FROM " . mjc_table( 'members' ) . " WHERE created_at >= %s" . $ns, array( date( 'Y-m' ) . '%' ) ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
	}
}
