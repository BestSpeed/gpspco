<?php
/**
 * ماژول بانک دانش.
 *
 * @package mojrian-club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * کلاس بانک دانش.
 */
class MJC_Knowledge {

	/**
	 * هوک‌ها.
	 */
	public static function hooks() {
		add_action( 'init', array( __CLASS__, 'register_cpt' ) );
	}

	/**
	 * ثبت نوع محتوا و دسته‌ها.
	 */
	public static function register_cpt() {
		register_post_type( 'mjc_knowledge', array(
			'labels'       => array(
				'name'          => 'بانک دانش',
				'singular_name' => 'مطلب',
				'add_new_item'  => 'افزودن مطلب جدید',
				'edit_item'     => 'ویرایش مطلب',
			),
			'public'       => true,
			'rewrite'      => array( 'slug' => 'knowledge' ),
			'show_ui'      => true,
			'show_in_menu' => 'mjc-dashboard',
			'menu_icon'    => 'dashicons-book-alt',
			'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
			'has_archive'  => false,
		) );
		register_taxonomy( 'mjc_knowledge_cat', 'mjc_knowledge', array(
			'labels'       => array( 'name' => 'دسته‌های بانک دانش', 'singular_name' => 'دسته' ),
			'hierarchical' => true,
			'rewrite'      => array( 'slug' => 'knowledge-cat' ),
			'show_ui'      => true,
			'show_in_menu' => 'mjc-dashboard',
		) );
	}

	/**
	 * فهرست مطالب با جست‌وجو و دسته.
	 *
	 * @param array $args آرگومان‌ها.
	 * @return array
	 */
	public static function search( $args = array() ) {
		$defaults = array(
			's'          => '',
			'category'   => '',
			'paged'      => 1,
			'per_page'   => 9,
		);
		$args = wp_parse_args( $args, $defaults );

		$query = array(
			'post_type'      => 'mjc_knowledge',
			'post_status'    => 'publish',
			'posts_per_page' => (int) $args['per_page'],
			'paged'          => max( 1, (int) $args['paged'] ),
		);
		if ( $args['s'] ) {
			$query['s'] = sanitize_text_field( $args['s'] );
		}
		if ( $args['category'] ) {
			$query['tax_query'] = array( array(
				'taxonomy' => 'mjc_knowledge_cat',
				'field'    => 'slug',
				'terms'    => sanitize_title( $args['category'] ),
			) );
		}
		return new WP_Query( $query );
	}
}
