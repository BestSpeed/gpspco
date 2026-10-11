<?php
/**
 * شورت‌کدها و قالب‌های سمت کاربر.
 *
 * @package mojrian-club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * کلاس شورت‌کدها.
 */
class MJC_Shortcodes {

	/**
	 * هوک‌ها.
	 */
	public static function hooks() {
		$map = array(
			'mojrian_account'       => 'render_account',
			'mojrian_home'          => 'render_home',
			'mojrian_login'         => 'render_login',
			'mojrian_register'      => 'render_register',
			'mojrian_dashboard'     => 'render_dashboard',
			'mojrian_profile'       => 'render_profile',
			'mojrian_projects'      => 'render_projects',
			'mojrian_points'        => 'render_points',
			'mojrian_rewards'       => 'render_rewards',
			'mojrian_courses'       => 'render_courses',
			'mojrian_knowledge'     => 'render_knowledge',
			'mojrian_surveys'       => 'render_surveys',
			'mojrian_campaigns'     => 'render_campaigns',
			'mojrian_referral'      => 'render_referral',
			'mojrian_wallet'        => 'render_wallet',
			'mojrian_card'          => 'render_card',
			'mojrian_notifications' => 'render_notifications',
			'mojrian_guide'         => 'render_guide',
			'mojrian_faq'           => 'render_faq',
			'mojrian_inquiry'       => 'render_inquiry',
			'mojrian_contact'       => 'render_contact',
		);
		foreach ( $map as $tag => $method ) {
			add_shortcode( $tag, array( __CLASS__, $method ) );
		}
	}

	/* -------------------------------------------------------------------
	 * ابزارهای رندر
	 * ---------------------------------------------------------------- */

	/**
	 * اسکلت صفحه‌ی عضو (با منوی اعضا و بررسی دسترسی).
	 *
	 * @param string $content محتوا.
	 * @param bool   $need_active نیاز به عضو تأییدشده.
	 * @return string
	 */
	private static function member_layout( $content, $need_active = true ) {
		ob_start();
		echo '<div class="mjc-wrap">';
		mjc_render_notice();
		if ( ! is_user_logged_in() ) {
			echo '<div class="mjc-card mjc-center"><p>' . esc_html__( 'برای دسترسی به این بخش ابتدا وارد شوید.', 'mojrian-club' ) . '</p>';
			echo '<a class="mjc-btn mjc-btn-primary" href="' . esc_url( mjc_page_url( 'login' ) ) . '">' . esc_html__( 'ورود به باشگاه', 'mojrian-club' ) . '</a></div>';
			echo '</div>';
			return ob_get_clean();
		}
		if ( $need_active && ! mjc_is_active_member() && ! current_user_can( 'mjc_manage_club' ) ) {
			$member = mjc_member();
			if ( $member && 'rejected' === $member->status ) {
				echo '<div class="mjc-card"><div class="mjc-empty">متأسفانه درخواست عضویت شما پذیرفته نشد.' . ( $member->reject_reason ? ' دلیل: ' . esc_html( $member->reject_reason ) : '' ) . '</div></div>';
			} else {
				echo '<div class="mjc-card"><div class="mjc-empty">حساب شما در انتظار تأیید مدیر باشگاه است. پس از تأیید، همه‌ی امکانات فعال می‌شود.</div></div>';
			}
			echo '</div>';
			return ob_get_clean();
		}
		echo self::member_nav();
		echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML داخلی.
		echo '</div>';
		return ob_get_clean();
	}

	/**
	 * منوی تب‌های اعضا.
	 *
	 * @return string
	 */
	public static function member_nav() {
		$items = array(
			'dashboard'     => 'داشبورد',
			'profile'       => 'پروفایل من',
			'projects'      => 'پروژه‌ها',
			'points'        => 'تاریخچه امتیاز',
			'rewards'       => 'فروشگاه امتیاز',
			'courses'       => 'دوره‌ها',
			'surveys'       => 'نظرسنجی‌ها',
			'campaigns'     => 'کمپین‌ها',
			'wallet'        => 'کیف پول',
			'card'          => 'کارت عضویت',
			'referral'      => 'معرفی دوستان',
			'notifications' => 'اعلان‌ها',
		);
		$current = '';
		$page_map = mjc_pages();
		foreach ( $items as $key => $label ) {
			if ( is_page() && isset( $page_map[ $key ] ) && get_the_ID() === (int) $page_map[ $key ] ) {
				$current = $key;
			}
		}
		$html = '<nav class="mjc-nav"><ul>';
		foreach ( $items as $key => $label ) {
			$badge = '';
			if ( 'notifications' === $key ) {
				$unread = MJC_Notify::unread_count( get_current_user_id() );
				if ( $unread ) {
					$badge = ' <span class="mjc-badge mjc-badge-dot">' . mjc_num( $unread ) . '</span>';
				}
			}
			$active = ( $current === $key ) ? ' class="active"' : '';
			$html  .= '<li' . $active . '><a href="' . esc_url( mjc_page_url( $key ) ) . '">' . esc_html( $label ) . $badge . '</a></li>';
		}
		$html .= '</ul></nav>';
		return $html;
	}

	/**
	 * ابتدای فرم.
	 *
	 * @param string $form_key کلید فرم.
	 * @param string $nonce_action نام nonce.
	 * @return string
	 */
	private static function form_open( $form_key, $nonce_action ) {
		$out = '<form method="post" class="mjc-form" enctype="multipart/form-data">';
		$out .= wp_nonce_field( $nonce_action, 'mjc_nonce', true, false );
		$out .= '<input type="hidden" name="mjc_form" value="' . esc_attr( $form_key ) . '" />';
		return $out;
	}

	/**
	 * بستن فرم.
	 *
	 * @param string $submit_text متن دکمه.
	 * @return string
	 */
	private static function form_close( $submit_text ) {
		return '<div class="mjc-actions"><button type="submit" class="mjc-btn mjc-btn-primary">' . esc_html( $submit_text ) . '</button></div></form>';
	}

	/**
	 * فیلد متنی.
	 *
	 * @param array $args آرگومان‌ها.
	 * @return string
	 */
	private static function field( $args ) {
		$args = wp_parse_args( $args, array(
			'name' => '', 'label' => '', 'type' => 'text', 'value' => '',
			'required' => false, 'placeholder' => '', 'options' => array(), 'rows' => 4,
		) );
		$req  = $args['required'] ? ' required' : '';
		$html = '<div class="mjc-field"><label for="f_' . esc_attr( $args['name'] ) . '">' . esc_html( $args['label'] ) . ( $args['required'] ? ' <span class="mjc-req">*</span>' : '' ) . '</label>';
		if ( 'select' === $args['type'] ) {
			$html .= '<select name="' . esc_attr( $args['name'] ) . '" id="f_' . esc_attr( $args['name'] ) . '"' . $req . '>';
			foreach ( $args['options'] as $val => $text ) {
				$html .= '<option value="' . esc_attr( $val ) . '" ' . selected( (string) $args['value'], (string) $val, false ) . '>' . esc_html( $text ) . '</option>';
			}
			$html .= '</select>';
		} elseif ( 'textarea' === $args['type'] ) {
			$html .= '<textarea name="' . esc_attr( $args['name'] ) . '" id="f_' . esc_attr( $args['name'] ) . '" rows="' . (int) $args['rows'] . '" placeholder="' . esc_attr( $args['placeholder'] ) . '"' . $req . '>' . esc_textarea( $args['value'] ) . '</textarea>';
		} else {
			$html .= '<input type="' . esc_attr( $args['type'] ) . '" name="' . esc_attr( $args['name'] ) . '" id="f_' . esc_attr( $args['name'] ) . '" value="' . esc_attr( $args['value'] ) . '" placeholder="' . esc_attr( $args['placeholder'] ) . '"' . $req . ' />';
		}
		$html .= '</div>';
		return $html;
	}

	/**
	 * جدول ساده.
	 *
	 * @param array $head سرستون‌ها.
	 * @param array $rows ردیف‌ها (آرایه‌ی آرایه‌ها از HTML).
	 * @return string
	 */
	private static function table( $head, $rows ) {
		if ( ! $rows ) {
			return mjc_empty_state( 'موردی برای نمایش وجود ندارد.' );
		}
		$html = '<table class="mjc-table"><thead><tr>';
		foreach ( $head as $h ) {
			$html .= '<th>' . esc_html( $h ) . '</th>';
		}
		$html .= '</tr></thead><tbody>';
		foreach ( $rows as $row ) {
			$html .= '<tr>';
			foreach ( $row as $cell ) {
				$html .= '<td>' . $cell . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML داخلی.
			}
			$html .= '</tr>';
		}
		return $html . '</tbody></table>';
	}

	/**
	 * نشان وضعیت.
	 *
	 * @param string $status وضعیت.
	 * @return string
	 */
	private static function badge( $status ) {
		return '<span class="mjc-badge mjc-badge-' . esc_attr( $status ) . '">' . esc_html( mjc_status_label( $status ) ) . '</span>';
	}

	/* -------------------------------------------------------------------
	 * صفحات عمومی
	 * ---------------------------------------------------------------- */

	/* -------------------------------------------------------------------
	 * حساب کاربری یکپارچه (ویجت المنتور / شورت‌کد [mojrian_account])
	 * ---------------------------------------------------------------- */

	/**
	 * صفحه‌ی حساب کاربری: مهمان → ورود/ثبت‌نام؛ عضو → هاب حساب باشگاه.
	 *
	 * @return string
	 */
	public static function render_account() {
		ob_start();
		echo '<div class="mjc-wrap">';
		mjc_render_notice();
		if ( ! is_user_logged_in() ) {
			echo self::auth_panel();
			echo '</div>';
			return ob_get_clean();
		}
		$member = mjc_member();
		if ( ! $member || ( 'approved' !== $member->status && ! current_user_can( 'mjc_manage_club' ) ) ) {
			echo '<div class="mjc-card mjc-center">';
			if ( $member && 'rejected' === $member->status ) {
				echo '<div class="mjc-empty">متأسفانه درخواست عضویت شما پذیرفته نشد.' . ( $member->reject_reason ? ' دلیل: ' . esc_html( $member->reject_reason ) : '' ) . '</div>';
			} else {
				echo '<div class="mjc-empty">حساب شما در انتظار تأیید مدیر باشگاه است. پس از تأیید، حساب کاربری شما فعال می‌شود.</div>';
			}
			echo '<p><a class="mjc-btn mjc-btn-ghost" style="color:#334155;border-color:#cbd5e1" href="' . esc_url( wp_logout_url( mjc_page_url( 'account' ) ) ) . '">خروج از حساب</a></p></div>';
			echo '</div>';
			return ob_get_clean();
		}
		echo self::account_hub( $member );
		echo '</div>';
		return ob_get_clean();
	}

	/**
	 * پنل ورود/ثبت‌نام برای مهمان (تب‌دار).
	 *
	 * @return string
	 */
	private static function auth_panel() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$tab = isset( $_GET['mjc_tab'] ) ? sanitize_key( wp_unslash( $_GET['mjc_tab'] ) ) : 'login';
		$ref = isset( $_GET['ref'] ) ? sanitize_text_field( wp_unslash( $_GET['ref'] ) ) : '';
		// phpcs:enable
		$base = get_permalink();

		ob_start();
		echo '<div class="mjc-auth-card">';
		echo '<h1 class="mjc-center">' . esc_html( mjc_get_setting( 'club_title', 'باشگاه مجریان' ) ) . '</h1>';
		echo '<p class="mjc-center mjc-text-sm">برای ورود به حساب کاربری باشگاه، وارد شوید یا ثبت‌نام کنید.</p>';
		echo '<div class="mjc-tabs">';
		echo '<a class="mjc-tab' . ( 'login' === $tab ? ' active' : '' ) . '" href="' . esc_url( add_query_arg( 'mjc_tab', 'login', $base ) ) . '">ورود</a>';
		echo '<a class="mjc-tab' . ( 'register' === $tab ? ' active' : '' ) . '" href="' . esc_url( add_query_arg( 'mjc_tab', 'register', $base ) ) . '">ثبت‌نام</a>';
		echo '</div>';

		if ( 'register' === $tab ) {
			echo self::form_open( 'otp_send', 'mjc_otp' );
			echo '<input type="hidden" name="purpose" value="register" />';
			echo self::field( array( 'name' => 'mobile', 'label' => 'شماره موبایل', 'type' => 'tel', 'required' => true, 'placeholder' => '09xxxxxxxxx' ) );
			echo self::form_close( 'ارسال کد تأیید' );
			echo '<hr class="mjc-sep" />';
			echo self::form_open( 'register', 'mjc_register' );
			echo self::field( array( 'name' => 'full_name', 'label' => 'نام و نام خانوادگی', 'required' => true ) );
			echo self::field( array( 'name' => 'mobile', 'label' => 'شماره موبایل', 'type' => 'tel', 'required' => true ) );
			echo self::field( array( 'name' => 'otp', 'label' => 'کد تأیید پیامکی' ) );
			echo self::field( array( 'name' => 'national_code', 'label' => 'کد ملی', 'required' => true ) );
			echo '<div class="mjc-grid mjc-grid-2">';
			echo self::field( array( 'name' => 'province', 'label' => 'استان', 'required' => true ) );
			echo self::field( array( 'name' => 'city', 'label' => 'شهر' ) );
			echo '</div>';
			echo self::field( array(
				'name' => 'job_type', 'label' => 'نوع فعالیت', 'type' => 'select', 'required' => true,
				'options' => array( '' => 'انتخاب کنید', 'water' => 'آب و فاضلاب', 'gas' => 'گاز', 'heating' => 'شوفاژ و تأسیسات حرارتی', 'hvac' => 'تهویه مطبوع', 'other' => 'سایر' ),
			) );
			echo self::field( array( 'name' => 'experience', 'label' => 'سابقه کار (سال)', 'type' => 'number' ) );
			echo self::field( array( 'name' => 'workshop_address', 'label' => 'نشانی کارگاه/دفتر' ) );
			echo self::field( array( 'name' => 'password', 'label' => 'رمز عبور (حداقل ۸ کاراکتر)', 'type' => 'password', 'required' => true ) );
			echo self::field( array( 'name' => 'referral_code', 'label' => 'کد معرف (اختیاری)', 'value' => $ref ) );
			echo '<label class="mjc-check"><input type="checkbox" name="accept_rules" value="1" required /> <span>قوانین و حریم خصوصی باشگاه را می‌پذیرم.</span></label>';
			echo self::form_close( 'ثبت‌نام در باشگاه' );
		} else {
			echo self::form_open( 'login', 'mjc_login' );
			echo self::field( array( 'name' => 'username', 'label' => 'نام کاربری یا شماره موبایل', 'required' => true ) );
			echo self::field( array( 'name' => 'password', 'label' => 'رمز عبور', 'type' => 'password', 'required' => true ) );
			echo self::form_close( 'ورود به حساب' );
			echo '<p class="mjc-center mjc-text-sm"><a href="' . esc_url( mjc_page_url( 'login' ) . '?tab=reset' ) . '">فراموشی رمز عبور</a> · <a href="' . esc_url( mjc_page_url( 'login' ) . '?tab=otp' ) . '">ورود با کد یک‌بارمصرف</a></p>';
		}
		echo '</div>';
		return ob_get_clean();
	}

	/**
	 * هاب «حساب کاربری باشگاه مجریان» — مطابق ماکت: سایدبار لینک‌ها + معرفی بخش‌ها.
	 *
	 * @param object $member ردیف عضو.
	 * @return string
	 */
	private static function account_hub( $member ) {
		$name = MJC_Members::display_name( $member );
		$urls = array(
			'profile'   => mjc_page_url( 'profile' ),
			'guide'     => mjc_page_url( 'guide' ),
			'projects'  => mjc_page_url( 'projects' ),
			'rewards'   => mjc_page_url( 'rewards' ),
		);

		ob_start();
		echo '<div class="mjc-account-wrap">';

		// سایدبار: آواتار، خوش‌آمد، نام کاربری و دکمه‌های بخش‌ها (مطابق ماکت).
		echo '<aside class="mjc-account-side">';
		echo '<div class="mjc-side-avatar"><span aria-hidden="true">👤</span></div>';
		echo '<div class="mjc-side-user"><strong>' . esc_html( $name ) . ' خوش آمدید</strong>';
		echo '<span>نام کاربری : ' . esc_html( mjc_num( $member->mobile ) ) . '</span></div>';
		echo '<a class="mjc-side-btn" href="' . esc_url( $urls['profile'] ) . '">تکمیل اطلاعات کاربر</a>';
		echo '<a class="mjc-side-btn" href="' . esc_url( $urls['guide'] ) . '">راهنمای باشگاه</a>';
		echo '<a class="mjc-side-btn mjc-side-active" href="' . esc_url( $urls['projects'] ) . '">ثبت پروژه تاسیسات</a>';
		echo '<a class="mjc-side-btn" href="' . esc_url( $urls['rewards'] ) . '">تبدیل امتیازات به جوایز</a>';
		echo '<a class="mjc-side-btn mjc-side-danger" href="' . esc_url( wp_logout_url( $urls['profile'] ) ) . '">خروج از حساب کاربری</a>';
		echo '</aside>';

		// کارت اصلی: خوش‌آمد + هدف باشگاه + توضیح هر بخش.
		echo '<div class="mjc-account-main">';
		echo '<h1 class="mjc-welcome-title">به حساب کاربری خود خوش آمدید</h1>';
		echo '<h2 class="mjc-welcome-sub">هدف از ایجاد ' . esc_html( mjc_get_setting( 'club_title', 'باشگاه مجریان' ) ) . '</h2>';
		echo '<p class="mjc-welcome-text">باشگاه مجریان با هدف ارج‌نهادن به تلاش مجریان گرامی ایجاد شده است؛ امتیازهایی که از ثبت پروژه به دست می‌آورید در قالب هدایا و جوایز کاربردی به شما بازگردانده می‌شود. همین حالا می‌توانید از مسیرهای روبه‌رو استفاده کنید.</p>';
		echo '<h2 class="mjc-welcome-sub">توضیحات کلی بخش‌های حساب کاربری</h2>';
		echo '<ul class="mjc-explain">';
		echo '<li><strong>تکمیل اطلاعات کاربر :</strong> برای ارسال دقیق بسته‌های پستی و بهره‌مندی از امکانات باشگاه، ابتدا اطلاعات شخصی خود را کامل نمایید.</li>';
		echo '<li><strong>ثبت پروژه تاسیسات :</strong> با ثبت هر پروژه تاسیساتی، پروژه شما بررسی و بر اساس مبلغ یا متراژ پروژه به شما امتیاز تعلق می‌گیرد.</li>';
		echo '<li><strong>تبدیل امتیازات به جوایز :</strong> در این بخش با توجه به امتیازی که تاکنون کسب کرده‌اید، جایزه مورد نظر خود را انتخاب نمایید.</li>';
		echo '<li><strong>خروج از حساب کاربری :</strong> با استفاده از گزینه خروج، از حساب کاربری خود خارج شوید.</li>';
		echo '</ul>';
		echo '</div></div>';
		return ob_get_clean();
	}


	/**
	 * صفحه‌ی اصلی.
	 *
	 * @return string
	 */
	public static function render_home() {
		$stats = MJC_Members::stats();
		$projects = MJC_Projects::stats();
		$points_total = (int) MJC_DB::var( 'SELECT COALESCE(SUM(amount),0) FROM ' . mjc_table( 'points_ledger' ) . ' WHERE amount > 0' ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$club = mjc_get_setting( 'club_title', 'باشگاه مجریان' );

		ob_start();
		echo '<div class="mjc-wrap">';
		mjc_render_notice();
		echo '<section class="mjc-hero">';
		echo '<h1>' . esc_html( $club ) . '</h1>';
		echo '<p>خانه‌ی مجریان تاسیسات مکانیکی؛ با عضویت و فعالیت در باشگاه، ' . esc_html( mjc_get_setting( 'points_label', 'امتیاز' ) ) . ' کسب کنید و از اعتبارات نقدی و غیرنقدی، دوره‌های آموزشی رایگان و طرح‌های تشویقی بهره‌مند شوید.</p>';
		echo '<div class="mjc-hero-actions">';
		if ( is_user_logged_in() ) {
			echo '<a class="mjc-btn mjc-btn-primary" href="' . esc_url( mjc_page_url( 'dashboard' ) ) . '">ورود به داشبورد</a>';
		} else {
			echo '<a class="mjc-btn mjc-btn-primary" href="' . esc_url( mjc_page_url( 'register' ) ) . '">ثبت‌نام در باشگاه</a>';
			echo '<a class="mjc-btn mjc-btn-ghost" href="' . esc_url( mjc_page_url( 'login' ) ) . '">ورود اعضا</a>';
		}
		echo '<a class="mjc-btn mjc-btn-ghost" href="' . esc_url( mjc_page_url( 'guide' ) ) . '">راهنمای باشگاه</a>';
		echo '</div></section>';

		echo '<section class="mjc-stats">';
		$cards = array(
			array( 'icon' => 'users', 'num' => $stats['total'], 'label' => 'عضو باشگاه' ),
			array( 'icon' => 'projects', 'num' => $projects['total'], 'label' => 'پروژه ثبت‌شده' ),
			array( 'icon' => 'points', 'num' => $points_total, 'label' => 'امتیاز اهداشده' ),
			array( 'icon' => 'card', 'num' => (int) MJC_DB::var( "SELECT COUNT(*) FROM " . mjc_table( 'members' ) . " WHERE grade != ''" ), 'label' => 'دارای پایه فنی' ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
		foreach ( $cards as $card ) {
			echo '<div class="mjc-stat"><span class="mjc-stat-num">' . esc_html( mjc_num( number_format( (float) $card['num'] ) ) ) . '</span><span class="mjc-stat-label">' . esc_html( $card['label'] ) . '</span></div>';
		}
		echo '</section>';

		echo '<section class="mjc-grid mjc-grid-3">';
		$features = array(
			array( 'ثبت پروژه و کسب امتیاز', 'پروژه‌های اجرایی خود را ثبت کنید و پس از تأیید، امتیاز باشگاه را دریافت کنید.', 'projects', 'projects' ),
			array( 'پایه فنی و استعلام', 'در ارزیابی مهارت شرکت کنید، پایه فنی بگیرید و برای کارفرمایان قابل استعلام شوید.', 'grades', 'inquiry' ),
			array( 'فروشگاه امتیاز', 'امتیازهای خود را به ابزارآلات تخصصی، اعتبار ریالی و هدایای باشگاه تبدیل کنید.', 'rewards', 'rewards' ),
			array( 'دوره‌های آموزشی', 'در دوره‌های رایگان مهارت‌افزایی شرکت کنید و گواهی پایان دوره بگیرید.', 'courses', 'courses' ),
			array( 'بانک دانش', 'جدیدترین آموزش‌ها، کاتالوگ‌ها و نکات فنی را مطالعه کنید.', 'knowledge', 'knowledge' ),
			array( 'معرفی دوستان', 'دوستان مجری خود را معرفی کنید و از پاداش عضویت آن‌ها بهره‌مند شوید.', 'referral', 'referral' ),
		);
		foreach ( $features as $f ) {
			$page_map = mjc_pages();
			$url      = isset( $page_map[ $f[3] ] ) ? mjc_page_url( $f[3] ) : home_url( '/' );
			echo '<div class="mjc-card mjc-feature"><div class="mjc-feature-icon mjc-icon-' . esc_attr( $f[2] ) . '"></div>';
			echo '<h3>' . esc_html( $f[0] ) . '</h3><p>' . esc_html( $f[1] ) . '</p>';
			echo '<a href="' . esc_url( $url ) . '">مشاهده ←</a></div>';
		}
		echo '</section>';

		echo '<section class="mjc-card mjc-center mjc-cta">';
		echo '<h2>آماده‌اید به خانواده‌ی بزرگ باشگاه بپیوندید؟</h2>';
		echo '<p>همین حالا ثبت‌نام کنید و از مزایای عضویت بهره‌مند شوید.</p>';
		if ( ! is_user_logged_in() ) {
			echo '<a class="mjc-btn mjc-btn-primary" href="' . esc_url( mjc_page_url( 'register' ) ) . '">ثبت‌نام رایگان</a>';
		}
		echo '</section></div>';
		return ob_get_clean();
	}

	/**
	 * صفحه‌ی ورود.
	 *
	 * @return string
	 */
	public static function render_login() {
		if ( is_user_logged_in() && ! isset( $_GET['tab'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return '<div class="mjc-wrap"><div class="mjc-card mjc-center"><p>شما وارد شده‌اید.</p><a class="mjc-btn mjc-btn-primary" href="' . esc_url( mjc_page_url( 'dashboard' ) ) . '">رفتن به داشبورد</a></div></div>';
		}
		$tab  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'password'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tabs = array( 'password' => 'ورود با رمز عبور', 'otp' => 'ورود با کد یک‌بارمصرف', 'reset' => 'فراموشی رمز عبور' );

		ob_start();
		echo '<div class="mjc-wrap mjc-narrow">';
		mjc_render_notice();
		echo '<div class="mjc-auth-card">';
		echo '<h1 class="mjc-center">ورود به ' . esc_html( mjc_get_setting( 'club_title', 'باشگاه مجریان' ) ) . '</h1>';
		echo '<div class="mjc-tabs">';
		foreach ( $tabs as $key => $label ) {
			$active = ( $tab === $key ) ? ' active' : '';
			echo '<a class="mjc-tab' . $active . '" href="' . esc_url( add_query_arg( 'tab', $key, mjc_page_url( 'login' ) ) ) . '">' . esc_html( $label ) . '</a>';
		}
		echo '</div>';

		if ( 'password' === $tab ) {
			echo self::form_open( 'login', 'mjc_login' );
			echo self::field( array( 'name' => 'username', 'label' => 'نام کاربری یا شماره موبایل', 'required' => true ) );
			echo self::field( array( 'name' => 'password', 'label' => 'رمز عبور', 'type' => 'password', 'required' => true ) );
			echo self::form_close( 'ورود' );
		} elseif ( 'otp' === $tab ) {
			echo self::form_open( 'otp_send', 'mjc_otp' );
			echo '<input type="hidden" name="purpose" value="login" />';
			echo self::field( array( 'name' => 'mobile', 'label' => 'شماره موبایل', 'type' => 'tel', 'required' => true, 'placeholder' => '09xxxxxxxxx' ) );
			echo self::form_close( 'ارسال کد تأیید' );
			echo '<hr class="mjc-sep" />';
			echo self::form_open( 'login_otp', 'mjc_login' );
			echo self::field( array( 'name' => 'otp', 'label' => 'کد تأیید دریافتی', 'required' => true ) );
			echo self::form_close( 'ورود با کد' );
		} else {
			echo self::form_open( 'otp_send', 'mjc_otp' );
			echo '<input type="hidden" name="purpose" value="reset" />';
			echo self::field( array( 'name' => 'mobile', 'label' => 'شماره موبایل', 'type' => 'tel', 'required' => true ) );
			echo self::form_close( 'ارسال کد تأیید' );
			echo '<hr class="mjc-sep" />';
			echo self::form_open( 'password_reset', 'mjc_reset' );
			echo self::field( array( 'name' => 'mobile', 'label' => 'شماره موبایل', 'type' => 'tel', 'required' => true ) );
			echo self::field( array( 'name' => 'otp', 'label' => 'کد تأیید', 'required' => true ) );
			echo self::field( array( 'name' => 'password1', 'label' => 'رمز عبور جدید', 'type' => 'password', 'required' => true ) );
			echo self::field( array( 'name' => 'password2', 'label' => 'تکرار رمز عبور جدید', 'type' => 'password', 'required' => true ) );
			echo self::form_close( 'تغییر رمز عبور' );
		}

		echo '<p class="mjc-center mjc-text-sm">عضو نیستید؟ <a href="' . esc_url( mjc_page_url( 'register' ) ) . '">ثبت‌نام در باشگاه</a></p>';
		echo '</div></div>';
		return ob_get_clean();
	}

	/**
	 * صفحه‌ی ثبت‌نام.
	 *
	 * @return string
	 */
	public static function render_register() {
		if ( is_user_logged_in() ) {
			return '<div class="mjc-wrap"><div class="mjc-card mjc-center"><p>شما قبلاً ثبت‌نام کرده‌اید.</p><a class="mjc-btn mjc-btn-primary" href="' . esc_url( mjc_page_url( 'dashboard' ) ) . '">داشبورد من</a></div></div>';
		}
		$ref = isset( $_GET['ref'] ) ? sanitize_text_field( wp_unslash( $_GET['ref'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		ob_start();
		echo '<div class="mjc-wrap mjc-narrow">';
		mjc_render_notice();
		echo '<div class="mjc-auth-card">';
		echo '<h1 class="mjc-center">ثبت‌نام در ' . esc_html( mjc_get_setting( 'club_title', 'باشگاه مجریان' ) ) . '</h1>';
		echo '<p class="mjc-center mjc-text-sm">پس از تکمیل فرم و تأیید موبایل، درخواست شما توسط مدیر باشگاه بررسی می‌شود.</p>';

		echo self::form_open( 'otp_send', 'mjc_otp' );
		echo '<input type="hidden" name="purpose" value="register" />';
		echo self::field( array( 'name' => 'mobile', 'label' => 'شماره موبایل', 'type' => 'tel', 'required' => true, 'placeholder' => '09xxxxxxxxx' ) );
		echo self::form_close( 'ارسال کد تأیید' );
		echo '<hr class="mjc-sep" />';

		echo self::form_open( 'register', 'mjc_register' );
		echo self::field( array( 'name' => 'full_name', 'label' => 'نام و نام خانوادگی', 'required' => true ) );
		echo self::field( array( 'name' => 'mobile', 'label' => 'شماره موبایل', 'type' => 'tel', 'required' => true ) );
		echo self::field( array( 'name' => 'otp', 'label' => 'کد تأیید پیامکی' ) );
		echo self::field( array( 'name' => 'national_code', 'label' => 'کد ملی', 'required' => true, 'placeholder' => '۱۰ رقم' ) );
		echo '<div class="mjc-grid mjc-grid-2">';
		echo self::field( array( 'name' => 'province', 'label' => 'استان', 'required' => true ) );
		echo self::field( array( 'name' => 'city', 'label' => 'شهر' ) );
		echo '</div>';
		echo self::field( array(
			'name' => 'job_type', 'label' => 'نوع فعالیت', 'type' => 'select', 'required' => true,
			'options' => array(
				''          => 'انتخاب کنید',
				'water'     => 'آب و فاضلاب',
				'gas'       => 'گاز',
				'heating'   => 'شوفاژ و تأسیسات حرارتی',
				'hvac'      => 'تهویه مطبوع',
				'other'     => 'سایر',
			),
		) );
		echo self::field( array( 'name' => 'experience', 'label' => 'سابقه کار (سال)', 'type' => 'number' ) );
		echo self::field( array( 'name' => 'workshop_address', 'label' => 'نشانی کارگاه/دفتر' ) );
		echo self::field( array( 'name' => 'password', 'label' => 'رمز عبور (حداقل ۸ کاراکتر)', 'type' => 'password', 'required' => true ) );
		echo self::field( array( 'name' => 'referral_code', 'label' => 'کد معرف (اختیاری)', 'value' => $ref ) );
		echo '<label class="mjc-check"><input type="checkbox" name="accept_rules" value="1" required /> <span>قوانین و حریم خصوصی باشگاه را مطالعه کرده و می‌پذیرم.</span></label>';
		echo self::form_close( 'ثبت‌نام' );
		echo '<p class="mjc-center mjc-text-sm">قبلاً ثبت‌نام کرده‌اید؟ <a href="' . esc_url( mjc_page_url( 'login' ) ) . '">ورود</a></p>';
		echo '</div></div>';
		return ob_get_clean();
	}

	/* -------------------------------------------------------------------
	 * صفحات اعضا
	 * ---------------------------------------------------------------- */

	/**
	 * داشبورد.
	 *
	 * @return string
	 */
	public static function render_dashboard() {
		$member = mjc_member();
		if ( ! $member ) {
			return self::member_layout( '', false );
		}
		$unread = MJC_Notify::unread_count( get_current_user_id() );
		list( $notifications ) = MJC_Notify::list_for_user( get_current_user_id(), 1, 5 );
		list( $projects ) = MJC_Projects::list_for_user( get_current_user_id(), 1, 5 );

		ob_start();
		echo '<div class="mjc-dash-head mjc-card">';
		echo '<div class="mjc-dash-user"><div class="mjc-avatar">' . esc_html( mb_substr( MJC_Members::display_name( $member ), 0, 1 ) ) . '</div>';
		echo '<div><h2>' . esc_html( MJC_Members::display_name( $member ) ) . '</h2>';
		echo '<p>کد عضویت: <strong>' . esc_html( mjc_num( $member->grade_certificate ? $member->grade_certificate : 'MJC-' . str_pad( (string) $member->id, 6, '0', STR_PAD_LEFT ) ) ) . '</strong></p></div></div>';
		echo '<div class="mjc-dash-badges">' . self::badge( $member->status );
		if ( $member->grade ) {
			echo ' <span class="mjc-badge mjc-badge-grade">' . esc_html( MJC_Grades::level_label( $member->grade ) ) . '</span>';
		}
		echo '</div></div>';

		echo '<div class="mjc-stats">';
		$label = esc_html( mjc_get_setting( 'points_label', 'امتیاز' ) );
		echo '<div class="mjc-stat"><span class="mjc-stat-num">' . esc_html( mjc_num( number_format( (float) $member->points_balance ) ) ) . '</span><span class="mjc-stat-label">' . $label . ' شما</span></div>';
		echo '<div class="mjc-stat"><span class="mjc-stat-num">' . esc_html( mjc_money( $member->wallet_balance ) ) . '</span><span class="mjc-stat-label">موجودی کیف پول (ریال)</span></div>';
		echo '<div class="mjc-stat"><span class="mjc-stat-num">' . esc_html( mjc_num( count( (array) $projects ) ) ) . '</span><span class="mjc-stat-label">آخرین پروژه‌ها</span></div>';
		echo '<div class="mjc-stat"><span class="mjc-stat-num">' . esc_html( mjc_num( $unread ) ) . '</span><span class="mjc-stat-label">اعلان خوانده‌نشده</span></div>';
		echo '</div>';

		echo '<div class="mjc-grid mjc-grid-2">';
		echo '<div class="mjc-card"><h3>دسترسی سریع</h3><div class="mjc-quick">';
		$quick = array(
			'projects'      => 'ثبت پروژه تاسیسات',
			'points'        => 'تاریخچه امتیاز',
			'rewards'       => 'فروشگاه امتیاز',
			'card'          => 'کارت عضویت',
			'courses'       => 'دوره‌های آموزشی',
			'referral'      => 'معرفی دوستان',
		);
		foreach ( $quick as $key => $text ) {
			echo '<a class="mjc-btn mjc-btn-soft" href="' . esc_url( mjc_page_url( $key ) ) . '">' . esc_html( $text ) . '</a>';
		}
		echo '</div></div>';

		echo '<div class="mjc-card"><h3>آخرین اعلان‌ها</h3>';
		if ( $notifications ) {
			echo '<ul class="mjc-list">';
			foreach ( $notifications as $n ) {
				echo '<li><strong>' . esc_html( $n->title ) . '</strong><br /><span class="mjc-text-sm">' . esc_html( mjc_date( $n->created_at ) ) . '</span></li>';
			}
			echo '</ul>';
			echo '<a href="' . esc_url( mjc_page_url( 'notifications' ) ) . '">همه‌ی اعلان‌ها ←</a>';
		} else {
			echo mjc_empty_state( 'اعلان جدیدی ندارید.' );
		}
		echo '</div></div>';
		return self::member_layout( ob_get_clean() );
	}

	/**
	 * «تکمیل اطلاعات کاربر» — فرم اطلاعات شخصی/پستی (مطابق ماکت) + نمایش امتیاز.
	 *
	 * @return string
	 */
	public static function render_profile() {
		if ( ! is_user_logged_in() ) {
			return self::login_required( 'برای تکمیل اطلاعات، ابتدا وارد شوید.' );
		}
		$user_id = get_current_user_id();
		$member  = MJC_Members::get_by_user( $user_id );
		if ( ! $member ) {
			$member = MJC_Members::create( $user_id );
		}
		$balance = MJC_Points::balance( $user_id );

		// انتخاب‌های تاریخ تولد (شمسی).
		$days   = array( '' => 'روز' );
		$months = array( '' => 'ماه', 1 => 'فروردین', 2 => 'اردیبهشت', 3 => 'خرداد', 4 => 'تیر', 5 => 'مرداد', 6 => 'شهریور', 7 => 'مهر', 8 => 'آبان', 9 => 'آذر', 10 => 'دی', 11 => 'بهمن', 12 => 'اسفند' );
		$years  = array( '' => 'سال' );
		for ( $i = 1; $i <= 31; $i++ ) {
			$days[ $i ] = $i;
		}
		for ( $i = (int) explode( '/', mjc_date( current_time( 'mysql' ), false, false ) )[0]; $i >= 1320; $i-- ) {
			$years[ $i ] = $i;
		}

		ob_start();
		echo '<div class="mjc-card">';
		echo '<h1 class="mjc-profile-head">برای ارسال دقیق بسته‌های پستی، لطفاً اطلاعات شخصی خود را کامل نمایید.</h1>';

		// نمایش امتیاز مجری — نوار جمع‌وجور (نمایش امتیاز در پروفایل طبق درخواست).
		echo '<div class="mjc-points-pill">امتیاز شما : <b>' . esc_html( mjc_num( number_format( (float) $balance ) ) ) . '</b> ' . esc_html( mjc_get_setting( 'points_label', 'امتیاز' ) ) . '</div>';

		echo '<h2 class="mjc-mf-title">تکمیل اطلاعات کاربران</h2>';
		echo self::form_open( 'profile', 'mjc_profile' );
		echo '<div class="mjc-mock-form" dir="rtl">';

		// نام و نام خانوادگی — دو فیلد کنار هم (نام در سمت راست).
		echo '<div class="mjc-mf-label">نام و نام خانوادگی <em>(ضروری)</em></div>';
		echo '<div class="mjc-mf-row mjc-mf-2">';
		echo '<div class="mjc-mf-col"><label for="f_first_name">نام</label><input type="text" name="first_name" id="f_first_name" value="' . esc_attr( $member->first_name ) . '" required /></div>';
		echo '<div class="mjc-mf-col"><label for="f_last_name">نام خانوادگی</label><input type="text" name="last_name" id="f_last_name" value="' . esc_attr( $member->last_name ) . '" required /></div>';
		echo '</div>';

		// کد ملی — ورودی باریک راست‌چین.
		echo '<div class="mjc-mf-label">کد ملی <em>(ضروری)</em></div>';
		echo '<div class="mjc-mf-row"><input class="mjc-mf-narrow" type="text" name="national_code" inputmode="numeric" value="' . esc_attr( $member->national_code ) . '" required /></div>';

		// شماره موبایل + شماره موبایل معرف — دو ستون با برچسب و راهنمای جدا.
		echo '<div class="mjc-mf-row mjc-mf-2">';
		echo '<div class="mjc-mf-col"><div class="mjc-mf-label">شماره موبایل <em>(ضروری)</em></div>';
		echo '<p class="mjc-mf-hint">لطفا شماره موبایلی که ثبت نام کرده اید را وارد کنید</p>';
		echo '<input type="tel" name="mobile" value="' . esc_attr( $member->mobile ) . '" required /></div>';
		echo '<div class="mjc-mf-col"><div class="mjc-mf-label">شماره موبایل معرف</div>';
		echo '<p class="mjc-mf-hint">لطفا شماره موبایل معرف خود را وارد نمایید</p>';
		echo '<input type="tel" name="referrer_mobile" value="' . esc_attr( $member->referrer_mobile ) . '" /></div>';
		echo '</div>';

		// تاریخ تولد — سه سلکت کوچک با برچسب زیر آن‌ها.
		echo '<div class="mjc-mf-label">تاریخ تولد <em>(ضروری)</em></div>';
		echo '<div class="mjc-birth">';
		echo '<div class="mjc-bc"><select name="birth_day" required><option value="">روز</option>';
		for ( $i = 1; $i <= 31; $i++ ) {
			echo '<option value="' . esc_attr( $i ) . '"' . selected( (string) $member->birth_day, (string) $i, false ) . '>' . esc_html( mjc_num( $i ) ) . '</option>';
		}
		echo '</select><span>روز</span></div>';
		echo '<div class="mjc-bc"><select name="birth_month" required><option value="">ماه</option>';
		foreach ( $months as $mv => $mt ) {
			if ( '' === $mv ) {
				continue;
			}
			echo '<option value="' . esc_attr( $mv ) . '"' . selected( (string) $member->birth_month, (string) $mv, false ) . '>' . esc_html( $mt ) . '</option>';
		}
		echo '</select><span>ماه</span></div>';
		echo '<div class="mjc-bc"><select name="birth_year" required><option value="">سال</option>';
		foreach ( $years as $yv => $yt ) {
			if ( '' === $yv ) {
				continue;
			}
			echo '<option value="' . esc_attr( $yv ) . '"' . selected( (string) $member->birth_year, (string) $yv, false ) . '>' . esc_html( mjc_num( $yv ) ) . '</option>';
		}
		echo '</select><span>سال</span></div>';
		echo '</div>';

		// ملیت / استان / شهر — سه ستون.
		$provinces = array( 'آذربایجان شرقی', 'آذربایجان غربی', 'اردبیل', 'اصفهان', 'البرز', 'ایلام', 'بوشهر', 'تهران', 'چهارمحال و بختیاری', 'خراسان جنوبی', 'خراسان رضوی', 'خراسان شمالی', 'خوزستان', 'زنجان', 'سمنان', 'سیستان و بلوچستان', 'فارس', 'قزوین', 'قم', 'کردستان', 'کرمان', 'کرمانشاه', 'کهگیلویه و بویراحمد', 'گلستان', 'گیلان', 'لرستان', 'مازندران', 'مرکزی', 'هرمزگان', 'همدان', 'یزد' );
		echo '<div class="mjc-mf-row mjc-mf-3">';
		echo '<div class="mjc-mf-col"><div class="mjc-mf-label">ملیت <em>(ضروری)</em></div>';
		echo '<select name="nationality" required><option value="ایران"' . selected( (string) ( $member->nationality ? $member->nationality : 'ایران' ), 'ایران', false ) . '>ایران</option><option value="سایر"' . selected( (string) $member->nationality, 'سایر', false ) . '>سایر</option></select></div>';
		echo '<div class="mjc-mf-col"><div class="mjc-mf-label">استان <em>(ضروری)</em></div>';
		echo '<select name="province" required><option value="">انتخاب کنید</option>';
		foreach ( $provinces as $pv ) {
			echo '<option value="' . esc_attr( $pv ) . '"' . selected( (string) $member->province, $pv, false ) . '>' . esc_html( $pv ) . '</option>';
		}
		echo '</select></div>';
		echo '<div class="mjc-mf-col"><div class="mjc-mf-label">شهر <em>(ضروری)</em></div>';
		echo '<input type="text" name="city" value="' . esc_attr( $member->city ) . '" required /></div>';
		echo '</div>';

		// نشانی.
		echo '<div class="mjc-mf-label">نشانی <em>(ضروری)</em></div>';
		echo '<div class="mjc-mf-row"><textarea name="address" rows="3" required>' . esc_textarea( $member->address ) . '</textarea></div>';

		// اطلاعات پستی.
		echo '<p class="mjc-form-note">برای ارسال بسته های پستی</p>';
		echo '<div class="mjc-mf-label">نشانی دقیق محل کار با کدپستی</div>';
		echo '<div class="mjc-mf-row"><input class="mjc-mf-narrow" type="text" name="work_address" value="' . esc_attr( $member->work_address ) . '" /></div>';
		echo '<div class="mjc-mf-label">کد پستی</div>';
		echo '<div class="mjc-mf-row"><input class="mjc-mf-narrow" type="text" name="postal_code" inputmode="numeric" value="' . esc_attr( $member->postal_code ) . '" /></div>';

		echo self::form_close( 'ارسال اطلاعات' );
		echo '</div>';

		return self::member_layout( ob_get_clean() );
	}


	/**
	 * پروژه‌ها.
	 *
	 * @return string
	 */
	public static function render_projects() {
		$paged = isset( $_GET['mpage'] ) ? max( 1, (int) $_GET['mpage'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		list( $rows, $total ) = MJC_Projects::list_for_user( get_current_user_id(), $paged, 10 );

		ob_start();
		echo '<div class="mjc-grid mjc-grid-2">';
		echo '<div class="mjc-card"><h2>ثبت پروژه تاسیسات</h2>';
		echo self::form_open( 'project', 'mjc_project' );
		echo self::field( array( 'name' => 'title', 'label' => 'عنوان پروژه', 'required' => true ) );
		echo '<div class="mjc-grid mjc-grid-2">';
		echo self::field( array( 'name' => 'project_type', 'label' => 'نوع پروژه', 'type' => 'select', 'options' => array( '' => 'انتخاب کنید' ) + MJC_Projects::TYPES ) );
		echo self::field( array( 'name' => 'units', 'label' => 'تعداد واحد', 'type' => 'number' ) );
		echo self::field( array( 'name' => 'province', 'label' => 'استان' ) );
		echo self::field( array( 'name' => 'city', 'label' => 'شهر' ) );
		echo '</div>';
		echo self::field( array( 'name' => 'brand', 'label' => 'محصولات/برند استفاده‌شده' ) );
		echo '<div class="mjc-grid mjc-grid-2">';
		echo self::field( array( 'name' => 'amount', 'label' => 'مبلغ پروژه (ریال)', 'type' => 'number', 'placeholder' => 'مثال: ۸۵۰۰۰۰۰۰۰' ) );
		echo self::field( array( 'name' => 'area', 'label' => 'متراژ (متر مربع)', 'type' => 'number', 'placeholder' => 'مثال: ۱۲۰' ) );
		echo '</div>';
		echo self::field( array( 'name' => 'start_date', 'label' => 'تاریخ شروع اجرا (شمسی)', 'placeholder' => '۱۴۰۳/۰۱/۰۱', 'class' => 'mjc-datepicker' ) );
		echo self::field( array( 'name' => 'description', 'label' => 'توضیحات', 'type' => 'textarea' ) );
		echo '<div class="mjc-field"><label for="f_images">تصاویر پروژه</label><input type="file" name="images[]" id="f_images" multiple accept="image/*" /></div>';
		echo self::form_close( 'ثبت پروژه' );
		echo '</div>';

		echo '<div class="mjc-card"><h2>پروژه‌های من</h2>';
		$table_rows = array();
		foreach ( $rows as $p ) {
			$table_rows[] = array(
				'<strong>' . esc_html( $p->title ) . '</strong>' . ( $p->description ? '<br /><span class="mjc-text-sm">' . esc_html( wp_trim_words( wp_strip_all_tags( $p->description ), 12 ) ) . '</span>' : '' ),
				esc_html( MJC_Projects::type_label( $p->project_type ) ),
				( $p->area > 0 ? esc_html( mjc_num( $p->area ) ) . ' متر' : '—' ) . '<br />' . ( $p->amount > 0 ? esc_html( mjc_money( $p->amount ) ) . ' ریال' : '' ),
				esc_html( mjc_date( $p->created_at, false ) ),
				self::badge( $p->status ) . ( 'rejected' === $p->status && $p->reject_reason ? '<br /><span class="mjc-text-sm">' . esc_html( $p->reject_reason ) . '</span>' : '' ),
				$p->points_awarded ? '<span class="mjc-points">+' . esc_html( mjc_num( $p->points_awarded ) ) . '</span>' : '—',
			);
		}
		echo self::table( array( 'عنوان', 'نوع', 'مبلغ/متراژ', 'تاریخ ثبت', 'وضعیت', 'امتیاز' ), $table_rows );
		echo '</div></div>';
		return self::member_layout( ob_get_clean() );
	}

	/**
	 * تاریخچه امتیاز.
	 *
	 * @return string
	 */
	public static function render_points() {
		$paged = isset( $_GET['mpage'] ) ? max( 1, (int) $_GET['mpage'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		list( $rows, $total ) = MJC_Points::history( get_current_user_id(), $paged, 20 );
		$member = mjc_member();
		$label  = mjc_get_setting( 'points_label', 'امتیاز' );

		ob_start();
		echo '<div class="mjc-card"><h2>تاریخچه ' . esc_html( $label ) . '</h2>';
		echo '<div class="mjc-balance">' . esc_html( $label ) . ' فعلی: <strong>' . esc_html( mjc_num( number_format( (float) ( $member ? $member->points_balance : 0 ) ) ) ) . '</strong></div>';
		$table_rows = array();
		foreach ( $rows as $r ) {
			$table_rows[] = array(
				esc_html( mjc_date( $r->created_at ) ),
				esc_html( $r->description ? $r->description : $r->action ),
				( $r->amount > 0 ? '<span class="mjc-points">+' : '<span class="mjc-points mjc-points-neg">' ) . esc_html( mjc_num( $r->amount ) ) . '</span>',
				esc_html( mjc_num( number_format( (float) $r->balance_after ) ) ),
			);
		}
		echo self::table( array( 'تاریخ', 'شرح', 'مقدار', 'موجودی' ), $table_rows );
		echo self::pagination( $paged, 20, $total );
		echo '</div>';
		return self::member_layout( ob_get_clean() );
	}

	/**
	 * صفحه‌بندی.
	 *
	 * @param int $paged صفحه جاری.
	 * @param int $per_page در هر صفحه.
	 * @param int $total کل.
	 * @return string
	 */
	private static function pagination( $paged, $per_page, $total ) {
		$pages = (int) ceil( $total / $per_page );
		if ( $pages <= 1 ) {
			return '';
		}
		$html = '<div class="mjc-pagination">';
		for ( $i = 1; $i <= $pages; $i++ ) {
			$cls = ( $i === (int) $paged ) ? ' class="active"' : '';
			$html .= '<a' . $cls . ' href="' . esc_url( add_query_arg( 'mpage', $i ) ) . '">' . esc_html( mjc_num( $i ) ) . '</a>';
		}
		return $html . '</div>';
	}

	/**
	 * «تبدیل امتیازات به جوایز» — انتخاب جایزه از گرید دایره‌ای (مطابق ماکت).
	 *
	 * @return string
	 */
	public static function render_rewards() {
		if ( ! is_user_logged_in() ) {
			return self::login_required( 'برای تبدیل امتیازات، ابتدا وارد شوید.' );
		}
		$user_id = get_current_user_id();
		$member  = MJC_Members::get_by_user( $user_id );
		if ( ! $member ) {
			$member = MJC_Members::create( $user_id );
		}
		$balance = MJC_Points::balance( $user_id );
		$label   = mjc_get_setting( 'points_label', 'امتیاز' );

		$items = get_posts( array(
			'post_type'      => 'mjc_reward',
			'post_status'    => 'publish',
			'numberposts'    => 48,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
			'no_found_rows'  => true,
		) );

		ob_start();
		echo '<div class="mjc-card">';
		echo '<h1 class="mjc-center mjc-rewards-title">جایزه خود را انتخاب نمایید</h1>';
		echo '<p class="mjc-center mjc-text-sm">یکی از جوایز را انتخاب نمایید و کلید ارسال را بزنید. در صورتی که امتیاز شما به امتیاز جایزه انتخاب‌شده برسد، در لیست ارسال‌ها اضافه می‌شود.</p>';

		if ( ! $member->address || ! $member->postal_code ) {
			echo '<p class="mjc-center mjc-text-sm" style="color:#b45309">برای ارسال جایزه، لطفاً «نشانی» و «کد پستی» را در بخش <a href="' . esc_url( mjc_page_url( 'profile' ) ) . '">تکمیل اطلاعات کاربر</a> وارد نمایید.</p>';
		}

		if ( $items ) {
			echo self::form_open( 'reward_request', 'mjc_reward' );
			echo '<div class="mjc-prize-grid">';
		foreach ( $items as $it ) {
			$cost  = (int) get_post_meta( $it->ID, 'mjc_points_cost', true );
			$stock = (int) get_post_meta( $it->ID, 'mjc_stock', true );
			$icon  = (string) get_post_meta( $it->ID, 'mjc_icon', true );
			$can   = ( $balance >= $cost && $stock > 0 );
			echo '<label class="mjc-prize' . ( $can ? '' : ' mjc-prize-off' ) . '">';
			echo '<input type="radio" name="reward_id" value="' . esc_attr( $it->ID ) . '"' . ( $can ? '' : ' disabled' ) . ' />';
			echo '<span class="mjc-prize-circle">';
			if ( has_post_thumbnail( $it->ID ) ) {
				echo get_the_post_thumbnail( $it->ID, 'thumbnail', array( 'class' => 'mjc-prize-img' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			} else {
				echo '<span class="mjc-prize-icon" aria-hidden="true">' . esc_html( $icon ? $icon : '🎁' ) . '</span>';
			}
			echo '<b class="mjc-prize-num">' . esc_html( mjc_num( number_format( $cost ) ) ) . '</b>';
			echo '<i>' . esc_html( $label ) . ' ' . esc_html( mjc_num( number_format( $cost ) ) ) . '</i>';
			echo '<span class="mjc-prize-check" aria-hidden="true">✓</span>';
			echo '</span>';
			echo '<span class="mjc-prize-name">' . esc_html( $it->post_title ) . '</span>';
			echo '</label>';
		}
			echo '</div>';
			echo self::form_close( 'تبدیل انجام شد' );
		} else {
			echo '<p class="mjc-empty">در حال حاضر جایزه‌ای تعریف نشده است. مدیر سایت می‌تواند جوایز را از بخش «جوایز (فروشگاه امتیاز)» اضافه کند.</p>';
		}

		// درخواست‌های ارسال.
		echo '<h2 class="mjc-form-title">درخواست‌های ارسال</h2>';
		global $wpdb;
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT rr.*, p.post_title AS reward_title FROM " . mjc_table( 'reward_requests' ) . " rr LEFT JOIN {$wpdb->posts} p ON p.ID = rr.reward_id WHERE rr.user_id = %d ORDER BY rr.id DESC LIMIT 10", $user_id ) );
		// phpcs:enable
		if ( $rows ) {
			echo '<div class="mjc-table-wrap"><table class="mjc-table"><thead><tr><th>جایزه</th><th>تاریخ</th><th>وضعیت</th></tr></thead><tbody>';
			foreach ( $rows as $r ) {
				echo '<tr><td>' . esc_html( (string) $r->reward_title ) . '</td><td>' . esc_html( mjc_num( mjc_date( $r->created_at, false ) ) ) . '</td><td>' . self::badge( $r->status ) . '</td></tr>';
			}
			echo '</tbody></table></div>';
		} else {
			echo '<p class="mjc-empty">هنوز درخواستی ثبت نکرده‌اید.</p>';
		}
		echo '</div>';
		return self::member_layout( ob_get_clean() );
	}


	/**
	 * دوره‌ها.
	 *
	 * @return string
	 */
	public static function render_courses() {
		$courses = get_posts( array( 'post_type' => 'mjc_course', 'post_status' => 'publish', 'numberposts' => 24 ) );
		$mine    = array();
		foreach ( (array) MJC_Courses::enrollments( get_current_user_id() ) as $e ) {
			$mine[ (int) $e->course_id ] = $e;
		}

		ob_start();
		echo '<div class="mjc-card"><h2>دوره‌های آموزشی</h2><div class="mjc-grid mjc-grid-3">';
		foreach ( $courses as $course ) {
			echo '<div class="mjc-reward">';
			if ( has_post_thumbnail( $course->ID ) ) {
				echo get_the_post_thumbnail( $course->ID, 'medium', array( 'class' => 'mjc-reward-img' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			echo '<h3>' . esc_html( $course->post_title ) . '</h3>';
			echo '<p class="mjc-text-sm">' . esc_html( wp_trim_words( wp_strip_all_tags( $course->post_content ), 20 ) ) . '</p>';
			if ( isset( $mine[ $course->ID ] ) ) {
				echo '<span class="mjc-badge mjc-badge-' . esc_attr( $mine[ $course->ID ]->status ) . '">' . esc_html( mjc_status_label( $mine[ $course->ID ]->status ) ) . '</span>';
			} else {
				echo self::form_open( 'course_enroll', 'mjc_course' );
				echo '<input type="hidden" name="course_id" value="' . esc_attr( $course->ID ) . '" />';
				echo self::form_close( 'ثبت‌نام در دوره' );
			}
			echo '</div>';
		}
		echo '</div>';
		if ( ! $courses ) {
			echo mjc_empty_state( 'دوره‌ای منتشر نشده است.' );
		}
		echo '</div>';
		return self::member_layout( ob_get_clean() );
	}

	/**
	 * بانک دانش.
	 *
	 * @return string
	 */
	public static function render_knowledge() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$search = isset( $_GET['ks'] ) ? sanitize_text_field( wp_unslash( $_GET['ks'] ) ) : '';
		$cat    = isset( $_GET['kcat'] ) ? sanitize_title( wp_unslash( $_GET['kcat'] ) ) : '';
		$paged  = isset( $_GET['mpage'] ) ? max( 1, (int) $_GET['mpage'] ) : 1;
		// phpcs:enable
		$query = MJC_Knowledge::search( array( 's' => $search, 'category' => $cat, 'paged' => $paged ) );
		$cats  = get_terms( array( 'taxonomy' => 'mjc_knowledge_cat', 'hide_empty' => true ) );

		ob_start();
		echo '<div class="mjc-card"><h2>بانک دانش</h2>';
		echo '<form method="get" class="mjc-search-form">';
		if ( isset( $_GET['page_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			echo '<input type="hidden" name="page_id" value="' . esc_attr( (int) $_GET['page_id'] ) . '" />'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
		echo '<input type="text" name="ks" value="' . esc_attr( $search ) . '" placeholder="جست‌وجو در مطالب..." />';
		if ( ! is_wp_error( $cats ) && $cats ) {
			echo '<select name="kcat"><option value="">همه‌ی دسته‌ها</option>';
			foreach ( $cats as $c ) {
				echo '<option value="' . esc_attr( $c->slug ) . '" ' . selected( $cat, $c->slug, false ) . '>' . esc_html( $c->name ) . '</option>';
			}
			echo '</select>';
		}
		echo '<button type="submit" class="mjc-btn mjc-btn-primary">جست‌وجو</button></form>';

		echo '<div class="mjc-grid mjc-grid-3">';
		foreach ( $query->posts as $post ) {
			echo '<div class="mjc-reward">';
			if ( has_post_thumbnail( $post->ID ) ) {
				echo get_the_post_thumbnail( $post->ID, 'medium', array( 'class' => 'mjc-reward-img' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			echo '<h3><a href="' . esc_url( get_permalink( $post->ID ) ) . '">' . esc_html( $post->post_title ) . '</a></h3>';
			echo '<p class="mjc-text-sm">' . esc_html( wp_trim_words( wp_strip_all_tags( $post->post_content ), 18 ) ) . '</p></div>';
		}
		echo '</div>';
		if ( ! $query->posts ) {
			echo mjc_empty_state( 'مطلبی یافت نشد.' );
		}
		echo '</div>';
		return ob_get_clean();
	}

	/**
	 * نظرسنجی‌ها.
	 *
	 * @return string
	 */
	public static function render_surveys() {
		$surveys = get_posts( array( 'post_type' => 'mjc_survey', 'post_status' => 'publish', 'numberposts' => 20 ) );

		ob_start();
		echo '<div class="mjc-card"><h2>نظرسنجی‌ها</h2>';
		foreach ( $surveys as $survey ) {
			$done = MJC_Surveys::has_participated( get_current_user_id(), $survey->ID );
			echo '<div class="mjc-survey-item">';
			echo '<h3>' . esc_html( $survey->post_title ) . '</h3>';
			echo '<p class="mjc-text-sm">' . esc_html( wp_trim_words( wp_strip_all_tags( $survey->post_content ), 30 ) ) . '</p>';
			if ( $done ) {
				echo '<span class="mjc-badge mjc-badge-approved">در این نظرسنجی شرکت کرده‌اید</span>';
			} else {
				$questions = MJC_Surveys::questions( $survey->ID );
				echo self::form_open( 'survey', 'mjc_survey' );
				echo '<input type="hidden" name="survey_id" value="' . esc_attr( $survey->ID ) . '" />';
				foreach ( $questions as $i => $q ) {
					$qtext = isset( $q['q'] ) ? $q['q'] : '';
					$type  = isset( $q['type'] ) ? $q['type'] : 'radio';
					echo '<div class="mjc-field"><label>' . esc_html( $qtext ) . '</label>';
					if ( 'text' === $type ) {
						echo '<input type="text" name="answers[q' . (int) $i . ']" />';
					} else {
						foreach ( (array) ( isset( $q['options'] ) ? $q['options'] : array() ) as $oi => $opt ) {
							echo '<label class="mjc-check"><input type="radio" name="answers[q' . (int) $i . ']" value="' . esc_attr( $oi ) . '" required /> <span>' . esc_html( $opt ) . '</span></label>';
						}
					}
					echo '</div>';
				}
				echo self::form_close( 'ثبت پاسخ' );
			}
			echo '</div>';
		}
		if ( ! $surveys ) {
			echo mjc_empty_state( 'نظرسنجی فعالی وجود ندارد.' );
		}
		echo '</div>';
		return self::member_layout( ob_get_clean() );
	}

	/**
	 * کمپین‌ها.
	 *
	 * @return string
	 */
	public static function render_campaigns() {
		$campaigns = get_posts( array( 'post_type' => 'mjc_campaign', 'post_status' => 'publish', 'numberposts' => 20 ) );

		ob_start();
		echo '<div class="mjc-grid mjc-grid-2">';
		foreach ( $campaigns as $campaign ) {
			$active  = MJC_Campaigns::is_active( $campaign->ID );
			$winners = MJC_Campaigns::winners( $campaign->ID );
			echo '<div class="mjc-card mjc-campaign' . ( $active ? '' : ' mjc-campaign-closed' ) . '">';
			if ( has_post_thumbnail( $campaign->ID ) ) {
				echo get_the_post_thumbnail( $campaign->ID, 'medium', array( 'class' => 'mjc-reward-img' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			echo '<h3>' . esc_html( $campaign->post_title ) . '</h3>';
			echo '<div class="mjc-text-sm">' . wp_kses_post( $campaign->post_content ) . '</div>';
			$end = get_post_meta( $campaign->ID, 'mjc_end_date', true );
			if ( $end ) {
				echo '<p class="mjc-text-sm">مهلت: ' . esc_html( mjc_date( $end . ' 00:00:00', false ) ) . '</p>';
			}
			if ( $winners ) {
				echo '<div class="mjc-winners"><strong>برندگان:</strong> ';
				foreach ( $winners as $uid ) {
					$u = get_userdata( $uid );
					echo '<span class="mjc-badge mjc-badge-approved">' . esc_html( $u ? $u->display_name : '—' ) . '</span> ';
				}
				echo '</div>';
			}
			if ( $active ) {
				echo self::form_open( 'campaign_join', 'mjc_campaign' );
				echo '<input type="hidden" name="campaign_id" value="' . esc_attr( $campaign->ID ) . '" />';
				echo self::form_close( 'شرکت در کمپین' );
			} else {
				echo '<span class="mjc-badge mjc-badge-closed">پایان‌یافته</span>';
			}
			echo '</div>';
		}
		echo '</div>';
		if ( ! $campaigns ) {
			echo mjc_empty_state( 'کمپین فعالی وجود ندارد.' );
		}
		return self::member_layout( ob_get_clean() );
	}

	/**
	 * معرفی دوستان.
	 *
	 * @return string
	 */
	public static function render_referral() {
		$link  = MJC_Referrals::link( get_current_user_id() );
		$code  = MJC_Referrals::code( get_current_user_id() );
		$refs  = MJC_Referrals::referrals( get_current_user_id() );

		ob_start();
		echo '<div class="mjc-card"><h2>معرفی دوستان</h2>';
		echo '<p>دوستان مجری خود را به باشگاه دعوت کنید؛ پس از تأیید عضویت آن‌ها، ' . esc_html( mjc_get_setting( 'points_label', 'امتیاز' ) ) . ' پاداش بگیرید.</p>';
		echo '<div class="mjc-referral-box"><div><span class="mjc-text-sm">کد معرف شما</span><strong>' . esc_html( mjc_num( $code ) ) . '</strong></div>';
		echo '<input type="text" readonly value="' . esc_attr( $link ) . '" id="mjc-ref-link" onclick="this.select();" /></div>';

		$table_rows = array();
		foreach ( $refs as $ref ) {
			$table_rows[] = array(
				esc_html( MJC_Members::display_name( $ref ) ),
				esc_html( mjc_date( $ref->created_at, false ) ),
				self::badge( $ref->status ),
			);
		}
		echo '<h3>افراد معرفی‌شده</h3>';
		echo self::table( array( 'نام', 'تاریخ عضویت', 'وضعیت' ), $table_rows );
		echo '</div>';
		return self::member_layout( ob_get_clean() );
	}

	/**
	 * کیف پول.
	 *
	 * @return string
	 */
	public static function render_wallet() {
		$member = mjc_member();
		$txs    = MJC_Wallet::transactions( get_current_user_id() );
		$min    = (int) mjc_get_setting( 'wallet_withdraw_min', 1000000 );

		ob_start();
		echo '<div class="mjc-grid mjc-grid-2">';
		echo '<div class="mjc-card"><h2>کیف پول</h2>';
		echo '<div class="mjc-balance">موجودی فعلی: <strong>' . esc_html( mjc_money( $member ? $member->wallet_balance : 0 ) ) . '</strong> ریال</div>';
		echo '<h3>درخواست برداشت</h3>';
		echo self::form_open( 'wallet_withdraw', 'mjc_wallet' );
		echo self::field( array( 'name' => 'amount', 'label' => 'مبلغ برداشت (ریال) — حداقل ' . mjc_money( $min ), 'required' => true ) );
		echo self::form_close( 'ثبت درخواست برداشت' );
		echo '</div>';

		echo '<div class="mjc-card"><h2>تراکنش‌ها</h2>';
		$table_rows = array();
		foreach ( $txs as $tx ) {
			$table_rows[] = array(
				esc_html( mjc_date( $tx->created_at ) ),
				esc_html( $tx->description ? $tx->description : mjc_status_label( $tx->type ) ),
				( $tx->amount > 0 ? '<span class="mjc-points">+' : '<span class="mjc-points mjc-points-neg">' ) . esc_html( mjc_money( $tx->amount ) ) . '</span>',
				self::badge( $tx->status ),
			);
		}
		echo self::table( array( 'تاریخ', 'شرح', 'مبلغ (ریال)', 'وضعیت' ), $table_rows );
		echo '</div></div>';
		return self::member_layout( ob_get_clean() );
	}

	/**
	 * کارت عضویت.
	 *
	 * @return string
	 */
	public static function render_card() {
		$member = mjc_member();
		$code   = $member ? ( $member->grade_certificate ? $member->grade_certificate : 'MJC-' . str_pad( (string) $member->id, 6, '0', STR_PAD_LEFT ) ) : '';
		$inquiry_url = mjc_page_url( 'inquiry' );

		ob_start();
		echo '<div class="mjc-card"><h2>کارت عضویت دیجیتال</h2>';
		if ( $member ) {
			echo '<div class="mjc-member-card">';
			echo '<div class="mjc-member-card-head">' . esc_html( mjc_get_setting( 'club_title', 'باشگاه مجریان' ) ) . '</div>';
			echo '<div class="mjc-member-card-body">';
			echo '<div><strong>' . esc_html( MJC_Members::display_name( $member ) ) . '</strong><br />';
			echo '<span>کد عضویت: ' . esc_html( mjc_num( $code ) ) . '</span><br />';
			echo '<span>پایه فنی: ' . esc_html( $member->grade ? MJC_Grades::level_label( $member->grade ) : '—' ) . '</span><br />';
			echo '<span>تاریخ عضویت: ' . esc_html( mjc_date( $member->created_at, false ) ) . '</span></div>';
			echo '<div class="mjc-qr" id="mjc-qr" data-url="' . esc_url( $inquiry_url ) . '"></div>';
			echo '</div></div>';
			echo '<p class="mjc-text-sm">این کارت برای احراز هویت حرفه‌ای شما نزد کارفرمایان قابل استفاده است؛ QR بالا به صفحه‌ی «استعلام فنی» اشاره دارد.</p>';
		}
		echo '</div>';
		return self::member_layout( ob_get_clean() );
	}

	/**
	 * اعلان‌ها.
	 *
	 * @return string
	 */
	public static function render_notifications() {
		$paged = isset( $_GET['mpage'] ) ? max( 1, (int) $_GET['mpage'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		list( $rows, $total ) = MJC_Notify::list_for_user( get_current_user_id(), $paged, 20 );

		ob_start();
		echo '<div class="mjc-card"><div class="mjc-flex-between"><h2>اعلان‌ها</h2>';
		echo self::form_open( 'notifications_read', 'mjc_notify' );
		echo self::form_close( 'علامت‌گذاری همه به‌عنوان خوانده‌شده' );
		echo '</div>';
		if ( $rows ) {
			echo '<ul class="mjc-list mjc-notif-list">';
			foreach ( $rows as $n ) {
				echo '<li class="' . ( $n->is_read ? '' : 'mjc-unread' ) . '"><strong>' . esc_html( $n->title ) . '</strong><p>' . esc_html( wp_trim_words( wp_strip_all_tags( $n->body ), 30 ) ) . '</p><span class="mjc-text-sm">' . esc_html( mjc_date( $n->created_at ) ) . '</span></li>';
			}
			echo '</ul>';
			echo self::pagination( $paged, 20, $total );
		} else {
			echo mjc_empty_state( 'اعلانی ندارید.' );
		}
		echo '</div>';
		return self::member_layout( ob_get_clean() );
	}

	/* -------------------------------------------------------------------
	 * راهنما، استعلام، تماس
	 * ---------------------------------------------------------------- */

	/**
	 * هاب راهنمای باشگاه.
	 *
	 * @return string
	 */
	public static function render_guide() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$search = isset( $_GET['gs'] ) ? sanitize_text_field( wp_unslash( $_GET['gs'] ) ) : '';
		$cat    = isset( $_GET['gcat'] ) ? sanitize_title( wp_unslash( $_GET['gcat'] ) ) : '';
		// phpcs:enable
		$articles = MJC_Guide::articles( $cat, $search );
		$cats     = MJC_Guide::guide_categories();

		ob_start();
		echo '<div class="mjc-wrap">';
		mjc_render_notice();
		echo '<div class="mjc-guide-hero"><h1>راهنمای باشگاه</h1><p>پاسخ همه‌ی پرسش‌های شما درباره‌ی عضویت، امتیازات، پروژه‌ها و پایه فنی</p>';
		echo '<form method="get" class="mjc-search-form mjc-search-hero">';
		if ( isset( $_GET['page_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			echo '<input type="hidden" name="page_id" value="' . esc_attr( (int) $_GET['page_id'] ) . '" />'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
		echo '<input type="text" name="gs" value="' . esc_attr( $search ) . '" placeholder="چه چیزی را می‌خواهید بدانید؟" />';
		echo '<button type="submit" class="mjc-btn mjc-btn-primary">جست‌وجو</button></form></div>';

		if ( ! is_wp_error( $cats ) && $cats ) {
			echo '<div class="mjc-grid mjc-grid-4">';
			foreach ( $cats as $c ) {
				$active = ( $cat === $c->slug ) ? ' mjc-cat-active' : '';
				echo '<a class="mjc-card mjc-cat-card' . $active . '" href="' . esc_url( add_query_arg( 'gcat', $c->slug ) ) . '"><strong>' . esc_html( $c->name ) . '</strong><span class="mjc-text-sm">' . esc_html( mjc_num( $c->count ) ) . ' مطلب</span></a>';
			}
			echo '</div>';
		}

		echo '<div class="mjc-grid mjc-grid-2">';
		foreach ( $articles as $article ) {
			echo '<a class="mjc-card mjc-article-card" href="' . esc_url( get_permalink( $article->ID ) ) . '"><h3>' . esc_html( $article->post_title ) . '</h3><p>' . esc_html( wp_trim_words( wp_strip_all_tags( $article->post_content ), 16 ) ) . '</p></a>';
		}
		echo '</div>';
		if ( ! $articles ) {
			echo mjc_empty_state( 'مقاله‌ای یافت نشد.' );
		}
		echo '<div class="mjc-card mjc-center"><h3>پاسخ خود را پیدا نکردید؟</h3><a class="mjc-btn mjc-btn-primary" href="' . esc_url( mjc_page_url( 'contact' ) ) . '">ارتباط با پشتیبانی</a></div>';
		echo '</div>';
		return ob_get_clean();
	}

	/**
	 * سوالات متداول.
	 *
	 * @return string
	 */
	public static function render_faq() {
		$faqs = MJC_Guide::faqs();
		ob_start();
		echo '<div class="mjc-wrap">';
		mjc_render_notice();
		echo '<div class="mjc-card"><h1>سوالات متداول</h1><div class="mjc-faq">';
		foreach ( $faqs as $faq ) {
			echo '<details class="mjc-faq-item"><summary>' . esc_html( $faq->post_title ) . '</summary>';
			echo '<div class="mjc-faq-body">' . wp_kses_post( apply_filters( 'the_content', $faq->post_content ) ) . '</div></details>';
		}
		echo '</div>';
		if ( ! $faqs ) {
			echo mjc_empty_state( 'سوالی ثبت نشده است.' );
		}
		echo '</div></div>';
		return ob_get_clean();
	}

	/**
	 * استعلام فنی.
	 *
	 * @return string
	 */
	public static function render_inquiry() {
		$result = null;
		$query  = isset( $_GET['mjc_inquiry'] ) ? rawurldecode( sanitize_text_field( wp_unslash( $_GET['mjc_inquiry'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $query ) {
			$cache_key = 'mjc_inquiry_' . get_current_user_id() . '_' . md5( $query );
			$result    = get_transient( $cache_key );
			if ( ! $result ) {
				$result = MJC_Grades::inquiry( $query );
				if ( is_wp_error( $result ) ) {
					$result = null;
				}
			}
		}

		ob_start();
		echo '<div class="mjc-wrap mjc-narrow">';
		mjc_render_notice();
		echo '<div class="mjc-card"><h1>استعلام فنی مجریان</h1>';
		echo '<p>برای بررسی اصالت پایه فنی مجری، کد ملی یا شماره موبایل او را وارد کنید.</p>';
		echo self::form_open( 'inquiry', 'mjc_inquiry' );
		echo self::field( array( 'name' => 'query', 'label' => 'کد ملی یا شماره موبایل مجری', 'required' => true, 'value' => $query ) );
		echo self::form_close( 'استعلام' );
		if ( $result && is_array( $result ) ) {
			echo '<div class="mjc-inquiry-result">';
			echo '<h3>نتیجه‌ی استعلام</h3>';
			echo '<ul class="mjc-list">';
			echo '<li><strong>نام:</strong> ' . esc_html( $result['name'] ) . '</li>';
			echo '<li><strong>وضعیت عضویت:</strong> ' . esc_html( $result['status'] ) . '</li>';
			echo '<li><strong>پایه فنی:</strong> ' . esc_html( $result['grade'] ) . '</li>';
			echo '<li><strong>کد استعلام:</strong> ' . esc_html( mjc_num( $result['certificate'] ) ) . '</li>';
			echo '</ul></div>';
		} elseif ( $query ) {
			echo mjc_empty_state( 'عضوی با این مشخصات یافت نشد.' );
		}
		echo '</div></div>';
		return ob_get_clean();
	}

	/**
	 * ارتباط با ما / تیکت.
	 *
	 * @return string
	 */
	public static function render_contact() {
		$tickets = is_user_logged_in() ? MJC_Tickets::list_for_user( get_current_user_id() ) : array();

		ob_start();
		echo '<div class="mjc-wrap mjc-narrow">';
		mjc_render_notice();
		echo '<div class="mjc-card"><h1>ارتباط با ما</h1>';
		echo self::form_open( 'ticket', 'mjc_ticket' );
		echo self::field( array( 'name' => 'subject', 'label' => 'موضوع', 'required' => true ) );
		echo self::field( array( 'name' => 'message', 'label' => 'متن پیام', 'type' => 'textarea', 'rows' => 6, 'required' => true ) );
		echo '<div class="mjc-field"><label for="f_attachment">پیوست (اختیاری)</label><input type="file" name="attachment[]" id="f_attachment" /></div>';
		echo self::form_close( 'ارسال پیام' );
		echo '</div>';

		if ( $tickets ) {
			echo '<div class="mjc-card"><h2>پیام‌های من</h2><div class="mjc-faq">';
			foreach ( $tickets as $t ) {
				echo '<details class="mjc-faq-item"><summary>' . esc_html( $t->subject ) . ' — ' . self::badge( $t->status ) . '</summary>';
				echo '<div class="mjc-faq-body"><p>' . esc_html( $t->message ) . '</p>';
				if ( $t->admin_reply ) {
					echo '<div class="mjc-admin-reply"><strong>پاسخ باشگاه:</strong><p>' . esc_html( $t->admin_reply ) . '</p></div>';
				}
				echo '<span class="mjc-text-sm">' . esc_html( mjc_date( $t->created_at ) ) . '</span></div></details>';
			}
			echo '</div></div>';
		}
		echo '</div>';
		return ob_get_clean();
	}
}
