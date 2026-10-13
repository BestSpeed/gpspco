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
		add_filter( 'authenticate', array( __CLASS__, 'authenticate_mobile' ), 30, 3 );
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
		$mobile = mjc_normalize_mobile( isset( $data['mobile'] ) ? $data['mobile'] : '' );
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
			// رمز انتخابی کاربر باید عیناً روی حساب وردپرس بنشیند تا ورود از «لاگین سایت» هم کار کند؛
			// جایگزینی بی‌صدای رمز با مقدار تصادفی باعث می‌شد کاربر هرگز نتواند وارد شود.
			return new WP_Error( 'mjc_fields', 'رمز عبور باید حداقل ۸ کاراکتر باشد.' );
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

		if ( 'approved' === $status && ! MJC_Members::is_staff( $user_id ) ) {
			$user = get_userdata( $user_id );
			if ( $user ) {
				$user->add_role( MJC_Roles::MEMBER );
			}
			MJC_Points::award_by_rule( $user_id, 'welcome', 'خوش‌آمدگویی عضویت در باشگاه', 'member', $member_id );
			if ( $referred_by ) {
				MJC_Points::award_by_rule( $referred_by, 'referral_joined', 'معرفی عضو جدید (' . mjc_pretty_mobile( $mobile ) . ')', 'member', $member_id );
			}
		}

		MJC_Audit::log( 'member_registered', 'member', $member_id, $mobile );

		// ثبت اطلاعات در حساب کاربران وردپرس (هویت مشترک سایت و باشگاه).
		mjc_sync_wp_account( $user_id, array(
			'full_name'        => $full_name,
			'mobile'           => $mobile,
			'national_code'    => $national_code,
			'province'         => $province,
			'city'             => $city,
			'job_type'         => $job_type,
			'experience'       => $experience,
			'workshop_address' => $address,
		) );

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
	/**
	 * ورود از «لاگین سایت» (wp-login.php) با شماره موبایل به هر قالبی (0912...، ارقام فارسی، جداکننده‌دار).
	 *
	 * نام کاربریِ ثبت‌نام در باشگاه، شماره‌ی نرمال‌شده (989...) است ولی کاربران در لاگین سایت
	 * موبایل را به شکل رایج وارد می‌کنند. بعد از ناموفق‌بودن احراز هویت پیش‌فرض وردپرس،
	 * با شکل نرمال‌شده دوباره تلاش می‌کنیم تا همان رمز عبور روی همان حساب بنشیند.
	 *
	 * @param WP_User|WP_Error|null $user نتیجه‌ی احراز هویت تا اینجا.
	 * @param string $username نام کاربری واردشده.
	 * @param string $password رمز عبور.
	 * @return WP_User|WP_Error|null|object
	 */
	public static function authenticate_mobile( $user, $username, $password ) {
		if ( $user && ! is_wp_error( $user ) ) {
			return $user;
		}
		$mobile = mjc_normalize_mobile( $username );
		if ( ! $mobile ) {
			return $user;
		}
		// موبایل می‌تواند نام کاربری باشد یا پس از انتخاب نام کاربری دلخواه، شناسه‌ی ورود باشد.
		$target = mjc_user_by_mobile( $mobile );
		if ( ! $target ) {
			return $user;
		}
		$found = wp_authenticate_username_password( null, $target->user_login, (string) $password );
		if ( $found && ! is_wp_error( $found ) ) {
			return apply_filters( 'wp_authenticate_user', $found, (string) $password );
		}
		return $user;
	}

	public static function login( $username, $password ) {
		$username = trim( (string) $username );
		$mobile   = mjc_normalize_mobile( $username );
		if ( $mobile ) {
			$target = mjc_user_by_mobile( $mobile );
			$username = $target ? $target->user_login : $mobile;
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

	/**
	 * بازیابی رمز عبور با OTP.
	 *
	 * @param string $mobile شماره.
	 * @param string $code کد.
	 * @param string $new_password رمز جدید.
	 * @return true|WP_Error
	 */
}
