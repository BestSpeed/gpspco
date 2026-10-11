<?php
/**
 * حذف کامل افزونه هنگام Uninstall.
 *
 * با حذف افزونه از «افزونه‌ها»، تمام تغییراتی که افزونه روی سایت ایجاد کرده
 * (صفحات ساخته‌شده، مطالب انواع اختصاصی، جداول دیتابیس، گزینه‌ها، متای کاربران،
 * رسانه‌های بارگذاری‌شده، نقش‌ها و دسترسی‌ها) به‌صورت کامل پاک می‌شود.
 *
 * @package mojrian-club
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

if ( ! function_exists( 'mjc_uninstall_delete_posts' ) ) {
	/**
	 * حذف قطعی نوشته‌ها/پیوست‌ها.
	 *
	 * @param array $ids شناسه‌ها.
	 */
	function mjc_uninstall_delete_posts( $ids ) {
		foreach ( array_unique( array_filter( array_map( 'intval', (array) $ids ) ) ) as $id ) {
			wp_delete_attachment( $id, true );
			wp_delete_post( $id, true );
		}
	}
}

// ۱) جمع‌آوری رسانه‌های افزونه (بنر، تصاویر/فیلم پروژه‌ها، آواتارها، تصاویر شاخص).
$attachments = array();
$settings    = (array) get_option( 'mjc_settings', array() );
if ( ! empty( $settings['dashboard_banner_id'] ) ) {
	$attachments[] = (int) $settings['dashboard_banner_id'];
}
// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
$projects = $wpdb->get_results( "SELECT images, film_id FROM {$wpdb->prefix}mjc_projects" );
foreach ( (array) $projects as $p ) {
	foreach ( (array) json_decode( (string) $p->images, true ) as $img ) {
		$attachments[] = (int) $img;
	}
	if ( ! empty( $p->film_id ) ) {
		$attachments[] = (int) $p->film_id;
	}
}
$avatars = $wpdb->get_col( "SELECT avatar_id FROM {$wpdb->prefix}mjc_members WHERE avatar_id > 0" );
$attachments = array_merge( $attachments, array_map( 'intval', (array) $avatars ) );
$thumbs = $wpdb->get_col(
	"SELECT pm.meta_value FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
	 WHERE p.post_type LIKE 'mjc_%' AND pm.meta_key = '_thumbnail_id'"
);
$attachments = array_merge( $attachments, array_map( 'intval', (array) $thumbs ) );

// ۲) حذف مطالب انواع اختصاصی افزونه (mjc_reward، mjc_campaign، mjc_course، mjc_guide، mjc_faq، mjc_knowledge، mjc_survey).
$cpt_posts = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type LIKE 'mjc_%'" );
mjc_uninstall_delete_posts( $cpt_posts );

// ۳) حذف رسانه‌های جمع‌آوری‌شده.
mjc_uninstall_delete_posts( $attachments );

// ۴) حذف صفحات ساخته‌شده توسط افزونه (نگاشت ذخیره‌شده + هر صفحه‌ای با شورت‌کد افزونه).
mjc_uninstall_delete_posts( isset( $settings['pages'] ) ? (array) $settings['pages'] : array() );
$shortcode_pages = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'page' AND post_content LIKE '%[mojrian_%'" );
mjc_uninstall_delete_posts( $shortcode_pages );

// ۵) حذف همه‌ی جداول افزونه.
$tables = $wpdb->get_col( "SHOW TABLES LIKE '{$wpdb->prefix}mjc_%'" );
foreach ( (array) $tables as $table ) {
	$wpdb->query( 'DROP TABLE IF EXISTS `' . esc_sql( $table ) . '`' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
}

// ۶) حذف همه‌ی گزینه‌ها و متای کاربران با پیشوند mjc_.
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'mjc_%'" );
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_mjc\_%' OR option_name LIKE '\_transient\_timeout\_mjc\_%'" );
$wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'mjc\_%'" );
// phpcs:enable

// ۷) نقش‌ها، دسترسی‌ها و بازگرداندن کاربران.
require_once plugin_dir_path( __FILE__ ) . 'includes/class-mjc-roles.php';
MJC_Roles::uninstall();
