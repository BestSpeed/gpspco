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
		add_action( 'admin_init', array( __CLASS__, 'ensure_icons' ) );
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
		// کاتالوگ دقیقاً مطابق ماکت «تبدیل امتیازات به جوایز» — آیکون‌ها از خود تصویر ماکت جدا شده‌اند.
		// نسخه ۲: بازنشانی کامل کاتالوگ (جایگزینی نسخه‌های قبلی) هنگام ارتقا.
		$seed_version = 2;
		if ( (int) get_option( 'mjc_rewards_seed_version', 0 ) >= $seed_version ) {
			return;
		}
		// حذف کاتالوگ قبلی (جوایز دستی مدیر هم با این بازنشانی جایگزین می‌شوند).
		$old = get_posts( array(
			'post_type'      => 'mjc_reward',
			'post_status'    => 'any',
			'numberposts'    => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		) );
		foreach ( $old as $oid ) {
			wp_delete_post( (int) $oid, true );
		}
		// ترتیب نمایش = ترتیب خواندن ماکت (ردیف‌ها از راست به چپ).
		$rewards = self::seed_catalog();
		foreach ( $rewards as $i => $r ) {
			$post_id = wp_insert_post( array(
				'post_type'    => 'mjc_reward',
				'post_status'  => 'publish',
				'post_title'   => $r['title'],
				'post_content' => $r['content'],
				'menu_order'   => $i + 1,
			) );
			if ( $post_id && ! is_wp_error( $post_id ) ) {
				update_post_meta( $post_id, 'mjc_points_cost', (int) $r['points'] );
				update_post_meta( $post_id, 'mjc_stock', (int) $r['stock'] );
				update_post_meta( $post_id, 'mjc_icon', sanitize_text_field( $r['icon'] ) );
				self::attach_icon( (int) $post_id, sanitize_text_field( $r['icon'] ) );
			}
		}
		update_option( 'mjc_rewards_seed_version', $seed_version );
		update_option( 'mjc_rewards_seeded', 1 );
	}

	/**
	 * کاتالوگ پیش‌فرض جوایز (منبع واحد برای seed و فهرست آیکون‌ها).
	 *
	 * @return array
	 */
	public static function seed_catalog() {
		// ترتیب نمایش = ترتیب خواندن ماکت (ردیف‌ها از راست به چپ).
		return array(
			array( 'title' => 'متر', 'points' => 140, 'stock' => 40, 'icon' => 'assets/img/rewards/r01.png', 'content' => 'نوار متر ۵ متری مخصوص مجریان تأسیسات.' ),
			array( 'title' => 'لوله اتو ۲۰', 'points' => 145, 'stock' => 40, 'icon' => 'assets/img/rewards/r02.png', 'content' => 'لوله اتو سایز ۲۰.' ),
			array( 'title' => 'لوله اتو ۲۵', 'points' => 245, 'stock' => 30, 'icon' => 'assets/img/rewards/r03.png', 'content' => 'لوله اتو سایز ۲۵.' ),
			array( 'title' => 'لوله اتو ۳۳', 'points' => 345, 'stock' => 30, 'icon' => 'assets/img/rewards/r04.png', 'content' => 'لوله اتو سایز ۳۳.' ),
			array( 'title' => 'لوله اتو ۴۰', 'points' => 445, 'stock' => 30, 'icon' => 'assets/img/rewards/r05.png', 'content' => 'لوله اتو سایز ۴۰.' ),
			array( 'title' => 'لوله اتو ۵۰', 'points' => 545, 'stock' => 30, 'icon' => 'assets/img/rewards/r06.png', 'content' => 'لوله اتو سایز ۵۰.' ),
			array( 'title' => 'لوله اتو ۳۴', 'points' => 625, 'stock' => 30, 'icon' => 'assets/img/rewards/r07.png', 'content' => 'لوله اتو سایز ۳۴.' ),
			array( 'title' => 'لوله اتو ۷۵', 'points' => 780, 'stock' => 25, 'icon' => 'assets/img/rewards/r08.png', 'content' => 'لوله اتو سایز ۷۵.' ),
			array( 'title' => 'قیچی لوله ان پی اس', 'points' => 550, 'stock' => 20, 'icon' => 'assets/img/rewards/r09.png', 'content' => 'قیچی برش لوله ان پی اس.' ),
			array( 'title' => 'فرز لوله ۲۵', 'points' => 445, 'stock' => 20, 'icon' => 'assets/img/rewards/r10.png', 'content' => 'فرز مخصوص برش لوله سایز ۲۵.' ),
			array( 'title' => 'فرز لوله ۳۲', 'points' => 485, 'stock' => 15, 'icon' => 'assets/img/rewards/r11.png', 'content' => 'فرز مخصوص برش لوله سایز ۳۲.' ),
			array( 'title' => 'زیرانداز', 'points' => 590, 'stock' => 15, 'icon' => 'assets/img/rewards/r12.png', 'content' => 'زیرانداز کار.' ),
			array( 'title' => 'فلکس', 'points' => 550, 'stock' => 15, 'icon' => 'assets/img/rewards/r13.png', 'content' => 'لوله فلکس.' ),
			array( 'title' => 'کیف ابزار', 'points' => 890, 'stock' => 12, 'icon' => 'assets/img/rewards/r14.png', 'content' => 'کیف ابزار جادار ویژه مجریان.' ),
			array( 'title' => 'برق سه کاره', 'points' => 390, 'stock' => 15, 'icon' => 'assets/img/rewards/r15.png', 'content' => 'ابزار برقی سه‌کاره.' ),
			array( 'title' => 'برق ۱۴', 'points' => 745, 'stock' => 12, 'icon' => 'assets/img/rewards/r16.png', 'content' => 'ابزار برقی سایز ۱۴.' ),
			array( 'title' => 'برق ۵۳', 'points' => 785, 'stock' => 12, 'icon' => 'assets/img/rewards/r17.png', 'content' => 'ابزار برقی سایز ۵۳.' ),
			array( 'title' => 'برق ۵۵', 'points' => 810, 'stock' => 12, 'icon' => 'assets/img/rewards/r18.png', 'content' => 'ابزار برقی سایز ۵۵.' ),
			array( 'title' => 'برق ۳۳', 'points' => 840, 'stock' => 12, 'icon' => 'assets/img/rewards/r19.png', 'content' => 'ابزار برقی سایز ۳۳.' ),
			array( 'title' => 'لباس کار تیشرت', 'points' => 390, 'stock' => 25, 'icon' => 'assets/img/rewards/r20.png', 'content' => 'لباس کار تیشرتی.' ),
			array( 'title' => 'لباس کار پافر', 'points' => 590, 'stock' => 20, 'icon' => 'assets/img/rewards/r21.png', 'content' => 'لباس کار پافر.' ),
			array( 'title' => 'جعبه پیچگوشتی', 'points' => 1490, 'stock' => 10, 'icon' => 'assets/img/rewards/r22.png', 'content' => 'جعبه پیچگوشتی چندتکه.' ),
			array( 'title' => 'دستگاه اتو لوله', 'points' => 3300, 'stock' => 6, 'icon' => 'assets/img/rewards/r23.png', 'content' => 'دستگاه اتو لوله‌جوش.' ),
			array( 'title' => 'کیف کامل ابزارآلات پیچ لازمه', 'points' => 3490, 'stock' => 5, 'icon' => 'assets/img/rewards/r24.png', 'content' => 'کیف کامل ابزارآلات.' ),
			array( 'title' => 'دستگاه پرس لوله', 'points' => 11390, 'stock' => 3, 'icon' => 'assets/img/rewards/r25.png', 'content' => 'دستگاه پرس لوله حرفه‌ای.' ),
		);
	}

	/**
	 * فهرست آیکون‌های آماده (برش‌خورده از ماکت) برای انتخاب در ویرایش جایزه.
	 *
	 * @return array مسیر نسبی => برچسب.
	 */
	public static function icon_catalog() {
		$catalog = array();
		foreach ( self::seed_catalog() as $r ) {
			$catalog[ $r['icon'] ] = $r['title'] . ' (' . basename( $r['icon'], '.png' ) . ')';
		}
		return $catalog;
	}

	/**
	 * نشانی نهایی آیکون/تصویر جایزه: تصویر شاخص، سپس آیکون بسته‌بندی‌شده.
	 *
	 * @param WP_Post|int $post شناسه یا شیء جایزه.
	 * @return string
	 */
	public static function icon_url( $post ) {
		$post = get_post( $post );
		if ( ! $post ) {
			return '';
		}
		$thumb = get_the_post_thumbnail_url( $post->ID, 'full' );
		if ( $thumb ) {
			return $thumb;
		}
		$icon = (string) get_post_meta( $post->ID, 'mjc_icon', true );
		if ( '' === $icon ) {
			return '';
		}
		if ( preg_match( '#^https?://#i', $icon ) ) {
			return $icon;
		}
		return MJC_PLUGIN_URL . ltrim( $icon, '/' );
	}

	/**
	 * پیوست آیکون بسته‌بندی‌شده به کتابخانه رسانه و تنظیم به‌عنوان تصویر شاخص جایزه.
	 *
	 * @param int    $post_id شناسه جایزه.
	 * @param string $icon_rel مسیر نسبی آیکون در افزونه (مثل assets/img/rewards/r01.png).
	 * @return bool
	 */
	public static function attach_icon( $post_id, $icon_rel ) {
		$post_id = (int) $post_id;
		$icon_rel = ltrim( (string) $icon_rel, '/' );
		if ( '' === $icon_rel ) {
			return false;
		}
		$src = MJC_PLUGIN_DIR . $icon_rel;
		if ( ! is_file( $src ) ) {
			return false;
		}
		if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
		}
		$uploads = wp_upload_dir();
		if ( ! empty( $uploads['error'] ) || empty( $uploads['basedir'] ) ) {
			return false;
		}
		$dir = trailingslashit( $uploads['basedir'] ) . 'mjc-icons';
		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
		}
		$name = basename( $icon_rel );
		$dest = trailingslashit( $dir ) . $name;
		if ( ! is_file( $dest ) && ! copy( $src, $dest ) ) {
			return false;
		}

		// استفاده از پیوست قبلی همین فایل (برای جلوگیری از تکرار در کتابخانه رسانه).
		$att_id  = 0;
		$existing = get_posts( array(
			'post_type'      => 'attachment',
			'post_status'    => 'any',
			'numberposts'    => 1,
			'fields'         => 'ids',
			'meta_key'       => '_mjc_icon_file',
			'meta_value'     => $name,
			'no_found_rows'  => true,
		) );
		if ( $existing ) {
			$att_id = (int) $existing[0];
		} else {
			$att_id = (int) wp_insert_attachment( array(
				'post_mime_type' => 'image/png',
				'post_title'     => sanitize_file_name( pathinfo( $name, PATHINFO_FILENAME ) ),
				'post_status'    => 'inherit',
			), $dest );
			if ( ! $att_id ) {
				return false;
			}
			update_post_meta( $att_id, '_mjc_icon_file', $name );
			if ( function_exists( 'wp_generate_attachment_metadata' ) ) {
				$meta = wp_generate_attachment_metadata( $att_id, $dest );
				if ( $meta ) {
					wp_update_attachment_metadata( $att_id, $meta );
				}
			}
		}
		if ( ! set_post_thumbnail( $post_id, $att_id ) && (int) get_post_thumbnail_id( $post_id ) !== $att_id ) {
			return false;
		}
		return true;
	}

	/**
	 * مهاجرت یک‌باره: آیکون بسته‌بندی‌شده‌ی جوایز پیش‌فرض را به‌عنوان تصویر شاخص ثبت کن.
	 * بدون تخریب — جوایزی که تصویر شاخص دستی دارند دست نمی‌خورند.
	 */
	public static function ensure_icons() {
		if ( (int) get_option( 'mjc_rewards_icons_version', 0 ) >= 1 ) {
			return;
		}
		$pending = false;
		$ids     = get_posts( array(
			'post_type'      => 'mjc_reward',
			'post_status'    => 'any',
			'numberposts'    => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		) );
		foreach ( $ids as $pid ) {
			if ( get_post_thumbnail_id( (int) $pid ) ) {
				continue;
			}
			$icon = (string) get_post_meta( (int) $pid, 'mjc_icon', true );
			if ( '' === $icon ) {
				continue;
			}
			if ( ! self::attach_icon( (int) $pid, $icon ) ) {
				$pending = true;
			}
		}
		if ( ! $pending ) {
			update_option( 'mjc_rewards_icons_version', 1 );
		}
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
