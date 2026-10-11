<?php
/**
 * ماژول ثبت و بررسی پروژه‌ها.
 *
 * @package mojrian-club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * کلاس پروژه‌ها.
 */
class MJC_Projects {

	const TYPES = array(
		'new'            => 'نوسازی',
		'renovation'     => 'بازسازی',
		'repair'         => 'تعمیر',
		'cultural_sport' => 'اماکن فرهنگی و ورزشی',
		'other_places'   => 'سایر اماکن',
	);

	/**
	 * هوک‌ها.
	 */
	public static function hooks() {}

	/**
	 * ثبت پروژه.
	 *
	 * @param int   $user_id شناسه کاربر.
	 * @param array $data داده‌ها.
	 * @return int|WP_Error
	 */
	public static function create( $user_id, $data ) {
		$title = sanitize_text_field( isset( $data['title'] ) ? $data['title'] : '' );
		if ( '' === $title ) {
			$title = 'پروژه ' . mjc_date( current_time( 'mysql' ), false );
		}
		$images = isset( $data['images'] ) ? array_map( 'intval', (array) $data['images'] ) : array();

		$id = MJC_DB::insert( 'projects', array(
			'user_id'      => (int) $user_id,
			'title'        => $title,
			'project_type' => sanitize_key( isset( $data['project_type'] ) ? $data['project_type'] : 'other' ),
			'province'     => sanitize_text_field( isset( $data['province'] ) ? $data['province'] : '' ),
			'city'         => sanitize_text_field( isset( $data['city'] ) ? $data['city'] : '' ),
			'units'        => (int) ( isset( $data['units'] ) ? $data['units'] : 0 ),
			'floors'       => (int) ( isset( $data['floors'] ) ? $data['floors'] : 0 ),
			'film_id'      => (int) ( isset( $data['film_id'] ) ? $data['film_id'] : 0 ),
			'amount'       => (int) ( isset( $data['amount'] ) ? $data['amount'] : 0 ),
			'area'         => (float) ( isset( $data['area'] ) ? $data['area'] : 0 ),
			'brand'        => sanitize_text_field( isset( $data['brand'] ) ? $data['brand'] : '' ),
			'start_date'   => MJC_Jalali::parse_input( isset( $data['start_date'] ) ? $data['start_date'] : '' ),
			'description'  => wp_kses_post( isset( $data['description'] ) ? $data['description'] : '' ),
			'images'       => wp_json_encode( $images ),
			'status'       => 'pending',
			'created_at'   => current_time( 'mysql' ),
			'updated_at'   => current_time( 'mysql' ),
		) );

		if ( $id ) {
			MJC_Audit::log( 'project_created', 'project', $id, $title );
			MJC_Notify::push( (int) $user_id, 'ثبت پروژه', 'پروژه «' . $title . '» ثبت شد و پس از بررسی، امتیاز آن اعمال می‌شود.', mjc_page_url( 'projects' ) );
		}
		return $id;
	}

	/**
	 * محاسبه‌ی امتیاز پروژه بر اساس تنظیمات (ثابت / متراژ / مبلغ).
	 *
	 * @param object $project ردیف پروژه.
	 * @return int
	 */
	public static function calculate_points( $project ) {
		$mode = mjc_get_setting( 'project_points_mode', 'fixed' );
		if ( 'area' === $mode ) {
			$rate  = (float) mjc_get_setting( 'points_per_meter', 2 );
			$points = (int) round( (float) $project->area * $rate );
		} elseif ( 'amount' === $mode ) {
			$rate   = (float) mjc_get_setting( 'points_per_10m', 10 );
			$points = (int) round( ( (float) $project->amount / 10000000 ) * $rate );
		} else {
			$rule   = MJC_Settings::rule( 'project_approved' );
			$points = $rule['enabled'] ? (int) $rule['points'] : 0;
		}
		$max = (int) mjc_get_setting( 'project_points_max', 0 );
		if ( $max > 0 ) {
			$points = min( $points, $max );
		}
		return max( 0, $points );
	}

	/**
	 * تغییر وضعیت پروژه (تأیید/رد).
	 *
	 * @param int    $project_id شناسه پروژه.
	 * @param string $status وضعیت جدید.
	 * @param string $reason دلیل رد.
	 * @return bool
	 */
	public static function set_status( $project_id, $status, $reason = '' ) {
		global $wpdb;
		$project = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . mjc_table( 'projects' ) . ' WHERE id = %d', (int) $project_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( ! $project ) {
			return false;
		}
		$points = 0;
		if ( 'approved' === $status && 'approved' !== $project->status ) {
			$points = self::calculate_points( $project );
			if ( $points > 0 ) {
				MJC_Points::log_transaction( (int) $project->user_id, $points, 'project_approved', 'تأیید پروژه «' . $project->title . '»', 'project', (int) $project_id );
				MJC_Notify::push( (int) $project->user_id, 'کسب ' . mjc_get_setting( 'points_label', 'امتیاز' ), sprintf( '%s امتیاز برای «تأیید پروژه %s» به حساب شما اضافه شد.', mjc_num( $points ), $project->title ), mjc_page_url( 'points' ) );
			}
		}
		MJC_DB::update( 'projects', array(
			'status'         => sanitize_key( $status ),
			'reject_reason'  => sanitize_text_field( $reason ),
			'points_awarded' => (int) $points,
			'updated_at'     => current_time( 'mysql' ),
		), array( 'id' => (int) $project_id ) );

		if ( 'approved' === $status ) {
			MJC_Notify::push( (int) $project->user_id, 'پروژه تأیید شد', 'پروژه «' . $project->title . '» تأیید شد' . ( $points ? ' و ' . mjc_num( $points ) . ' ' . mjc_get_setting( 'points_label', 'امتیاز' ) . ' به شما تعلق گرفت' : '' ) . '.', mjc_page_url( 'projects' ) );
		} elseif ( 'rejected' === $status ) {
			MJC_Notify::push( (int) $project->user_id, 'پروژه رد شد', 'پروژه «' . $project->title . '» رد شد. دلیل: ' . $reason );
		}
		MJC_Audit::log( 'project_' . $status, 'project', (int) $project_id, $reason );
		return true;
	}

	/**
	 * فهرست پروژه‌های یک کاربر.
	 *
	 * @param int $user_id شناسه کاربر.
	 * @param int $paged صفحه.
	 * @return array [rows, total]
	 */
	public static function list_for_user( $user_id, $paged = 1, $per_page = 10 ) {
		$offset = max( 0, ( $paged - 1 ) * $per_page );
		$rows   = MJC_DB::results(
			'SELECT * FROM ' . mjc_table( 'projects' ) . ' WHERE user_id = %d ORDER BY id DESC LIMIT %d OFFSET %d', // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			array( (int) $user_id, $per_page, $offset )
		);
		$total = (int) MJC_DB::var( 'SELECT COUNT(*) FROM ' . mjc_table( 'projects' ) . ' WHERE user_id = %d', array( (int) $user_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return array( $rows ? $rows : array(), $total );
	}

	/**
	 * عنوان نوع پروژه.
	 *
	 * @param string $key کلید.
	 * @return string
	 */
	public static function type_label( $key ) {
		return isset( self::TYPES[ $key ] ) ? self::TYPES[ $key ] : $key;
	}

	/**
	 * آمار پروژه‌ها.
	 *
	 * @return array
	 */
	public static function stats() {
		return array(
			'total'   => (int) MJC_DB::var( 'SELECT COUNT(*) FROM ' . mjc_table( 'projects' ) ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'pending' => (int) MJC_DB::var( "SELECT COUNT(*) FROM " . mjc_table( 'projects' ) . " WHERE status = 'pending'" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
	}
}
