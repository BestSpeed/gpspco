<?php
/**
 * تبدیل تاریخ میلادی ↔ شمسی (الگوریتم استاندارد jdf).
 *
 * @package mojrian-club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * کلاس تاریخ شمسی.
 */
class MJC_Jalali {

	/**
	 * نام ماه‌های شمسی.
	 *
	 * @return array
	 */
	public static function months() {
		return array( 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند' );
	}

	/**
	 * تبدیل میلادی به شمسی.
	 *
	 * @param int $gy سال.
	 * @param int $gm ماه.
	 * @param int $gd روز.
	 * @return array [jy, jm, jd]
	 */
	public static function to_jalali( $gy, $gm, $gd ) {
		$gy     = (int) $gy;
		$gm     = (int) $gm;
		$gd     = (int) $gd;
		$gdm    = array( 0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334 );
		$gy2    = ( $gm > 2 ) ? $gy + 1 : $gy;
		$days   = 355666 + ( 365 * $gy ) + (int) ( ( $gy2 + 3 ) / 4 ) - (int) ( ( $gy2 + 99 ) / 100 ) + (int) ( ( $gy2 + 399 ) / 400 ) + $gd + $gdm[ $gm - 1 ];
		$jy     = -1595 + ( 33 * (int) ( $days / 12053 ) );
		$days  %= 12053;
		$jy    += 4 * (int) ( $days / 1461 );
		$days  %= 1461;
		if ( $days > 365 ) {
			$jy  += (int) ( ( $days - 1 ) / 365 );
			$days = ( $days - 1 ) % 365;
		}
		if ( $days < 186 ) {
			$jm = 1 + (int) ( $days / 31 );
			$jd = 1 + ( $days % 31 );
		} else {
			$jm = 7 + (int) ( ( $days - 186 ) / 30 );
			$jd = 1 + ( ( $days - 186 ) % 30 );
		}
		return array( $jy, $jm, $jd );
	}

	/**
	 * تبدیل شمسی به میلادی.
	 *
	 * @param int $jy سال.
	 * @param int $jm ماه.
	 * @param int $jd روز.
	 * @return array [gy, gm, gd]
	 */
	public static function to_gregorian( $jy, $jm, $jd ) {
		$jy     = (int) $jy;
		$jm     = (int) $jm;
		$jd     = (int) $jd;
		$jy    += 1595;
		$days   = -355668 + ( 365 * $jy ) + ( (int) ( $jy / 33 ) * 8 ) + (int) ( ( ( $jy % 33 ) + 3 ) / 4 ) + $jd + ( ( $jm < 7 ) ? ( $jm - 1 ) * 31 : ( ( $jm - 7 ) * 30 ) + 186 );
		$gy     = 400 * (int) ( $days / 146097 );
		$days  %= 146097;
		if ( $days > 36524 ) {
			$days--;
			$gy += 100 * (int) ( $days / 36524 );
			$days %= 36524;
			if ( $days >= 365 ) {
				$days++;
			}
		}
		$gy += 4 * (int) ( $days / 1461 );
		$days %= 1461;
		if ( $days > 365 ) {
			$gy += (int) ( ( $days - 1 ) / 365 );
			$days = ( $days - 1 ) % 365;
		}
		$gd = $days + 1;
		$leap = ( ( ( $gy % 4 ) == 0 ) && ( ( $gy % 100 ) != 0 ) ) || ( ( $gy % 400 ) == 0 );
		$gdm  = array( 0, 31, ( $leap ? 29 : 28 ), 31, 30, 31, 30, 31, 31, 30, 31, 30, 31 );
		$gm   = 0;
		foreach ( $gdm as $m => $dim ) {
			if ( $gd <= $dim ) {
				$gm = $m;
				break;
			}
			$gd -= $dim;
		}
		return array( $gy, $gm ? $gm : 12, $gd );
	}

	/**
	 * قالب‌بندی رشته‌ی تاریخ میلادی به شمسی.
	 *
	 * @param string $datetime  تاریخ میلادی.
	 * @param bool   $with_time نمایش ساعت.
	 * @param bool   $fa_digits اعداد فارسی.
	 * @return string
	 */
	public static function format( $datetime, $with_time = true, $fa_digits = true ) {
		if ( empty( $datetime ) || '0000-00-00 00:00:00' === $datetime ) {
			return '—';
		}
		$ts = strtotime( $datetime );
		if ( ! $ts ) {
			return '—';
		}
		list( $jy, $jm, $jd ) = self::to_jalali( date( 'Y', $ts ), date( 'n', $ts ), date( 'j', $ts ) );
		$out = $jy . '/' . str_pad( (string) $jm, 2, '0', STR_PAD_LEFT ) . '/' . str_pad( (string) $jd, 2, '0', STR_PAD_LEFT );
		if ( $with_time ) {
			$out .= ' — ' . date( 'H:i', $ts );
		}
		return $fa_digits ? mjc_num( $out ) : $out;
	}

	/**
	 * مقدار مناسب برای datepicker شمسی (Y/m/d).
	 *
	 * @param string $datetime تاریخ میلادی.
	 * @return string
	 */
	public static function datepicker_value( $datetime ) {
		if ( empty( $datetime ) ) {
			return '';
		}
		$ts = strtotime( $datetime );
		if ( ! $ts ) {
			return '';
		}
		list( $jy, $jm, $jd ) = self::to_jalali( date( 'Y', $ts ), date( 'n', $ts ), date( 'j', $ts ) );
		return $jy . '/' . str_pad( (string) $jm, 2, '0', STR_PAD_LEFT ) . '/' . str_pad( (string) $jd, 2, '0', STR_PAD_LEFT );
	}

	/**
	 * تبدیل ورودی شمسی کاربر (1403/01/01) به میلادی برای ذخیره.
	 *
	 * @param string $jalali رشته‌ی شمسی.
	 * @return string Y-m-d یا رشته‌ی خالی.
	 */
	public static function parse_input( $jalali ) {
		$jalali = trim( (string) $jalali );
		if ( '' === $jalali ) {
			return '';
		}
		$jalali = mjc_to_en_digits( $jalali );
		$parts  = preg_split( '#[/\-.]#', $jalali );
		if ( count( $parts ) < 3 ) {
			return '';
		}
		list( $jy, $jm, $jd ) = array( (int) $parts[0], (int) $parts[1], (int) $parts[2] );
		if ( $jy < 1200 || $jm < 1 || $jm > 12 || $jd < 1 || $jd > 31 ) {
			return '';
		}
		list( $gy, $gm, $gd ) = self::to_gregorian( $jy, $jm, $jd );
		return sprintf( '%04d-%02d-%02d', $gy, $gm, $gd );
	}
}
