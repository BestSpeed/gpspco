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
		// بازگرداندن کاربرانِ دارای نقش باشگاه به نقش پیش‌فرض.
		$roles = array( self::MEMBER, self::MODER, self::MANAGER );
		$users = get_users( array( 'role__in' => $roles, 'fields' => 'ID' ) );
		foreach ( (array) $users as $uid ) {
			$user = get_userdata( (int) $uid );
			if ( ! $user ) {
				continue;
			}
			foreach ( $roles as $r ) {
				$user->remove_role( $r );
			}
			if ( ! array_filter( (array) $user->roles ) ) {
				$user->set_role( 'subscriber' );
			}
		}

		// حذف همه‌ی دسترسی‌های mjc_ از نقش مدیر.
		$admin = get_role( 'administrator' );
		if ( $admin ) {
			foreach ( array_keys( (array) $admin->capabilities ) as $cap ) {
				if ( 0 === strpos( (string) $cap, 'mjc_' ) ) {
					$admin->remove_cap( $cap );
				}
			}
		}

		remove_role( self::MEMBER );
		remove_role( self::MODER );
		remove_role( self::MANAGER );
	}
}
