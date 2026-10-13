<?php
/**
 * جعبه‌های متای CPT های افزونه (جوایز، کمپین).
 *
 * @package mojrian-club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * کلاس متا باکس‌ها.
 */
class MJC_Metaboxes {

	/**
	 * هوک‌ها.
	 */
	public static function hooks() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add' ) );
		add_action( 'save_post', array( __CLASS__, 'save' ) );
	}

	/**
	 * افزودن جعبه‌های متا.
	 */
	public static function add() {
		add_meta_box( 'mjc_reward_meta', 'مشخصات جایزه', array( __CLASS__, 'reward_box' ), 'mjc_reward', 'normal', 'high' );
		add_meta_box( 'mjc_campaign_meta', 'مشخصات کمپین', array( __CLASS__, 'campaign_box' ), 'mjc_campaign', 'normal', 'high' );
		add_meta_box( 'mjc_guide_meta', 'گزینه‌های راهنما', array( __CLASS__, 'guide_box' ), array( 'mjc_guide', 'mjc_faq' ), 'side', 'default' );
	}

	/**
	 * جعبه‌ی جایزه.
	 *
	 * @param WP_Post $post پست.
	 */
	public static function reward_box( $post ) {
		wp_nonce_field( 'mjc_meta', 'mjc_meta_nonce' );
		$cost     = get_post_meta( $post->ID, 'mjc_points_cost', true );
		$stock    = get_post_meta( $post->ID, 'mjc_stock', true );
		$icon_url = MJC_Rewards::icon_url( $post );
		$icon_path = (string) get_post_meta( $post->ID, 'mjc_icon', true );
		echo '<div class="mjc-reward-icon-wrap" style="display:flex;gap:14px;align-items:flex-start;">';
		if ( $icon_url ) {
			echo '<img src="' . esc_url( $icon_url ) . '" alt="" style="width:96px;height:96px;object-fit:contain;border:1px solid #ccd0d4;border-radius:10px;background:#fff;padding:6px;" />';
		} else {
			echo '<div style="width:96px;height:96px;border:1px dashed #ccd0d4;border-radius:10px;display:flex;align-items:center;justify-content:center;color:#8c8f94;font-size:12px;text-align:center;">بدون<br>تصویر</div>';
		}
		echo '<div style="flex:1;">';
		echo '<p style="margin:0 0 6px;"><label>هزینه (امتیاز): <input type="number" name="mjc_points_cost" value="' . esc_attr( $cost ) . '" /></label></p>';
		echo '<p style="margin:0 0 6px;"><label>موجودی: <input type="number" name="mjc_stock" value="' . esc_attr( $stock ) . '" /></label></p>';
		echo '<p style="margin:0 0 6px;"><label>آیکون آماده (برش‌خورده از ماکت): <select name="mjc_icon_preset"><option value="">— بدون تغییر —</option>';
		foreach ( MJC_Rewards::icon_catalog() as $path => $label ) {
			echo '<option value="' . esc_attr( $path ) . '" ' . selected( $icon_path, $path, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select></label></p>';
		echo '<p class="description" style="margin:0;">برای تصویر دلخواه از جعبه «تصویر شاخص» استفاده کنید. با انتخاب آیکون آماده و ذخیره، همان آیکون به‌عنوان تصویر شاخص جایزه نیز ثبت می‌شود.</p>';
		echo '</div></div>';
	}	/**
	 * جعبه‌ی کمپین.
	 *
	 * @param WP_Post $post پست.
	 */
	public static function campaign_box( $post ) {
		wp_nonce_field( 'mjc_meta', 'mjc_meta_nonce' );
		$end     = get_post_meta( $post->ID, 'mjc_end_date', true );
		$end_fa  = $end ? MJC_Jalali::datepicker_value( $end ) : '';
		$winners = get_post_meta( $post->ID, 'mjc_winners', true );
		echo '<p><label>تاریخ پایان (شمسی YYYY/MM/DD): <input type="text" name="mjc_end_date" value="' . esc_attr( $end_fa ) . '" placeholder="1404/12/29" dir="ltr" style="width:130px;" /></label></p>';
		echo '<p class="description">تاریخ پایان کمپین را به شمسی وارد کنید (مثال: 1404/12/29).</p>';
		if ( $winners ) {
			$ids = json_decode( (string) $winners, true );
			echo '<p><strong>برندگان:</strong> ';
			foreach ( (array) $ids as $uid ) {
				$u = get_userdata( (int) $uid );
				echo '<span class="button button-small">' . esc_html( $u ? $u->display_name : $uid ) . '</span> ';
			}
			echo '</p>';
		}
		echo '<p class="description">برای انجام قرعه‌کشی از فهرست «کمپین‌ها» در پنل باشگاه (دکمه‌ی قرعه‌کشی) استفاده کنید.</p>';
	}

	/**
	 * جعبه‌ی راهنما.
	 *
	 * @param WP_Post $post پست.
	 */
	public static function guide_box( $post ) {
		wp_nonce_field( 'mjc_meta', 'mjc_meta_nonce' );
		$toc = get_post_meta( $post->ID, 'mjc_show_toc', true );
		echo '<label><input type="checkbox" name="mjc_show_toc" value="1" ' . checked( $toc, '1', false ) . ' /> نمایش فهرست مطالب (TOC)</label>';
	}

	/**
	 * ذخیره‌ی متاها.
	 *
	 * @param int $post_id شناسه پست.
	 */
	public static function save( $post_id ) {
		if ( ! isset( $_POST['mjc_meta_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['mjc_meta_nonce'] ), 'mjc_meta' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$fields = array(
			'mjc_points_cost'  => 'intval',
			'mjc_stock'        => 'intval',
			'mjc_duration'     => 'intval',
		);
		// تاریخ پایان کمپین به شمسی دریافت و به میلادی ذخیره می‌شود (سازگاری با ورودی میلادی قدیمی).
		if ( isset( $_POST['mjc_end_date'] ) ) {
			$raw_end = sanitize_text_field( wp_unslash( $_POST['mjc_end_date'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			$raw_en  = mjc_to_en_digits( trim( $raw_end ) );
			$end     = '';
			if ( preg_match( '/^(\d{4})[-\/.](\d{1,2})[-\/.](\d{1,2})$/', $raw_en, $m ) && (int) $m[1] >= 1700 ) {
				// ورودی میلادی (سازگاری با مقادیر قدیمی).
				$end = sprintf( '%04d-%02d-%02d', (int) $m[1], (int) $m[2], (int) $m[3] );
			} else {
				$end = MJC_Jalali::parse_input( $raw_end );
			}
			update_post_meta( $post_id, 'mjc_end_date', $end );
		}
		foreach ( $fields as $key => $sanitize ) {
			if ( isset( $_POST[ $key ] ) ) {
				update_post_meta( $post_id, $key, call_user_func( $sanitize, wp_unslash( $_POST[ $key ] ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			}
		}
		if ( isset( $_POST['mjc_icon_preset'] ) ) {
			$preset = sanitize_text_field( wp_unslash( $_POST['mjc_icon_preset'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			if ( '' !== $preset && isset( MJC_Rewards::icon_catalog()[ $preset ] ) ) {
				update_post_meta( $post_id, 'mjc_icon', $preset );
				MJC_Rewards::attach_icon( (int) $post_id, $preset );
			}
		}
		if ( isset( $_POST['mjc_questions'] ) ) {
			$raw = wp_unslash( $_POST['mjc_questions'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			$decoded = json_decode( (string) $raw, true );
			update_post_meta( $post_id, 'mjc_questions', $decoded ? wp_json_encode( $decoded, JSON_UNESCAPED_UNICODE ) : '' );
		}
		if ( isset( $_POST['mjc_show_toc'] ) ) {
			update_post_meta( $post_id, 'mjc_show_toc', '1' );
		} else {
			delete_post_meta( $post_id, 'mjc_show_toc' );
		}
	}
}
