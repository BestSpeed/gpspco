<?php
/**
 * ماژول «راهنمای باشگاه»: مقالات راهنما، سوالات متداول و قالب اختصاصی.
 *
 * @package mojrian-club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * کلاس راهنما.
 */
class MJC_Guide {

	/**
	 * هوک‌ها.
	 */
	public static function hooks() {
		add_action( 'init', array( __CLASS__, 'register_cpts' ) );
		add_filter( 'template_include', array( __CLASS__, 'template' ) );
		add_filter( 'body_class', array( __CLASS__, 'body_class' ) );
	}

	/**
	 * ثبت نوع‌های محتوا.
	 */
	public static function register_cpts() {
		register_post_type( 'mjc_guide', array(
			'labels'       => array(
				'name'          => 'راهنمای باشگاه',
				'singular_name' => 'مقاله راهنما',
				'add_new_item'  => 'افزودن مقاله راهنما',
				'edit_item'     => 'ویرایش مقاله راهنما',
			),
			'public'       => true,
			'rewrite'      => array( 'slug' => 'guide' ),
			'show_ui'      => true,
			'show_in_menu' => 'mjc-dashboard',
			'menu_icon'    => 'dashicons-book',
			'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes' ),
			'has_archive'  => false,
		) );
		register_taxonomy( 'mjc_guide_cat', 'mjc_guide', array(
			'labels'       => array( 'name' => 'دسته‌های راهنما', 'singular_name' => 'دسته راهنما' ),
			'hierarchical' => true,
			'rewrite'      => array( 'slug' => 'guide-cat' ),
			'show_ui'      => true,
			'show_in_menu' => 'mjc-dashboard',
		) );

		register_post_type( 'mjc_faq', array(
			'labels'       => array(
				'name'          => 'سوالات متداول',
				'singular_name' => 'سوال',
				'add_new_item'  => 'افزودن سوال جدید',
				'edit_item'     => 'ویرایش سوال',
			),
			'public'       => true,
			'rewrite'      => array( 'slug' => 'faq-item' ),
			'show_ui'      => true,
			'show_in_menu' => 'mjc-dashboard',
			'menu_icon'    => 'dashicons-editor-help',
			'supports'     => array( 'title', 'editor', 'page-attributes' ),
			'has_archive'  => false,
		) );
		register_taxonomy( 'mjc_faq_cat', 'mjc_faq', array(
			'labels'       => array( 'name' => 'دسته‌های سوالات', 'singular_name' => 'دسته سوال' ),
			'hierarchical' => true,
			'rewrite'      => array( 'slug' => 'faq-cat' ),
			'show_ui'      => true,
			'show_in_menu' => 'mjc-dashboard',
		) );
	}

	/**
	 * قالب اختصاصی تک‌مطلب راهنما/سوال.
	 *
	 * @param string $template مسیر قالب.
	 * @return string
	 */
	public static function template( $template ) {
		if ( is_singular( array( 'mjc_guide', 'mjc_faq' ) ) ) {
			$plugin_tpl = MJC_PLUGIN_DIR . 'public/templates/single-guide.php';
			if ( file_exists( $plugin_tpl ) ) {
				return $plugin_tpl;
			}
		}
		return $template;
	}

	/**
	 * کلاس بدنه برای استایل‌دهی.
	 *
	 * @param array $classes کلاس‌ها.
	 * @return array
	 */
	public static function body_class( $classes ) {
		if ( is_singular( array( 'mjc_guide', 'mjc_faq' ) ) ) {
			$classes[] = 'mjc-guide-single';
		}
		return $classes;
	}

	/**
	 * مقالات راهنما.
	 *
	 * @param string $term_slug اسلاگ دسته (اختیاری).
	 * @param string $search جست‌وجو (اختیاری).
	 * @return array
	 */
	public static function articles( $term_slug = '', $search = '' ) {
		$args = array(
			'post_type'      => 'mjc_guide',
			'post_status'    => 'publish',
			'posts_per_page' => 50,
			'orderby'        => 'menu_order date',
			'order'          => 'ASC',
		);
		if ( $term_slug ) {
			$args['tax_query'] = array( array(
				'taxonomy' => 'mjc_guide_cat',
				'field'    => 'slug',
				'terms'    => $term_slug,
			) );
		}
		if ( $search ) {
			$args['s'] = $search;
		}
		return get_posts( $args );
	}

	/**
	 * سوالات متداول.
	 *
	 * @param string $term_slug اسلاگ دسته.
	 * @return array
	 */
	public static function faqs( $term_slug = '' ) {
		$args = array(
			'post_type'      => 'mjc_faq',
			'post_status'    => 'publish',
			'posts_per_page' => 100,
			'orderby'        => 'menu_order date',
			'order'          => 'ASC',
		);
		if ( $term_slug ) {
			$args['tax_query'] = array( array(
				'taxonomy' => 'mjc_faq_cat',
				'field'    => 'slug',
				'terms'    => $term_slug,
			) );
		}
		return get_posts( $args );
	}

	/**
	 * دسته‌های راهنما.
	 *
	 * @return array
	 */
	public static function guide_categories() {
		return get_terms( array( 'taxonomy' => 'mjc_guide_cat', 'hide_empty' => true ) );
	}

	/**
	 * محتوای اولیه‌ی راهنما و سوالات (هنگام فعال‌سازی).
	 */
	public static function seed() {
		if ( get_option( 'mjc_guide_seeded' ) ) {
			return;
		}

		$cat_register = wp_insert_term( 'راهنمای ثبت‌نام', 'mjc_guide_cat' );
		$cat_points   = wp_insert_term( 'کسب و مصرف امتیاز', 'mjc_guide_cat' );
		$cat_project  = wp_insert_term( 'ثبت پروژه', 'mjc_guide_cat' );
		$cat_grade    = wp_insert_term( 'پایه فنی و استعلام', 'mjc_guide_cat' );

		$faq_cat_main = wp_insert_term( 'سوالات عمومی', 'mjc_faq_cat' );
		$faq_cat_acc  = wp_insert_term( 'حساب و امنیت', 'mjc_faq_cat' );

		$articles = array(
			array(
				'title'   => 'گام‌به‌گام ثبت‌نام در باشگاه مجریان',
				'cat'     => is_wp_error( $cat_register ) ? 0 : $cat_register['term_id'],
				'content' => '<p>برای عضویت در باشگاه مجریان مراحل زیر را دنبال کنید:</p>
<ol>
<li>از منوی سایت گزینه «ثبت‌نام» را انتخاب کنید.</li>
<li>شماره موبایل فعال خود را وارد کنید؛ کد تأیید (OTP) برای شما ارسال می‌شود.</li>
<li>اطلاعات هویتی شامل نام و نام خانوادگی، کد ملی، استان و شهر، نوع فعالیت و سابقه را تکمیل کنید.</li>
<li>در صورت داشتن کد معرف، آن را در فرم وارد کنید تا معرف شما نیز امتیاز دریافت کند.</li>
<li>پس از ثبت، درخواست شما توسط مدیر باشگاه بررسی و نتیجه از طریق پیامک و اعلان باشگاه اطلاع‌رسانی می‌شود.</li>
</ol>
<p class="mjc-tip">نکته: فقط مجریان تاسیساتی (آب و فاضلاب، گاز، شوفاژ و …) می‌توانند عضو شوند؛ ثبت‌نام فروشندگان و افراد غیرمرتبط تأیید نمی‌شود.</p>',
			),
			array(
				'title'   => 'چگونه امتیاز کسب کنیم؟',
				'cat'     => is_wp_error( $cat_points ) ? 0 : $cat_points['term_id'],
				'content' => '<p>امتیاز باشگاه (گیتا) با فعالیت‌های زیر قابل کسب است:</p>
<ul>
<li><strong>تکمیل پروفایل:</strong> پس از تکمیل اطلاعات هویتی و حرفه‌ای.</li>
<li><strong>ثبت پروژه:</strong> با تأیید هر پروژه‌ی اجرایی توسط باشگاه.</li>
<li><strong>شرکت در دوره‌های آموزشی:</strong> پس از تکمیل دوره.</li>
<li><strong>مشارکت در نظرسنجی‌ها و کمپین‌ها.</strong></li>
<li><strong>معرفی دوستان:</strong> پس از تأیید عضویت فرد معرفی‌شده.</li>
</ul>
<p>تاریخچه‌ی کامل امتیازها در صفحه «تاریخچه امتیاز» قابل مشاهده است.</p>',
			),
			array(
				'title'   => 'راهنمای ثبت پروژه و دریافت امتیاز',
				'cat'     => is_wp_error( $cat_project ) ? 0 : $cat_project['term_id'],
				'content' => '<p>برای ثبت پروژه وارد داشبورد شوید و «ثبت پروژه» را انتخاب کنید. عنوان پروژه، نوع (مسکونی، تجاری و …)، استان/شهر، تعداد واحد، محصولات استفاده‌شده، تاریخ شروع اجرا و تصاویر پروژه را وارد کنید.</p>
<p>پروژه‌ها معمولاً طی چند روز کاری بررسی می‌شوند؛ پس از تأیید، امتیاز مربوطه به‌صورت خودکار اعمال می‌شود. در صورت رد شدن، دلیل در بخش «پروژه‌های من» نمایش داده می‌شود.</p>',
			),
			array(
				'title'   => 'پایه فنی چیست و چطور استعلام بگیریم؟',
				'cat'     => is_wp_error( $cat_grade ) ? 0 : $cat_grade['term_id'],
				'content' => '<p>پایه فنی، سنجش مهارت علمی و عملی مجریان است که پس از ارزیابی، سطح شما (پایه ۳ تا طلایی) تعیین می‌شود. برای درخواست، از داشبورد «درخواست پایه فنی» را انتخاب کنید.</p>
<p>پس از ارزیابی، کد استعلام برای شما صادر می‌شود. کارفرمایان می‌توانند از صفحه «استعلام فنی مجریان» با کد ملی یا موبایل مجری، اصالت پایه فنی او را بررسی کنند.</p>',
			),
		);

		foreach ( $articles as $i => $article ) {
			$post_id = wp_insert_post( array(
				'post_type'    => 'mjc_guide',
				'post_status'  => 'publish',
				'post_title'   => $article['title'],
				'post_content' => $article['content'],
				'menu_order'   => $i + 1,
			) );
			if ( $post_id && $article['cat'] ) {
				wp_set_object_terms( $post_id, array( (int) $article['cat'] ), 'mjc_guide_cat' );
			}
		}

		$faqs = array(
			array(
				'title'   => 'چه کسانی می‌توانند در باشگاه عضو شوند؟',
				'cat'     => is_wp_error( $faq_cat_main ) ? 0 : $faq_cat_main['term_id'],
				'content' => '<p>این باشگاه ویژه‌ی مجریان تاسیسات مکانیکی (آب و فاضلاب، گاز، شوفاژ و تهویه) است. فروشندگان، نمایندگی‌ها و افراد غیرمرتبط تأیید نمی‌شوند.</p>',
			),
			array(
				'title'   => 'رمز عبورم را فراموش کرده‌ام؛ چه کنم؟',
				'cat'     => is_wp_error( $faq_cat_acc ) ? 0 : $faq_cat_acc['term_id'],
				'content' => '<p>در صفحه «ورود» گزینه «فراموشی رمز عبور» را انتخاب کنید. با شماره موبایل خود کد یک‌بارمصرف دریافت و رمز جدید تعریف می‌کنید.</p>',
			),
			array(
				'title'   => 'تأیید ثبت‌نام چقدر طول می‌کشد؟',
				'cat'     => is_wp_error( $faq_cat_main ) ? 0 : $faq_cat_main['term_id'],
				'content' => '<p>درخواست‌ها به‌صورت روزانه بررسی می‌شوند و نتیجه معمولاً ظرف حداکثر ۷۲ ساعت از طریق پیامک اطلاع‌رسانی می‌شود.</p>',
			),
			array(
				'title'   => 'امتیازها تا چه زمانی معتبرند؟',
				'cat'     => is_wp_error( $faq_cat_acc ) ? 0 : $faq_cat_acc['term_id'],
				'content' => '<p>امتیازهای باشگاه تا پایان عضویت معتبرند و در «فروشگاه امتیاز» قابل تبدیل به جوایز نقدی و غیرنقدی هستند.</p>',
			),
		);

		foreach ( $faqs as $i => $faq ) {
			$post_id = wp_insert_post( array(
				'post_type'    => 'mjc_faq',
				'post_status'  => 'publish',
				'post_title'   => $faq['title'],
				'post_content' => $faq['content'],
				'menu_order'   => $i + 1,
			) );
			if ( $post_id && $faq['cat'] ) {
				wp_set_object_terms( $post_id, array( (int) $faq['cat'] ), 'mjc_faq_cat' );
			}
		}

		update_option( 'mjc_guide_seeded', 1 );
	}
}
