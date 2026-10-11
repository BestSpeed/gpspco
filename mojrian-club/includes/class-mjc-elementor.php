<?php
/**
 * یکپارچه‌سازی با المنتور (Elementor Widget).
 *
 * ویجت «حساب کاربری باشگاه مجریان»: در صفحه‌ی المنتور قرار می‌گیرد و
 * به مهمان فرم ورود/ثبت‌نام و به عضو واردشده، حساب کاربری باشگاه را نمایش می‌دهد.
 *
 * @package mojrian-club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * کلاس اتصال به المنتور.
 */
class MJC_Elementor {

	/**
	 * هوک‌ها (ایمن بدون نصب المنتور).
	 */
	public static function hooks() {
		add_action( 'elementor/elements/categories_registered', array( __CLASS__, 'register_category' ) );
		add_action( 'elementor/widgets/register', array( __CLASS__, 'register_widgets' ) );
		// سازگاری با نسخه‌های قدیمی‌تر المنتور.
		add_action( 'elementor/widgets/widgets_registered', array( __CLASS__, 'register_widgets_legacy' ) );
	}

	/**
	 * دسته‌ی اختصاصی ویجت‌ها در المنتور.
	 *
	 * @param object $elements_manager مدیر المان‌ها.
	 */
	public static function register_category( $elements_manager ) {
		if ( ! is_object( $elements_manager ) || ! method_exists( $elements_manager, 'add_category' ) ) {
			return;
		}
		$elements_manager->add_category(
			'mojrian-club',
			array(
				'title' => 'باشگاه مجریان',
				'icon'  => 'eicon-user-circle',
			)
		);
	}

	/**
	 * ثبت ویجت‌ها (المنتور ۳.۵+).
	 *
	 * @param object $widgets_manager مدیر ویجت‌ها.
	 */
	public static function register_widgets( $widgets_manager ) {
		if ( ! is_object( $widgets_manager ) || ! class_exists( '\Elementor\Widget_Base' ) ) {
			return;
		}
		require_once MJC_PLUGIN_DIR . 'includes/elementor/class-mjc-widget-account.php';
		if ( method_exists( $widgets_manager, 'register' ) ) {
			$widgets_manager->register( new \MJC_Elementor_Widget_Account() );
		} elseif ( method_exists( $widgets_manager, 'register_widget_type' ) ) {
			$widgets_manager->register_widget_type( new \MJC_Elementor_Widget_Account() );
		}
	}

	/**
	 * ثبت ویجت‌ها (نسخه‌های قدیمی).
	 *
	 * @param object $widgets_manager مدیر ویجت‌ها.
	 */
	public static function register_widgets_legacy( $widgets_manager ) {
		if ( ! is_object( $widgets_manager ) || ! class_exists( '\Elementor\Widget_Base' ) ) {
			return;
		}
		require_once MJC_PLUGIN_DIR . 'includes/elementor/class-mjc-widget-account.php';
		if ( method_exists( $widgets_manager, 'register_widget_type' ) ) {
			$widgets_manager->register_widget_type( new \MJC_Elementor_Widget_Account() );
		} elseif ( method_exists( $widgets_manager, 'register' ) ) {
			$widgets_manager->register( new \MJC_Elementor_Widget_Account() );
		}
	}
}
