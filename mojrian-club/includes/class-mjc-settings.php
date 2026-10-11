<?php
/**
 * تنظیمات افزونه (API تنظیمات وردپرس).
 *
 * @package mojrian-club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * کلاس تنظیمات.
 */
class MJC_Settings {

	/**
	 * هوک‌ها.
	 */
	public static function hooks() {
		add_filter( 'plugin_action_links_' . MJC_PLUGIN_BASENAME, array( __CLASS__, 'action_links' ) );
	}

	/**
	 * لینک‌های سریع در فهرست افزونه‌ها.
	 *
	 * @param array $links لینک‌ها.
	 * @return array
	 */
	public static function action_links( $links ) {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'admin.php?page=mjc-settings' ) ) . '">' . esc_html__( 'تنظیمات باشگاه', 'mojrian-club' ) . '</a>' );
		return $links;
	}

	/**
	 * مقادیر پیش‌فرض تنظیمات.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'club_title'          => 'باشگاه مجریان',
			'points_label'        => 'امتیاز',
			'primary_color'       => '#0e7490',
			'sms_gateway'         => 'log',
			'sms_api_key'         => '',
			'sms_sender'          => '',
			'otp_length'          => 5,
			'otp_ttl'             => 120,
			'otp_max_per_hour'    => 5,
			'registration_approval' => 'manual',
			'wallet_withdraw_min' => 1000000,
			'project_points_mode' => 'fixed',
			'points_per_meter'    => 2,
			'points_per_10m'      => 10,
			'project_points_max'  => 0,
			'dashboard_banner_id' => 0,
			'manager_message'     => '',
			'points_rules'        => self::default_rules(),
			'pages'               => array(),
		);
	}

	/**
	 * قوانین پیش‌فرض امتیازدهی.
	 *
	 * @return array
	 */
	public static function default_rules() {
		return array(
			'welcome'            => array( 'label' => 'خوش‌آمدگویی عضویت', 'points' => 100, 'enabled' => 1 ),
			'profile_complete'   => array( 'label' => 'تکمیل پروفایل', 'points' => 50, 'enabled' => 1 ),
			'project_approved'   => array( 'label' => 'تأیید هر پروژه', 'points' => 200, 'enabled' => 1 ),
			'survey_participation' => array( 'label' => 'شرکت در نظرسنجی', 'points' => 20, 'enabled' => 1 ),
			'course_completed'   => array( 'label' => 'تکمیل دوره آموزشی', 'points' => 150, 'enabled' => 1 ),
			'campaign_join'      => array( 'label' => 'شرکت در کمپین', 'points' => 10, 'enabled' => 1 ),
			'referral_joined'    => array( 'label' => 'معرفی عضو جدید (پس از تأیید)', 'points' => 300, 'enabled' => 1 ),
		);
	}

	/**
	 * مقدار یک قانون امتیاز.
	 *
	 * @param string $key کلید قانون.
	 * @return array ['points' => int, 'enabled' => bool]
	 */
	public static function rule( $key ) {
		$rules = mjc_get_setting( 'points_rules', self::default_rules() );
		if ( ! is_array( $rules ) || empty( $rules[ $key ] ) ) {
			$defaults = self::default_rules();
			$rules    = isset( $defaults[ $key ] ) ? $defaults : array( 'points' => 0, 'enabled' => 0 );
		}
		return array(
			'points'  => isset( $rules[ $key ]['points'] ) ? (int) $rules[ $key ]['points'] : 0,
			'enabled' => ! empty( $rules[ $key ]['enabled'] ),
		);
	}

	/**
	 * ذخیره‌ی تنظیمات از فرم ادمین.
	 */
	public static function save_from_request() {
		if ( empty( $_POST['mjc_settings_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['mjc_settings_nonce'] ), 'mjc_save_settings' ) ) {
			return;
		}
		if ( ! current_user_can( 'mjc_manage_settings' ) ) {
			return;
		}

		// بنر داشبورد اعضا (اختیاری).
		if ( ! empty( $_FILES['dashboard_banner']['name'] ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
			$att_id = media_handle_upload( 'dashboard_banner', 0 ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			if ( ! is_wp_error( $att_id ) ) {
				$opts = array_merge( (array) get_option( 'mjc_settings', array() ), array( 'dashboard_banner_id' => (int) $att_id ) );
				update_option( 'mjc_settings', $opts );
			}
		}

		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$raw_rules = isset( $_POST['rules'] ) ? wp_unslash( $_POST['rules'] ) : array();
		// phpcs:enable
		$rules = array();
		foreach ( self::default_rules() as $key => $def ) {
			$rules[ $key ] = array(
				'label'   => $def['label'],
				'points'  => isset( $raw_rules[ $key ]['points'] ) ? (int) $raw_rules[ $key ]['points'] : 0,
				'enabled' => isset( $raw_rules[ $key ]['enabled'] ) ? 1 : 0,
			);
		}

		$data = array(
			'club_title'            => isset( $_POST['club_title'] ) ? sanitize_text_field( wp_unslash( $_POST['club_title'] ) ) : 'باشگاه مجریان',
			'points_label'          => isset( $_POST['points_label'] ) ? sanitize_text_field( wp_unslash( $_POST['points_label'] ) ) : 'امتیاز',
			'primary_color'         => isset( $_POST['primary_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['primary_color'] ) ) : '#0e7490',
			'sms_gateway'           => isset( $_POST['sms_gateway'] ) ? sanitize_key( wp_unslash( $_POST['sms_gateway'] ) ) : 'log',
			'sms_api_key'           => isset( $_POST['sms_api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['sms_api_key'] ) ) : '',
			'sms_sender'            => isset( $_POST['sms_sender'] ) ? sanitize_text_field( wp_unslash( $_POST['sms_sender'] ) ) : '',
			'otp_length'            => isset( $_POST['otp_length'] ) ? max( 4, min( 8, (int) $_POST['otp_length'] ) ) : 5,
			'otp_ttl'               => isset( $_POST['otp_ttl'] ) ? max( 30, (int) $_POST['otp_ttl'] ) : 120,
			'otp_max_per_hour'      => isset( $_POST['otp_max_per_hour'] ) ? max( 1, (int) $_POST['otp_max_per_hour'] ) : 5,
			'registration_approval' => ( isset( $_POST['registration_approval'] ) && 'auto' === $_POST['registration_approval'] ) ? 'auto' : 'manual',
			'wallet_withdraw_min'   => isset( $_POST['wallet_withdraw_min'] ) ? (int) $_POST['wallet_withdraw_min'] : 1000000,
			'project_points_mode'   => isset( $_POST['project_points_mode'] ) && in_array( $_POST['project_points_mode'], array( 'fixed', 'area', 'amount' ), true ) ? sanitize_key( wp_unslash( $_POST['project_points_mode'] ) ) : 'fixed',
			'points_per_meter'      => isset( $_POST['points_per_meter'] ) ? max( 0, (float) $_POST['points_per_meter'] ) : 2,
			'points_per_10m'        => isset( $_POST['points_per_10m'] ) ? max( 0, (float) $_POST['points_per_10m'] ) : 10,
			'project_points_max'    => isset( $_POST['project_points_max'] ) ? max( 0, (int) $_POST['project_points_max'] ) : 0,
			'manager_message'       => isset( $_POST['manager_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['manager_message'] ) ) : '',
			'points_rules'          => $rules,
		);
		$color = $data['primary_color'];
		if ( ! $color ) {
			$data['primary_color'] = '#0e7490';
		}
		mjc_update_settings( $data );
	}
}
