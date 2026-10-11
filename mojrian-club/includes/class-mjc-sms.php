<?php
/**
 * سرویس پیامک (Adapter قابل تعویض).
 *
 * @package mojrian-club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * کلاس پیامک.
 */
class MJC_SMS {

	/**
	 * ارسال پیامک.
	 *
	 * @param string $mobile شماره موبایل.
	 * @param string $message متن پیام.
	 * @return bool
	 */
	public static function send( $mobile, $message ) {
		$mobile = self::normalize_mobile( $mobile );
		if ( ! $mobile ) {
			return false;
		}

		$gateway = mjc_get_setting( 'sms_gateway', 'log' );
		$status  = 'sent';

		// هوک برای اتصال درگاه دلخواه (Kavenegar، SMS.ir و ...) بدون تغییر هسته.
		$handled = apply_filters( 'mjc_sms_send', null, $mobile, $message, $gateway );
		if ( true === $handled ) {
			self::log( $mobile, $message, 'sent' );
			return true;
		}
		if ( false === $handled ) {
			self::log( $mobile, $message, 'failed' );
			return false;
		}

		if ( 'log' === $gateway ) {
			// حالت توسعه: پیامک در «گزارش پیامک» ادمین ثبت می‌شود.
			self::log( $mobile, $message, 'sent' );
			return true;
		}

		if ( 'kavenegar' === $gateway ) {
			$api_key = mjc_get_setting( 'sms_api_key', '' );
			$sender  = mjc_get_setting( 'sms_sender', '' );
			if ( ! $api_key ) {
				$status = 'failed';
			} else {
				$url   = 'https://api.kavenegar.com/v1/' . rawurlencode( $api_key ) . '/sms/send.json';
				$response = wp_remote_post( $url, array(
					'timeout' => 15,
					'body'    => array(
						'receptor' => $mobile,
						'message'  => $message,
						'sender'   => $sender,
					),
				) );
				$status = ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) ? 'sent' : 'failed';
			}
			self::log( $mobile, $message, $status );
			return 'sent' === $status;
		}

		self::log( $mobile, $message, 'failed' );
		return false;
	}

	/**
	 * نرمال‌سازی شماره موبایل ایران.
	 *
	 * @param string $mobile شماره.
	 * @return string خالی اگر نامعتبر.
	 */
	public static function normalize_mobile( $mobile ) {
		$mobile = mjc_to_en_digits( $mobile );
		$mobile = preg_replace( '/[\s\-()+]/', '', (string) $mobile );
		if ( preg_match( '/^989\d{9}$/', $mobile ) ) {
			return $mobile;
		}
		if ( preg_match( '/^0?9\d{9}$/', $mobile ) ) {
			return '98' . ltrim( $mobile, '0' );
		}
		return '';
	}

	/**
	 * نمایش شماره به فرمت 09xx xxx xxxx.
	 *
	 * @param string $mobile شماره ذخیره‌شده (98...).
	 * @return string
	 */
	public static function pretty_mobile( $mobile ) {
		$mobile = (string) $mobile;
		if ( strpos( $mobile, '989' ) === 0 ) {
			$mobile = '0' . substr( $mobile, 2 );
		}
		return mjc_num( $mobile );
	}

	/**
	 * ثبت در گزارش پیامک.
	 *
	 * @param string $mobile شماره.
	 * @param string $message متن.
	 * @param string $status وضعیت.
	 */
	public static function log( $mobile, $message, $status ) {
		MJC_DB::insert( 'sms_log', array(
			'mobile'     => $mobile,
			'message'    => $message,
			'status'     => $status,
			'created_at' => current_time( 'mysql' ),
		) );
	}

	/**
	 * ساخت و ارسال کد یک‌بارمصرف.
	 *
	 * @param string $mobile شماره.
	 * @param string $purpose منظور (register|login|reset).
	 * @return string|WP_Error کد یا خطا.
	 */
	public static function send_otp( $mobile, $purpose ) {
		$mobile = self::normalize_mobile( $mobile );
		if ( ! $mobile ) {
			return new WP_Error( 'mjc_invalid_mobile', 'شماره موبایل معتبر نیست.' );
		}

		// محدودیت نرخ: حداکثر N بار در ساعت.
		$count_key = 'mjc_otp_count_' . md5( $mobile . $purpose );
		$count     = (int) get_transient( $count_key );
		$max       = (int) mjc_get_setting( 'otp_max_per_hour', 5 );
		if ( $count >= $max ) {
			return new WP_Error( 'mjc_otp_limit', 'تعداد درخواست کد بیش از حد مجاز است.' );
		}
		set_transient( $count_key, $count + 1, HOUR_IN_SECONDS );

		$len  = max( 4, min( 8, (int) mjc_get_setting( 'otp_length', 5 ) ) );
		$code = (string) random_int( (int) str_repeat( '1', $len ), (int) str_repeat( '9', $len ) );
		$ttl  = max( 30, (int) mjc_get_setting( 'otp_ttl', 120 ) );
		set_transient( 'mjc_otp_' . md5( $mobile . $purpose ), $code, $ttl );

		$club  = mjc_get_setting( 'club_title', 'باشگاه مجریان' );
		$sent  = self::send( $mobile, sprintf( 'کد تأیید %s: %s', $club, $code ) );
		if ( ! $sent ) {
			return new WP_Error( 'mjc_sms_failed', 'ارسال پیامک ناموفق بود.' );
		}
		return $code;
	}

	/**
	 * بررسی کد یک‌بارمصرف.
	 *
	 * @param string $mobile شماره.
	 * @param string $code کد.
	 * @param string $purpose منظور.
	 * @return bool
	 */
	public static function verify_otp( $mobile, $code, $purpose ) {
		$mobile  = self::normalize_mobile( $mobile );
		$code    = mjc_to_en_digits( trim( (string) $code ) );
		$key     = 'mjc_otp_' . md5( $mobile . $purpose );
		$stored  = (string) get_transient( $key );
		if ( $stored && hash_equals( $stored, $code ) ) {
			delete_transient( $key );
			return true;
		}
		return false;
	}
}
