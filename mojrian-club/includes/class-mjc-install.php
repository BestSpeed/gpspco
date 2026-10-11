<?php
/**
 * فعال‌سازی/غیرفعال‌سازی و ساخت صفحات.
 *
 * @package mojrian-club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * کلاس نصب.
 */
class MJC_Install {

	/**
	 * هوک‌ها (بارگذاری مجدد مپینگ صفحات).
	 */
	public static function hooks() {}

	/**
	 * اجرای فعال‌سازی.
	 */
	public static function activate() {
		MJC_DB::install();
		MJC_Roles::install();

		// تنظیمات پیش‌فرض.
		$settings = get_option( 'mjc_settings', array() );
		if ( ! is_array( $settings ) || empty( $settings ) ) {
			update_option( 'mjc_settings', MJC_Settings::defaults() );
		} else {
			// تکمیل کلیدهای جدید در به‌روزرسانی‌ها.
			update_option( 'mjc_settings', array_merge( MJC_Settings::defaults(), $settings ) );
		}

		self::create_pages();
		MJC_Guide::seed();

		flush_rewrite_rules();
	}

	/**
	 * غیرفعال‌سازی.
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}

	/**
	 * ساخت خودکار صفحات با شورت‌کدها.
	 */
	public static function create_pages() {
		$pages = array(
			'home'          => array( 'صفحه اصلی باشگاه', '[mojrian_home]' ),
			'login'         => array( 'ورود', '[mojrian_login]' ),
			'register'      => array( 'ثبت‌نام', '[mojrian_register]' ),
			'dashboard'     => array( 'داشبورد من', '[mojrian_dashboard]' ),
			'profile'       => array( 'پروفایل من', '[mojrian_profile]' ),
			'projects'      => array( 'پروژه‌های من', '[mojrian_projects]' ),
			'points'        => array( 'تاریخچه امتیاز', '[mojrian_points]' ),
			'rewards'       => array( 'فروشگاه امتیاز', '[mojrian_rewards]' ),
			'courses'       => array( 'دوره‌های آموزشی', '[mojrian_courses]' ),
			'knowledge'     => array( 'بانک دانش', '[mojrian_knowledge]' ),
			'surveys'       => array( 'نظرسنجی‌ها', '[mojrian_surveys]' ),
			'campaigns'     => array( 'کمپین‌ها و قرعه‌کشی', '[mojrian_campaigns]' ),
			'referral'      => array( 'معرفی دوستان', '[mojrian_referral]' ),
			'wallet'        => array( 'کیف پول', '[mojrian_wallet]' ),
			'card'          => array( 'کارت عضویت', '[mojrian_card]' ),
			'notifications' => array( 'اعلان‌ها', '[mojrian_notifications]' ),
			'guide'         => array( 'راهنمای باشگاه', '[mojrian_guide]' ),
			'faq'           => array( 'سوالات متداول', '[mojrian_faq]' ),
			'inquiry'       => array( 'استعلام فنی مجریان', '[mojrian_inquiry]' ),
			'contact'       => array( 'ارتباط با ما', '[mojrian_contact]' ),
		);

		$map = mjc_pages();
		foreach ( $pages as $key => $def ) {
			if ( ! empty( $map[ $key ] ) && get_post( $map[ $key ] ) ) {
				continue;
			}
			$existing = get_page_by_path( sanitize_title( $def[0] ) );
			if ( $existing ) {
				$map[ $key ] = $existing->ID;
				continue;
			}
			$page_id = wp_insert_post( array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $def[0],
				'post_content' => $def[1],
			) );
			if ( $page_id && ! is_wp_error( $page_id ) ) {
				$map[ $key ] = (int) $page_id;
			}
		}
		mjc_update_settings( array( 'pages' => $map ) );
	}
}
