<?php
/**
 * جعبه‌های متای CPT های افزونه (جوایز، دوره‌ها، نظرسنجی، کمپین).
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
		add_meta_box( 'mjc_course_meta', 'مشخصات دوره', array( __CLASS__, 'course_box' ), 'mjc_course', 'normal', 'high' );
		add_meta_box( 'mjc_survey_meta', 'پرسش‌های نظرسنجی (JSON)', array( __CLASS__, 'survey_box' ), 'mjc_survey', 'normal', 'high' );
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
		$cost  = get_post_meta( $post->ID, 'mjc_points_cost', true );
		$stock = get_post_meta( $post->ID, 'mjc_stock', true );
		echo '<p><label>هزینه (امتیاز): <input type="number" name="mjc_points_cost" value="' . esc_attr( $cost ) . '" /></label></p>';
		echo '<p><label>موجودی: <input type="number" name="mjc_stock" value="' . esc_attr( $stock ) . '" /></label></p>';
	}

	/**
	 * جعبه‌ی دوره.
	 *
	 * @param WP_Post $post پست.
	 */
	public static function course_box( $post ) {
		wp_nonce_field( 'mjc_meta', 'mjc_meta_nonce' );
		$duration = get_post_meta( $post->ID, 'mjc_duration', true );
		$points   = get_post_meta( $post->ID, 'mjc_course_points', true );
		echo '<p><label>مدت دوره (ساعت): <input type="number" name="mjc_duration" value="' . esc_attr( $duration ) . '" /></label></p>';
		echo '<p><label>امتیاز تکمیل دوره (اختیاری؛ اگر خالی باشد از قوانین امتیاز استفاده می‌شود): <input type="number" name="mjc_course_points" value="' . esc_attr( $points ) . '" /></label></p>';
		echo '<p class="description">برای تکمیل دوره‌ی هر کاربر، از فهرست «دوره‌ها» در پنل باشگاه اقدام کنید.</p>';
	}

	/**
	 * جعبه‌ی نظرسنجی.
	 *
	 * @param WP_Post $post پست.
	 */
	public static function survey_box( $post ) {
		wp_nonce_field( 'mjc_meta', 'mjc_meta_nonce' );
		$questions = get_post_meta( $post->ID, 'mjc_questions', true );
		echo '<p>پرسش‌ها را به‌صورت JSON وارد کنید:</p>';
		echo '<textarea name="mjc_questions" rows="8" style="width:100%;direction:ltr;font-family:monospace;">' . esc_textarea( $questions ) . '</textarea>';
		echo '<p class="description">نمونه: [{"q":"از خدمات باشگاه راضی هستید؟","type":"radio","options":["بله","خیر","تا حدی"]},{"q":"پیشنهاد شما؟","type":"text"}]</p>';
	}

	/**
	 * جعبه‌ی کمپین.
	 *
	 * @param WP_Post $post پست.
	 */
	public static function campaign_box( $post ) {
		wp_nonce_field( 'mjc_meta', 'mjc_meta_nonce' );
		$end     = get_post_meta( $post->ID, 'mjc_end_date', true );
		$winners = get_post_meta( $post->ID, 'mjc_winners', true );
		echo '<p><label>تاریخ پایان (میلادی YYYY-MM-DD): <input type="text" name="mjc_end_date" value="' . esc_attr( $end ) . '" placeholder="2026-12-31" /></label></p>';
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
			'mjc_course_points'=> 'intval',
			'mjc_end_date'     => 'sanitize_text_field',
		);
		foreach ( $fields as $key => $sanitize ) {
			if ( isset( $_POST[ $key ] ) ) {
				update_post_meta( $post_id, $key, call_user_func( $sanitize, wp_unslash( $_POST[ $key ] ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
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
