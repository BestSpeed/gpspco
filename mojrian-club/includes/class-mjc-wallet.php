<?php
/**
 * ماژول کیف پول (اعتبار ریالی).
 *
 * @package mojrian-club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * کلاس کیف پول.
 */
class MJC_Wallet {

	/**
	 * هوک‌ها.
	 */
	public static function hooks() {}

	/**
	 * ثبت تراکنش کیف پول.
	 *
	 * @param int    $user_id شناسه کاربر.
	 * @param int    $amount مقدار ریال (مثبت واریز، منفی برداشت).
	 * @param string $type نوع.
	 * @param string $description توضیح.
	 * @param string $status وضعیت (done|pending).
	 * @return int|WP_Error
	 */
	public static function transaction( $user_id, $amount, $type, $description, $status = 'done' ) {
		$member = MJC_Members::get_by_user( $user_id );
		if ( ! $member ) {
			return new WP_Error( 'mjc_access', 'عضو یافت نشد.' );
		}
		$amount  = (int) $amount;
		$balance = (int) $member->wallet_balance;

		if ( 'done' === $status ) {
			$balance = max( 0, $balance + $amount );
			MJC_DB::update( 'members', array(
				'wallet_balance' => $balance,
				'updated_at'     => current_time( 'mysql' ),
			), array( 'user_id' => (int) $user_id ) );
		}

		return MJC_DB::insert( 'wallet_transactions', array(
			'user_id'       => (int) $user_id,
			'amount'        => $amount,
			'balance_after' => $balance,
			'type'          => sanitize_key( $type ),
			'status'        => sanitize_key( $status ),
			'description'   => sanitize_text_field( $description ),
			'created_by'    => get_current_user_id(),
			'created_at'    => current_time( 'mysql' ),
		) );
	}

	/**
	 * درخواست برداشت.
	 *
	 * @param int $user_id شناسه کاربر.
	 * @param int $amount مبلغ.
	 * @return int|WP_Error
	 */
	public static function withdraw_request( $user_id, $amount ) {
		$member = MJC_Members::get_by_user( $user_id );
		$amount = (int) $amount;
		$min    = (int) mjc_get_setting( 'wallet_withdraw_min', 1000000 );
		if ( ! $member ) {
			return new WP_Error( 'mjc_access', 'دسترسی غیرمجاز.' );
		}
		if ( $amount < $min ) {
			return new WP_Error( 'mjc_fields', 'حداقل مبلغ برداشت ' . mjc_money( $min ) . ' ریال است.' );
		}
		if ( $amount > (int) $member->wallet_balance ) {
			return new WP_Error( 'mjc_fields', 'موجودی کیف پول کافی نیست.' );
		}
		// بلوکه شدن مبلغ هنگام درخواست.
		MJC_DB::update( 'members', array(
			'wallet_balance' => (int) $member->wallet_balance - $amount,
			'updated_at'     => current_time( 'mysql' ),
		), array( 'user_id' => (int) $user_id ) );

		$id = MJC_DB::insert( 'wallet_transactions', array(
			'user_id'       => (int) $user_id,
			'amount'        => -$amount,
			'balance_after' => (int) $member->wallet_balance - $amount,
			'type'          => 'withdraw',
			'status'        => 'pending',
			'description'   => 'درخواست برداشت',
			'created_by'    => (int) $user_id,
			'created_at'    => current_time( 'mysql' ),
		) );
		if ( $id ) {
			MJC_Notify::push( (int) $user_id, 'درخواست برداشت', 'درخواست برداشت ' . mjc_money( $amount ) . ' ریال ثبت شد.', mjc_page_url( 'wallet' ) );
		}
		return $id;
	}

	/**
	 * تأیید/رد درخواست برداشت.
	 *
	 * @param int    $tx_id شناسه تراکنش.
	 * @param string $status approved|rejected.
	 * @return bool
	 */
	public static function review_withdraw( $tx_id, $status ) {
		global $wpdb;
		$tx = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . mjc_table( 'wallet_transactions' ) . ' WHERE id = %d', (int) $tx_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( ! $tx || 'pending' !== $tx->status ) {
			return false;
		}
		if ( 'approved' === $status ) {
			MJC_DB::update( 'wallet_transactions', array( 'status' => 'done' ), array( 'id' => (int) $tx_id ) );
			MJC_Notify::push( (int) $tx->user_id, 'برداشت تأیید شد', 'مبلغ ' . mjc_money( abs( (int) $tx->amount ) ) . ' ریال به حساب شما واریز خواهد شد.' );
		} else {
			// بازگرداندن مبلغ بلوکه‌شده.
			MJC_DB::update( 'wallet_transactions', array( 'status' => 'rejected' ), array( 'id' => (int) $tx_id ) );
			self::transaction( (int) $tx->user_id, abs( (int) $tx->amount ), 'refund', 'بازگشت درخواست برداشت ردشده' );
			MJC_Notify::push( (int) $tx->user_id, 'برداشت رد شد', 'درخواست برداشت شما رد شد و مبلغ به کیف پول بازگردانده شد.' );
		}
		MJC_Audit::log( 'wallet_withdraw_' . $status, 'wallet_tx', (int) $tx_id );
		return true;
	}

	/**
	 * تراکنش‌های کاربر.
	 *
	 * @param int $user_id شناسه کاربر.
	 * @return array
	 */
	public static function transactions( $user_id ) {
		return MJC_DB::results(
			'SELECT * FROM ' . mjc_table( 'wallet_transactions' ) . ' WHERE user_id = %d ORDER BY id DESC LIMIT 50', // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			array( (int) $user_id )
		);
	}
}
