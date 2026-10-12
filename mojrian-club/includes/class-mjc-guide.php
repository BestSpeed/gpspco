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
		if ( (int) get_option( 'mjc_guide_seed_version' ) >= 3 ) {
			return;
		}

		$cat_register = wp_insert_term( 'راهنمای ثبت‌نام و حساب کاربری', 'mjc_guide_cat' );
		$cat_points   = wp_insert_term( 'کسب امتیاز و پروژه‌ها', 'mjc_guide_cat' );
		$cat_rewards  = wp_insert_term( 'تبدیل امتیازات به جوایز', 'mjc_guide_cat' );

		$faq_cat_main = wp_insert_term( 'سوالات عمومی', 'mjc_faq_cat' );
		$faq_cat_acc  = wp_insert_term( 'حساب و امنیت', 'mjc_faq_cat' );

		$articles = array(
			array(
				'title'   => 'آشنایی با حساب کاربری باشگاه مجریان',
				'cat'     => is_wp_error( $cat_register ) ? 0 : $cat_register['term_id'],
				'content' => '<p>پس از ورود به حساب کاربری، صفحه‌ی «حساب کاربری باشگاه» برای شما باز می‌شود. در بالای این صفحه، <strong>فعالیت‌های شما</strong> (امتیاز دریافتی و فعلی) نمایش داده می‌شود و از طریق کلیدهای کنار صفحه به همه‌ی بخش‌ها دسترسی دارید:</p>
<ul>
<li><strong>تکمیل اطلاعات کاربر:</strong> مشاهده و ویرایش اطلاعات هویتی/پستی + تصویر پروفایل</li>
<li><strong>ثبت پروژه تاسیسات:</strong> ثبت پروژه‌ی اجرایی و پیگیری وضعیت آن</li>
<li><strong>تاریخچه امتیاز:</strong> ریز کسب‌وکار امتیازها</li>
<li><strong>تبدیل امتیازات به جوایز:</strong> خرج کردن امتیازها در فروشگاه باشگاه</li>
<li><strong>کمپین‌ها، معرفی دوستان و اعلان‌ها</strong></li>
</ul>',
			),
			array(
				'title'   => 'گام‌به‌گام ثبت‌نام در باشگاه مجریان',
				'cat'     => is_wp_error( $cat_register ) ? 0 : $cat_register['term_id'],
				'content' => '<p>۱. در صفحه‌ی حساب کاربری، تب «ثبت‌نام» را انتخاب کنید.</p>
<p>۲. شماره موبایل فعال خود را وارد کنید و «ارسال کد تأیید» را بزنید؛ کد یک‌بارمصرف برای شما پیامک می‌شود.</p>
<p>۳. اطلاعات هویتی (نام، کد ملی)، استان و شهر، نوع فعالیت (آب و فاضلاب، گاز، شوفاژ و…) و سابقه‌ی کار را تکمیل کنید.</p>
<p>۴. رمز عبور تعریف کنید، قوانین را بپذیرید و «ثبت‌نام در باشگاه» را بزنید.</p>
<p>۵. درخواست شما توسط مدیر باشگاه بررسی می‌شود؛ نتیجه از طریق پیامک و بخش «اعلان‌ها» اطلاع‌رسانی می‌شود.</p>
<p class="mjc-tip">نکته: فقط مجریان تاسیساتی می‌توانند عضو شوند؛ پس از تأیید، امتیاز خوش‌آمدگویی به حساب شما اضافه می‌شود.</p>',
			),
			array(
				'title'   => 'چگونه امتیاز کسب کنیم؟ (ثبت پروژه و فعالیت‌ها)',
				'cat'     => is_wp_error( $cat_points ) ? 0 : $cat_points['term_id'],
				'content' => '<p>مهم‌ترین راه کسب امتیاز، <strong>ثبت پروژه‌ی اجرایی</strong> است. در بخش «پروژه‌های من»، عنوان پروژه، نوع آن (مسکونی، تجاری و…)، استان/شهر، <strong>مبلغ پروژه (ریال)</strong> و <strong>متراژ (متر مربع)</strong>، محصولات استفاده‌شده، تاریخ اجرا و تصاویر پروژه را وارد کنید.</p>
<p>پس از تأیید پروژه توسط باشگاه، امتیاز به‌صورت خودکار اعمال می‌شود. بسته به تنظیمات باشگاه، امتیاز هر پروژه می‌تواند <strong>ثابت</strong>، بر اساس <strong>متراژ</strong> یا بر اساس <strong>مبلغ پروژه</strong> محاسبه شود (میزان دقیق در همین صفحه‌ی «تاریخچه امتیاز» قابل مشاهده است).</p>
<p>راه‌های دیگر کسب امتیاز: تکمیل پروفایل، شرکت در کمپین‌ها و معرفی دوستان.</p>',
			),
			array(
				'title'   => 'تبدیل امتیازات به جوایز — راهنمای خرید جایزه',
				'cat'     => is_wp_error( $cat_rewards ) ? 0 : $cat_rewards['term_id'],
				'content' => '<p>در بخش «تبدیل امتیازات به جوایز»، فهرست جوایز باشگاه (کارت هدیه نقدی، ابزارآلات تخصصی، بیمه تکمیلی، بن خرید و…) همراه با هزینه‌ی امتیازی هر کدام نمایش داده می‌شود.</p>
<ol>
<li>جایزه‌ی مورد نظر را انتخاب کنید.</li>
<li>نشانی ارسال را وارد و «درخواست جایزه» را بزنید.</li>
<li>امتیاز هزینه‌ی جایزه از حساب شما کسر و درخواست «در انتظار بررسی» می‌شود.</li>
<li>پس از تأیید و ارسال توسط باشگاه، وضعیت در همین صفحه به‌روزرسانی می‌شود.</li>
</ol>
<p class="mjc-tip">اگر درخواست شما رد شود، امتیاز به‌صورت خودکار به حساب شما بازگردانده می‌شود.</p>',
			),
		);

		// پاک‌سازی مقاله‌ی قدیمی «پایه فنی» (قابلیت حذف شده) در نصب‌های قبلی.
		$stale = get_page_by_path( sanitize_title( 'پایه فنی چیست و چگونه استعلام بگیریم؟' ), OBJECT, 'mjc_guide' );
		if ( $stale ) {
			wp_delete_post( (int) $stale->ID, true );
		}

		foreach ( $articles as $i => $article ) {
			// جلوگیری از درج تکراری.
			$existing = get_page_by_path( sanitize_title( $article['title'] ), OBJECT, 'mjc_guide' );
			if ( $existing ) {
				continue;
			}
			$post_id = wp_insert_post( array(
				'post_type'    => 'mjc_guide',
				'post_status'  => 'publish',
				'post_title'   => $article['title'],
				'post_content' => $article['content'],
				'menu_order'   => $i + 1,
			) );
			if ( $post_id && ! is_wp_error( $post_id ) && $article['cat'] ) {
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
				'content' => '<p>در صفحه‌ی «ورود» گزینه‌ی «فراموشی رمز عبور» را انتخاب کنید. با شماره موبایل خود کد یک‌بارمصرف دریافت و رمز جدید تعریف می‌کنید.</p>',
			),
			array(
				'title'   => 'امتیاز هر پروژه چگونه محاسبه می‌شود؟',
				'cat'     => is_wp_error( $faq_cat_main ) ? 0 : $faq_cat_main['term_id'],
				'content' => '<p>بسته به تنظیمات باشگاه، امتیاز پروژه به‌صورت ثابت یا بر اساس متراژ یا مبلغ پروژه محاسبه می‌شود. مقدار امتیاز اعمال‌شده برای هر پروژه در «پروژه‌های من» و «تاریخچه امتیاز» نمایش داده می‌شود.</p>',
			),
			array(
				'title'   => 'تأیید ثبت‌نام چقدر طول می‌کشد؟',
				'cat'     => is_wp_error( $faq_cat_main ) ? 0 : $faq_cat_main['term_id'],
				'content' => '<p>درخواست‌ها به‌صورت روزانه بررسی می‌شوند و نتیجه معمولاً ظرف حداکثر ۷۲ ساعت از طریق پیامک اطلاع‌رسانی می‌شود.</p>',
			),
			array(
				'title'   => 'چگونه امتیازهایم را خرج کنم؟',
				'cat'     => is_wp_error( $faq_cat_acc ) ? 0 : $faq_cat_acc['term_id'],
				'content' => '<p>به بخش «تبدیل امتیازات به جوایز» در حساب کاربری بروید، جایزه را انتخاب و درخواست دهید. امتیاز کسر و پس از تأیید، جایزه برای شما ارسال می‌شود.</p>',
			),
		);

		foreach ( $faqs as $i => $faq ) {
			$existing = get_page_by_path( sanitize_title( $faq['title'] ), OBJECT, 'mjc_faq' );
			if ( $existing ) {
				continue;
			}
			$post_id = wp_insert_post( array(
				'post_type'    => 'mjc_faq',
				'post_status'  => 'publish',
				'post_title'   => $faq['title'],
				'post_content' => $faq['content'],
				'menu_order'   => $i + 1,
			) );
			if ( $post_id && ! is_wp_error( $post_id ) && $faq['cat'] ) {
				wp_set_object_terms( $post_id, array( (int) $faq['cat'] ), 'mjc_faq_cat' );
			}
		}

		update_option( 'mjc_guide_seed_version', 3 );
		update_option( 'mjc_guide_seeded', 1 );
	}
}
