<?php
/**
 * ماژول کمپین‌های تشویقی و قرعه‌کشی.
 *
 * @package mojrian-club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * کلاس کمپین‌ها.
 */
class MJC_Campaigns {

	/**
	 * هوک‌ها.
	 */
	public static function hooks() {
		add_action( 'init', array( __CLASS__, 'register_cpt' ) );
		add_filter( 'template_include', array( __CLASS__, 'template' ) );
	}

	/**
	 * ثبت نوع محتوای کمپین.
	 */
	public static function register_cpt() {
		register_post_type( 'mjc_campaign', array(
			'labels'       => array(
				'name'          => 'کمپین‌ها و قرعه‌کشی',
				'singular_name' => 'کمپین',
				'add_new_item'  => 'افزودن کمپین جدید',
				'edit_item'     => 'ویرایش کمپین',
			),
			'public'       => true,
			'rewrite'      => array( 'slug' => 'campaign' ),
			'show_ui'      => true,
			'show_in_menu' => 'mjc-dashboard',
			'menu_icon'    => 'dashicons-megaphone',
			'supports'     => array( 'title', 'editor', 'thumbnail' ),
			'has_archive'  => false,
		) );

		// نکته: بازسازی قواعد بازنویسی در MJC_Install::maybe_flush (init با اولویت ۲۰،
		// بعد از ثبت همه‌ی CPTها) انجام می‌شود تا قواعد راهنما/کمپین/جوایز با هم ساخته شوند.
	}

	/**
	 * قالب اختصاصی صفحه‌ی کمپین.
	 *
	 * @param string $template قالب فعلی.
	 * @return string
	 */
	public static function template( $template ) {
		if ( is_singular( array( 'mjc_campaign' ) ) ) {
			$plugin_tpl = MJC_PLUGIN_DIR . 'public/templates/single-mjc_campaign.php';
			if ( file_exists( $plugin_tpl ) ) {
				return $plugin_tpl;
			}
		}
		return $template;
	}

	/**
	 * آیا کمپین فعال است؟
	 *
	 * @param int $campaign_id شناسه.
	 * @return bool
	 */
	public static function is_active( $campaign_id ) {
		$end = get_post_meta( (int) $campaign_id, 'mjc_end_date', true );
		if ( ! $end ) {
			return true;
		}
		return strtotime( $end . ' 23:59:59' ) >= time();
	}

	/**
	 * شرکت در کمپین.
	 *
	 * @param int $user_id شناسه کاربر.
	 * @param int $campaign_id شناسه کمپین.
	 * @return int|WP_Error
	 */
	public static function join( $user_id, $campaign_id ) {
		global $wpdb;
		$campaign = get_post( (int) $campaign_id );
		if ( ! $campaign || 'mjc_campaign' !== $campaign->post_type ) {
			return new WP_Error( 'mjc_notfound', 'کمپین یافت نشد.' );
		}
		if ( ! self::is_active( $campaign_id ) ) {
			return new WP_Error( 'mjc_closed', 'این کمپین به پایان رسیده است.' );
		}
		$exists = $wpdb->get_row( $wpdb->prepare( 'SELECT id FROM ' . mjc_table( 'lottery_entries' ) . ' WHERE campaign_id = %d AND user_id = %d', (int) $campaign_id, (int) $user_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( $exists ) {
			return new WP_Error( 'mjc_exists', 'قبلاً در این کمپین شرکت کرده‌اید.' );
		}
		$id = MJC_DB::insert( 'lottery_entries', array(
			'campaign_id' => (int) $campaign_id,
			'user_id'     => (int) $user_id,
			'created_at'  => current_time( 'mysql' ),
		) );
		if ( $id ) {
			MJC_Points::award_by_rule( (int) $user_id, 'campaign_join', 'شرکت در کمپین «' . $campaign->post_title . '»', 'campaign', (int) $campaign_id );
		}
		return $id;
	}

	/**
	 * انجام قرعه‌کشی (انتخاب برنده‌ی تصادفی).
	 *
	 * @param int $campaign_id شناسه کمپین.
	 * @param int $winners تعداد برندگان.
	 * @return array شناسه کاربران برنده.
	 */
	public static function draw( $campaign_id, $winners = 1 ) {
		global $wpdb;
		$winners = max( 1, (int) $winners );
		$ids     = $wpdb->get_col( $wpdb->prepare( 'SELECT user_id FROM ' . mjc_table( 'lottery_entries' ) . ' WHERE campaign_id = %d', (int) $campaign_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( empty( $ids ) ) {
			return array();
		}
		shuffle( $ids );
		$selected = array_slice( array_values( array_unique( $ids ) ), 0, $winners );

		update_post_meta( (int) $campaign_id, 'mjc_winners', wp_json_encode( $selected ) );
		foreach ( $selected as $uid ) {
			MJC_Notify::push( (int) $uid, 'تبریک! برنده شدید', 'شما در قرعه‌کشی «' . get_the_title( (int) $campaign_id ) . '» برنده شدید. باشگاه با شما تماس می‌گیرد.' );
		}
		MJC_Audit::log( 'lottery_draw', 'campaign', (int) $campaign_id, 'winners: ' . implode( ',', $selected ) );
		return $selected;
	}

	/**
	 * برندگان قبلی.
	 *
	 * @param int $campaign_id شناسه.
	 * @return array
	 */
	public static function winners( $campaign_id ) {
		$raw = get_post_meta( (int) $campaign_id, 'mjc_winners', true );
		$arr = $raw ? json_decode( (string) $raw, true ) : array();
		return is_array( $arr ) ? array_map( 'intval', $arr ) : array();
	}
}
