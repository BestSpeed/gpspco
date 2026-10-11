<?php
/**
 * لایه‌ی دیتابیس: ساخت جداول و کوئری‌های مشترک.
 *
 * @package mojrian-club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * کلاس دیتابیس.
 */
class MJC_DB {

	/**
	 * ساخت همه‌ی جداول افزونه.
	 */
	public static function install() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();
		$p       = $wpdb->prefix . 'mjc_';

		$sqls = array();

		$sqls[] = "CREATE TABLE {$p}members (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED NOT NULL,
			mobile VARCHAR(20) NOT NULL DEFAULT '',
			national_code VARCHAR(20) NOT NULL DEFAULT '',
			full_name VARCHAR(120) NOT NULL DEFAULT '',
			province VARCHAR(80) NOT NULL DEFAULT '',
			city VARCHAR(80) NOT NULL DEFAULT '',
			job_type VARCHAR(80) NOT NULL DEFAULT '',
			experience TINYINT UNSIGNED NOT NULL DEFAULT 0,
			workshop_address VARCHAR(255) NOT NULL DEFAULT '',
			status VARCHAR(20) NOT NULL DEFAULT 'pending',
			reject_reason VARCHAR(255) NOT NULL DEFAULT '',
			points_balance BIGINT NOT NULL DEFAULT 0,
			wallet_balance BIGINT NOT NULL DEFAULT 0,
			grade VARCHAR(40) NOT NULL DEFAULT '',
			grade_certificate VARCHAR(40) NOT NULL DEFAULT '',
			referral_code VARCHAR(20) NOT NULL DEFAULT '',
			referred_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			updated_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			UNIQUE KEY user_id (user_id),
			KEY mobile (mobile),
			KEY status (status),
			KEY referral_code (referral_code)
		) $charset;";

		$sqls[] = "CREATE TABLE {$p}points_ledger (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED NOT NULL,
			amount INT NOT NULL DEFAULT 0,
			balance_after BIGINT NOT NULL DEFAULT 0,
			action VARCHAR(40) NOT NULL DEFAULT '',
			description VARCHAR(255) NOT NULL DEFAULT '',
			ref_type VARCHAR(20) NOT NULL DEFAULT '',
			ref_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			created_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY action (action),
			KEY created_at (created_at)
		) $charset;";

		$sqls[] = "CREATE TABLE {$p}projects (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED NOT NULL,
			title VARCHAR(200) NOT NULL DEFAULT '',
			project_type VARCHAR(60) NOT NULL DEFAULT '',
			province VARCHAR(80) NOT NULL DEFAULT '',
			city VARCHAR(80) NOT NULL DEFAULT '',
			units INT UNSIGNED NOT NULL DEFAULT 0,
			amount BIGINT UNSIGNED NOT NULL DEFAULT 0,
			area DECIMAL(12,2) NOT NULL DEFAULT 0,
			brand VARCHAR(120) NOT NULL DEFAULT '',
			start_date VARCHAR(20) NOT NULL DEFAULT '',
			description TEXT NULL,
			images LONGTEXT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'pending',
			reject_reason VARCHAR(255) NOT NULL DEFAULT '',
			points_awarded INT NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			updated_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY status (status),
			KEY created_at (created_at)
		) $charset;";

		$sqls[] = "CREATE TABLE {$p}grade_requests (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED NOT NULL,
			requested_level VARCHAR(40) NOT NULL DEFAULT '',
			status VARCHAR(20) NOT NULL DEFAULT 'pending',
			theory_score DECIMAL(5,2) NULL,
			practical_score DECIMAL(5,2) NULL,
			final_level VARCHAR(40) NOT NULL DEFAULT '',
			notes TEXT NULL,
			reviewed_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			updated_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY status (status)
		) $charset;";

		$sqls[] = "CREATE TABLE {$p}reward_requests (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED NOT NULL,
			reward_id BIGINT UNSIGNED NOT NULL,
			points INT NOT NULL DEFAULT 0,
			status VARCHAR(20) NOT NULL DEFAULT 'pending',
			reject_reason VARCHAR(255) NOT NULL DEFAULT '',
			shipping_address VARCHAR(255) NOT NULL DEFAULT '',
			created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			updated_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY reward_id (reward_id),
			KEY status (status)
		) $charset;";

		$sqls[] = "CREATE TABLE {$p}wallet_transactions (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED NOT NULL,
			amount BIGINT NOT NULL DEFAULT 0,
			balance_after BIGINT NOT NULL DEFAULT 0,
			type VARCHAR(20) NOT NULL DEFAULT 'credit',
			status VARCHAR(20) NOT NULL DEFAULT 'done',
			description VARCHAR(255) NOT NULL DEFAULT '',
			created_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY status (status)
		) $charset;";

		$sqls[] = "CREATE TABLE {$p}enrollments (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED NOT NULL,
			course_id BIGINT UNSIGNED NOT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'active',
			progress INT UNSIGNED NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			UNIQUE KEY user_course (user_id, course_id)
		) $charset;";

		$sqls[] = "CREATE TABLE {$p}survey_responses (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			survey_id BIGINT UNSIGNED NOT NULL,
			user_id BIGINT UNSIGNED NOT NULL,
			answers LONGTEXT NULL,
			created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			UNIQUE KEY survey_user (survey_id, user_id)
		) $charset;";

		$sqls[] = "CREATE TABLE {$p}lottery_entries (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			campaign_id BIGINT UNSIGNED NOT NULL,
			user_id BIGINT UNSIGNED NOT NULL,
			created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			UNIQUE KEY campaign_user (campaign_id, user_id)
		) $charset;";

		$sqls[] = "CREATE TABLE {$p}notifications (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED NOT NULL,
			title VARCHAR(200) NOT NULL DEFAULT '',
			body TEXT NULL,
			url VARCHAR(255) NOT NULL DEFAULT '',
			is_read TINYINT(1) NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY is_read (is_read)
		) $charset;";

		$sqls[] = "CREATE TABLE {$p}tickets (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED NOT NULL,
			subject VARCHAR(200) NOT NULL DEFAULT '',
			message TEXT NULL,
			attachment_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			status VARCHAR(20) NOT NULL DEFAULT 'open',
			admin_reply TEXT NULL,
			created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			updated_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY status (status)
		) $charset;";

		$sqls[] = "CREATE TABLE {$p}audit_log (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			actor_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			action VARCHAR(60) NOT NULL DEFAULT '',
			object_type VARCHAR(40) NOT NULL DEFAULT '',
			object_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			details TEXT NULL,
			created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY actor_id (actor_id),
			KEY action (action),
			KEY created_at (created_at)
		) $charset;";

		$sqls[] = "CREATE TABLE {$p}sms_log (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			mobile VARCHAR(20) NOT NULL DEFAULT '',
			message TEXT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'sent',
			created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY mobile (mobile)
		) $charset;";

		foreach ( $sqls as $sql ) {
			dbDelta( $sql );
		}
	}

	/**
	 * درج ردیف.
	 *
	 * @param string $table نام کوتاه جدول.
	 * @param array  $data  داده‌ها.
	 * @return int شناسه یا 0.
	 */
	public static function insert( $table, $data ) {
		global $wpdb;
		$result = $wpdb->insert( mjc_table( $table ), $data );
		return $result ? (int) $wpdb->insert_id : 0;
	}

	/**
	 * به‌روزرسانی ردیف.
	 *
	 * @param string $table نام کوتاه جدول.
	 * @param array  $data  داده‌ها.
	 * @param array  $where شرط.
	 * @return int تعداد ردیف.
	 */
	public static function update( $table, $data, $where ) {
		global $wpdb;
		return (int) $wpdb->update( mjc_table( $table ), $data, $where );
	}

	/**
	 * اجرای کوئری آماده.
	 *
	 * @param string $sql  کوئری با placeholder.
	 * @param array  $args آرگومان‌ها.
	 * @return array|null
	 */
	public static function results( $sql, $args = array() ) {
		global $wpdb;
		if ( $args ) {
			$sql = $wpdb->prepare( $sql, $args ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}
		return $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * مقدار تکی.
	 *
	 * @param string $sql  کوئری.
	 * @param array  $args آرگومان‌ها.
	 * @return mixed
	 */
	public static function var( $sql, $args = array() ) {
		global $wpdb;
		if ( $args ) {
			$sql = $wpdb->prepare( $sql, $args ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}
		return $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}
}
