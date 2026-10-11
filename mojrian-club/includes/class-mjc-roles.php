<?php
/**
 * نقش‌ها و دسترسی‌های افزونه.
 *
 * @package mojrian-club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * کلاس نقش‌ها.
 */
class MJC_Roles {

	const MEMBER   = 'mjc_member';
	const MODER    = 'mjc_moderator';
	const MANAGER  = 'mjc_manager';

	/**
	 * دسترسی‌های مدیر باشگاه.
	 *
	 * @return array
	 */
	public static function manager_caps() {
		return array(
			'mjc_manage_club',
			'mjc_manage_members',
			'mjc_manage_projects',
			'mjc_manage_points',
			'mjc_manage_grades',
			'mjc_manage_rewards',
			'mjc_manage_wallet',
			'mjc_manage_content',
			'mjc_manage_surveys',
			'mjc_manage_campaigns',
			'mjc_manage_tickets',
			'mjc_view_reports',
			'mjc_manage_settings',
			'read',
			'upload_files',
		);
	}

	/**
	 * دسترسی‌های ناظر.
	 *
	 * @return array
	 */
	public static function moderator_caps() {
		return array(
			'mjc_manage_members',
			'mjc_manage_projects',
			'mjc_manage_points',
			'mjc_manage_grades',
			'mjc_manage_rewards',
			'mjc_manage_surveys',
			'mjc_manage_tickets',
			'read',
			'upload_files',
		);
	}

	/**
	 * افزودن نقش‌ها.
	 */
	public static function install() {
		add_role( self::MEMBER, 'مجری عضو باشگاه', array( 'read' => true ) );
		add_role( self::MODER, 'ناظر باشگاه', array_fill_keys( self::moderator_caps(), true ) );
		add_role( self::MANAGER, 'مدیر باشگاه', array_fill_keys( self::manager_caps(), true ) );

		$admin = get_role( 'administrator' );
		if ( $admin ) {
			foreach ( self::manager_caps() as $cap ) {
				$admin->add_cap( $cap );
			}
		}
	}

	/**
	 * حذف نقش‌ها هنگام حذف کامل افزونه.
	 */
	public static function uninstall() {
		remove_role( self::MEMBER );
		remove_role( self::MODER );
		remove_role( self::MANAGER );
		$admin = get_role( 'administrator' );
		if ( $admin ) {
			foreach ( self::manager_caps() as $cap ) {
				$admin->remove_cap( $cap );
			}
		}
	}
}
