<?php
/**
 * Plugin Name:       باشگاه مجریان | Mojrian Club
 * Plugin URI:        https://github.com/BestSpeed/gpspco
 * Description:       افزونه جامع باشگاه مجریان؛ عضویت و تأیید مجریان، ثبت پروژه، موتور امتیاز (گیتا)، پایه فنی و استعلام، دوره‌های آموزشی، بانک دانش، فروشگاه امتیاز، نظرسنجی، کمپین و قرعه‌کشی، کیف پول، کارت عضویت، راهنمای باشگاه و پنل مدیریت کامل — فارسی و راست‌به‌چپ.
 * Version:           1.1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            گروه صنایع گیتی پسند — تیم توسعه باشگاه مجریان
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       mojrian-club
 * Domain Path:       /languages
 */

// جلوگیری از دسترسی مستقیم
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MJC_VERSION', '1.1.0' );
define( 'MJC_PLUGIN_FILE', __FILE__ );
define( 'MJC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MJC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MJC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/*
 * بارگذاری فایل‌ها
 */
require_once MJC_PLUGIN_DIR . 'includes/helpers.php';
require_once MJC_PLUGIN_DIR . 'includes/class-mjc-jalali.php';
require_once MJC_PLUGIN_DIR . 'includes/class-mjc-db.php';
require_once MJC_PLUGIN_DIR . 'includes/class-mjc-roles.php';
require_once MJC_PLUGIN_DIR . 'includes/class-mjc-settings.php';
require_once MJC_PLUGIN_DIR . 'includes/class-mjc-sms.php';
require_once MJC_PLUGIN_DIR . 'includes/class-mjc-audit.php';
require_once MJC_PLUGIN_DIR . 'includes/class-mjc-members.php';
require_once MJC_PLUGIN_DIR . 'includes/class-mjc-points.php';
require_once MJC_PLUGIN_DIR . 'includes/class-mjc-notifications.php';
require_once MJC_PLUGIN_DIR . 'includes/class-mjc-auth.php';
require_once MJC_PLUGIN_DIR . 'includes/class-mjc-projects.php';
require_once MJC_PLUGIN_DIR . 'includes/class-mjc-grades.php';
require_once MJC_PLUGIN_DIR . 'includes/class-mjc-rewards.php';
require_once MJC_PLUGIN_DIR . 'includes/class-mjc-wallet.php';
require_once MJC_PLUGIN_DIR . 'includes/class-mjc-courses.php';
require_once MJC_PLUGIN_DIR . 'includes/class-mjc-knowledge.php';
require_once MJC_PLUGIN_DIR . 'includes/class-mjc-surveys.php';
require_once MJC_PLUGIN_DIR . 'includes/class-mjc-campaigns.php';
require_once MJC_PLUGIN_DIR . 'includes/class-mjc-referrals.php';
require_once MJC_PLUGIN_DIR . 'includes/class-mjc-tickets.php';
require_once MJC_PLUGIN_DIR . 'includes/class-mjc-guide.php';
require_once MJC_PLUGIN_DIR . 'includes/class-mjc-forms.php';
require_once MJC_PLUGIN_DIR . 'includes/class-mjc-shortcodes.php';
require_once MJC_PLUGIN_DIR . 'includes/class-mjc-elementor.php';
require_once MJC_PLUGIN_DIR . 'includes/class-mjc-install.php';

if ( is_admin() ) {
	require_once MJC_PLUGIN_DIR . 'admin/class-mjc-list-table.php';
	require_once MJC_PLUGIN_DIR . 'admin/class-mjc-metaboxes.php';
	require_once MJC_PLUGIN_DIR . 'admin/class-mjc-admin.php';
}

require_once MJC_PLUGIN_DIR . 'public/class-mjc-public.php';

/**
 * اجرای افزونه
 */
function mjc_run() {
	load_plugin_textdomain( 'mojrian-club', false, dirname( MJC_PLUGIN_BASENAME ) . '/languages' );

	MJC_Install::hooks();
	MJC_Settings::hooks();
	MJC_Auth::hooks();
	MJC_Forms::hooks();
	MJC_Shortcodes::hooks();
	MJC_Public::hooks();
	MJC_Points::hooks();
	MJC_Projects::hooks();
	MJC_Grades::hooks();
	MJC_Rewards::hooks();
	MJC_Wallet::hooks();
	MJC_Courses::hooks();
	MJC_Knowledge::hooks();
	MJC_Surveys::hooks();
	MJC_Campaigns::hooks();
	MJC_Referrals::hooks();
	MJC_Tickets::hooks();
	MJC_Guide::hooks();
	MJC_Members::hooks();
	MJC_Elementor::hooks();

	if ( is_admin() ) {
		MJC_Metaboxes::hooks();
		MJC_Admin::hooks();
	}
}
add_action( 'plugins_loaded', 'mjc_run' );

/*
 * هوک‌های فعال‌سازی/غیرفعال‌سازی
 */
register_activation_hook( __FILE__, array( 'MJC_Install', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'MJC_Install', 'deactivate' ) );
