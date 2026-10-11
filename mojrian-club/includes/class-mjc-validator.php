<?php
/**
 * اعتبارسنجی ورودی‌های کاربران (موبایل و کد ملی).
 *
 * @package mojrian-club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * کلاس اعتبارسنجی.
 */
class MJC_Validator {

	/**
	 * اعتبارسنجی و نرمال‌سازی شماره موبایل.
	 *
	 * @param string $mobile شماره خام.
	 * @return string شماره‌ی نرمال (989xxxxxxxxx) یا رشته‌ی خالی.
	 */
	public static function mobile( $mobile ) {
		return mjc_normalize_mobile( $mobile );
	}

	/**
	 * اعتبارسنجی کد ملی (۱۰ رقم).
	 *
	 * @param string $code کد ملی خام.
	 * @return string کد ملی معتبر یا رشته‌ی خالی.
	 */
	public static function national_code( $code ) {
		$code = preg_replace( '/\D/', '', mjc_to_en_digits( (string) $code ) );
		if ( preg_match( '/^\d{10}$/', $code ) ) {
			return $code;
		}
		return '';
	}
}
