<?php
/**
 * توابع کمکی عمومی افزونه باشگاه مجریان.
 *
 * @package mojrian-club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ترجمه با text domain افزونه.
 *
 * @param string $text متن فارسی.
 * @return string
 */
function mjc__( $text ) {
	return __( $text, 'mojrian-club' );
}

/**
 * نام کامل جدول دیتابیس افزونه.
 *
 * @param string $name نام کوتاه جدول بدون پیشوند mjc_.
 * @return string
 */
function mjc_table( $name ) {
	global $wpdb;
	return $wpdb->prefix . 'mjc_' . $name;
}

/**
 * خواندن یک تنظیم.
 *
 * @param string $key     کلید.
 * @param mixed  $default مقدار پیش‌فرض.
 * @return mixed
 */
function mjc_get_setting( $key, $default = '' ) {
	$settings = get_option( 'mjc_settings', array() );
	if ( ! is_array( $settings ) ) {
		$settings = array();
	}
	return array_key_exists( $key, $settings ) ? $settings[ $key ] : $default;
}

/**
 * ذخیره‌ی گروهی تنظیمات (ادغام).
 *
 * @param array $data کلید/مقدار.
 */
function mjc_update_settings( $data ) {
	$settings = get_option( 'mjc_settings', array() );
	if ( ! is_array( $settings ) ) {
		$settings = array();
	}
	update_option( 'mjc_settings', array_merge( $settings, $data ) );
}

/**
 * نگاشت صفحات ساخته‌شده در فعال‌سازی.
 *
 * @return array
 */
function mjc_pages() {
	$pages = mjc_get_setting( 'pages', array() );
	return is_array( $pages ) ? $pages : array();
}

/**
 * نشانی یک صفحه از نگاشت صفحات.
 *
 * @param string $key کلید صفحه (مثلاً dashboard).
 * @return string
 */
function mjc_page_url( $key ) {
	$pages = mjc_pages();
	$id    = isset( $pages[ $key ] ) ? (int) $pages[ $key ] : 0;
	if ( $id ) {
		$url = get_permalink( $id );
		if ( $url ) {
			return $url;
		}
	}
	return home_url( '/' );
}

/**
 * تبدیل اعداد انگلیسی به فارسی برای نمایش.
 *
 * @param string|number $value مقدار.
 * @return string
 */
function mjc_num( $value ) {
	return strtr( (string) $value, array(
		'0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴',
		'5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹',
	) );
}

/**
 * تبدیل ارقام فارسی/عربی به انگلیسی (برای ورودی‌های کاربر).
 *
 * @param string $value رشته.
 * @return string
 */
function mjc_to_en_digits( $value ) {
	return strtr( (string) $value, array(
		'۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
		'۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
		'٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
		'٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
	) );
}

/**
 * نمایش مبلغ (ریال) با جداکننده و رقم فارسی.
 *
 * @param int|float $amount مبلغ.
 * @return string
 */
function mjc_money( $amount ) {
	return str_replace( ',', '٬', mjc_num( number_format( (float) $amount ) ) );
}

/**
 * نمایش تاریخ/زمان میلادی به شمسی.
 *
 * @param string    $datetime  رشته‌ی تاریخ میلادی (Y-m-d H:i:s).
 * @param bool      $with_time نمایش ساعت.
 * @param bool      $fa_digits اعداد فارسی.
 * @return string
 */
function mjc_date( $datetime, $with_time = true, $fa_digits = true ) {
	return MJC_Jalali::format( $datetime, $with_time, $fa_digits );
}

/**
 * عضو (ردیف جدول اعضا) برای کاربر جاری یا شناسه داده‌شده.
 *
 * @param int $user_id شناسه کاربر.
 * @return object|null
 */
function mjc_member( $user_id = 0 ) {
	$user_id = $user_id ? (int) $user_id : get_current_user_id();
	return MJC_Members::get_by_user( $user_id );
}

/**
 * آیا کاربر جاری عضو تأییدشده‌ی باشگاه است؟
 *
 * @return bool
 */
function mjc_is_active_member() {
	$member = mjc_member();
	return $member && 'approved' === $member->status;
}

/**
 * نام برچسب وضعیت‌ها به فارسی.
 *
 * @param string $status کلید وضعیت.
 * @return string
 */
function mjc_status_label( $status ) {
	$map = array(
		'pending'  => 'در انتظار بررسی',
		'approved' => 'تأیید شده',
		'rejected' => 'رد شده',
		'suspended'=> 'معلق',
		'shipped'  => 'ارسال شده',
		'closed'   => 'بسته شده',
		'answered' => 'پاسخ داده شده',
		'open'     => 'باز',
		'active'   => 'فعال',
		'completed'=> 'تکمیل شده',
		'done'     => 'انجام شده',
		'withdraw' => 'درخواست برداشت',
		'credit'   => 'واریز',
		'debit'    => 'برداشت',
		'refund'   => 'بازگشت',
	);
	return isset( $map[ $status ] ) ? $map[ $status ] : $status;
}

/**
 * ساخت نشانی با پیام وضعیت (الگوی PRG).
 *
 * @param string $url   نشانی پایه.
 * @param string $code  کد پیام.
 * @param string $text  متن پیام (اختیاری).
 * @return string
 */
function mjc_notice_url( $url, $code, $text = '' ) {
	$args = array( 'mjc_notice' => $code );
	if ( '' !== $text ) {
		$args['mjc_notice_text'] = $text;
	}
	return add_query_arg( $args, $url );
}

/**
 * رندر پیام‌های وضعیت از query string.
 */
function mjc_render_notice() {
	if ( empty( $_GET['mjc_notice'] ) ) {
		return;
	}
	$code = sanitize_key( wp_unslash( $_GET['mjc_notice'] ) );
	$text = isset( $_GET['mjc_notice_text'] ) ? sanitize_text_field( wp_unslash( $_GET['mjc_notice_text'] ) ) : '';

	$map = array(
		'ok'            => array( 'success', 'عملیات با موفقیت انجام شد.' ),
		'error'         => array( 'error', 'خطایی رخ داد. لطفاً دوباره تلاش کنید.' ),
		'registered'    => array( 'success', 'ثبت‌نام انجام شد. پس از تأیید مدیر باشگاه، امکانات برای شما فعال می‌شود.' ),
		'otp_sent'      => array( 'success', 'کد تأیید به شماره موبایل شما ارسال شد.' ),
		'otp_invalid'   => array( 'error', 'کد تأیید نادرست یا منقضی شده است.' ),
		'login_failed'  => array( 'error', 'نام کاربری یا رمز عبور اشتباه است.' ),
		'not_approved'  => array( 'warning', 'حساب شما هنوز تأیید نشده است. پس از تأیید، امکانات باشگاه فعال می‌شود.' ),
		'pending'       => array( 'warning', 'درخواست شما ثبت شد و در انتظار بررسی است.' ),
		'limit'         => array( 'error', 'تعداد درخواست‌ها بیش از حد مجاز است. کمی بعد دوباره تلاش کنید.' ),
		'fields'        => array( 'error', 'لطفاً همه‌ی فیلدهای الزامی را تکمیل کنید.' ),
		'refunded'      => array( 'success', 'انجام شد و در صورت نیاز امتیاز بازگردانده شد.' ),
	);

	$cls  = isset( $map[ $code ] ) ? $map[ $code ][0] : 'info';
	$def  = isset( $map[ $code ] ) ? $map[ $code ][1] : $code;
	$msg  = '' !== $text ? $text : $def;
	printf( '<div class="mjc-notice mjc-notice-%s">%s</div>', esc_attr( $cls ), esc_html( $msg ) );
}

/**
 * شناسه‌ی فایل‌های آپلودشده‌ی فرم‌ها (چندگانه).
 *
 * @param string $field نام فیلد.
 * @return array آرایه‌ای از attachment ID.
 */
function mjc_uploaded_ids( $field ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce در متد فرم بررسی می‌شود.
	if ( empty( $_FILES[ $field ] ) ) {
		return array();
	}
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';

	$files = wp_unslash( $_FILES[ $field ] ); // phpcs:ignore
	$ids   = array();
	if ( ! empty( $files['name'] ) && is_array( $files['name'] ) ) {
		foreach ( $files['name'] as $i => $name ) {
			if ( empty( $name ) ) {
				continue;
			}
			$_FILES[ $field . '_single' ] = array(
				'name'     => $files['name'][ $i ],
				'type'     => $files['type'][ $i ],
				'tmp_name' => $files['tmp_name'][ $i ],
				'error'    => $files['error'][ $i ],
				'size'     => $files['size'][ $i ],
			);
			$id = media_handle_upload( $field . '_single', 0 );
			if ( ! is_wp_error( $id ) ) {
				$ids[] = (int) $id;
			}
			unset( $_FILES[ $field . '_single' ] );
		}
	} elseif ( ! empty( $files['name'] ) ) {
		$id = media_handle_upload( $field, 0 );
		if ( ! is_wp_error( $id ) ) {
			$ids[] = (int) $id;
		}
	}
	return $ids;
}

/**
 * قالب HTML یک کارت خالی/میان‌پیام.
 *
 * @param string $text متن.
 * @return string
 */
function mjc_empty_state( $text ) {
	return '<div class="mjc-empty">' . esc_html( $text ) . '</div>';
}

/**
 * نرمال‌سازی شماره موبایل به فرمت 989xxxxxxxxx.
 *
 * @param string $mobile شماره خام.
 * @return string شماره نرمال یا رشته‌ی خالی.
 */
function mjc_normalize_mobile( $mobile ) {
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
function mjc_pretty_mobile( $mobile ) {
	$mobile = (string) $mobile;
	if ( strpos( $mobile, '989' ) === 0 ) {
		$mobile = '0' . substr( $mobile, 2 );
	}
	return mjc_num( $mobile );
}
