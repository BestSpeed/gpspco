<?php
/**
 * پنل مدیریت باشگاه مجریان.
 *
 * @package mojrian-club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * کلاس ادمین.
 */
class MJC_Admin {

	/**
	 * هوک‌ها.
	 */
	public static function hooks() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_actions' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_action( 'admin_notices', array( __CLASS__, 'notices' ) );
	}

	/**
	 * بارگذاری استایل ادمین.
	 */
	public static function enqueue( $hook ) {
		if ( strpos( $hook, 'mjc-' ) === false && strpos( $hook, 'mjc_' ) === false ) {
			return;
		}
		wp_enqueue_style( 'mojrian-club-admin', MJC_PLUGIN_URL . 'assets/css/mojrian-club-admin.css', array(), MJC_VERSION );
		wp_enqueue_script( 'mojrian-club-admin', MJC_PLUGIN_URL . 'assets/js/mojrian-club-admin.js', array(), MJC_VERSION, true );
	}

	/**
	 * منوی ادمین.
	 */
	public static function menu() {
		add_menu_page(
			'باشگاه مجریان',
			'باشگاه مجریان',
			'read',
			'mjc-dashboard',
			array( __CLASS__, 'page_dashboard' ),
			'dashicons-groups',
			3
		);
		add_submenu_page( 'mjc-dashboard', 'داشبورد باشگاه', 'داشبورد', 'mjc_manage_club', 'mjc-dashboard', array( __CLASS__, 'page_dashboard' ) );
		add_submenu_page( 'mjc-dashboard', 'اعضای باشگاه', 'اعضا', 'mjc_manage_members', 'mjc-members', array( __CLASS__, 'page_members' ) );
		add_submenu_page( 'mjc-dashboard', 'پروژه‌ها', 'پروژه‌ها', 'mjc_manage_projects', 'mjc-projects', array( __CLASS__, 'page_projects' ) );
		add_submenu_page( 'mjc-dashboard', 'امتیازها', 'امتیازها', 'mjc_manage_points', 'mjc-points', array( __CLASS__, 'page_points' ) );
		add_submenu_page( 'mjc-dashboard', 'پایه فنی', 'پایه فنی', 'mjc_manage_grades', 'mjc-grades', array( __CLASS__, 'page_grades' ) );
		add_submenu_page( 'mjc-dashboard', 'درخواست‌های جایزه', 'درخواست‌های جایزه', 'mjc_manage_rewards', 'mjc-reward-requests', array( __CLASS__, 'page_reward_requests' ) );
		add_submenu_page( 'mjc-dashboard', 'کیف پول', 'کیف پول', 'mjc_manage_wallet', 'mjc-wallet', array( __CLASS__, 'page_wallet' ) );
		add_submenu_page( 'mjc-dashboard', 'تیکت‌ها و پیام‌ها', 'تیکت‌ها', 'mjc_manage_tickets', 'mjc-tickets', array( __CLASS__, 'page_tickets' ) );
		add_submenu_page( 'mjc-dashboard', 'اعلان همگانی', 'اعلان همگانی', 'mjc_manage_club', 'mjc-notify', array( __CLASS__, 'page_notify' ) );
		add_submenu_page( 'mjc-dashboard', 'گزارش‌ها', 'گزارش‌ها', 'mjc_view_reports', 'mjc-reports', array( __CLASS__, 'page_reports' ) );
		add_submenu_page( 'mjc-dashboard', 'تنظیمات باشگاه', 'تنظیمات', 'mjc_manage_settings', 'mjc-settings', array( __CLASS__, 'page_settings' ) );
		add_submenu_page( 'mjc-dashboard', 'گزارش رویدادها', 'گزارش رویدادها', 'mjc_manage_settings', 'mjc-audit', array( __CLASS__, 'page_audit' ) );
	}

	/**
	 * پیام‌های وضعیت ادمین.
	 */
	public static function notices() {
		if ( empty( $_GET['mjc_msg'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		$msg = sanitize_text_field( wp_unslash( $_GET['mjc_msg'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		printf( '<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html( $msg ) );
	}

	/**
	 * هدایت با پیام.
	 *
	 * @param string $page صفحه.
	 * @param string $msg پیام.
	 */
	private static function redirect( $page, $msg = 'عملیات با موفقیت انجام شد.' ) {
		wp_safe_redirect( add_query_arg( array( 'page' => $page, 'mjc_msg' => rawurlencode( $msg ) ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * بررسی nonce اکشن.
	 *
	 * @param string $action نام اکشن.
	 * @return bool
	 */
	private static function verify_action( $action ) {
		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_key( $_GET['_wpnonce'] ) : ( isset( $_POST['_wpnonce'] ) ? sanitize_key( $_POST['_wpnonce'] ) : '' );
		return $nonce && wp_verify_nonce( $nonce, $action );
	}

	/**
	 * پردازش اکشن‌های ادمین.
	 */
	public static function handle_actions() {
		// خروجی CSV.
		if ( ! empty( $_GET['mjc_export'] ) && current_user_can( 'mjc_view_reports' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			self::export_csv( sanitize_key( wp_unslash( $_GET['mjc_export'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		// ذخیره‌ی تنظیمات.
		if ( ! empty( $_POST['mjc_settings_nonce'] ) ) {
			MJC_Settings::save_from_request();
			self::redirect( 'mjc-settings', 'تنظیمات ذخیره شد.' );
		}

		// اکشن‌های POST فرم‌های ادمین.
		if ( isset( $_POST['mjc_admin_form'] ) ) {
			$form = sanitize_key( $_POST['mjc_admin_form'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			if ( 'points_adjust' === $form && current_user_can( 'mjc_manage_points' ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_wpnonce'] ?? '' ) ), 'mjc_points_adjust' ) ) {
				$uid    = (int) ( $_POST['user_id'] ?? 0 );
				$amount = (int) ( $_POST['amount'] ?? 0 );
				$desc   = sanitize_text_field( wp_unslash( $_POST['description'] ?? 'اصلاح دستی' ) );
				MJC_Points::manual_adjust( $uid, $amount, $desc );
				self::redirect( 'mjc-points', 'امتیاز اعمال شد.' );
			}
			if ( 'grade_review' === $form && current_user_can( 'mjc_manage_grades' ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_wpnonce'] ?? '' ) ), 'mjc_grade_review' ) ) {
				MJC_Grades::review(
					(int) ( $_POST['request_id'] ?? 0 ),
					(float) ( $_POST['theory_score'] ?? 0 ),
					(float) ( $_POST['practical_score'] ?? 0 ),
					sanitize_key( wp_unslash( $_POST['final_level'] ?? '' ) ),
					sanitize_text_field( wp_unslash( $_POST['notes'] ?? '' ) )
				);
				self::redirect( 'mjc-grades', 'نتیجه‌ی ارزیابی ثبت شد.' );
			}
			if ( 'ticket_reply' === $form && current_user_can( 'mjc_manage_tickets' ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_wpnonce'] ?? '' ) ), 'mjc_ticket_reply' ) ) {
				MJC_Tickets::reply(
					(int) ( $_POST['ticket_id'] ?? 0 ),
					wp_kses_post( wp_unslash( $_POST['admin_reply'] ?? '' ) ),
					sanitize_key( wp_unslash( $_POST['status'] ?? 'answered' ) )
				);
				self::redirect( 'mjc-tickets', 'پاسخ ثبت شد.' );
			}
			if ( 'broadcast' === $form && current_user_can( 'mjc_manage_club' ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_wpnonce'] ?? '' ) ), 'mjc_broadcast' ) ) {
				$count = MJC_Notify::broadcast(
					sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) ),
					sanitize_text_field( wp_unslash( $_POST['body'] ?? '' ) ),
					esc_url_raw( wp_unslash( $_POST['url'] ?? '' ) )
				);
				self::redirect( 'mjc-notify', 'اعلان برای ' . $count . ' عضو ارسال شد.' );
			}
			if ( 'wallet_credit' === $form && current_user_can( 'mjc_manage_wallet' ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_wpnonce'] ?? '' ) ), 'mjc_wallet_credit' ) ) {
				$uid    = (int) ( $_POST['user_id'] ?? 0 );
				$amount = (int) ( $_POST['amount'] ?? 0 );
				$desc   = sanitize_text_field( wp_unslash( $_POST['description'] ?? 'شارژ دستی' ) );
				MJC_Wallet::transaction( $uid, $amount, $amount >= 0 ? 'credit' : 'debit', $desc );
				self::redirect( 'mjc-wallet', 'تراکنش کیف پول ثبت شد.' );
			}
			if ( 'grade_levels' === $form && current_user_can( 'mjc_manage_settings' ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_wpnonce'] ?? '' ) ), 'mjc_grade_levels' ) ) {
				$raw    = isset( $_POST['levels'] ) && is_array( $_POST['levels'] ) ? wp_unslash( $_POST['levels'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
				$levels = array();
				foreach ( (array) $raw as $key => $row ) {
					$key = sanitize_key( $key );
					$levels[ $key ] = array(
						'label'         => sanitize_text_field( $row['label'] ?? '' ),
						'min_theory'    => (float) ( $row['min_theory'] ?? 0 ),
						'min_practical' => (float) ( $row['min_practical'] ?? 0 ),
					);
				}
				mjc_update_settings( array( 'grade_levels' => $levels ) );
				self::redirect( 'mjc-grades', 'سطوح پایه فنی ذخیره شد.' );
			}
			if ( 'project_approve_form' === $form && current_user_can( 'mjc_manage_projects' ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_wpnonce'] ?? '' ) ), 'mjc_project_approve' ) ) {
				$pid    = (int) ( $_POST['project_id'] ?? 0 );
				$amount = (int) mjc_to_en_digits( str_replace( array( ',', '،' ), '', (string) wp_unslash( $_POST['project_amount'] ?? '0' ) ) );
				$area   = (float) mjc_to_en_digits( (string) wp_unslash( $_POST['project_area'] ?? '0' ) );
				$mode   = mjc_get_setting( 'project_points_mode', 'fixed' );
				if ( ! $pid ) {
					self::redirect( 'mjc-projects', 'پروژه یافت نشد.' );
				}
				if ( 'amount' === $mode && $amount <= 0 ) {
					self::redirect( 'mjc-projects', 'برای محاسبه امتیاز بر اساس قیمت، وارد کردن مبلغ پروژه در زمان تأیید الزامی است.' );
				}
				if ( 'area' === $mode && $area <= 0 ) {
					self::redirect( 'mjc-projects', 'برای محاسبه امتیاز بر اساس متراژ، وارد کردن متراژ پروژه الزامی است.' );
				}
				// مبلغ/متراژ در لحظه‌ی تأیید توسط مدیر سایت ثبت می‌شود.
				$data = array( 'updated_at' => current_time( 'mysql' ) );
				if ( $amount > 0 ) {
					$data['amount'] = $amount;
				}
				if ( $area > 0 ) {
					$data['area'] = $area;
				}
				MJC_DB::update( 'projects', $data, array( 'id' => $pid ) );
				MJC_Audit::log( 'project_amount_set', 'project', $pid, 'مبلغ/متراژ پروژه در زمان تأیید توسط مدیر ثبت شد.' );
				MJC_Projects::set_status( $pid, 'approved' );
				self::redirect( 'mjc-projects', 'پروژه تأیید و امتیاز اعمال شد.' );
			}
		}

		// اکشن‌های GET با nonce.
		if ( empty( $_GET['mjc_action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		$action = sanitize_key( wp_unslash( $_GET['mjc_action'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$id     = isset( $_GET['mjc_id'] ) ? (int) $_GET['mjc_id'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! self::verify_action( 'mjc_' . $action . '_' . $id ) ) {
			wp_die( esc_html__( 'خطای امنیتی.', 'mojrian-club' ) );
		}

		switch ( $action ) {
			case 'member_approve':
				if ( current_user_can( 'mjc_manage_members' ) ) {
					MJC_Members::set_status( $id, 'approved' );
					self::redirect( 'mjc-members', 'عضو تأیید شد.' );
				}
				break;
			case 'member_suspend':
				if ( current_user_can( 'mjc_manage_members' ) ) {
					MJC_Members::set_status( $id, 'suspended' );
					self::redirect( 'mjc-members', 'عضو معلق شد.' );
				}
				break;
			case 'member_reject':
				if ( current_user_can( 'mjc_manage_members' ) ) {
					$reason = isset( $_GET['reason'] ) ? sanitize_text_field( wp_unslash( $_GET['reason'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					MJC_Members::set_status( $id, 'rejected', $reason );
					self::redirect( 'mjc-members', 'عضو رد شد.' );
				}
				break;
			case 'project_approve':
				if ( current_user_can( 'mjc_manage_projects' ) ) {
					MJC_Projects::set_status( $id, 'approved' );
					self::redirect( 'mjc-projects', 'پروژه تأیید و امتیاز اعمال شد.' );
				}
				break;
			case 'project_reject':
				if ( current_user_can( 'mjc_manage_projects' ) ) {
					$reason = isset( $_GET['reason'] ) ? sanitize_text_field( wp_unslash( $_GET['reason'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					MJC_Projects::set_status( $id, 'rejected', $reason );
					self::redirect( 'mjc-projects', 'پروژه رد شد.' );
				}
				break;
			case 'reward_approve':
				if ( current_user_can( 'mjc_manage_rewards' ) ) {
					MJC_Rewards::set_status( $id, 'approved' );
					self::redirect( 'mjc-reward-requests', 'درخواست جایزه تأیید شد.' );
				}
				break;
			case 'reward_ship':
				if ( current_user_can( 'mjc_manage_rewards' ) ) {
					MJC_Rewards::set_status( $id, 'shipped' );
					self::redirect( 'mjc-reward-requests', 'جایزه ارسال شد.' );
				}
				break;
			case 'reward_reject':
				if ( current_user_can( 'mjc_manage_rewards' ) ) {
					$reason = isset( $_GET['reason'] ) ? sanitize_text_field( wp_unslash( $_GET['reason'] ) ) : 'عدم تأیید'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					MJC_Rewards::set_status( $id, 'rejected', $reason );
					self::redirect( 'mjc-reward-requests', 'درخواست رد و امتیاز بازگردانده شد.' );
				}
				break;
			case 'wallet_approve':
				if ( current_user_can( 'mjc_manage_wallet' ) ) {
					MJC_Wallet::review_withdraw( $id, 'approved' );
					self::redirect( 'mjc-wallet', 'درخواست برداشت تأیید شد.' );
				}
				break;
			case 'wallet_reject':
				if ( current_user_can( 'mjc_manage_wallet' ) ) {
					MJC_Wallet::review_withdraw( $id, 'rejected' );
					self::redirect( 'mjc-wallet', 'درخواست برداشت رد شد.' );
				}
				break;
			case 'lottery_draw':
				if ( current_user_can( 'mjc_manage_campaigns' ) ) {
					$count   = isset( $_GET['winners'] ) ? max( 1, (int) $_GET['winners'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$winners = MJC_Campaigns::draw( $id, $count );
					self::redirect( 'mjc-dashboard', $winners ? 'قرعه‌کشی انجام شد: ' . count( $winners ) . ' برنده.' : 'شرکت‌کننده‌ای وجود ندارد.' );
				}
				break;
			case 'course_complete':
				if ( current_user_can( 'mjc_manage_content' ) ) {
					$uid = isset( $_GET['mjc_uid'] ) ? (int) $_GET['mjc_uid'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					MJC_Courses::complete( $uid, $id );
					self::redirect( 'mjc-dashboard', 'دوره تکمیل و امتیاز اعمال شد.' );
				}
				break;
		}
	}

	/**
	 * خروجی CSV.
	 *
	 * @param string $type نوع گزارش.
	 */
	private static function export_csv( $type ) {
		if ( ! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ?? '' ), 'mjc_export_' . $type ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			wp_die( esc_html__( 'خطای امنیتی.', 'mojrian-club' ) );
		}
		$filename = 'mjc-' . $type . '-' . gmdate( 'Ymd-His' ) . '.csv';
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		$out = fopen( 'php://output', 'w' );
		fwrite( $out, "\xEF\xBB\xBF" ); // BOM برای اکسل.

		if ( 'members' === $type ) {
			fputcsv( $out, array( 'ID', 'نام', 'موبایل', 'کد ملی', 'استان', 'شهر', 'وضعیت', 'امتیاز', 'کیف پول', 'پایه فنی', 'تاریخ عضویت' ) );
			$rows = MJC_DB::results( 'SELECT * FROM ' . mjc_table( 'members' ) . ' ORDER BY id DESC LIMIT 5000' ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			foreach ( (array) $rows as $r ) {
				fputcsv( $out, array( $r->id, $r->full_name, $r->mobile, $r->national_code, $r->province, $r->city, $r->status, $r->points_balance, $r->wallet_balance, $r->grade, $r->created_at ) );
			}
		} elseif ( 'points' === $type ) {
			fputcsv( $out, array( 'ID', 'User ID', 'مقدار', 'موجودی', 'اقدام', 'شرح', 'تاریخ' ) );
			$rows = MJC_DB::results( 'SELECT * FROM ' . mjc_table( 'points_ledger' ) . ' ORDER BY id DESC LIMIT 5000' ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			foreach ( (array) $rows as $r ) {
				fputcsv( $out, array( $r->id, $r->user_id, $r->amount, $r->balance_after, $r->action, $r->description, $r->created_at ) );
			}
		}
		fclose( $out );
		exit;
	}

	/* -------------------------------------------------------------------
	 * صفحات
	 * ---------------------------------------------------------------- */

	/**
	 * هدر صفحه.
	 *
	 * @param string $title عنوان.
	 */
	private static function page_header( $title ) {
		echo '<div class="wrap mjc-admin-wrap"><h1 class="wp-heading-inline">' . esc_html( $title ) . '</h1><hr class="wp-header-end" />';
	}

	/**
	 * دکمه‌ی اکشن.
	 *
	 * @param string $action اکشن.
	 * @param int    $id شناسه.
	 * @param string $label برچسب.
	 * @param string $class کلاس.
	 * @param array  $extra پارامتر اضافه.
	 * @return string
	 */
	private static function action_btn( $action, $id, $label, $class = 'button-primary', $extra = array() ) {
		$url = wp_nonce_url( add_query_arg( array_merge( array( 'page' => sanitize_key( $_GET['page'] ?? 'mjc-dashboard' ), 'mjc_action' => $action, 'mjc_id' => $id ), $extra ), admin_url( 'admin.php' ) ), 'mjc_' . $action . '_' . $id ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return '<a class="button ' . esc_attr( $class ) . '" href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a> ';
	}

	/**
	 * داشبورد.
	 */
	public static function page_dashboard() {
		if ( ! current_user_can( 'mjc_manage_club' ) ) {
			wp_die( 'دسترسی غیرمجاز.' );
		}
		$member_stats  = MJC_Members::stats();
		$project_stats = MJC_Projects::stats();
		$pending_rewards = (int) MJC_DB::var( "SELECT COUNT(*) FROM " . mjc_table( 'reward_requests' ) . " WHERE status = 'pending'" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$pending_grades  = (int) MJC_DB::var( "SELECT COUNT(*) FROM " . mjc_table( 'grade_requests' ) . " WHERE status = 'pending'" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$open_tickets    = (int) MJC_DB::var( "SELECT COUNT(*) FROM " . mjc_table( 'tickets' ) . " WHERE status = 'open'" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$points_month    = (int) MJC_DB::var( "SELECT COALESCE(SUM(amount),0) FROM " . mjc_table( 'points_ledger' ) . " WHERE amount > 0 AND created_at >= %s", array( date( 'Y-m-01 00:00:00' ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		self::page_header( 'داشبورد باشگاه مجریان' );
		$cards = array(
			array( 'کل اعضا', $member_stats['total'], 'mjc-members' ),
			array( 'در انتظار تأیید', $member_stats['pending'], 'mjc-members' ),
			(array( 'پروژه‌های در انتظار', $project_stats['pending'], 'mjc-projects' )),
			array( 'امتیاز اهداشده این ماه', $points_month, 'mjc-points' ),
			array( 'درخواست جایزه در انتظار', $pending_rewards, 'mjc-reward-requests' ),
			array( 'درخواست پایه فنی', $pending_grades, 'mjc-grades' ),
			array( 'تیکت‌های باز', $open_tickets, 'mjc-tickets' ),
			array( 'عضو تأییدشده', $member_stats['approved'], 'mjc-members' ),
		);
		echo '<div class="mjc-kpi-grid">';
		foreach ( $cards as $card ) {
			$card = (array) $card;
			echo '<div class="mjc-kpi"><span class="mjc-kpi-num">' . esc_html( mjc_num( number_format( (float) $card[1] ) ) ) . '</span><span class="mjc-kpi-label">' . esc_html( $card[0] ) . '</span>';
			echo '<a href="' . esc_url( admin_url( 'admin.php?page=' . $card[2] ) ) . '">مشاهده</a></div>';
		}
		echo '</div>';

		// آخرین اعضا.
		echo '<div class="mjc-admin-cols"><div class="mjc-admin-box"><h2>آخرین اعضا</h2><table class="widefat striped"><thead><tr><th>نام</th><th>موبایل</th><th>وضعیت</th><th>تاریخ</th></tr></thead><tbody>';
		$latest = MJC_DB::results( 'SELECT * FROM ' . mjc_table( 'members' ) . ' ORDER BY id DESC LIMIT 8' ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		foreach ( (array) $latest as $m ) {
			echo '<tr><td><strong>' . esc_html( MJC_Members::display_name( $m ) ) . '</strong></td><td>' . esc_html( MJC_SMS::pretty_mobile( $m->mobile ) ) . '</td><td>' . esc_html( mjc_status_label( $m->status ) ) . '</td><td>' . esc_html( mjc_date( $m->created_at, false ) ) . '</td></tr>';
		}
		echo '</tbody></table></div>';

		// آخرین پروژه‌ها.
		echo '<div class="mjc-admin-box"><h2>آخرین پروژه‌ها</h2><table class="widefat striped"><thead><tr><th>عنوان</th><th>وضعیت</th><th>تاریخ</th></tr></thead><tbody>';
		$latest_p = MJC_DB::results( 'SELECT * FROM ' . mjc_table( 'projects' ) . ' ORDER BY id DESC LIMIT 8' ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		foreach ( (array) $latest_p as $p ) {
			echo '<tr><td><strong>' . esc_html( $p->title ) . '</strong></td><td>' . esc_html( mjc_status_label( $p->status ) ) . '</td><td>' . esc_html( mjc_date( $p->created_at, false ) ) . '</td></tr>';
		}
		echo '</tbody></table></div></div>';

		// قرعه‌کشی سریع کمپین‌ها.
		$campaigns = get_posts( array( 'post_type' => 'mjc_campaign', 'post_status' => 'publish', 'numberposts' => 5 ) );
		if ( $campaigns ) {
			echo '<div class="mjc-admin-box"><h2>قرعه‌کشی کمپین‌ها</h2><table class="widefat striped"><thead><tr><th>کمپین</th><th>برندگان</th><th>اقدام</th></tr></thead><tbody>';
			foreach ( $campaigns as $c ) {
				$winners = MJC_Campaigns::winners( $c->ID );
				echo '<tr><td>' . esc_html( $c->post_title ) . '</td><td>' . ( $winners ? esc_html( count( $winners ) ) : '—' ) . '</td><td>' . self::action_btn( 'lottery_draw', $c->ID, 'قرعه‌کشی', 'button-secondary', array( 'winners' => 1 ) ) . '</td></tr>';
			}
			echo '</tbody></table></div>';
		}
		echo '</div>';
	}

	/**
	 * اعضا.
	 */
	public static function page_members() {
		if ( ! current_user_can( 'mjc_manage_members' ) ) {
			wp_die( 'دسترسی غیرمجاز.' );
		}
		self::page_header( 'اعضای باشگاه' );

		// جست‌وجو و فیلتر.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		$status = isset( $_GET['mstatus'] ) ? sanitize_key( wp_unslash( $_GET['mstatus'] ) ) : '';
		$paged  = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1;
		// phpcs:enable
		global $wpdb;
		$where  = array( '1=1' );
		$args   = array();
		if ( $search ) {
			$where[] = '(full_name LIKE %s OR mobile LIKE %s OR national_code LIKE %s)';
			$like    = '%' . $wpdb->esc_like( $search ) . '%';
			array_push( $args, $like, $like, $like );
		}
		if ( $status ) {
			$where[] = 'status = %s';
			$args[]  = $status;
		}
		$where_sql = implode( ' AND ', $where );
		$per_page  = 20;
		$total     = (int) MJC_DB::var( 'SELECT COUNT(*) FROM ' . mjc_table( 'members' ) . ' WHERE ' . $where_sql, $args ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$offset    = ( $paged - 1 ) * $per_page;
		$args2     = array_merge( $args, array( $per_page, $offset ) );
		$rows      = MJC_DB::results( 'SELECT * FROM ' . mjc_table( 'members' ) . ' WHERE ' . $where_sql . ' ORDER BY id DESC LIMIT %d OFFSET %d', $args2 ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		echo '<form method="get" class="mjc-filters"><input type="hidden" name="page" value="mjc-members" />';
		echo '<input type="text" name="s" value="' . esc_attr( $search ) . '" placeholder="جست‌وجو (نام، موبایل، کد ملی)" />';
		echo '<select name="mstatus"><option value="">همه وضعیت‌ها</option>';
		foreach ( array( 'pending' => 'در انتظار بررسی', 'approved' => 'تأیید شده', 'rejected' => 'رد شده', 'suspended' => 'معلق' ) as $k => $v ) {
			echo '<option value="' . esc_attr( $k ) . '" ' . selected( $status, $k, false ) . '>' . esc_html( $v ) . '</option>';
		}
		echo '</select><button class="button">فیلتر</button>';
		echo '<a class="button" href="' . esc_url( wp_nonce_url( admin_url( 'admin.php?mjc_export=members' ), 'mjc_export_members' ) ) . '">خروجی CSV</a></form>';

		$table             = new MJC_List_Table();
		$table->columns_cfg = array(
			'cb'       => '<input type="checkbox" />',
			'name'     => 'نام',
			'mobile'   => 'موبایل',
			'city'     => 'استان/شهر',
			'grade'    => 'پایه فنی',
			'points'   => 'امتیاز',
			'status'   => 'وضعیت',
			'created'  => 'تاریخ عضویت',
			'actions'  => 'اقدامات',
		);
		$table->rows_data   = $rows ? $rows : array();
		$table->row_formatter = function ( $m ) {
			return array(
				'name'    => '<strong>' . esc_html( MJC_Members::display_name( $m ) ) . '</strong><br /><span class="description">' . esc_html( $m->national_code ) . '</span>',
				'mobile'  => esc_html( MJC_SMS::pretty_mobile( $m->mobile ) ),
				'city'    => esc_html( trim( $m->province . ' / ' . $m->city, ' /' ) ),
				'grade'   => $m->grade ? esc_html( MJC_Grades::level_label( $m->grade ) ) : '—',
				'points'  => esc_html( mjc_num( number_format( (float) $m->points_balance ) ) ),
				'status'  => esc_html( mjc_status_label( $m->status ) ),
				'created' => esc_html( mjc_date( $m->created_at, false ) ),
				'actions' => ( 'approved' !== $m->status ? MJC_Admin::action_btn( 'member_approve', $m->id, 'تأیید' ) : '' )
					. ( 'suspended' !== $m->status && 'approved' === $m->status ? MJC_Admin::action_btn( 'member_suspend', $m->id, 'تعلیق', 'button-secondary' ) : '' )
					. ( 'rejected' !== $m->status ? MJC_Admin::action_btn( 'member_reject', $m->id, 'رد', 'button-secondary', array( 'reason' => 'عدم احراز شرایط' ) ) : '' ),
			);
		};
		$table->prepare_items();
		$table->display();

		$pages = ceil( $total / $per_page );
		if ( $pages > 1 ) {
			echo '<div class="tablenav"><div class="tablenav-pages">';
			for ( $i = 1; $i <= $pages; $i++ ) {
				printf( '<a class="button %s" href="%s">%s</a> ', $i === (int) $paged ? 'button-primary' : '', esc_url( add_query_arg( array( 'page' => 'mjc-members', 'paged' => $i, 's' => $search, 'mstatus' => $status ), admin_url( 'admin.php' ) ) ), esc_html( mjc_num( $i ) ) );
			}
			echo '</div></div>';
		}
		echo '</div>';
	}

	/**
	 * پروژه‌ها.
	 */
	public static function page_projects() {
		if ( ! current_user_can( 'mjc_manage_projects' ) ) {
			wp_die( 'دسترسی غیرمجاز.' );
		}
		self::page_header( 'پروژه‌های ثبت‌شده' );

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$status = isset( $_GET['mstatus'] ) ? sanitize_key( wp_unslash( $_GET['mstatus'] ) ) : 'pending';
		// phpcs:enable
		global $wpdb;
		$sql  = 'SELECT p.*, m.full_name, m.mobile FROM ' . mjc_table( 'projects' ) . ' p LEFT JOIN ' . mjc_table( 'members' ) . ' m ON m.user_id = p.user_id';
		$rows = MJC_DB::results( $sql . ( $status ? $wpdb->prepare( ' WHERE p.status = %s', $status ) : '' ) . ' ORDER BY p.id DESC LIMIT 50' ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		echo '<p><a class="button ' . ( 'pending' === $status ? 'button-primary' : '' ) . '" href="' . esc_url( admin_url( 'admin.php?page=mjc-projects&mstatus=pending' ) ) . '">در انتظار بررسی</a> ';
		echo '<a class="button ' . ( '' === $status ? 'button-primary' : '' ) . '" href="' . esc_url( admin_url( 'admin.php?page=mjc-projects&mstatus=' ) ) . '">همه</a></p>';

		$table              = new MJC_List_Table();
		$table->columns_cfg = array(
			'title'   => 'عنوان پروژه',
			'owner'   => 'ثبت‌کننده',
			'type'    => 'نوع',
			'units'   => 'واحد',
			'amount'  => 'مبلغ/متراژ',
			'date'    => 'تاریخ ثبت',
			'status'  => 'وضعیت',
			'actions' => 'اقدامات',
		);
		$table->rows_data = $rows ? $rows : array();
		$table->row_formatter = function ( $p ) {
			$gallery = '';
			$images  = json_decode( (string) $p->images, true );
			foreach ( (array) $images as $img ) {
				$gallery .= wp_get_attachment_image( (int) $img, 'thumbnail' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			return array(
				'title'   => '<strong>' . esc_html( $p->title ) . '</strong>' . ( $p->description ? '<br /><span class="description">' . esc_html( wp_trim_words( wp_strip_all_tags( $p->description ), 12 ) ) . '</span>' : '' ) . '<br />' . $gallery,
				'owner'   => esc_html( ( $p->full_name ? $p->full_name : '—' ) . ' (' . MJC_SMS::pretty_mobile( $p->mobile ) . ')' ),
				'type'    => esc_html( MJC_Projects::type_label( $p->project_type ) ),
				'units'   => esc_html( mjc_num( $p->units ) ),
				'amount'  => ( $p->amount > 0 ? esc_html( mjc_money( $p->amount ) ) . ' ریال' : '<em>—</em>' ) . '<br />' . ( $p->area > 0 ? esc_html( mjc_num( $p->area ) ) . ' متر' : '' ) . '<br /><span class="description">مبلغ پروژه در زمان تأیید توسط مدیر تعیین می‌شود</span>',
				'date'    => esc_html( mjc_date( $p->created_at, false ) ),
				'status'  => esc_html( mjc_status_label( $p->status ) ) . ( 'approved' === $p->status ? '<br /><span class="description">+' . esc_html( mjc_num( $p->points_awarded ) ) . ' امتیاز</span>' : '' ),
				'actions' => ( 'approved' !== $p->status
					? '<form method="post" style="margin:0 0 6px">' . wp_nonce_field( 'mjc_project_approve', '_wpnonce', true, false ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						. '<input type="hidden" name="mjc_admin_form" value="project_approve_form" /><input type="hidden" name="project_id" value="' . esc_attr( $p->id ) . '" />'
						. '<label style="display:block;margin-bottom:4px">مبلغ (ریال): <input type="text" name="project_amount" value="' . esc_attr( $p->amount ) . '" style="width:110px" /></label>'
						. '<label style="display:block;margin-bottom:6px">متراژ: <input type="text" name="project_area" value="' . esc_attr( $p->area ) . '" style="width:70px" /></label>'
						. '<button type="submit" class="button button-primary">تأیید و اعمال امتیاز</button></form>'
					: '' )
					. ( 'rejected' !== $p->status ? MJC_Admin::action_btn( 'project_reject', $p->id, 'رد', 'button-secondary', array( 'reason' => 'مغایرت با شرایط ثبت پروژه' ) ) : '' ),
			);
		};
		$table->prepare_items();
		$table->display();
		echo '</div>';
	}

	/**
	 * امتیازها.
	 */
	public static function page_points() {
		if ( ! current_user_can( 'mjc_manage_points' ) ) {
			wp_die( 'دسترسی غیرمجاز.' );
		}
		self::page_header( 'مدیریت امتیازها' );
		$rules = mjc_get_setting( 'points_rules', MJC_Settings::default_rules() );

		echo '<div class="mjc-admin-cols">';
		// قوانین.
		echo '<div class="mjc-admin-box"><h2>قوانین امتیازدهی</h2><form method="post">';
		wp_nonce_field( 'mjc_save_settings', 'mjc_settings_nonce' );
		echo '<table class="widefat striped"><thead><tr><th>فعال</th><th>اقدام</th><th>امتیاز</th></tr></thead><tbody>';
		foreach ( (array) $rules as $key => $rule ) {
			echo '<tr><td><input type="checkbox" name="rules[' . esc_attr( $key ) . '][enabled]" value="1" ' . checked( ! empty( $rule['enabled'] ), true, false ) . ' /></td>';
			echo '<td>' . esc_html( $rule['label'] ) . '</td>';
			echo '<td><input type="number" name="rules[' . esc_attr( $key ) . '][points]" value="' . esc_attr( $rule['points'] ) . '" style="width:90px" /></td></tr>';
		}
		echo '</tbody></table><p><button class="button-primary">ذخیره قوانین</button></p></form>';

		// اصلاح دستی.
		echo '<h2>افزایش/کسر دستی امتیاز</h2><form method="post">';
		wp_nonce_field( 'mjc_points_adjust', '_wpnonce' );
		echo '<input type="hidden" name="mjc_admin_form" value="points_adjust" />';
		echo '<p><label>شناسه کاربر (User ID): <input type="number" name="user_id" required /></label></p>';
		echo '<p><label>مقدار (منفی برای کسر): <input type="number" name="amount" required /></label></p>';
		echo '<p><label>دلیل: <input type="text" name="description" required style="width:300px" /></label></p>';
		echo '<button class="button-primary">اعمال</button></form></div>';

		// دفتر کل.
		echo '<div class="mjc-admin-box"><h2>آخرین تراکنش‌های امتیاز</h2>';
		echo '<p><a class="button" href="' . esc_url( wp_nonce_url( admin_url( 'admin.php?mjc_export=points' ), 'mjc_export_points' ) ) . '">خروجی CSV</a></p>';
		echo '<table class="widefat striped"><thead><tr><th>تاریخ</th><th>کاربر</th><th>شرح</th><th>مقدار</th><th>موجودی</th></tr></thead><tbody>';
		$ledger = MJC_DB::results( 'SELECT * FROM ' . mjc_table( 'points_ledger' ) . ' ORDER BY id DESC LIMIT 25' ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		foreach ( (array) $ledger as $l ) {
			$u = get_userdata( (int) $l->user_id );
			echo '<tr><td>' . esc_html( mjc_date( $l->created_at ) ) . '</td><td>' . esc_html( $u ? $u->display_name : $l->user_id ) . '</td><td>' . esc_html( $l->description ) . '</td><td>' . esc_html( ( $l->amount > 0 ? '+' : '' ) . mjc_num( $l->amount ) ) . '</td><td>' . esc_html( mjc_num( number_format( (float) $l->balance_after ) ) ) . '</td></tr>';
		}
		echo '</tbody></table></div></div></div>';
	}

	/**
	 * پایه فنی.
	 */
	public static function page_grades() {
		if ( ! current_user_can( 'mjc_manage_grades' ) ) {
			wp_die( 'دسترسی غیرمجاز.' );
		}
		self::page_header( 'پایه فنی (ارزیابی مجریان)' );

		// تنظیم سطوح.
		$levels = MJC_Grades::levels();
		echo '<div class="mjc-admin-box"><h2>سطوح پایه فنی</h2><form method="post">';
		wp_nonce_field( 'mjc_grade_levels', '_wpnonce' );
		echo '<input type="hidden" name="mjc_admin_form" value="grade_levels" />';
		echo '<table class="widefat striped"><thead><tr><th>کلید</th><th>عنوان</th><th>حداقل نمره تئوری</th><th>حداقل نمره عملی</th></tr></thead><tbody>';
		foreach ( $levels as $key => $row ) {
			echo '<tr><td><code>' . esc_html( $key ) . '</code></td>';
			echo '<td><input type="text" name="levels[' . esc_attr( $key ) . '][label]" value="' . esc_attr( $row['label'] ) . '" /></td>';
			echo '<td><input type="number" step="0.5" name="levels[' . esc_attr( $key ) . '][min_theory]" value="' . esc_attr( $row['min_theory'] ) . '" /></td>';
			echo '<td><input type="number" step="0.5" name="levels[' . esc_attr( $key ) . '][min_practical]" value="' . esc_attr( $row['min_practical'] ) . '" /></td></tr>';
		}
		echo '</tbody></table><p><button class="button-primary">ذخیره سطوح</button></p></form></div>';

		// درخواست‌ها.
		echo '<div class="mjc-admin-box"><h2>درخواست‌های ارزیابی</h2><table class="widefat striped"><thead><tr><th>عضو</th><th>سطح درخواستی</th><th>تاریخ</th><th>وضعیت</th><th>ثبت نتیجه</th></tr></thead><tbody>';
		$requests = MJC_DB::results( 'SELECT g.*, m.full_name, m.mobile, m.user_id AS m_user FROM ' . mjc_table( 'grade_requests' ) . ' g LEFT JOIN ' . mjc_table( 'members' ) . ' m ON m.user_id = g.user_id ORDER BY g.id DESC LIMIT 30' ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		foreach ( (array) $requests as $r ) {
			echo '<tr><td><strong>' . esc_html( $r->full_name ? $r->full_name : '—' ) . '</strong><br /><span class="description">' . esc_html( MJC_SMS::pretty_mobile( $r->mobile ) ) . '</span></td>';
			echo '<td>' . esc_html( MJC_Grades::level_label( $r->requested_level ) ) . '</td>';
			echo '<td>' . esc_html( mjc_date( $r->created_at, false ) ) . '</td>';
			echo '<td>' . esc_html( mjc_status_label( $r->status ) ) . ( $r->final_level ? ' — ' . esc_html( MJC_Grades::level_label( $r->final_level ) ) : '' ) . '</td><td>';
			if ( 'pending' === $r->status ) {
				echo '<details><summary class="button button-small">ثبت نتیجه</summary><form method="post" style="margin-top:8px">';
				wp_nonce_field( 'mjc_grade_review', '_wpnonce' );
				echo '<input type="hidden" name="mjc_admin_form" value="grade_review" /><input type="hidden" name="request_id" value="' . esc_attr( $r->id ) . '" />';
				echo '<p>نمره تئوری: <input type="number" step="0.5" name="theory_score" style="width:70px" /> نمره عملی: <input type="number" step="0.5" name="practical_score" style="width:70px" /></p>';
				echo '<p>سطح اعطایی: <select name="final_level"><option value="">رد درخواست</option>';
				foreach ( MJC_Grades::levels() as $k => $row ) {
					echo '<option value="' . esc_attr( $k ) . '">' . esc_html( $row['label'] ) . '</option>';
				}
				echo '</select></p><p>یادداشت: <input type="text" name="notes" style="width:220px" /></p>';
				echo '<button class="button-primary">ثبت نتیجه</button></form></details>';
			} else {
				echo '—';
			}
			echo '</td></tr>';
		}
		echo '</tbody></table></div></div>';
	}

	/**
	 * درخواست‌های جایزه.
	 */
	public static function page_reward_requests() {
		if ( ! current_user_can( 'mjc_manage_rewards' ) ) {
			wp_die( 'دسترسی غیرمجاز.' );
		}
		self::page_header( 'درخواست‌های جایزه' );
		echo '<table class="widefat striped"><thead><tr><th>عضو</th><th>جایزه</th><th>امتیاز</th><th>نشانی</th><th>تاریخ</th><th>وضعیت</th><th>اقدامات</th></tr></thead><tbody>';
		$requests = MJC_DB::results( 'SELECT r.*, m.full_name, m.mobile, p.post_title FROM ' . mjc_table( 'reward_requests' ) . ' r LEFT JOIN ' . mjc_table( 'members' ) . ' m ON m.user_id = r.user_id LEFT JOIN ' . $GLOBALS['wpdb']->posts . ' p ON p.ID = r.reward_id ORDER BY r.id DESC LIMIT 40' ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		foreach ( (array) $requests as $r ) {
			echo '<tr><td><strong>' . esc_html( $r->full_name ? $r->full_name : '—' ) . '</strong></td><td>' . esc_html( $r->post_title ? $r->post_title : '—' ) . '</td>';
			echo '<td>' . esc_html( mjc_num( $r->points ) ) . '</td><td>' . esc_html( $r->shipping_address ) . '</td>';
			echo '<td>' . esc_html( mjc_date( $r->created_at, false ) ) . '</td><td>' . esc_html( mjc_status_label( $r->status ) ) . '</td><td>';
			if ( 'pending' === $r->status ) {
				echo MJC_Admin::action_btn( 'reward_approve', $r->id, 'تأیید' );
				echo MJC_Admin::action_btn( 'reward_reject', $r->id, 'رد', 'button-secondary', array( 'reason' => 'عدم تأیید' ) );
			} elseif ( 'approved' === $r->status ) {
				echo MJC_Admin::action_btn( 'reward_ship', $r->id, 'ارسال شد' );
			} else {
				echo '—';
			}
			echo '</td></tr>';
		}
		echo '</tbody></table></div>';
	}

	/**
	 * کیف پول.
	 */
	public static function page_wallet() {
		if ( ! current_user_can( 'mjc_manage_wallet' ) ) {
			wp_die( 'دسترسی غیرمجاز.' );
		}
		self::page_header( 'کیف پول اعضا' );

		echo '<div class="mjc-admin-box"><h2>شارژ/برداشت دستی</h2><form method="post">';
		wp_nonce_field( 'mjc_wallet_credit', '_wpnonce' );
		echo '<input type="hidden" name="mjc_admin_form" value="wallet_credit" />';
		echo '<p><label>شناسه کاربر: <input type="number" name="user_id" required /></label> ';
		echo '<label>مبلغ (ریال، منفی برای برداشت): <input type="number" name="amount" required /></label> ';
		echo '<label>شرح: <input type="text" name="description" required /></label> <button class="button-primary">ثبت</button></p></form></div>';

		echo '<div class="mjc-admin-box"><h2>آخرین تراکنش‌ها</h2><table class="widefat striped"><thead><tr><th>تاریخ</th><th>کاربر</th><th>نوع</th><th>مبلغ</th><th>وضعیت</th><th>اقدام</th></tr></thead><tbody>';
		$txs = MJC_DB::results( 'SELECT t.*, m.full_name FROM ' . mjc_table( 'wallet_transactions' ) . ' t LEFT JOIN ' . mjc_table( 'members' ) . ' m ON m.user_id = t.user_id ORDER BY t.id DESC LIMIT 30' ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		foreach ( (array) $txs as $tx ) {
			echo '<tr><td>' . esc_html( mjc_date( $tx->created_at ) ) . '</td><td>' . esc_html( $tx->full_name ? $tx->full_name : $tx->user_id ) . '</td>';
			echo '<td>' . esc_html( mjc_status_label( $tx->type ) ) . '</td><td>' . esc_html( mjc_money( $tx->amount ) ) . '</td><td>' . esc_html( mjc_status_label( $tx->status ) ) . '</td><td>';
			if ( 'pending' === $tx->status ) {
				echo MJC_Admin::action_btn( 'wallet_approve', $tx->id, 'تأیید' );
				echo MJC_Admin::action_btn( 'wallet_reject', $tx->id, 'رد', 'button-secondary' );
			} else {
				echo '—';
			}
			echo '</td></tr>';
		}
		echo '</tbody></table></div></div>';
	}

	/**
	 * تیکت‌ها.
	 */
	public static function page_tickets() {
		if ( ! current_user_can( 'mjc_manage_tickets' ) ) {
			wp_die( 'دسترسی غیرمجاز.' );
		}
		self::page_header( 'تیکت‌ها و پیام‌ها' );
		echo '<table class="widefat striped"><thead><tr><th>موضوع</th><th>کاربر</th><th>تاریخ</th><th>وضعیت</th><th>پاسخ</th></tr></thead><tbody>';
		$tickets = MJC_DB::results( 'SELECT t.*, m.full_name, m.mobile FROM ' . mjc_table( 'tickets' ) . ' t LEFT JOIN ' . mjc_table( 'members' ) . ' m ON m.user_id = t.user_id ORDER BY t.id DESC LIMIT 40' ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		foreach ( (array) $tickets as $t ) {
			echo '<tr><td><strong>' . esc_html( $t->subject ) . '</strong><br /><span class="description">' . esc_html( wp_trim_words( wp_strip_all_tags( $t->message ), 15 ) ) . '</span></td>';
			echo '<td>' . esc_html( $t->full_name ? $t->full_name : 'مهمان' ) . '<br /><span class="description">' . esc_html( MJC_SMS::pretty_mobile( $t->mobile ) ) . '</span></td>';
			echo '<td>' . esc_html( mjc_date( $t->created_at, false ) ) . '</td><td>' . esc_html( mjc_status_label( $t->status ) ) . '</td><td>';
			echo '<details><summary class="button button-small">پاسخ</summary><form method="post" style="margin-top:8px">';
			wp_nonce_field( 'mjc_ticket_reply', '_wpnonce' );
			echo '<input type="hidden" name="mjc_admin_form" value="ticket_reply" /><input type="hidden" name="ticket_id" value="' . esc_attr( $t->id ) . '" />';
			echo '<textarea name="admin_reply" rows="3" style="width:100%">' . esc_textarea( $t->admin_reply ) . '</textarea>';
			echo '<p><select name="status"><option value="answered">پاسخ داده شده</option><option value="closed">بسته شده</option><option value="open">باز</option></select> <button class="button-primary">ثبت پاسخ</button></p></form></details>';
			echo '</td></tr>';
		}
		echo '</tbody></table></div>';
	}

	/**
	 * اعلان همگانی.
	 */
	public static function page_notify() {
		if ( ! current_user_can( 'mjc_manage_club' ) ) {
			wp_die( 'دسترسی غیرمجاز.' );
		}
		self::page_header( 'ارسال اعلان همگانی' );
		echo '<div class="mjc-admin-box"><form method="post">';
		wp_nonce_field( 'mjc_broadcast', '_wpnonce' );
		echo '<input type="hidden" name="mjc_admin_form" value="broadcast" />';
		echo '<p><label>عنوان: <input type="text" name="title" required style="width:400px" /></label></p>';
		echo '<p><label>متن اعلان:<br /><textarea name="body" rows="5" style="width:600px" required></textarea></label></p>';
		echo '<p><label>نشانی (اختیاری): <input type="text" name="url" style="width:400px" placeholder="https://..." /></label></p>';
		echo '<button class="button-primary">ارسال برای همه‌ی اعضای تأییدشده</button></form></div></div>';
	}

	/**
	 * گزارش‌ها.
	 */
	public static function page_reports() {
		if ( ! current_user_can( 'mjc_view_reports' ) ) {
			wp_die( 'دسترسی غیرمجاز.' );
		}
		self::page_header( 'گزارش‌ها' );

		$month_start = date( 'Y-m-01 00:00:00' );
		$stats       = array(
			'اعضای جدید این ماه' => (int) MJC_DB::var( 'SELECT COUNT(*) FROM ' . mjc_table( 'members' ) . ' WHERE created_at >= %s', array( $month_start ) ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'پروژه‌های این ماه'   => (int) MJC_DB::var( 'SELECT COUNT(*) FROM ' . mjc_table( 'projects' ) . ' WHERE created_at >= %s', array( $month_start ) ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'امتیاز اهداشده این ماه' => (int) MJC_DB::var( 'SELECT COALESCE(SUM(amount),0) FROM ' . mjc_table( 'points_ledger' ) . ' WHERE amount > 0 AND created_at >= %s', array( $month_start ) ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'امتیاز مصرف‌شده این ماه' => (int) MJC_DB::var( 'SELECT COALESCE(SUM(-amount),0) FROM ' . mjc_table( 'points_ledger' ) . ' WHERE amount < 0 AND created_at >= %s', array( $month_start ) ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'جوایز تحویل‌شده' => (int) MJC_DB::var( "SELECT COUNT(*) FROM " . mjc_table( 'reward_requests' ) . " WHERE status = 'shipped'" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'گردش کیف پول (ریال)' => (int) MJC_DB::var( 'SELECT COALESCE(SUM(amount),0) FROM ' . mjc_table( 'wallet_transactions' ) . " WHERE status = 'done'" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);

		echo '<div class="mjc-kpi-grid">';
		foreach ( $stats as $label => $value ) {
			echo '<div class="mjc-kpi"><span class="mjc-kpi-num">' . esc_html( mjc_num( number_format( (float) $value ) ) ) . '</span><span class="mjc-kpi-label">' . esc_html( $label ) . '</span></div>';
		}
		echo '</div>';

		echo '<p><a class="button button-primary" href="' . esc_url( wp_nonce_url( admin_url( 'admin.php?mjc_export=members' ), 'mjc_export_members' ) ) . '">خروجی CSV اعضا</a> ';
		echo '<a class="button button-primary" href="' . esc_url( wp_nonce_url( admin_url( 'admin.php?mjc_export=points' ), 'mjc_export_points' ) ) . '">خروجی CSV امتیازها</a></p>';
		echo '</div>';
	}

	/**
	 * تنظیمات + گزارش پیامک.
	 */
	public static function page_settings() {
		if ( ! current_user_can( 'mjc_manage_settings' ) ) {
			wp_die( 'دسترسی غیرمجاز.' );
		}
		self::page_header( 'تنظیمات باشگاه' );
		$s = array_merge( MJC_Settings::defaults(), (array) get_option( 'mjc_settings', array() ) );

		echo '<div class="mjc-admin-cols"><div class="mjc-admin-box"><h2>تنظیمات عمومی</h2><form method="post">';
		wp_nonce_field( 'mjc_save_settings', 'mjc_settings_nonce' );
		echo '<table class="form-table"><tr><th>عنوان باشگاه</th><td><input type="text" name="club_title" value="' . esc_attr( $s['club_title'] ) . '" /></td></tr>';
		echo '<tr><th>نام واحد امتیاز</th><td><input type="text" name="points_label" value="' . esc_attr( $s['points_label'] ) . '" /></td></tr>';
		echo '<tr><th>رنگ اصلی</th><td><input type="color" name="primary_color" value="' . esc_attr( $s['primary_color'] ) . '" /></td></tr>';
		echo '<tr><th>تأیید ثبت‌نام</th><td><select name="registration_approval"><option value="manual" ' . selected( $s['registration_approval'], 'manual', false ) . '>دستی توسط مدیر</option><option value="auto" ' . selected( $s['registration_approval'], 'auto', false ) . '>خودکار</option></select></td></tr>';
		echo '<tr><th>حداقل مبلغ برداشت (ریال)</th><td><input type="number" name="wallet_withdraw_min" value="' . esc_attr( $s['wallet_withdraw_min'] ) . '" /></td></tr></table>';

		echo '<h2>امتیازدهی پروژه‌ها</h2><p class="description">مدیر سایت مشخص می‌کند امتیاز هر پروژه‌ی تأییدشده بر اساس «قیمت (مبلغ)» محاسبه شود یا «متراژ»، و میزان امتیاز هر یک را تعیین می‌کند. مبلغ پروژه نیز در لحظه‌ی تأیید توسط مدیر وارد می‌شود.</p><table class="form-table">';
		echo '<tr><th>محاسبه امتیاز هر پروژه</th><td><select name="project_points_mode">';
		echo '<option value="fixed" ' . selected( $s['project_points_mode'], 'fixed', false ) . '>ثابت (مطابق قوانین امتیاز)</option>';
		echo '<option value="area" ' . selected( $s['project_points_mode'], 'area', false ) . '>بر اساس متراژ</option>';
		echo '<option value="amount" ' . selected( $s['project_points_mode'], 'amount', false ) . '>بر اساس قیمت (مبلغ پروژه)</option>';
		echo '</select></td></tr>';
		echo '<tr><th>میزان امتیاز به ازای هر متر مربع (متراژ پروژه)</th><td><input type="number" step="0.5" name="points_per_meter" value="' . esc_attr( $s['points_per_meter'] ) . '" /></td></tr>';
		echo '<tr><th>میزان امتیاز به ازای هر ۱۰ میلیون ریال (قیمت پروژه)</th><td><input type="number" step="0.5" name="points_per_10m" value="' . esc_attr( $s['points_per_10m'] ) . '" /></td></tr>';
		echo '<tr><th>سقف امتیاز هر پروژه (۰ = بدون سقف)</th><td><input type="number" name="project_points_max" value="' . esc_attr( $s['project_points_max'] ) . '" /></td></tr></table>';

		echo '<h2>پیامک</h2><table class="form-table">';
		echo '<tr><th>درگاه</th><td><select name="sms_gateway"><option value="log" ' . selected( $s['sms_gateway'], 'log', false ) . '>حالت توسعه (ثبت در گزارش پیامک)</option><option value="kavenegar" ' . selected( $s['sms_gateway'], 'kavenegar', false ) . '>کاوه‌نگار</option></select></td></tr>';
		echo '<tr><th>کلید API</th><td><input type="text" name="sms_api_key" value="' . esc_attr( $s['sms_api_key'] ) . '" style="width:300px" /></td></tr>';
		echo '<tr><th>فرستنده</th><td><input type="text" name="sms_sender" value="' . esc_attr( $s['sms_sender'] ) . '" /></td></tr>';
		echo '<tr><th>طول کد OTP</th><td><input type="number" name="otp_length" value="' . esc_attr( $s['otp_length'] ) . '" /></td></tr>';
		echo '<tr><th>اعتبار کد (ثانیه)</th><td><input type="number" name="otp_ttl" value="' . esc_attr( $s['otp_ttl'] ) . '" /></td></tr>';
		echo '<tr><th>حداکثر درخواست در ساعت</th><td><input type="number" name="otp_max_per_hour" value="' . esc_attr( $s['otp_max_per_hour'] ) . '" /></td></tr></table>';
		echo '<p><button class="button-primary">ذخیره تنظیمات</button></p></form></div>';

		echo '<div class="mjc-admin-box"><h2>گزارش پیامک‌ها</h2><table class="widefat striped"><thead><tr><th>شماره</th><th>متن</th><th>وضعیت</th><th>تاریخ</th></tr></thead><tbody>';
		$logs = MJC_DB::results( 'SELECT * FROM ' . mjc_table( 'sms_log' ) . ' ORDER BY id DESC LIMIT 20' ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		foreach ( (array) $logs as $log ) {
			echo '<tr><td>' . esc_html( MJC_SMS::pretty_mobile( $log->mobile ) ) . '</td><td>' . esc_html( $log->message ) . '</td><td>' . esc_html( $log->status ) . '</td><td>' . esc_html( mjc_date( $log->created_at ) ) . '</td></tr>';
		}
		echo '</tbody></table><p class="description">در حالت «توسعه»، کدهای OTP اینجا نمایش داده می‌شوند تا جریان ثبت‌نام/ورود بدون درگاه پیامک قابل تست باشد.</p></div></div></div>';
	}

	/**
	 * گزارش رویدادها.
	 */
	public static function page_audit() {
		if ( ! current_user_can( 'mjc_manage_settings' ) ) {
			wp_die( 'دسترسی غیرمجاز.' );
		}
		self::page_header( 'گزارش رویدادها (Audit Log)' );
		echo '<table class="widefat striped"><thead><tr><th>تاریخ</th><th>کاربر</th><th>رویداد</th><th>شیء</th><th>جزئیات</th></tr></thead><tbody>';
		$logs = MJC_DB::results( 'SELECT * FROM ' . mjc_table( 'audit_log' ) . ' ORDER BY id DESC LIMIT 100' ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		foreach ( (array) $logs as $log ) {
			$u = $log->actor_id ? get_userdata( (int) $log->actor_id ) : null;
			echo '<tr><td>' . esc_html( mjc_date( $log->created_at ) ) . '</td><td>' . esc_html( $u ? $u->display_name : 'سیستم' ) . '</td>';
			echo '<td><code>' . esc_html( $log->action ) . '</code></td><td>' . esc_html( $log->object_type . '#' . $log->object_id ) . '</td><td>' . esc_html( $log->details ) . '</td></tr>';
		}
		echo '</tbody></table></div>';
	}
}
