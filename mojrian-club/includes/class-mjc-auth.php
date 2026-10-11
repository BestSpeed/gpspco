<?php
/**
 * ثبت‌نام، ورود و بازیابی رمز عبور.
 *
 * @package mojrian-club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * کلاس احراز هویت.
 */
class MJC_Auth {

	/**
	 * هوک‌ها.
	 */
	public static function hooks() {
		// جلوگیری از ورود کاربران معلق/تأییدنشده به بخش اعضا (به‌جز ادمین‌ها).
		add_action( 'wp_login', array( __CLASS__, 'after_login' ), 10, 2 );
	}

	/**
	 * هدایت پس از ورود بر اساس نقش.
	 *
	 * @param string   $user_login نام کاربری.
	 * @param WP_User  $user کاربر.
	 */
	public static function after_login( $user_login, $user ) {
		if ( user_can( $user, 'mjc_manage_club' ) ) {
			return;
		}
		if ( in_array( MJC_Roles::MEMBER, (array) $user->roles, true ) || MJC_Members::get_by_user( $user->ID ) ) {
			$member = MJC_Members::get_by_user( $user->ID );
			$target = ( $member && 'approved' === $member->status ) ? mjc_page_url( 'dashboard' ) : mjc_page_url( 'login' );
			wp_safe_redirect( add_query_arg( ( $member && 'approved' !== $member->status ) ? array( 'mjc_notice' => 'not_approved' ) : array(), $target ) );
			exit;
		}
	}

	/**
	 * ثبت‌نام عضو جدید.
	 *
	 * @param array $data داده‌های فرم (شامل otp_verified اختیاری).
	 * @return int|WP_Error شناسه کاربر.
	 */
	public static function register( $data ) {
		$mobile = MJC_SMS::normalize_mobile( isset( $data['mobile'] ) ? $data['mobile'] : '' );
		if ( ! $mobile ) {
			return new WP_Error( 'mjc_fields', 'شماره موبایل معتبر نیست.' );
		}

		$full_name     = sanitize_text_field( isset( $data['full_name'] ) ? $data['full_name'] : '' );
		$national_code = mjc_to_en_digits( sanitize_text_field( isset( $data['national_code'] ) ? $data['national_code'] : '' ) );
		$province      = sanitize_text_field( isset( $data['province'] ) ? $data['province'] : '' );
		$city          = sanitize_text_field( isset( $data['city'] ) ? $data['city'] : '' );
		$job_type      = sanitize_text_field( isset( $data['job_type'] ) ? $data['job_type'] : '' );
		$experience    = (int) ( isset( $data['experience'] ) ? $data['experience'] : 0 );
		$address       = sanitize_text_field( isset( $data['workshop_address'] ) ? $data['workshop_address'] : '' );
		$password      = isset( $data['password'] ) ? (string) $data['password'] : '';

		if ( '' === $full_name || '' === $national_code || '' === $province || '' === $job_type ) {
			return new WP_Error( 'mjc_fields', 'تکمیل نام، کد ملی، استان و نوع فعالیت الزامی است.' );
		}
		if ( ! preg_match( '/^\d{10}$/', $national_code ) ) {
			return new WP_Error( 'mjc_fields', 'کد ملی باید ۱۰ رقم باشد.' );
		}
		if ( username_exists( $mobile ) || email_exists( $mobile . '@mjc.local' ) ) {
			return new WP_Error( 'mjc_exists', 'این شماره موبایل قبلاً ثبت شده است. از گزینه‌ی «ورود» استفاده کنید.' );
		}
		if ( strlen( $password ) < 8 ) {
			$password = wp_generate_password( 12, true, false );
		}

		$user_id = wp_insert_user( array(
			'user_login' => $mobile,
			'user_pass'  => $password,
			'user_email' => $mobile . '@mjc.local',
			'first_name' => $full_name,
			'display_name' => $full_name,
			'role'       => 'subscriber',
		) );
		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		// کد معرف.
		$referred_by = 0;
		if ( ! empty( $data['referral_code'] ) ) {
			$ref_code = sanitize_text_field( $data['referral_code'] );
			$referrer = MJC_DB::var( 'SELECT user_id FROM ' . mjc_table( 'members' ) . ' WHERE referral_code = %s', array( $ref_code ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$referred_by = $referrer ? (int) $referrer : 0;
		}

		$status = ( 'auto' === mjc_get_setting( 'registration_approval', 'manual' ) ) ? 'approved' : 'pending';

		$member_id = MJC_Members::upsert( $user_id, array(
			'mobile'           => $mobile,
			'full_name'        => $full_name,
			'national_code'    => $national_code,
			'province'         => $province,
			'city'             => $city,
			'job_type'         => $job_type,
			'experience'       => $experience,
			'workshop_address' => $address,
			'status'           => $status,
			'referral_code'    => MJC_Members::generate_referral_code(),
			'referred_by'      => $referred_by,
		) );

		if ( 'approved' === $status ) {
			$user = get_userdata( $user_id );
			if ( $user ) {
				$user->set_role( MJC_Roles::MEMBER );
			}
			MJC_Points::award_by_rule( $user_id, 'welcome', 'خوش‌آمدگویی عضویت در باشگاه', 'member', $member_id );
			if ( $referred_by ) {
				MJC_Points::award_by_rule( $referred_by, 'referral_joined', 'معرفی عضو جدید (' . MJC_SMS::pretty_mobile( $mobile ) . ')', 'member', $member_id );
			}
		}

		MJC_Audit::log( 'member_registered', 'member', $member_id, $mobile );

		// ورود خودکار پس از ثبت‌نام.
		wp_set_current_user( $user_id );
		wp_set_auth_cookie( $user_id );

		return $user_id;
	}

	/**
	 * ورود با موبایل/نام کاربری + رمز.
	 *
	 * @param string $username نام کاربری یا موبایل.
	 * @param string $password رمز.
	 * @return true|WP_Error
	 */
	public static function login( $username, $password ) {
		$username = trim( (string) $username );
		$mobile   = MJC_SMS::normalize_mobile( $username );
		if ( $mobile ) {
			$username = $mobile;
		}
		$user = wp_signon( array(
			'user_login'    => $username,
			'user_password' => (string) $password,
			'remember'      => true,
		), is_ssl() );
		if ( is_wp_error( $user ) ) {
			return new WP_Error( 'mjc_login', 'نام کاربری یا رمز عبور اشتباه است.' );
		}
		return true;
	}

	/**
	 * ورود با کد یک‌بارمصرف.
	 *
	 * @param string $mobile شماره.
	 * @param string $code کد.
	 * @return true|WP_Error
	 */
	public static function login_with_otp( $mobile, $code ) {
		$mobile = MJC_SMS::normalize_mobile( $mobile );
		if ( ! MJC_SMS::verify_otp( $mobile, $code, 'login' ) ) {
			return new WP_Error( 'mjc_otp', 'کد تأیید نادرست یا منقضی شده است.' );
		}
		$user = get_user_by( 'login', $mobile );
		if ( ! $user ) {
			// شاید با نام کاربری دیگری ثبت شده؛ جست‌وجو با متا.
			$users = get_users( array( 'meta_key' => 'mjc_mobile', 'meta_value' => $mobile, 'number' => 1 ) );
			$user  = $users ? $users[0] : null;
		}
		if ( ! $user ) {
			return new WP_Error( 'mjc_nouser', 'کاربری با این شماره یافت نشد.' );
		}
		wp_set_current_user( $user->ID );
		wp_set_auth_cookie( $user->ID );
		return true;
	}

	/**
	 * بازیابی رمز عبور با OTP.
	 *
	 * @param string $mobile شماره.
	 * @param string $code کد.
	 * @param string $new_password رمز جدید.
	 * @return true|WP_Error
	 */
	public static function reset_password( $mobile, $code, $new_password ) {
		$mobile = MJC_SMS::normalize_mobile( $mobile );
		if ( ! MJC_SMS::verify_otp( $mobile, $code, 'reset' ) ) {
			return new WP_Error( 'mjc_otp', 'کد تأیید نادرست یا منقضی شده است.' );
		}
		if ( strlen( (string) $new_password ) < 8 ) {
			return new WP_Error( 'mjc_fields', 'رمز عبور باید حداقل ۸ کاراکتر باشد.' );
		}
		$user = get_user_by( 'login', $mobile );
		if ( ! $user ) {
			return new WP_Error( 'mjc_nouser', 'کاربری با این شماره یافت نشد.' );
		}
		wp_set_password( (string) $new_password, $user->ID );
		MJC_Notify::push( $user->ID, 'تغییر رمز عبور', 'رمز عبور حساب شما با موفقیت تغییر کرد.' );
		return true;
	}
}
