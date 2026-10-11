<?php
/**
 * ماژول فروشگاه امتیاز و جوایز.
 *
 * @package mojrian-club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * کلاس جوایز.
 */
class MJC_Rewards {

	/**
	 * هوک‌ها.
	 */
	public static function hooks() {}

	/**
	 * درخواست تبدیل امتیاز به جایزه.
	 *
	 * @param int    $user_id شناسه کاربر.
	 * @param int    $reward_id شناسه جایزه.
	 * @param string $address نشانی ارسال.
	 * @return int|WP_Error
	 */
	public static function request( $user_id, $reward_id, $address = '' ) {
		$reward = get_post( (int) $reward_id );
		if ( ! $reward || 'mjc_reward' !== $reward->post_type || 'publish' !== $reward->post_status ) {
			return new WP_Error( 'mjc_notfound', 'جایزه یافت نشد.' );
		}
		$cost  = (int) get_post_meta( $reward->ID, 'mjc_points_cost', true );
		$stock_raw = get_post_meta( $reward->ID, 'mjc_stock', true );
		$stock_unlimited = ( '' === $stock_raw || null === $stock_raw );
		$stock = $stock_unlimited ? 999999 : (int) $stock_raw;
		$member = MJC_Members::get_by_user( $user_id );
		if ( ! $member ) {
			return new WP_Error( 'mjc_access', 'دسترسی غیرمجاز.' );
		}
		if ( ! $stock_unlimited && $stock <= 0 ) {
			return new WP_Error( 'mjc_stock', 'این جایزه موجود نیست.' );
		}
		if ( (int) $member->points_balance < $cost ) {
			return new WP_Error( 'mjc_points', 'امتیاز شما برای این جایزه کافی نیست.' );
		}

		// کسر امتیاز هنگام درخواست (در صورت رد، بازگردانده می‌شود).
		MJC_Points::log_transaction( $user_id, -$cost, 'reward_request', 'درخواست جایزه «' . $reward->post_title . '»', 'reward', $reward->ID );

		$id = MJC_DB::insert( 'reward_requests', array(
			'user_id'          => (int) $user_id,
			'reward_id'        => (int) $reward->ID,
			'points'           => $cost,
			'status'           => 'pending',
			'shipping_address' => sanitize_text_field( $address ),
			'created_at'       => current_time( 'mysql' ),
			'updated_at'       => current_time( 'mysql' ),
		) );

		if ( $id ) {
			if ( ! $stock_unlimited ) {
				update_post_meta( $reward->ID, 'mjc_stock', max( 0, $stock - 1 ) );
			}
			MJC_Notify::push( (int) $user_id, 'درخواست جایزه', 'درخواست جایزه «' . $reward->post_title . '» ثبت شد و در انتظار بررسی است.', mjc_page_url( 'rewards' ) );
			MJC_Audit::log( 'reward_requested', 'reward_request', $id, $reward->post_title );
		}
		return $id;
	}

	/**
	 * تغییر وضعیت درخواست جایزه.
	 *
	 * @param int    $request_id شناسه درخواست.
	 * @param string $status وضعیت جدید.
	 * @param string $reason دلیل.
	 * @return bool
	 */
	public static function set_status( $request_id, $status, $reason = '' ) {
		global $wpdb;
		$req = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . mjc_table( 'reward_requests' ) . ' WHERE id = %d', (int) $request_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( ! $req ) {
			return false;
		}
		MJC_DB::update( 'reward_requests', array(
			'status'        => sanitize_key( $status ),
			'reject_reason' => sanitize_text_field( $reason ),
			'updated_at'    => current_time( 'mysql' ),
		), array( 'id' => (int) $request_id ) );

		if ( 'rejected' === $status && 'rejected' !== $req->status ) {
			MJC_Points::log_transaction( (int) $req->user_id, (int) $req->points, 'reward_refund', 'بازگشت امتیاز جایزه ردشده', 'reward_request', (int) $request_id );
			MJC_Notify::push( (int) $req->user_id, 'درخواست جایزه رد شد', 'درخواست شما رد شد و امتیاز بازگردانده شد. دلیل: ' . $reason );
		} elseif ( 'approved' === $status || 'shipped' === $status ) {
			MJC_Notify::push( (int) $req->user_id, 'وضعیت جایزه', 'درخواست جایزه‌ی شما «' . mjc_status_label( $status ) . '» شد.', mjc_page_url( 'rewards' ) );
		}
		MJC_Audit::log( 'reward_' . $status, 'reward_request', (int) $request_id, $reason );
		return true;
	}

	/**
	 * فهرست درخواست‌های کاربر.
	 *
	 * @param int $user_id شناسه کاربر.
	 * @return array
	 */
	public static function requests_for_user( $user_id ) {
		return MJC_DB::results(
			'SELECT * FROM ' . mjc_table( 'reward_requests' ) . ' WHERE user_id = %d ORDER BY id DESC LIMIT 50', // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			array( (int) $user_id )
		);
	}

	/**
	 * هزینه‌ی امتیازی جایزه.
	 *
	 * @param int $reward_id شناسه.
	 * @return int
	 */
	public static function cost( $reward_id ) {
		return (int) get_post_meta( (int) $reward_id, 'mjc_points_cost', true );
	}
}
