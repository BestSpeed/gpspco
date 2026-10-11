<?php
/**
 * ماژول دوره‌های آموزشی.
 *
 * @package mojrian-club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * کلاس دوره‌ها.
 */
class MJC_Courses {

	/**
	 * هوک‌ها.
	 */
	public static function hooks() {
		add_action( 'init', array( __CLASS__, 'register_cpt' ) );
	}

	/**
	 * ثبت نوع محتوای دوره.
	 */
	public static function register_cpt() {
		register_post_type( 'mjc_course', array(
			'labels'       => array(
				'name'          => 'دوره‌های آموزشی',
				'singular_name' => 'دوره',
				'add_new_item'  => 'افزودن دوره جدید',
				'edit_item'     => 'ویرایش دوره',
			),
			'public'       => false,
			'show_ui'      => true,
			'show_in_menu' => 'mjc-dashboard',
			'menu_icon'    => 'dashicons-welcome-learn-more',
			'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
			'capability_type' => 'post',
		) );
	}

	/**
	 * ثبت‌نام در دوره.
	 *
	 * @param int $user_id شناسه کاربر.
	 * @param int $course_id شناسه دوره.
	 * @return int|WP_Error
	 */
	public static function enroll( $user_id, $course_id ) {
		global $wpdb;
		$course = get_post( (int) $course_id );
		if ( ! $course || 'mjc_course' !== $course->post_type ) {
			return new WP_Error( 'mjc_notfound', 'دوره یافت نشد.' );
		}
		$exists = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . mjc_table( 'enrollments' ) . ' WHERE user_id = %d AND course_id = %d', (int) $user_id, (int) $course_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( $exists ) {
			return new WP_Error( 'mjc_exists', 'قبلاً در این دوره ثبت‌نام کرده‌اید.' );
		}
		$id = MJC_DB::insert( 'enrollments', array(
			'user_id'    => (int) $user_id,
			'course_id'  => (int) $course_id,
			'status'     => 'active',
			'created_at' => current_time( 'mysql' ),
		) );
		if ( $id ) {
			MJC_Notify::push( (int) $user_id, 'ثبت‌نام دوره', 'در دوره‌ی «' . $course->post_title . '» ثبت‌نام کردید.', mjc_page_url( 'courses' ) );
		}
		return $id;
	}

	/**
	 * تکمیل دوره (ادمین/مدرس).
	 *
	 * @param int $user_id شناسه کاربر.
	 * @param int $course_id شناسه دوره.
	 * @return bool
	 */
	public static function complete( $user_id, $course_id ) {
		MJC_DB::update( 'enrollments', array(
			'status'   => 'completed',
			'progress' => 100,
		), array( 'user_id' => (int) $user_id, 'course_id' => (int) $course_id ) );
		MJC_Points::award_by_rule( (int) $user_id, 'course_completed', 'تکمیل دوره‌ی «' . get_the_title( (int) $course_id ) . '»', 'course', (int) $course_id );
		MJC_Audit::log( 'course_completed', 'course', (int) $course_id, 'user ' . (int) $user_id );
		return true;
	}

	/**
	 * دوره‌های ثبت‌نام‌شده‌ی کاربر.
	 *
	 * @param int $user_id شناسه کاربر.
	 * @return array
	 */
	public static function enrollments( $user_id ) {
		return MJC_DB::results(
			'SELECT * FROM ' . mjc_table( 'enrollments' ) . ' WHERE user_id = %d ORDER BY id DESC', // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			array( (int) $user_id )
		);
	}
}
