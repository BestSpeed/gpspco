<?php
/**
 * ماژول تیکت و ارتباط با ما.
 *
 * @package mojrian-club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * کلاس تیکت‌ها.
 */
class MJC_Tickets {

	/**
	 * هوک‌ها.
	 */
	public static function hooks() {}

	/**
	 * ثبت تیکت.
	 *
	 * @param int    $user_id شناسه کاربر (0 برای پیام عمومی).
	 * @param string $subject عنوان.
	 * @param string $message متن.
	 * @param int    $attachment_id پیوست.
	 * @return int|WP_Error
	 */
	public static function create( $user_id, $subject, $message, $attachment_id = 0 ) {
		$subject = sanitize_text_field( $subject );
		$message = wp_kses_post( $message );
		if ( '' === $subject || '' === $message ) {
			return new WP_Error( 'mjc_fields', 'عنوان و متن پیام الزامی است.' );
		}
		return MJC_DB::insert( 'tickets', array(
			'user_id'       => (int) $user_id,
			'subject'       => $subject,
			'message'       => $message,
			'attachment_id' => (int) $attachment_id,
			'status'        => 'open',
			'created_at'    => current_time( 'mysql' ),
			'updated_at'    => current_time( 'mysql' ),
		) );
	}

	/**
	 * پاسخ ادمین.
	 *
	 * @param int    $ticket_id شناسه تیکت.
	 * @param string $reply پاسخ.
	 * @param string $status وضعیت جدید.
	 * @return bool
	 */
	public static function reply( $ticket_id, $reply, $status = 'answered' ) {
		global $wpdb;
		$ticket = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . mjc_table( 'tickets' ) . ' WHERE id = %d', (int) $ticket_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( ! $ticket ) {
			return false;
		}
		MJC_DB::update( 'tickets', array(
			'admin_reply' => wp_kses_post( $reply ),
			'status'      => sanitize_key( $status ),
			'updated_at'  => current_time( 'mysql' ),
		), array( 'id' => (int) $ticket_id ) );
		if ( $ticket->user_id ) {
			MJC_Notify::push( (int) $ticket->user_id, 'پاسخ به پیام شما', 'به پیام «' . $ticket->subject . '» پاسخ داده شد.', mjc_page_url( 'contact' ) );
		}
		MJC_Audit::log( 'ticket_reply', 'ticket', (int) $ticket_id );
		return true;
	}

	/**
	 * تیکت‌های کاربر.
	 *
	 * @param int $user_id شناسه کاربر.
	 * @return array
	 */
	public static function list_for_user( $user_id ) {
		return MJC_DB::results(
			'SELECT * FROM ' . mjc_table( 'tickets' ) . ' WHERE user_id = %d ORDER BY id DESC LIMIT 50', // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			array( (int) $user_id )
		);
	}
}
