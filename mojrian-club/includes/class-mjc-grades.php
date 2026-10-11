<?php
/**
 * ماژول پایه فنی (ارزیابی و استعلام).
 *
 * @package mojrian-club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * کلاس پایه فنی.
 */
class MJC_Grades {

	/**
	 * سطوح قابل تعریف.
	 *
	 * @return array
	 */
	public static function levels() {
		$levels = mjc_get_setting( 'grade_levels', array() );
		if ( ! is_array( $levels ) || ! $levels ) {
			$levels = array(
				'grade3' => array( 'label' => 'پایه ۳', 'min_theory' => 50, 'min_practical' => 50 ),
				'grade2' => array( 'label' => 'پایه ۲', 'min_theory' => 70, 'min_practical' => 70 ),
				'grade1' => array( 'label' => 'پایه ۱', 'min_theory' => 85, 'min_practical' => 85 ),
				'golden' => array( 'label' => 'پایه طلایی', 'min_theory' => 95, 'min_practical' => 95 ),
			);
		}
		return $levels;
	}

	/**
	 * برچسب سطح.
	 *
	 * @param string $key کلید سطح.
	 * @return string
	 */
	public static function level_label( $key ) {
		$levels = self::levels();
		return isset( $levels[ $key ] ) ? $levels[ $key ]['label'] : ( $key ? $key : '—' );
	}

	/**
	 * هوک‌ها.
	 */
	public static function hooks() {}

	/**
	 * ثبت درخواست ارزیابی.
	 *
	 * @param int   $user_id شناسه کاربر.
	 * @param array $data داده‌ها.
	 * @return int|WP_Error
	 */
	public static function request( $user_id, $data ) {
		$levels = self::levels();
		$level  = sanitize_key( isset( $data['requested_level'] ) ? $data['requested_level'] : '' );
		if ( ! $level || ! isset( $levels[ $level ] ) ) {
			return new WP_Error( 'mjc_fields', 'سطح درخواستی را انتخاب کنید.' );
		}
		$open = MJC_DB::var( "SELECT id FROM " . mjc_table( 'grade_requests' ) . " WHERE user_id = %d AND status = 'pending'", array( (int) $user_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( $open ) {
			return new WP_Error( 'mjc_exists', 'درخواست در حال بررسی دارید.' );
		}
		$id = MJC_DB::insert( 'grade_requests', array(
			'user_id'         => (int) $user_id,
			'requested_level' => $level,
			'status'          => 'pending',
			'notes'           => wp_kses_post( isset( $data['notes'] ) ? $data['notes'] : '' ),
			'created_at'      => current_time( 'mysql' ),
			'updated_at'      => current_time( 'mysql' ),
		) );
		if ( $id ) {
			MJC_Notify::push( (int) $user_id, 'درخواست پایه فنی', 'درخواست ارزیابی پایه فنی شما ثبت شد.', mjc_page_url( 'dashboard' ) );
		}
		return $id;
	}

	/**
	 * ثبت نتیجه‌ی ارزیابی توسط ادمین.
	 *
	 * @param int    $request_id شناسه درخواست.
	 * @param float  $theory نمره تئوری.
	 * @param float  $practical نمره عملی.
	 * @param string $final_level سطح اعطایی.
	 * @param string $notes توضیح.
	 * @return bool
	 */
	public static function review( $request_id, $theory, $practical, $final_level, $notes = '' ) {
		global $wpdb;
		$req = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . mjc_table( 'grade_requests' ) . ' WHERE id = %d', (int) $request_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( ! $req ) {
			return false;
		}
		$final_level = sanitize_key( $final_level );
		$known       = self::levels();
		if ( $final_level && ! isset( $known[ $final_level ] ) ) {
			$final_level = '';
		}

		MJC_DB::update( 'grade_requests', array(
			'status'          => $final_level ? 'approved' : 'rejected',
			'theory_score'    => (float) $theory,
			'practical_score' => (float) $practical,
			'final_level'     => $final_level,
			'notes'           => wp_kses_post( $notes ),
			'reviewed_by'     => get_current_user_id(),
			'updated_at'      => current_time( 'mysql' ),
		), array( 'id' => (int) $request_id ) );

		if ( $final_level ) {
			$certificate = 'MJC-' . strtoupper( wp_generate_password( 6, false, false ) );
			MJC_DB::update( 'members', array(
				'grade'               => $final_level,
				'grade_certificate'   => $certificate,
				'updated_at'          => current_time( 'mysql' ),
			), array( 'user_id' => (int) $req->user_id ) );
			MJC_Notify::push( (int) $req->user_id, 'نتیجه‌ی پایه فنی', 'تبریک! پایه فنی شما «' . self::level_label( $final_level ) . '» ثبت شد. کد استعلام: ' . $certificate, mjc_page_url( 'inquiry' ) );
		} else {
			MJC_Notify::push( (int) $req->user_id, 'نتیجه‌ی پایه فنی', 'متأسفانه در ارزیابی پایه فنی پذیرفته نشدید. ' . $notes );
		}
		MJC_Audit::log( 'grade_review', 'grade_request', (int) $request_id, $final_level ? $final_level : 'rejected' );
		return true;
	}

	/**
	 * استعلام عمومی (بدون افشای اطلاعات محرمانه).
	 *
	 * @param string $query کد ملی یا موبایل.
	 * @return array|WP_Error
	 */
	public static function inquiry( $query ) {
		$query = mjc_to_en_digits( trim( (string) $query ) );
		$mobile = MJC_SMS::normalize_mobile( $query );
		global $wpdb;
		if ( $mobile ) {
			$member = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . mjc_table( 'members' ) . ' WHERE mobile = %s', $mobile ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		} else {
			$member = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . mjc_table( 'members' ) . ' WHERE national_code = %s', $query ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}
		if ( ! $member ) {
			return new WP_Error( 'mjc_notfound', 'عضوی با این مشخصات یافت نشد.' );
		}
		$name = (string) $member->full_name;
		$masked = mb_strlen( $name ) > 2 ? mb_substr( $name, 0, 1 ) . '***' . mb_substr( $name, -1 ) : '***';
		return array(
			'name'       => $masked,
			'grade'      => $member->grade ? self::level_label( $member->grade ) : '—',
			'certificate'=> $member->grade_certificate ? $member->grade_certificate : '—',
			'status'     => 'approved' === $member->status ? 'عضو فعال باشگاه' : mjc_status_label( $member->status ),
		);
	}
}
