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
	public static function hooks() {
		add_action( 'init', array( __CLASS__, 'register_cpt' ) );
	}

	/**
	 * ثبت نوع محتوای جایزه (فروشگاه امتیاز).
	 */
	public static function register_cpt() {
		register_post_type( 'mjc_reward', array(
			'labels'       => array(
				'name'          => 'جوایز (فروشگاه امتیاز)',
				'singular_name' => 'جایزه',
				'add_new_item'  => 'افزودن جایزه جدید',
				'edit_item'     => 'ویرایش جایزه',
			),
			'public'       => false,
			'show_ui'      => true,
			'show_in_menu' => 'mjc-dashboard',
			'menu_icon'    => 'dashicons-awards',
			'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
			'capability_type' => 'post',
		) );
	}

	/**
	 * جوایز پیش‌فرض فروشگاه (هنگام فعال‌سازی).
	 */
	public static function seed_default_rewards() {
		// اگر هیچ جایزه‌ای وجود نداشته باشد (حتی اگر قبلاً seed شده باشد) دوباره ساخته می‌شود.
		global $wpdb;
		$exists = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'mjc_reward' AND post_status = 'publish'" );
		if ( $exists > 0 ) {
			update_option( 'mjc_rewards_seeded', 1 );
			return;
		}
		// کاتالوگ پیش‌فرض مطابق ماکت «تبدیل امتیازات به جوایز» (ابزارآلات و لوله).
		$rewards = array(
			array( 'title' => 'متر (نوار متر)', 'points' => 140, 'stock' => 40, 'icon' => '📏', 'content' => 'نوار متر ۵ متری مخصوص مجریان تأسیسات.' ),
			array( 'title' => 'لوله ۲۵', 'points' => 145, 'stock' => 40, 'icon' => '🧱', 'content' => 'لوله تأسیسات سایز ۲۵.' ),
			array( 'title' => 'لوله ۵۵', 'points' => 245, 'stock' => 30, 'icon' => '🧱', 'content' => 'لوله تأسیسات سایز ۵۵.' ),
			array( 'title' => 'لوله ۳۳', 'points' => 345, 'stock' => 30, 'icon' => '🧱', 'content' => 'لوله تأسیسات سایز ۳۳.' ),
			array( 'title' => 'لوله ۵۰', 'points' => 445, 'stock' => 30, 'icon' => '🧱', 'content' => 'لوله تأسیسات سایز ۵۰.' ),
			array( 'title' => 'قیچی لوله‌بری', 'points' => 550, 'stock' => 20, 'icon' => '✂️', 'content' => 'قیچی برش لوله پلیمری.' ),
			array( 'title' => 'فرز برش لوله', 'points' => 485, 'stock' => 15, 'icon' => '⚙️', 'content' => 'فرز مخصوص برش لوله.' ),
			array( 'title' => 'کولیس', 'points' => 590, 'stock' => 15, 'icon' => '📐', 'content' => 'کولیس اندازه‌گیری دقیق.' ),
			array( 'title' => 'کیف ابزار', 'points' => 890, 'stock' => 12, 'icon' => '🧰', 'content' => 'کیف ابزار جادار ویژه مجریان.' ),
			array( 'title' => 'دریل سه‌کاره', 'points' => 390, 'stock' => 15, 'icon' => '🔧', 'content' => 'دریل سه‌کاره صنعتی.' ),
			array( 'title' => 'دریل ۵۳۰', 'points' => 785, 'stock' => 10, 'icon' => '🔧', 'content' => 'دریل ۵۳۰ وات.' ),
			array( 'title' => 'جعبه پیچ‌گوشتی', 'points' => 1490, 'stock' => 10, 'icon' => '🪛', 'content' => 'جعبه پیچ‌گوشتی چندتکه.' ),
			array( 'title' => 'اتو لوله‌جوش', 'points' => 3300, 'stock' => 6, 'icon' => '🔥', 'content' => 'دستگاه اتو لوله‌جوش.' ),
			array( 'title' => 'لباس کار', 'points' => 590, 'stock' => 20, 'icon' => '🦺', 'content' => 'لباس کار مخصوص مجریان.' ),
			array( 'title' => 'دستگاه پرس لوله', 'points' => 11390, 'stock' => 3, 'icon' => '🛠️', 'content' => 'دستگاه پرس لوله حرفه‌ای.' ),
		);
		foreach ( $rewards as $r ) {
			$post_id = wp_insert_post( array(
				'post_type'    => 'mjc_reward',
				'post_status'  => 'publish',
				'post_title'   => $r['title'],
				'post_content' => $r['content'],
			) );
			if ( $post_id && ! is_wp_error( $post_id ) ) {
				update_post_meta( $post_id, 'mjc_points_cost', (int) $r['points'] );
				update_post_meta( $post_id, 'mjc_stock', (int) $r['stock'] );
				update_post_meta( $post_id, 'mjc_icon', sanitize_text_field( $r['icon'] ) );
			}
		}
		update_option( 'mjc_rewards_seeded', 1 );
	}

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
