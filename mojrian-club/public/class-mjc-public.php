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
