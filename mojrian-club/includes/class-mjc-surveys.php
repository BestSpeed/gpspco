<?php
/**
 * ماژول نظرسنجی‌ها.
 *
 * @package mojrian-club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * کلاس نظرسنجی.
 */
class MJC_Surveys {

	/**
	 * هوک‌ها.
	 */
	public static function hooks() {
		add_action( 'init', array( __CLASS__, 'register_cpt' ) );
	}

	/**
	 * ثبت نوع محتوای نظرسنجی.
	 */
	public static function register_cpt() {
		register_post_type( 'mjc_survey', array(
			'labels'       => array(
				'name'          => 'نظرسنجی‌ها',
				'singular_name' => 'نظرسنجی',
				'add_new_item'  => 'افزودن نظرسنجی جدید',
				'edit_item'     => 'ویرایش نظرسنجی',
			),
			'public'       => false,
			'show_ui'      => true,
			'show_in_menu' => 'mjc-dashboard',
			'menu_icon'    => 'dashicons-editor-alignleft',
			'supports'     => array( 'title', 'editor' ),
		) );
	}

	/**
	 * پرسش‌های نظرسنجی (ذخیره‌شده به‌صورت JSON در متا).
	 *
	 * @param int $survey_id شناسه.
	 * @return array
	 */
	public static function questions( $survey_id ) {
		$raw = get_post_meta( (int) $survey_id, 'mjc_questions', true );
		if ( ! $raw ) {
			return array();
		}
		$questions = json_decode( (string) $raw, true );
		return is_array( $questions ) ? $questions : array();
	}

	/**
	 * ثبت پاسخ کاربر.
	 *
	 * @param int   $user_id شناسه کاربر.
	 * @param int   $survey_id شناسه نظرسنجی.
	 * @param array $answers پاسخ‌ها.
	 * @return int|WP_Error
	 */
	public static function submit( $user_id, $survey_id, $answers ) {
		global $wpdb;
		$survey = get_post( (int) $survey_id );
		if ( ! $survey || 'mjc_survey' !== $survey->post_type ) {
			return new WP_Error( 'mjc_notfound', 'نظرسنجی یافت نشد.' );
		}
		$exists = $wpdb->get_row( $wpdb->prepare( 'SELECT id FROM ' . mjc_table( 'survey_responses' ) . ' WHERE survey_id = %d AND user_id = %d', (int) $survey_id, (int) $user_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( $exists ) {
			return new WP_Error( 'mjc_exists', 'قبلاً در این نظرسنجی شرکت کرده‌اید.' );
		}

		$clean = array();
		foreach ( (array) $answers as $key => $value ) {
			$clean[ sanitize_key( $key ) ] = sanitize_text_field( $value );
		}

		$id = MJC_DB::insert( 'survey_responses', array(
			'survey_id'   => (int) $survey_id,
			'user_id'     => (int) $user_id,
			'answers'     => wp_json_encode( $clean, JSON_UNESCAPED_UNICODE ),
			'created_at'  => current_time( 'mysql' ),
		) );
		if ( $id ) {
			MJC_Points::award_by_rule( (int) $user_id, 'survey_participation', 'شرکت در نظرسنجی «' . $survey->post_title . '»', 'survey', (int) $survey_id );
		}
		return $id;
	}

	/**
	 * آیا کاربر شرکت کرده است؟
	 *
	 * @param int $user_id شناسه کاربر.
	 * @param int $survey_id شناسه نظرسنجی.
	 * @return bool
	 */
	public static function has_participated( $user_id, $survey_id ) {
		return (bool) MJC_DB::var( 'SELECT id FROM ' . mjc_table( 'survey_responses' ) . ' WHERE survey_id = %d AND user_id = %d', array( (int) $survey_id, (int) $user_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * شمارش پاسخ‌ها.
	 *
	 * @param int $survey_id شناسه.
	 * @return int
	 */
	public static function response_count( $survey_id ) {
		return (int) MJC_DB::var( 'SELECT COUNT(*) FROM ' . mjc_table( 'survey_responses' ) . ' WHERE survey_id = %d', array( (int) $survey_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}
}
