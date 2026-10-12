<?php
/**
 * لایه‌ی عمومی: بارگذاری استایل‌ها و اسکریپت‌ها.
 *
 * @package mojrian-club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * کلاس عمومی.
 */
class MJC_Public {

	/**
	 * هوک‌ها.
	 */
	public static function hooks() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'guard_item_query' ) );
	}

	/**
	 * خنثی‌سازی کوئری‌وارد همنام با CPT در لینک‌های قدیمی (?mjc_campaign=ID و ?mjc_guide=ID).
	 *
	 * این نام‌ها دقیقاً همنام کوئری‌وارد خودِ پست‌تایپ‌ها هستند و وردپرس مقدار عددی را
	 * «نامکِ تک‌مطلب» تفسیر می‌کند؛ هم‌نشینی با نشانی صفحه → تداخل کوئری → ۴۰۴. این مقدار
	 * عددی را از کوئری اصلی حذف می‌کنیم تا صفحه بارگذاری شود و شورت‌کد با $_GET نمای
	 * اختصاصی مطلب را بسازد. لینک‌های جدید از کوئری‌وارد غیرهم‌نامِ mjc_campaign_item و
	 * mjc_guide_item استفاده می‌کنند که اصلاً وارد کوئری اصلی وردپرس نمی‌شوند.
	 */
	public static function guard_item_query( $query ) {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}
		foreach ( array( 'mjc_campaign', 'mjc_guide' ) as $var ) {
			$v = $query->get( $var );
			if ( '' !== $v && null !== $v && ctype_digit( (string) $v ) ) {
				$query->set( $var, '' );
				$query->set( 'name', '' );
				$query->set( 'p', '' );
				$query->set( 'post_type', '' );
			}
		}
	}

	/**
	 * بارگذاری asset ها.
	 */
	public static function enqueue() {
		wp_enqueue_style(
			'mojrian-club',
			MJC_PLUGIN_URL . 'assets/css/mojrian-club.css',
			array(),
			MJC_VERSION
		);

		// فونت وزیرمتن (در صورت دسترسی به اینترنت).
		wp_enqueue_style(
			'mojrian-club-font',
			'https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css',
			array(),
			'33.3'
		);

		// رنگ اصلی قابل تنظیم.
		$color = mjc_get_setting( 'primary_color', '#0e7490' );
		if ( $color ) {
			wp_add_inline_style( 'mojrian-club', ':root{--mjc-primary:' . esc_attr( $color ) . ';}' );
		}

		wp_enqueue_script(
			'mojrian-club',
			MJC_PLUGIN_URL . 'assets/js/mojrian-club.js',
			array(),
			MJC_VERSION,
			true
		);

		// QR کارت عضویت (فقط وقتی لازم است).
		if ( is_page() && get_the_ID() === (int) ( mjc_pages()['card'] ?? 0 ) ) {
		}
	}
}
