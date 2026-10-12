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
	 * هوک‌ها: ارتقای خودکار دیتابیس هنگام به‌روزرسانی افزونه.
	 */
	public static function hooks() {
		add_action( 'admin_init', array( __CLASS__, 'maybe_upgrade_db' ) );
	}

	/**
	 * اجرای مجدد اسکیما وقتی نسخه‌ی افزونه تغییر کرده است (افزودن ستون‌های جدید و...).
	 */
	public static function maybe_upgrade_db() {
		$db_version = get_option( 'mjc_db_version', '0' );
		if ( version_compare( $db_version, MJC_VERSION, '<' ) ) {
			MJC_DB::install();
			self::create_pages();
			MJC_Rewards::seed_default_rewards();
			MJC_Guide::seed();
			update_option( 'mjc_db_version', MJC_VERSION );
		}
	}

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
		MJC_Rewards::seed_default_rewards();
		MJC_Guide::seed();
		update_option( 'mjc_db_version', MJC_VERSION );

		flush_rewrite_rules();
	}

	/**
	 * بازسازی قواعد بازنویسی بعد از ثبت همه‌ی نوع‌های محتوا.
	 *
	 * روی 'init' با اولویت ۲۰ اجرا می‌شود — بعد از register_cpt همه‌ی پست‌تایپ‌های
	 * افزونه (mjc_reward / mjc_campaign / mjc_guide / mjc_faq) که روی اولویت ۱۰ ثبت
	 * شده‌اند؛ flush هنگام فعال‌سازی (قبل از ثبت CPTها) قواعد را خالی می‌ساخت و
	 * لینک‌های کمپین و راهنمای باشگاه ۴۰۴ می‌شدند.
	 */
	public static function maybe_flush() {
		if ( get_option( 'mjc_rewrite_flush' ) !== MJC_VERSION ) {
			flush_rewrite_rules( false );
			update_option( 'mjc_rewrite_flush', MJC_VERSION );
		}
	}

	public static function deactivate() {
		flush_rewrite_rules();
	}

	/**
	 * ساخت خودکار صفحات با شورت‌کدها.
	 */
	public static function create_pages() {
		$pages = array(
			'account'       => array( 'حساب کاربری باشگاه', '[mojrian_account]' ),
			'home'          => array( 'صفحه اصلی باشگاه', '[mojrian_home]' ),
			'login'         => array( 'ورود', '[mojrian_login]' ),
			'register'      => array( 'ثبت‌نام', '[mojrian_register]' ),
			'dashboard'     => array( 'داشبورد', '[mojrian_dashboard]' ),
			'profile'       => array( 'تکمیل اطلاعات کاربر', '[mojrian_profile]' ),
			'projects'      => array( 'ثبت پروژه تاسیسات', '[mojrian_projects]' ),
			'points'        => array( 'تاریخچه امتیاز', '[mojrian_points]' ),
			'rewards'       => array( 'تبدیل امتیازات به جوایز', '[mojrian_rewards]' ),
			'campaigns'     => array( 'کمپین‌ها و قرعه‌کشی', '[mojrian_campaigns]' ),
			'notifications' => array( 'اعلانات', '[mojrian_notifications]' ),
			'referral'      => array( 'معرفی دوستان', '[mojrian_referral]' ),
			'guide'         => array( 'راهنمای باشگاه', '[mojrian_guide]' ),
			'faq'           => array( 'سوالات متداول', '[mojrian_faq]' ),
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
