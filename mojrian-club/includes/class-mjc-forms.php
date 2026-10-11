<?php
/**
 * پردازش فرم‌های سمت کاربر (الگوی PRG + Nonce).
 *
 * @package mojrian-club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * کلاس فرم‌ها.
 */
class MJC_Forms {

	/**
	 * هوک‌ها.
	 */
	public static function hooks() {
		add_action( 'template_redirect', array( __CLASS__, 'handle' ) );
	}

	/**
	 * مسیریابی فرم‌ها.
	 */
	public static function handle() {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- در هر متد بررسی می‌شود.
		if ( empty( $_POST['mjc_form'] ) ) {
			return;
		}
		$form = sanitize_key( $_POST['mjc_form'] );
		$back = wp_get_referer();
		if ( ! $back ) {
			$back = home_url( add_query_arg( array() ) );
		}
		// پس از ثبت، همیشه به نمای لیست برگرد (نه فرم).
		$back = remove_query_arg( 'mjc_new', $back );

		$method = 'form_' . str_replace( '-', '_', $form );
		if ( ! method_exists( __CLASS__, $method ) ) {
			return;
		}

		$result = self::$method();
		if ( is_wp_error( $result ) ) {
			$back = mjc_notice_url( $back, 'error', $result->get_error_message() );
		} elseif ( is_array( $result ) && isset( $result['redirect'] ) ) {
			$back = $result['redirect'];
		} else {
			$code = is_string( $result ) ? $result : 'ok';
			$back = mjc_notice_url( $back, $code );
		}
		wp_safe_redirect( $back );
		exit;
		// phpcs:enable
	}

	/**
	 * بررسی nonce یک فرم.
	 *
	 * @param string $name نام اکشن.
	 */
	private static function verify( $name ) {
		if ( empty( $_POST['mjc_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['mjc_nonce'] ), $name ) ) {
			wp_die( esc_html__( 'خطای امنیتی؛ لطفاً دوباره تلاش کنید.', 'mojrian-club' ) );
		}
	}

	/**
	 * فرم: ثبت‌نام.
	 *
	 * @return string|WP_Error
	 */
	public static function form_register() {
		self::verify( 'mjc_register' );
		if ( is_user_logged_in() ) {
			return 'ok';
		}
		if ( empty( $_POST['accept_rules'] ) ) {
			return new WP_Error( 'fields', 'پذیرش قوانین و حریم خصوصی الزامی است.' );
		}
		$mobile = mjc_normalize_mobile( isset( $_POST['mobile'] ) ? wp_unslash( $_POST['mobile'] ) : '' ); // phpcs:ignore

		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$data = array(
			'full_name'        => isset( $_POST['full_name'] ) ? sanitize_text_field( wp_unslash( $_POST['full_name'] ) ) : '',
			'mobile'           => $mobile,
			'national_code'    => isset( $_POST['national_code'] ) ? sanitize_text_field( wp_unslash( $_POST['national_code'] ) ) : '',
			'province'         => isset( $_POST['province'] ) ? sanitize_text_field( wp_unslash( $_POST['province'] ) ) : '',
			'city'             => isset( $_POST['city'] ) ? sanitize_text_field( wp_unslash( $_POST['city'] ) ) : '',
			'job_type'         => isset( $_POST['job_type'] ) ? sanitize_text_field( wp_unslash( $_POST['job_type'] ) ) : '',
			'experience'       => isset( $_POST['experience'] ) ? (int) $_POST['experience'] : 0,
			'workshop_address' => isset( $_POST['workshop_address'] ) ? sanitize_text_field( wp_unslash( $_POST['workshop_address'] ) ) : '',
			'password'         => isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : '',
			'referral_code'    => isset( $_POST['referral_code'] ) ? sanitize_text_field( wp_unslash( $_POST['referral_code'] ) ) : '',
		);
		// phpcs:enable

		$result = MJC_Auth::register( $data );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		$member = MJC_Members::get_by_user( $result );
		if ( $member && 'approved' === $member->status ) {
			return array( 'redirect' => mjc_notice_url( mjc_page_url( 'dashboard' ), 'registered' ) );
		}
		return 'registered';
	}

	/**
	 * فرم: ورود با رمز.
	 *
	 * @return string|WP_Error
	 */
	public static function form_login() {
		self::verify( 'mjc_login' );
		$username = isset( $_POST['username'] ) ? sanitize_text_field( wp_unslash( $_POST['username'] ) ) : '';
		$password = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : '';
		$result   = MJC_Auth::login( $username, $password );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		$user   = wp_get_current_user();
		$member = MJC_Members::get_by_user( $user->ID );
		if ( user_can( $user, 'mjc_manage_club' ) ) {
			return array( 'redirect' => admin_url( 'admin.php?page=mjc-dashboard' ) );
		}
		if ( $member && 'approved' === $member->status ) {
			return array( 'redirect' => mjc_notice_url( mjc_page_url( 'dashboard' ), 'ok' ) );
		}
		return 'not_approved';
	}

	/**
	 * فرم: ورود با OTP.
	 *
	 * @return string|WP_Error
	 */

	/**
	 * فرم: بازیابی رمز.
	 *
	 * @return string|WP_Error
	 */

	/**
	 * فرم: ارسال OTP (ثبت‌نام/ورود/بازیابی).
	 *
	 * @return string|WP_Error
	 */

	/**
	 * فرم: پروفایل.
	 *
	 * @return string|WP_Error
	 */
	public static function form_profile() {
		self::verify( 'mjc_profile' );
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return new WP_Error( 'access', 'ابتدا وارد شوید.' );
		}
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$first = isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '';
		$last  = isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '';
		$full  = trim( $first . ' ' . $last );
		if ( '' === $full ) {
			$full = isset( $_POST['full_name'] ) ? sanitize_text_field( wp_unslash( $_POST['full_name'] ) ) : '';
		}
		$ncode_raw  = isset( $_POST['national_code'] ) ? mjc_to_en_digits( sanitize_text_field( wp_unslash( $_POST['national_code'] ) ) ) : '';
		$mobile_raw = isset( $_POST['mobile'] ) ? mjc_to_en_digits( sanitize_text_field( wp_unslash( $_POST['mobile'] ) ) ) : '';
		$ref_raw    = isset( $_POST['referrer_mobile'] ) ? mjc_to_en_digits( sanitize_text_field( wp_unslash( $_POST['referrer_mobile'] ) ) ) : '';
		$bday   = isset( $_POST['birth_day'] ) ? (int) mjc_to_en_digits( sanitize_text_field( wp_unslash( $_POST['birth_day'] ) ) ) : 0;
		$bmonth = isset( $_POST['birth_month'] ) ? (int) mjc_to_en_digits( sanitize_text_field( wp_unslash( $_POST['birth_month'] ) ) ) : 0;
		$byear  = isset( $_POST['birth_year'] ) ? (int) mjc_to_en_digits( sanitize_text_field( wp_unslash( $_POST['birth_year'] ) ) ) : 0;
		// phpcs:enable
		$mobile  = MJC_Validator::mobile( $mobile_raw );
		$ref_mob = '' !== $ref_raw ? MJC_Validator::mobile( $ref_raw ) : null;
		if ( '' !== $ref_raw && ! $ref_mob ) {
			return new WP_Error( 'fields', 'شماره موبایل معرف معتبر نیست.' );
		}
		$ncode   = MJC_Validator::national_code( $ncode_raw );
		$address = isset( $_POST['address'] ) ? sanitize_text_field( wp_unslash( $_POST['address'] ) ) : '';
		$postal  = isset( $_POST['postal_code'] ) ? mjc_to_en_digits( sanitize_text_field( wp_unslash( $_POST['postal_code'] ) ) ) : '';
		$nat     = isset( $_POST['nationality'] ) ? sanitize_text_field( wp_unslash( $_POST['nationality'] ) ) : 'ایران';
		$prov    = isset( $_POST['province'] ) ? sanitize_text_field( wp_unslash( $_POST['province'] ) ) : '';
		$city    = isset( $_POST['city'] ) ? sanitize_text_field( wp_unslash( $_POST['city'] ) ) : '';
		$work    = isset( $_POST['work_address'] ) ? sanitize_text_field( wp_unslash( $_POST['work_address'] ) ) : '';
		$errors  = array();
		if ( '' === $full ) {
			$errors[] = 'نام و نام خانوادگی الزامی است.';
		}
		if ( '' === $ncode_raw || ! $ncode ) {
			$errors[] = 'کد ملی معتبر نیست.';
		}
		if ( ! $mobile ) {
			$errors[] = 'شماره موبایل معتبر نیست.';
		}
		$birthdate = '';
		if ( $bday >= 1 && $bday <= 31 && $bmonth >= 1 && $bmonth <= 12 && $byear >= 1300 && $byear < 1500 ) {
			$birthdate = sprintf( '%04d-%02d-%02d', $byear, $bmonth, $bday );
		}
		if ( '' === $birthdate ) {
			$errors[] = 'تاریخ تولد را کامل انتخاب کنید.';
		}
		if ( '' === $address ) {
			$errors[] = 'نشانی الزامی است.';
		}
		if ( '' === $city || '' === $prov ) {
			$errors[] = 'استان و شهر الزامی است.';
		}
		if ( $errors ) {
			return new WP_Error( 'fields', implode( ' ', $errors ) );
		}

		$data = array(
			'full_name'        => $full,
			'first_name'       => $first,
			'last_name'        => $last,
			'national_code'    => $ncode,
			'mobile'           => $mobile,
			'referrer_mobile'  => $ref_mob ? $ref_mob : '',
			'birth_day'        => $bday,
			'birth_month'      => $bmonth,
			'birth_year'       => $byear,
			'birthdate'        => $birthdate,
			'nationality'      => $nat ? $nat : 'ایران',
			'province'         => $prov,
			'city'             => $city,
			'address'          => $address,
			'work_address'     => $work,
			'postal_code'      => preg_replace( '/[^0-9]/', '', $postal ),
			'workshop_address' => $work,
		);
		MJC_Members::upsert( $user_id, $data );
		if ( ! empty( $data['full_name'] ) ) {
			wp_update_user( array( 'ID' => $user_id, 'display_name' => $data['full_name'] ) );
		}
		do_action( 'mjc_profile_updated', $user_id );
		return 'ok';
	}

	/**
	 * فرم: ثبت پروژه.
	 *
	 * @return string|WP_Error
	 */
	public static function form_project() {
		self::verify( 'mjc_project' );
		$user_id = get_current_user_id();
		if ( ! mjc_is_active_member() ) {
			return new WP_Error( 'access', 'فقط اعضای تأییدشده می‌توانند پروژه ثبت کنند.' );
		}
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$ptype  = isset( $_POST['project_type'] ) ? sanitize_key( wp_unslash( $_POST['project_type'] ) ) : '';
		$floors = isset( $_POST['floors'] ) ? (int) mjc_to_en_digits( sanitize_text_field( wp_unslash( $_POST['floors'] ) ) ) : 0;
		$units  = isset( $_POST['units'] ) ? (int) mjc_to_en_digits( sanitize_text_field( wp_unslash( $_POST['units'] ) ) ) : 0;
		$area   = isset( $_POST['area'] ) ? (float) mjc_to_en_digits( sanitize_text_field( wp_unslash( $_POST['area'] ) ) ) : 0;
		$accept = ! empty( $_POST['accept_rules'] );
		// phpcs:enable

		$errors = array();
		if ( ! array_key_exists( $ptype, MJC_Projects::TYPES ) ) {
			$errors[] = 'نوع کاربری پروژه را انتخاب کنید.';
		}
		if ( $floors < 1 ) {
			$errors[] = 'تعداد طبقات پروژه را وارد کنید.';
		}
		if ( $units < 1 ) {
			$errors[] = 'تعداد واحد پروژه را وارد کنید.';
		}
		if ( $area <= 0 ) {
			$errors[] = 'متراژ کل مساحت پروژه را وارد کنید.';
		}
		if ( ! $accept ) {
			$errors[] = 'تأیید مقررات الزامی است.';
		}

		// هر ۶ تصویر ضروری است (مطابق ماکت).
		$provided = 0;
		if ( isset( $_FILES['images']['name'] ) && is_array( $_FILES['images']['name'] ) ) {
			// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			foreach ( (array) wp_unslash( $_FILES['images']['name'] ) as $i => $raw_name ) {
				$name = (string) $raw_name;
				if ( '' === trim( $name ) ) {
					continue;
				}
				$provided++;
				$ext  = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );
				if ( ! in_array( $ext, array( 'jpg', 'jpeg', 'png', 'bmp' ), true ) ) {
					$errors[] = 'فقط فایل‌های jpg, jpeg, png, bmp برای تصاویر مجاز است.';
					break;
				}
				if ( isset( $_FILES['images']['size'][ $i ] ) && (int) $_FILES['images']['size'][ $i ] > 20 * MB_IN_BYTES ) {
					$errors[] = 'حجم هر تصویر باید حداکثر ۲۰ مگابایت باشد.';
					break;
				}
			}
			// phpcs:enable
		}
		if ( $provided < 6 ) {
			$errors[] = 'هر ۶ تصویر (عکس ۱ تا عکس ۶) ضروری است.';
		}

		// فیلم اختیاری.
		$has_film = ! empty( $_FILES['film']['name'] );
		if ( $has_film ) {
			// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$fext = strtolower( pathinfo( (string) wp_unslash( $_FILES['film']['name'] ), PATHINFO_EXTENSION ) );
			// phpcs:enable
			if ( ! in_array( $fext, array( 'mp4', 'mkv', 'mov', 'avi' ), true ) ) {
				$errors[] = 'فقط فایل‌های mp4, mkv برای فیلم مجاز است.';
				$has_film = false;
			}
			if ( (int) $_FILES['film']['size'] > 50 * MB_IN_BYTES ) {
				$errors[] = 'حجم فیلم باید حداکثر ۵۰ مگابایت باشد.';
				$has_film = false;
			}
		}
		if ( $errors ) {
			return new WP_Error( 'fields', implode( ' ', $errors ) );
		}

		$images  = mjc_uploaded_ids( 'images' );
		$film_ids = $has_film ? mjc_uploaded_ids( 'film' ) : array();

		$result = MJC_Projects::create( $user_id, array(
			'title'        => 'پروژه ' . MJC_Projects::type_label( $ptype ) . ' — ' . mjc_date( current_time( 'mysql' ), false ),
			'project_type' => $ptype,
			'floors'       => $floors,
			'units'        => $units,
			'area'         => $area,
			'images'       => $images,
			'film_id'      => $film_ids ? (int) $film_ids[0] : 0,
		) );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		return 'pending';
	}

	/**
	 * فرم: درخواست پایه فنی.
	 *
	 * @return string|WP_Error
	 */
	public static function form_grade_request() {
		self::verify( 'mjc_grade' );
		if ( ! mjc_is_active_member() ) {
			return new WP_Error( 'access', 'فقط اعضای تأییدشده می‌توانند درخواست دهند.' );
		}
		$result = MJC_Grades::request( get_current_user_id(), array(
			'requested_level' => isset( $_POST['requested_level'] ) ? sanitize_key( wp_unslash( $_POST['requested_level'] ) ) : '',
			'notes'           => isset( $_POST['notes'] ) ? wp_kses_post( wp_unslash( $_POST['notes'] ) ) : '',
		) );
		return is_wp_error( $result ) ? $result : 'pending';
	}

	/**
	 * فرم: درخواست جایزه.
	 *
	 * @return string|WP_Error
	 */
	public static function form_reward_request() {
		self::verify( 'mjc_reward' );
		if ( ! mjc_is_active_member() ) {
			return new WP_Error( 'access', 'فقط اعضای تأییدشده می‌توانند درخواست دهند.' );
		}
		$result = MJC_Rewards::request(
			get_current_user_id(),
			isset( $_POST['reward_id'] ) ? (int) $_POST['reward_id'] : 0,
			isset( $_POST['shipping_address'] ) ? sanitize_text_field( wp_unslash( $_POST['shipping_address'] ) ) : ''
		);
		return is_wp_error( $result ) ? $result : 'pending';
	}

	/**
	 * فرم: ثبت‌نام دوره.
	 *
	 * @return string|WP_Error
	 */
	public static function form_course_enroll() {
		self::verify( 'mjc_course' );
		if ( ! mjc_is_active_member() ) {
			return new WP_Error( 'access', 'فقط اعضای تأییدشده می‌توانند ثبت‌نام کنند.' );
		}
		$result = MJC_Courses::enroll( get_current_user_id(), isset( $_POST['course_id'] ) ? (int) $_POST['course_id'] : 0 );
		return is_wp_error( $result ) ? $result : 'ok';
	}

	/**
	 * فرم: پاسخ نظرسنجی.
	 *
	 * @return string|WP_Error
	 */
	public static function form_survey() {
		self::verify( 'mjc_survey' );
		if ( ! mjc_is_active_member() ) {
			return new WP_Error( 'access', 'فقط اعضای تأییدشده می‌توانند شرکت کنند.' );
		}
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$answers = array();
		if ( isset( $_POST['answers'] ) && is_array( $_POST['answers'] ) ) {
			$answers = wp_unslash( $_POST['answers'] );
		}
		// phpcs:enable
		$result = MJC_Surveys::submit( get_current_user_id(), isset( $_POST['survey_id'] ) ? (int) $_POST['survey_id'] : 0, $answers );
		return is_wp_error( $result ) ? $result : 'ok';
	}

	/**
	 * فرم: شرکت در کمپین.
	 *
	 * @return string|WP_Error
	 */
	public static function form_campaign_join() {
		self::verify( 'mjc_campaign' );
		if ( ! mjc_is_active_member() ) {
			return new WP_Error( 'access', 'فقط اعضای تأییدشده می‌توانند شرکت کنند.' );
		}
		$result = MJC_Campaigns::join( get_current_user_id(), isset( $_POST['campaign_id'] ) ? (int) $_POST['campaign_id'] : 0 );
		return is_wp_error( $result ) ? $result : 'ok';
	}

	/**
	 * فرم: درخواست برداشت کیف پول.
	 *
	 * @return string|WP_Error
	 */
	public static function form_wallet_withdraw() {
		self::verify( 'mjc_wallet' );
		if ( ! mjc_is_active_member() ) {
			return new WP_Error( 'access', 'فقط اعضای تأییدشده می‌توانند درخواست دهند.' );
		}
		$amount = isset( $_POST['amount'] ) ? (int) mjc_to_en_digits( str_replace( array( ',', '،' ), '', wp_unslash( $_POST['amount'] ) ) ) : 0; // phpcs:ignore
		$result = MJC_Wallet::withdraw_request( get_current_user_id(), $amount );
		return is_wp_error( $result ) ? $result : 'pending';
	}

	/**
	 * فرم: تیکت/تماس.
	 *
	 * @return string|WP_Error
	 */
	public static function form_ticket() {
		self::verify( 'mjc_ticket' );
		$attachments = mjc_uploaded_ids( 'attachment' );
		$result      = MJC_Tickets::create(
			get_current_user_id(),
			isset( $_POST['subject'] ) ? sanitize_text_field( wp_unslash( $_POST['subject'] ) ) : '',
			isset( $_POST['message'] ) ? wp_kses_post( wp_unslash( $_POST['message'] ) ) : '',
			$attachments ? $attachments[0] : 0
		);
		return is_wp_error( $result ) ? $result : 'ok';
	}

	/**
	 * فرم: استعلام فنی.
	 *
	 * @return string|WP_Error
	 */
	public static function form_inquiry() {
		self::verify( 'mjc_inquiry' );
		$query  = isset( $_POST['query'] ) ? sanitize_text_field( wp_unslash( $_POST['query'] ) ) : '';
		$result = MJC_Grades::inquiry( $query );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		set_transient( 'mjc_inquiry_' . get_current_user_id() . '_' . md5( $query ), $result, 5 * MINUTE_IN_SECONDS );
		return array( 'redirect' => add_query_arg( 'mjc_inquiry', rawurlencode( $query ), mjc_page_url( 'inquiry' ) ) );
	}

	/**
	 * فرم: خواندن اعلان‌ها.
	 *
	 * @return string
	 */
	public static function form_notifications_read() {
		self::verify( 'mjc_notify' );
		MJC_Notify::mark_all_read( get_current_user_id() );
		return 'ok';
	}
}
