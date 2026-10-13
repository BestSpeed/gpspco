<?php
/**
 * Mojrian Club smoke suite for php-wasm (run via smoke.js).
 * Boot order is authoritative: stubs first, then globals, then the plugin.
 */

error_reporting( E_ALL );
ini_set( 'display_errors', '1' );

require '/stubs/wp-stubs.php';
require '/stubs/db-stubs.php';

$GLOBALS['mjc_stub']->is_admin        = true;
$GLOBALS['mjc_stub_sql_unknown']      = array();
$GLOBALS['wpdb']                      = new wpdb();
$GLOBALS['mjc_stub_violations']       = array();
$GLOBALS['mjc_stub']->referer         = 'http://test.local/mojrian/dashboard/';

require '/plugin/mojrian-club.php';
do_action( 'plugins_loaded' );
do_action( 'init' ); // ثبت CPTها و flush قواعد بازنویسی (مطابق وردپرس واقعی).
call_user_func( $GLOBALS['mjc_stub']->activation );

$pass = 0;
$fail = 0;

function t( $name, $fn ) {
	global $pass, $fail;
	try {
		$fn();
		$pass++;
		echo "ok  - $name\n";
	} catch ( Throwable $e ) {
		$fail++;
		echo "FAIL- $name :: " . $e->getMessage() . "\n";
	}
}

function ok( $cond, $msg = 'expected true' ) {
	if ( ! $cond ) {
		throw new Exception( $msg );
	}
}

function eq( $a, $b, $msg = '' ) {
	if ( $a !== $b ) {
		throw new Exception( ( $msg ? $msg . ' :: ' : '' ) . 'expected ' . var_export( $b, true ) . ' got ' . var_export( $a, true ) );
	}
}

function contains( $hay, $needle, $msg = '' ) {
	if ( false === strpos( (string) $hay, (string) $needle ) ) {
		throw new Exception( ( $msg ? $msg . ' :: ' : '' ) . 'missing: ' . substr( (string) $needle, 0, 80 ) );
	}
}

function not_contains( $hay, $needle, $msg = '' ) {
	if ( false !== strpos( (string) $hay, (string) $needle ) ) {
		throw new Exception( ( $msg ? $msg . ' :: ' : '' ) . 'should not contain: ' . substr( (string) $needle, 0, 80 ) );
	}
}

function mjc_test_register( $mobile, $full_name = 'کاربر تست', $referral_code = '' ) {
	return MJC_Auth::register( array(
		'full_name'     => $full_name,
		'mobile'        => $mobile,
		'national_code' => '0012345678',
		'province'      => 'تهران',
		'city'          => 'تهران',
		'job_type'      => 'installer',
		'password'      => 'secret123',
		'referral_code' => $referral_code,
	) );
}

function mjc_test_images() {
	$files = array( 'name' => array(), 'type' => array(), 'tmp_name' => array(), 'error' => array(), 'size' => array() );
	for ( $i = 0; $i < 6; $i++ ) {
		$files['name'][]     = 'img' . $i . '.jpg';
		$files['type'][]     = 'image/jpeg';
		$files['tmp_name'][] = '/tmp/img' . $i . '.jpg';
		$files['error'][]    = 0;
		$files['size'][]     = 1024;
	}
	return $files;
}

function mjc_test_set_status( $user_id, $status, $reason = '' ) {
	$row = MJC_Members::get_by_user( $user_id );
	if ( ! $row ) {
		throw new Exception( 'no member row for user ' . $user_id );
	}
	return MJC_Members::set_status( (int) $row->id, $status, $reason );
}

/* ---------- fixtures ---------- */

$admin = mjc_stub_create_user( 'site-admin', array( 'administrator' ), 'site-admin@test.local' );
$mj    = mjc_stub_create_user( 'moder', array( 'mjc_manager' ), 'moder@test.local' );
$a     = mjc_stub_create_user( 'member-a', array( 'mjc_member' ), 'member-a@test.local' );
$b     = mjc_stub_create_user( 'member-b', array( 'mjc_member' ), 'member-b@test.local' );

$GLOBALS['userA_id']  = (int) $a->ID;
$GLOBALS['userB_id']  = (int) $b->ID;
$GLOBALS['admin_id']  = (int) $admin->ID;
$GLOBALS['moder_id']  = (int) $mj->ID;

t( 'roles: default + plugin roles registered', function () {
	ok( get_role( 'mjc_member' ), 'mjc_member role missing' );
	ok( get_role( 'mjc_manager' ), 'mjc_manager role missing' );
	ok( get_role( 'mjc_member' )->has_cap( 'read' ), 'member read cap missing' );
	ok( ! get_role( 'mjc_member' )->has_cap( 'mjc_manage_members' ), 'member must not manage members' );
	ok( get_role( 'mjc_manager' )->has_cap( 'mjc_manage_members' ), 'manager cap missing' );
	ok( get_role( 'mjc_manager' )->has_cap( 'mjc_manage_settings' ), 'manager settings cap missing' );
	ok( ! get_role( 'mjc_member' )->has_cap( 'mjc_manage_settings' ), 'member must not manage settings' );
} );

t( 'pages created: dashboard, profile, projects, guide, notifications', function () {
	$pages = array();
	foreach ( $GLOBALS['mjc_stub']->posts as $p ) {
		if ( 'page' === $p->post_type ) {
			$pages[ $p->post_name ] = $p;
		}
	}
	$by_content = array();
	foreach ( $pages as $p ) {
		$by_content[ $p->post_content ] = $p;
	}
	ok( isset( $by_content['[mojrian_dashboard]'] ), 'dashboard page missing' );
	ok( isset( $by_content['[mojrian_profile]'] ), 'profile page missing' );
	ok( isset( $by_content['[mojrian_projects]'] ), 'projects page missing' );
	ok( isset( $by_content['[mojrian_guide]'] ), 'guide page missing' );
	ok( isset( $by_content['[mojrian_notifications]'] ), 'notifications page missing' );
} );

t( 'shortcodes registered', function () {
	foreach ( array( 'mojrian_dashboard', 'mojrian_profile', 'mojrian_projects', 'mojrian_points', 'mojrian_rewards', 'mojrian_notifications', 'mojrian_referral', 'mojrian_guide', 'mojrian_login' ) as $sc ) {
		ok( isset( $GLOBALS['mjc_stub']->shortcodes[ $sc ] ), "$sc missing" );
	}
} );

t( 'removed features leave no traces (shortcodes, tables, CPTs)', function () {
	foreach ( array( 'mojrian_courses', 'mojrian_knowledge', 'mojrian_surveys', 'mojrian_wallet', 'mojrian_grades', 'mojrian_inquiry' ) as $sc ) {
		ok( ! isset( $GLOBALS['mjc_stub']->shortcodes[ $sc ] ), "$sc still registered" );
	}
	foreach ( array( 'wp_mjc_wallet_transactions', 'wp_mjc_enrollments', 'wp_mjc_survey_responses', 'wp_mjc_grade_requests' ) as $tbl ) {
		ok( ! isset( $GLOBALS['wpdb']->tables[ $tbl ] ), "$tbl still in schema" );
	}
	ok( ! isset( $GLOBALS['mjc_stub']->post_types['mjc_course'] ), 'course CPT still registered' );
	ok( ! isset( $GLOBALS['mjc_stub']->post_types['mjc_survey'] ), 'survey CPT still registered' );
} );

t( 'register: creates member row, auto-login', function () {
	$r = mjc_test_register( '09120000001' );
	ok( ! is_wp_error( $r ), 'register failed: ' . ( is_wp_error( $r ) ? $r->get_error_message() : '' ) );
	$u = get_user_by( 'login', mjc_normalize_mobile( '09120000001' ) );
	ok( $u, 'user not created' );
	$row = MJC_Members::get_by_user( $u->ID );
	ok( $row, 'member row missing' );
	eq( $row->status, 'pending', 'manual approval default' );
	eq( get_current_user_id(), (int) $u->ID, 'auto-login expected' );
} );

t( 'register: duplicate mobile rejected', function () {
	$r = mjc_test_register( '09120000001' );
	ok( is_wp_error( $r ), 'expected WP_Error' );
} );

t( 'register: invalid mobile + short password handled', function () {
	$r = MJC_Auth::register( array( 'mobile' => '123', 'full_name' => 'x', 'national_code' => '0012345678', 'province' => 'x', 'job_type' => 'x' ) );
	ok( is_wp_error( $r ), 'expected WP_Error for bad mobile' );
} );

t( 'register: auto approval setting grants member role', function () {
	mjc_update_settings( array( 'registration_approval' => 'auto' ) );
	$r = mjc_test_register( '09120000002', 'کاربر خودکار' );
	ok( ! is_wp_error( $r ), 'register failed' );
	$u = get_user_by( 'login', mjc_normalize_mobile( '09120000002' ) );
	ok( $u, 'user not created' );
	$row = MJC_Members::get_by_user( $u->ID );
	eq( $row->status, 'approved' );
	ok( in_array( 'mjc_member', $u->roles, true ), 'member role granted' );
	mjc_update_settings( array( 'registration_approval' => 'manual' ) );
} );

t( 'members: create + display name + pending state', function () {
	global $userA_id;
	$row = MJC_Members::create( $userA_id );
	ok( $row, 'row not created' );
	eq( (int) $row->points_balance, 0 );
	eq( $row->status, 'pending' );
	$st = MJC_Members::stats();
	ok( (int) $st['pending'] >= 1, 'pending count' );
	ok( (int) $st['total'] >= 1, 'total count' );
} );

t( 'members: upsert preserves status on update and on insert, staff always approved', function () {
	global $userA_id, $userB_id, $moder_id;
	mjc_test_set_status( $userA_id, 'approved' );
	MJC_Members::upsert( $userA_id, array( 'city' => 'تهران', 'province' => 'تهران' ) );
	eq( MJC_Members::get_by_user( $userA_id )->status, 'approved', 'update without status must preserve it' );
	MJC_Members::upsert( $userB_id, array( 'mobile' => '09120000003', 'full_name' => 'کاربر بی', 'city' => 'مشهد', 'province' => 'خراسان' ) );
	eq( MJC_Members::get_by_user( $userB_id )->status, 'pending', 'insert must start pending' );
	MJC_Members::upsert( $moder_id, array( 'full_name' => 'مدیر تست', 'status' => 'pending' ) );
	eq( MJC_Members::get_by_user( $moder_id )->status, 'approved', 'staff upsert must force approved' );
} );

t( 'members: set_status manages roles, but never touches staff', function () {
	global $userA_id;
	mjc_test_set_status( $userA_id, 'approved' );
	ok( in_array( 'mjc_member', get_userdata( $userA_id )->roles, true ), 'member role not granted' );
	eq( MJC_Members::get_by_user( $userA_id )->status, 'approved' );

	mjc_test_set_status( $userA_id, 'rejected' );
	ok( ! in_array( 'mjc_member', get_userdata( $userA_id )->roles, true ), 'member role not removed' );

	mjc_test_set_status( $userA_id, 'pending' );
	ok( ! in_array( 'mjc_member', get_userdata( $userA_id )->roles, true ), 'pending must not have member role' );

	// کاربران مدیر: set_status باید رد شود و ردیف/نقش دست نخورده بماند.
	$moder_row = MJC_Members::get_by_user( $GLOBALS['moder_id'] );
	ok( $moder_row, 'staff row missing' );
	eq( MJC_Members::set_status( (int) $moder_row->id, 'pending' ), false, 'staff set_status must refuse' );
	eq( MJC_Members::get_by_user( $GLOBALS['moder_id'] )->status, 'approved', 'staff status must stay approved' );
	ok( in_array( 'administrator', get_userdata( $GLOBALS['admin_id'] )->roles, true ), 'admin role untouched' );
} );

t( 'repair_admin_lockout restores demoted admins and never promotes non-staff', function () {
	global $userA_id, $admin_id;
	get_userdata( $admin_id )->set_role( 'mjc_member' );
	MJC_Members::upsert( $userA_id, array( 'full_name' => 'کاربر الف' ) );
	mjc_test_set_status( $userA_id, 'pending' );
	MJC_Members::repair_admin_lockout();
	ok( in_array( 'administrator', get_userdata( $admin_id )->roles, true ), 'admin not restored' );
	ok( ! in_array( 'administrator', get_userdata( $userA_id )->roles, true ), 'non-staff promoted!' );
	ok( MJC_Members::is_staff( $admin_id ), 'admin must be staff' );
	ok( ! MJC_Members::is_staff( $userA_id ), 'member must not be staff' );
} );

t( 'approval_counts: keys without removed features, staff excluded', function () {
	$c = MJC_Admin::approval_counts();
	eq( array_keys( $c ), array( 'members', 'projects', 'rewards' ) );
	$before = (int) $c['members'];
	MJC_Members::upsert( $GLOBALS['moder_id'], array( 'full_name' => 'مدیر تست', 'status' => 'pending' ) );
	eq( (int) MJC_Admin::approval_counts()['members'], $before, 'staff must never count as pending member' );
} );

t( 'menu badges: every approval menu + total on باشگاه مجریان, removed items gone', function () {
	wp_set_current_user( $GLOBALS['admin_id'] );
	MJC_Admin::menu();
	$menu_html = '';
	foreach ( $GLOBALS['mjc_stub']->menus as $m ) {
		$menu_html .= ( isset( $m['menu_title'] ) ? $m['menu_title'] : '' ) . ' ' . ( isset( $m['page_title'] ) ? $m['page_title'] : '' ) . "\n";
	}
	$counts = MJC_Admin::approval_counts();
	$sum    = 0;
	$badges = 0;
	foreach ( $counts as $c ) {
		$c = (int) $c;
		$sum += $c;
		if ( $c > 0 ) {
			$badges++;
		}
	}
	$expected = $badges + ( $sum > 0 ? 1 : 0 );
	contains( $menu_html, 'awaiting-mod count-' );
	eq( substr_count( $menu_html, 'awaiting-mod' ), $expected, 'badge count' );
	contains( $menu_html, 'باشگاه مجریان' );
	not_contains( $menu_html, 'پایه فنی' );
	not_contains( $menu_html, 'کیف پول' );
} );

t( 'admin menu order: داشبورد first, جوایز right after it', function () {
	$GLOBALS['submenu'] = array( 'mjc-dashboard' => array(
		array( 'اعضا', 'mjc-manage-members', 'mjc-members', 'اعضا' ),
		array( 'داشبورد', 'mjc-manage-members', 'mjc-dashboard', 'داشبورد' ),
		array( 'جوایز (فروشگاه امتیاز)', 'mjc-manage-members', 'edit.php?post_type=mjc_reward', 'جوایز' ),
		array( 'پروژه‌ها', 'mjc-manage-members', 'mjc-projects', 'پروژه‌ها' ),
	) );
	MJC_Admin::fix_menu_order();
	$items = $GLOBALS['submenu']['mjc-dashboard'];
	eq( $items[0][2], 'mjc-dashboard' );
	eq( $items[1][2], 'edit.php?post_type=mjc_reward' );
	eq( $items[2][2], 'mjc-members' );
	eq( $items[3][2], 'mjc-projects' );
} );

t( 'admin pages: members table excludes staff', function () {
	wp_set_current_user( $GLOBALS['admin_id'] );
	ob_start();
	$cb = $GLOBALS['mjc_stub']->menu_pages['mjc-members'];
	$cb();
	$html = ob_get_clean();
	contains( $html, 'کاربر الف' );
	contains( $html, 'کاربر بی' );
	not_contains( $html, 'site-admin' );
	not_contains( $html, 'moder' );
} );

t( 'points: award + balance + history', function () {
	global $userA_id;
	$before = (int) MJC_Points::balance( $userA_id );
	MJC_Points::log_transaction( $userA_id, 100, 'seed', 'امتیاز اولیه' );
	MJC_Points::log_transaction( $userA_id, 50, 'seed', 'هدیه' );
	MJC_Points::log_transaction( $userA_id, -20, 'reward_request', 'درخواست جایزه' );
	$bal = (int) MJC_Points::balance( $userA_id );
	eq( $bal, $before + 130 );
	list( $rows, $total ) = MJC_Points::history( $userA_id, 1, 10 );
	ok( (int) $total >= 3, 'history rows' );
} );

t( 'projects: form requires active member and 6 images', function () {
	global $userB_id;
	wp_set_current_user( $userB_id );
	$_POST = array( 'mjc_nonce' => wp_create_nonce( 'mjc_project' ), 'project_type' => 'new', 'floors' => '2', 'units' => '4', 'area' => '80', 'accept_rules' => '1' );
	$r = MJC_Forms::form_project();
	ok( is_wp_error( $r ), 'pending member must not create project' );
	mjc_test_set_status( $userB_id, 'approved' );
	$r = MJC_Forms::form_project();
	ok( is_wp_error( $r ), 'missing images must fail' );
	contains( $r->get_error_message(), 'هر ۶ تصویر' );
} );

t( 'projects: create via form + review awards points', function () {
	global $userA_id;
	wp_set_current_user( $userA_id );
	mjc_test_set_status( $userA_id, 'approved' );
	$_POST = array( 'mjc_nonce' => wp_create_nonce( 'mjc_project' ), 'project_type' => 'new', 'floors' => '3', 'units' => '6', 'area' => '120', 'accept_rules' => '1' );
	$_FILES = array( 'images' => mjc_test_images() );
	$r = MJC_Forms::form_project();
	eq( is_wp_error( $r ) ? 'ERR: ' . $r->get_error_message() : $r, 'pending', 'project submit' );
	unset( $_FILES['images'] );
	list( $rows, $total ) = MJC_Projects::list_for_user( $userA_id, 1, 10 );
	ok( (int) $total >= 1, 'project row missing' );
	$pid = (int) $rows[0]->id;
	$before = (int) MJC_Points::balance( $userA_id );
	ok( MJC_Projects::set_status( $pid, 'approved' ), 'set_status failed' );
	global $wpdb;
	$row = $wpdb->get_row( 'SELECT * FROM ' . mjc_table( 'projects' ) . ' WHERE id = ' . (int) $pid );
	eq( $row->status, 'approved' );
	ok( (int) $row->points_awarded > 0, 'points should be awarded by rate' );
	eq( (int) MJC_Points::balance( $userA_id ), $before + (int) $row->points_awarded );
} );

t( 'projects: reject records reason and never awards', function () {
	global $userA_id;
	$_POST = array( 'mjc_nonce' => wp_create_nonce( 'mjc_project' ), 'project_type' => 'renovation', 'floors' => '1', 'units' => '2', 'area' => '40', 'accept_rules' => '1' );
	$_FILES = array( 'images' => mjc_test_images() );
	wp_set_current_user( $userA_id );
	$r = MJC_Forms::form_project();
	eq( is_wp_error( $r ) ? 'ERR: ' . $r->get_error_message() : $r, 'pending', 'project submit 2' );
	unset( $_FILES['images'] );
	list( $rows, $total ) = MJC_Projects::list_for_user( $userA_id, 1, 10 );
	$pid = (int) $rows[0]->id;
	$before = (int) MJC_Points::balance( $userA_id );
	MJC_Projects::set_status( $pid, 'rejected', 'تصاویر نامعتبر' );
	global $wpdb;
	$row = $wpdb->get_row( 'SELECT * FROM ' . mjc_table( 'projects' ) . ' WHERE id = ' . (int) $pid );
	eq( $row->status, 'rejected' );
	eq( $row->reject_reason, 'تصاویر نامعتبر' );
	eq( (int) MJC_Points::balance( $userA_id ), $before );
} );

t( 'projects: form renders type options and required fields', function () {
	global $userA_id;
	wp_set_current_user( $userA_id );
	$_GET['mjc_new'] = '1';
	$html = MJC_Shortcodes::render_projects();
	unset( $_GET['mjc_new'] );
	contains( $html, 'name="project_type"' );
	contains( $html, 'name="floors"' );
	contains( $html, 'name="units"' );
	contains( $html, 'name="area"' );
	contains( $html, 'accept_rules' );
} );

t( 'projects: list shows green create button', function () {
	global $userA_id;
	wp_set_current_user( $userA_id );
	$html = MJC_Shortcodes::render_projects();
	contains( $html, 'mjc-btn-green' );
	contains( $html, 'ایجاد پروژه جدید' );
} );

t( 'auth: login with correct password', function () {
	$u = wp_signon( array( 'user_login' => 'member-a', 'user_password' => 'pass' ) );
	ok( ! is_wp_error( $u ), 'login failed' );
} );

t( 'auth: login with wrong password fails', function () {
	$u = wp_signon( array( 'user_login' => 'member-a', 'user_password' => 'nope' ) );
	ok( is_wp_error( $u ), 'should fail' );
} );

t( 'settings: points basis stored', function () {
	mjc_update_settings( array( 'points_basis' => 'price', 'points_per_million' => '5', 'points_per_sqm' => '2' ) );
	eq( mjc_get_setting( 'points_basis' ), 'price' );
	mjc_update_settings( array( 'points_basis' => 'area' ) );
	eq( mjc_get_setting( 'points_basis' ), 'area' );
} );

t( 'referrals: code generated + referral flow via register', function () {
	global $userA_id;
	$code = MJC_Referrals::code( $userA_id );
	ok( strlen( $code ) >= 6, 'code too short' );
	$before = count( (array) MJC_Referrals::referrals( $userA_id ) );
	$r = mjc_test_register( '09120000004', 'کاربر دعوتی', $code );
	ok( ! is_wp_error( $r ), 'register with referral failed' );
	$refs = MJC_Referrals::referrals( $userA_id );
	eq( count( (array) $refs ), $before + 1, 'referral not linked' );
} );

t( 'rewards: catalog seeded (25 items, points cost > 0)', function () {
	$catalog = get_posts( array( 'post_type' => 'mjc_reward', 'numberposts' => 30, 'post_status' => 'publish' ) );
	ok( count( $catalog ) >= 25, 'catalog too small: ' . count( $catalog ) );
	$titles = array();
	foreach ( $catalog as $p ) {
		$titles[] = $p->post_title;
		ok( (float) get_post_meta( $p->ID, 'mjc_points_cost', true ) > 0, 'zero cost: ' . $p->post_title );
	}
	ok( in_array( 'متر', $titles, true ) && in_array( 'دستگاه پرس لوله', $titles, true ), 'catalog items missing' );
} );

t( 'rewards: icons ensured and idempotent (non-destructive)', function () {
	MJC_Rewards::ensure_icons();
	eq( (int) get_option( 'mjc_rewards_icons_version', 0 ), 1, 'icons version not set' );
	$catalog = get_posts( array( 'post_type' => 'mjc_reward', 'numberposts' => 30, 'post_status' => 'any' ) );
	$with_thumb = 0;
	$mtimes = array();
	foreach ( $catalog as $p ) {
		if ( get_post_thumbnail_id( $p->ID ) ) {
			$with_thumb++;
		}
	}
	ok( $with_thumb >= 25, 'thumbnails missing: ' . $with_thumb );
	foreach ( glob( '/uploads/mjc-icons/*.png' ) as $f ) {
		$mtimes[ $f ] = filemtime( $f );
	}
	ok( count( $mtimes ) >= 25, 'icon files missing' );
	MJC_Rewards::ensure_icons();
	foreach ( $mtimes as $f => $m ) {
		ok( file_exists( $f ), 'icon deleted on second run' );
		eq( filemtime( $f ), $m, 'icon overwritten on second run' );
	}
} );

t( 'rewards: request flow deducts balance', function () {
	global $userB_id;
	mjc_test_set_status( $userB_id, 'approved' );
	MJC_Points::log_transaction( $userB_id, 500, 'seed', 'برای جایزه' );
	$catalog = get_posts( array( 'post_type' => 'mjc_reward', 'numberposts' => 30, 'post_status' => 'publish' ) );
	$cheap = null;
	foreach ( $catalog as $p ) {
		$cost = (float) get_post_meta( $p->ID, 'mjc_points_cost', true );
		if ( $cost > 0 && $cost <= 500 ) {
			$cheap = $p;
			break;
		}
	}
	ok( $cheap, 'no affordable reward' );
	$before = (int) MJC_Points::balance( $userB_id );
	$res = MJC_Rewards::request( $userB_id, (int) $cheap->ID, 'تهران' );
	ok( ! is_wp_error( $res ), 'request failed: ' . ( is_wp_error( $res ) ? $res->get_error_message() : '' ) );
	$cost = (float) get_post_meta( $cheap->ID, 'mjc_points_cost', true );
	eq( (int) MJC_Points::balance( $userB_id ), $before - (int) $cost );
} );

t( 'notifications: push + list + mark read', function () {
	global $userA_id;
	MJC_Notify::push( $userA_id, 'اعلان اول', 'متن اعلان اول' );
	MJC_Notify::push( $userA_id, 'اعلان دوم', 'متن اعلان دوم' );
	list( $rows, $total ) = MJC_Notify::list_for_user( $userA_id, 1, 50 );
	$titles = array_map( function ( $r ) { return $r->title; }, (array) $rows );
	ok( in_array( 'اعلان اول', $titles, true ) && in_array( 'اعلان دوم', $titles, true ), 'pushed notifications missing' );
	ok( (int) $total >= 2, 'total' );
	MJC_Notify::mark_all_read( $userA_id );
	eq( (int) MJC_Notify::unread_count( $userA_id ), 0 );
} );

t( 'notifications: dashboard shows only last 3, page shows all', function () {
	$uid = mjc_stub_create_user( 'notif-user', array( 'mjc_member' ), 'notif@test.local' )->ID;
	MJC_Members::create( (int) $uid );
	mjc_test_set_status( (int) $uid, 'approved' );
	for ( $i = 1; $i <= 4; $i++ ) {
		MJC_Notify::push( (int) $uid, 'متن اعلان ' . $i, 'بدنه ' . $i );
	}
	wp_set_current_user( (int) $uid );
	$dash = MJC_Shortcodes::render_dashboard();
	$shown = 0;
	for ( $i = 1; $i <= 4; $i++ ) {
		$shown += ( false !== strpos( $dash, 'متن اعلان ' . $i ) ) ? 1 : 0;
	}
	eq( $shown, 3, 'dashboard must show exactly 3' );
	contains( $dash, 'مشاهده همه' );
	$all = MJC_Shortcodes::render_notifications();
	for ( $i = 1; $i <= 4; $i++ ) {
		contains( $all, 'متن اعلان ' . $i );
	}
} );

t( 'member_nav includes اعلانات page', function () {
	global $userA_id;
	wp_set_current_user( $userA_id );
	$html = MJC_Shortcodes::render_dashboard();
	contains( $html, 'اعلانات' );
} );

t( 'profile: prefill first_name/last_name after save (always filled)', function () {
	global $userA_id;
	wp_set_current_user( $userA_id );
	MJC_Members::upsert( $userA_id, array( 'first_name' => 'علی', 'last_name' => 'رضایی' ) );
	$html = MJC_Shortcodes::render_profile();
	contains( $html, 'value="علی"' );
	contains( $html, 'value="رضایی"' );
} );

t( 'profile form: avatar field present (≤500KB hint)', function () {
	global $userA_id;
	wp_set_current_user( $userA_id );
	$html = MJC_Shortcodes::render_profile();
	contains( $html, 'name="avatar"' );
	contains( $html, '۵۰۰ کیلوبایت' );
	contains( $html, 'تصویر کاربر' );
} );

t( 'dashboard: greeting uses full name once set, میهمان otherwise', function () {
	$uid = (int) mjc_stub_create_user( 'hello-user', array( 'mjc_member' ), 'hello@test.local' )->ID;
	MJC_Members::create( $uid );
	mjc_test_set_status( $uid, 'approved' );
	wp_set_current_user( $uid );
	$html = MJC_Shortcodes::render_dashboard();
	contains( $html, 'میهمان خوش آمدید' );
	MJC_Members::upsert( $uid, array( 'first_name' => 'ندا', 'last_name' => 'محمدی' ) );
	$html = MJC_Shortcodes::render_dashboard();
	contains( $html, 'ندا محمدی خوش آمدید' );
	not_contains( $html, 'میهمان خوش آمدید' );
} );

t( 'profile: submit saves avatar and shows it in sidebar context', function () {
	global $userA_id;
	wp_set_current_user( $userA_id );
	$_POST = array(
		'mjc_nonce'    => wp_create_nonce( 'mjc_profile' ),
		'first_name'   => 'علی',
		'last_name'    => 'رضایی',
		'mobile'       => '09123456789',
		'phone'        => '02112345678',
		'national_code' => '1234567890',
		'work_address'  => 'تهران، جنت آباد',
		'birth_year'   => '1360',
		'birth_month'  => '05',
		'birth_day'    => '12',
		'address'      => 'تهران، خیابان آزادی، پلاک ۱۰',
		'province'     => 'تهران',
		'city'         => 'تهران',
	);
	$_FILES = array( 'avatar' => array( 'name' => 'me.png', 'type' => 'image/png', 'size' => 4096, 'tmp_name' => '/tmp/me.png', 'error' => 0 ) );
	$r = MJC_Forms::form_profile();
	ok( ! is_wp_error( $r ), 'profile submit failed: ' . ( is_wp_error( $r ) ? $r->get_error_message() : '' ) );
	unset( $_FILES['avatar'] );
	$row = MJC_Members::get_by_user( $userA_id );
	ok( (int) $row->avatar_id > 0, 'avatar_id not saved' );
	eq( $row->first_name, 'علی', 'prefill preserved' );
} );

t( 'profile: avatar over 500KB rejected', function () {
	global $userA_id;
	wp_set_current_user( $userA_id );
	$_POST = array(
		'mjc_nonce'   => wp_create_nonce( 'mjc_profile' ),
		'first_name'  => 'علی',
		'last_name'   => 'رضایی',
		'mobile'      => '09123456789',
		'phone'       => '02112345678',
		'national_code' => '1234567890',
		'work_address'  => 'تهران، جنت آباد',
		'birth_year'  => '1360',
		'birth_month' => '05',
		'birth_day'   => '12',
		'address'     => 'تهران، خیابان آزادی، پلاک ۱۰',
		'province'    => 'تهران',
		'city'        => 'تهران',
	);
	$_FILES = array( 'avatar' => array( 'name' => 'big.png', 'type' => 'image/png', 'size' => 512 * 1024, 'tmp_name' => '/tmp/big.png', 'error' => 0 ) );
	$r = MJC_Forms::form_profile();
	ok( is_wp_error( $r ), 'expected WP_Error' );
	contains( $r->get_error_message(), 'حجم تصویر کاربر باید حداکثر ۵۰۰ کیلوبایت باشد.' );
	unset( $_FILES['avatar'] );
} );

t( 'dashboard: sidebar on the right (first in DOM), پیام مدیر full-width on top', function () {
	global $userA_id;
	wp_set_current_user( $userA_id );
	$html = MJC_Shortcodes::render_dashboard();
	$side = strpos( $html, 'mjc-acc-side' );
	$main = strpos( $html, 'mjc-acc-main' );
	ok( false !== $side && false !== $main, 'layout missing' );
	ok( $side < $main, 'sidebar must come before main content (right side in RTL)' );
	$msg = strpos( $html, 'پیام مدیر' );
	$layout = strpos( $html, 'mjc-acc-layout' );
	ok( false !== $msg && $msg < $layout, 'پیام مدیر must be full-width above the layout' );
	contains( $html, 'mjc-mgr-full' );
} );

t( 'dashboard: فعالیت‌های شما box with 4 stats', function () {
	global $userA_id;
	wp_set_current_user( $userA_id );
	$html = MJC_Shortcodes::render_dashboard();
	contains( $html, 'فعالیت‌های شما' );
	contains( $html, 'میزان امتیاز دریافتی شما' );
	contains( $html, 'امتیاز فعلی شما' );
	contains( $html, 'تعداد پروژه‌های ثبت شده' );
	contains( $html, 'تعداد دعوتی‌ها' );
} );

t( 'rewards page: button label «تبدیل امتیاز»', function () {
	global $userA_id;
	wp_set_current_user( $userA_id );
	$rewards_html = MJC_Shortcodes::render_rewards();
	contains( $rewards_html, 'تبدیل امتیاز' );
	not_contains( $rewards_html, 'تبدیل انجام شد' );
} );

t( 'campaigns: dashboard links open the campaign page (no 404)', function () {
	global $userA_id;
	$cid = wp_insert_post( array(
		'post_type'    => 'mjc_campaign',
		'post_status'  => 'publish',
		'post_title'   => 'کمپین ویژه پاییز',
		'post_content' => 'شرح کمپین ویژه.',
	) );
	update_post_meta( $cid, 'mjc_end_date', '2030-01-01' );
	wp_set_current_user( $userA_id );
	$html = MJC_Shortcodes::render_dashboard();
	contains( $html, 'mjc_campaign_item=' . (int) $cid, 'dashboard campaign link must use the campaign detail route' );
	not_contains( $html, 'mjc_campaign=', 'bare CPT query var must not be used (collision → 404)' );
	contains( $html, 'کمپین ویژه پاییز' );
	// نمای تک‌کمپین.
	$_GET['mjc_campaign_item'] = (int) $cid;
	$html = MJC_Shortcodes::render_campaigns();
	unset( $_GET['mjc_campaign_item'] );
	contains( $html, 'کمپین ویژه پاییز' );
	contains( $html, 'بازگشت به کمپین‌ها' );
	contains( $html, 'شرکت در کمپین' );
	// شناسه‌ی نامعتبر.
	$_GET['mjc_campaign_item'] = 999999;
	$html = MJC_Shortcodes::render_campaigns();
	unset( $_GET['mjc_campaign_item'] );
	contains( $html, 'کمپین موردنظر یافت نشد.' );
} );

t( 'campaigns: legacy ?mjc_campaign=ID links still work (fallback)', function () {
	global $userA_id;
	$cid = wp_insert_post( array(
		'post_type'    => 'mjc_campaign',
		'post_status'  => 'publish',
		'post_title'   => 'کمپین سازگاری قدیمی',
		'post_content' => 'شرح کمپین قدیمی.',
	) );
	wp_set_current_user( $userA_id );
	$_GET['mjc_campaign'] = (int) $cid; // نام قدیمیِ همنام با CPT (لینک‌های قبل از 1.6.9).
	$html = MJC_Shortcodes::render_campaigns();
	unset( $_GET['mjc_campaign'] );
	contains( $html, 'کمپین سازگاری قدیمی', 'legacy campaign query var must fall back to detail view' );
	contains( $html, 'بازگشت به کمپین‌ها' );
} );

t( 'guard_item_query neutralizes legacy CPT query vars in main query', function () {
	$was_admin = $GLOBALS['mjc_stub']->is_admin;
	$GLOBALS['mjc_stub']->is_admin = false;
	$q = new class {
		public $qv = array();
		public function is_main_query() { return true; }
		public function get( $k ) { return isset( $this->qv[ $k ] ) ? $this->qv[ $k ] : ''; }
		public function set( $k, $v ) { $this->qv[ $k ] = $v; }
	};
	$q->qv = array( 'mjc_campaign' => '13479', 'name' => '13479', 'post_type' => 'mjc_campaign', 'pagename' => 'کمپینها-و-قرعهکشی' );
	MJC_Public::guard_item_query( $q );
	eq( $q->get( 'mjc_campaign' ), '', 'legacy numeric CPT var must be cleared' );
	eq( $q->get( 'name' ), '', 'conflicting name must be cleared' );
	eq( $q->get( 'post_type' ), '', 'conflicting post_type must be cleared' );
	eq( $q->get( 'pagename' ), 'کمپینها-و-قرعهکشی', 'pagename must survive so the page resolves' );
	// مقدار نامکی (غیرعددی) نباید دست بخورد — مسیر زیبای تک‌مطلب به هسته سپرده می‌شود.
	$q2 = new class {
		public $qv = array();
		public function is_main_query() { return true; }
		public function get( $k ) { return isset( $this->qv[ $k ] ) ? $this->qv[ $k ] : ''; }
		public function set( $k, $v ) { $this->qv[ $k ] = $v; }
	};
	$q2->qv = array( 'mjc_guide' => 'some-article', 'name' => 'some-article' );
	MJC_Public::guard_item_query( $q2 );
	eq( $q2->get( 'mjc_guide' ), 'some-article', 'slug values must be left to core' );
	eq( $q2->get( 'name' ), 'some-article', 'slug name must survive' );
	$GLOBALS['mjc_stub']->is_admin = $was_admin;
} );

t( 'campaigns: rewrite rules flushed after CPT registration + single template exists', function () {
	eq( get_option( 'mjc_rewrite_flush' ), MJC_VERSION, 'rewrite flush must run after CPT registration' );
	ok( $GLOBALS['mjc_stub']->flushed, 'flush_rewrite_rules not called' );
	ok( file_exists( '/plugin/public/templates/single-mjc_campaign.php' ), 'single campaign template missing' );
} );

t( 'guest: profile and rewards require login (no fatal)', function () {
	wp_set_current_user( 0 );
	$html = MJC_Shortcodes::render_profile();
	contains( $html, 'ورود به باشگاه' );
	$html = MJC_Shortcodes::render_rewards();
	contains( $html, 'ورود به باشگاه' );
	wp_set_current_user( $GLOBALS['userA_id'] );
} );

t( 'guide: category + article present', function () {
	$terms = get_terms( array( 'taxonomy' => 'mjc_guide_cat' ) );
	ok( is_array( $terms ) );
	$articles = get_posts( array( 'post_type' => 'mjc_guide', 'numberposts' => 10, 'post_status' => 'publish' ) );
	ok( count( $articles ) >= 1, 'guide article missing' );
} );

t( 'audit: admin pages render without notices', function () {
	wp_set_current_user( $GLOBALS['admin_id'] );
	$pages = array( 'mjc-dashboard', 'mjc-settings', 'mjc-reports' );
	foreach ( $pages as $slug ) {
		if ( ! isset( $GLOBALS['mjc_stub']->menu_pages[ $slug ] ) ) {
			throw new Exception( "$slug missing in menu_pages" );
		}
		ob_start();
		$cb = $GLOBALS['mjc_stub']->menu_pages[ $slug ];
		try {
			$cb();
		} catch ( Throwable $e ) {
			ob_end_clean();
			throw $e;
		}
		$out = ob_get_clean();
		ok( strlen( $out ) > 0, "$slug rendered empty" );
	}
} );

t( 'member dashboard renders + has dashboard widgets', function () {
	global $userA_id;
	wp_set_current_user( $userA_id );
	$html = MJC_Shortcodes::render_dashboard();
	contains( $html, 'به حساب کاربری خود خوش آمدید' );
	contains( $html, 'ثبت پروژه تاسیسات' );
	contains( $html, 'تبدیل امتیازات به جوایز' );
	contains( $html, 'تکمیل اطلاعات کاربر' );
	contains( $html, 'راهنمای باشگاه' );
	contains( $html, 'آخرین کمپین‌ها' );
	contains( $html, 'اعلان‌ها' );
	not_contains( $html, 'grade' );
	not_contains( $html, 'کیف پول' );
} );

// راهنمای باشگاه: لینک مقاله‌ها نباید ۴۰۴ بدهد (نمای ?mjc_guide=ID).
t( 'guide: listing links are rewrite-independent (?mjc_guide=ID)', function () {
	$gid = wp_insert_post( array( 'post_title' => 'راهنمای آزمایشی عضویت', 'post_content' => 'متن آزمایشی راهنما برای عضویت', 'post_type' => 'mjc_guide', 'post_status' => 'publish' ) );
	$out = MJC_Shortcodes::render_guide();
	contains( $out, 'mjc_guide_item=' . (int) $gid, 'guide listing must link ?mjc_guide_item=ID' );
	not_contains( $out, 'mjc_guide=', 'bare CPT query var must not be used (collision → 404)' );
	contains( $out, 'راهنمای آزمایشی عضویت', 'guide listing shows article title' );
} );

t( 'guide: ?mjc_guide=ID detail view (no 404 route)', function () {
	$gid = wp_insert_post( array( 'post_title' => 'مقاله تفصیلی امتیاز', 'post_content' => 'جزئیات محاسبه امتیاز در متن مقاله', 'post_type' => 'mjc_guide', 'post_status' => 'publish' ) );
	$_GET['mjc_guide_item'] = (string) $gid;
	$out = MJC_Shortcodes::render_guide();
	$_GET = array();
	contains( $out, 'مقاله تفصیلی امتیاز', 'detail view shows title' );
	contains( $out, 'بازگشت به راهنمای باشگاه', 'detail view has back link' );
	contains( $out, 'جزئیات محاسبه امتیاز در متن مقاله', 'detail view shows content' );
} );

t( 'guide: legacy ?mjc_guide=ID links still work (fallback)', function () {
	$gid = wp_insert_post( array( 'post_title' => 'مقاله قدیمی سازگاری', 'post_content' => 'متن مقاله قدیمی', 'post_type' => 'mjc_guide', 'post_status' => 'publish' ) );
	$_GET['mjc_guide'] = (string) $gid; // نام قدیمیِ همنام با CPT (لینک‌های قبل از 1.6.9).
	$out = MJC_Shortcodes::render_guide();
	$_GET = array();
	contains( $out, 'مقاله قدیمی سازگاری', 'legacy guide query var must fall back to detail view' );
	contains( $out, 'بازگشت به راهنمای باشگاه' );
} );

t( 'guide: unknown article id message', function () {
	$_GET['mjc_guide_item'] = '999999';
	$out = MJC_Shortcodes::render_guide();
	$_GET = array();
	contains( $out, 'مقاله موردنظر یافت نشد.', 'unknown guide id message' );
} );

t( 'guide: rewrite flush runs after ALL plugin CPT registrations', function () {
	// اولین flush درخواست باید بعد از ثبت همه‌ی CPTها انجام شده باشد (mjc_guide هم ثبت شده باشد).
	ok( ! empty( $GLOBALS['mjc_stub']->flush_history ), 'at least one flush ran' );
	$first = $GLOBALS['mjc_stub']->flush_history[0];
	foreach ( array( 'mjc_guide', 'mjc_faq', 'mjc_campaign', 'mjc_reward' ) as $pt ) {
		ok( in_array( $pt, $first, true ), $pt . ' must be registered when rewrite flush runs' );
	}
	ok( get_option( 'mjc_rewrite_flush' ) === MJC_VERSION, 'flush version option must equal MJC_VERSION' );
} );

// 1.6.10: اندازه‌ی عنوان‌ها و جای عنوان کمپین.
t( 'v1.6.10: campaign title is below the campaign image and 20px', function () {
	$tpl = file_get_contents( '/plugin/public/templates/single-mjc_campaign.php' );
	ok( strpos( $tpl, 'get_the_post_thumbnail' ) < strpos( $tpl, '<h1>' ), 'campaign title must be below the image in single template' );
	$css = file_get_contents( '/plugin/assets/css/mojrian-club.css' );
	contains( $css, '.mjc-campaign h1, .mjc-campaign h2, .mjc-campaign h3 { font-size: 20px; }', 'campaign titles must be 20px' );
} );

t( 'v1.6.10: guide article, login and secondary page titles are 20px', function () {
	$css = file_get_contents( '/plugin/assets/css/mojrian-club.css' );
	contains( $css, '.mjc-guide-article h1, .mjc-article-card h3 { font-size: 20px; }', 'guide article titles must be 20px' );
	contains( $css, '.mjc-page-title, h1.mjc-page-title, h2.mjc-page-title { font-size: 20px; }', 'page-title class must be 20px' );
	contains( $css, '.mjc-acc-title, .mjc-rewards-title, .mjc-profile-head { font-size: 20px; }', 'dashboard/rewards/profile titles must be 20px' );
	// عنوان هیروی راهنمای باشگاه (صفحه‌ی اصلی) نباید ۲۰ شود.
	not_contains( $css, '.mjc-guide-hero h1, .mjc-article-card h3', 'guide hero must not be in the 20px rule' );
	wp_set_current_user( 0 );
	$out = MJC_Shortcodes::render_login();
	contains( $out, 'mjc-center mjc-page-title', 'login title must carry the 20px class' );
	$out = MJC_Shortcodes::render_register();
	contains( $out, 'mjc-center mjc-page-title', 'register title must carry the 20px class' );
	wp_set_current_user( $GLOBALS['userA_id'] );
	$out = MJC_Shortcodes::render_dashboard();
	contains( $out, 'mjc-acc-title', 'dashboard title class present' );
	$out = MJC_Shortcodes::render_guide();
	not_contains( $out, 'mjc-guide-hero h1', 'guide listing keeps hero markup' );
} );

// 1.6.11: متن داشبورد باید دقیقاً متن تصویر حساب کاربری باشد.
t( 'v1.6.11: dashboard text matches the account text exactly', function () {
	global $userA_id;
	wp_set_current_user( $userA_id );
	$out = MJC_Shortcodes::render_dashboard();
	contains( $out, 'به حساب کاربری خود خوش آمدید', 'title' );
	contains( $out, 'هدف از ایجاد باشگاه مجریان</h2>', 'subtitle 1 exact' );
	contains( $out, 'باشگاه مجریان با هدف ارج‌نهادن به تلاش مجریان گرامی ایجاد شده است، امتیازاتی که از طریق پروژه به دست می‌آورید در قالب هدایا و جوایز کاربردی به شما بازپرداخت می‌شود. همین حالا می‌توانید از سفرهای رویایی استفاده کنید.', 'paragraph exact' );
	contains( $out, 'توضیحات کلی بخش‌های حساب کاربری', 'subtitle 2 exact' );
	contains( $out, '<b>تکمیل اطلاعات کاربر:</b> برای ارسال دقیق بسته‌های پستی، لطفا اطلاعات شخصی خود را کامل نمایید.', 'item 1 exact' );
	contains( $out, '<b>ثبت پروژه تاسیسات:</b> با ثبت هر پروژه تاسیساتی، پروژه شما بررسی و بر اساس مبلغ یا متراژ به شما امتیاز تعلق می‌گیرد.', 'item 2 exact' );
	contains( $out, '<b>تبدیل امتیازات به جوایز:</b> در این بخش با توجه به امتیازی که تاکنون کسب کرده‌اید، جایزه مورد نظر خود را انتخاب نمایید.', 'item 3 exact' );
	contains( $out, '<b>خروج از حساب کاربری:</b> با استفاده از گزینه خروج، از حساب کاربری خود خارج می‌شوید.', 'item 4 exact' );
	not_contains( $out, 'پخش هدایایی تبلیغاتی', 'old paragraph gone' );
	not_contains( $out, 'توضیحات کلید های قسمت حساب کاربری', 'old subtitle gone' );
	not_contains( $out, 'خروج شدن از قسمت', 'old item gone' );
} );

t( 'final invariants', function () {
	eq( count( array_filter( (array) $GLOBALS['mjc_stub_sql_unknown'] ) ), 0, 'unknown SQL: ' . json_encode( $GLOBALS['mjc_stub_sql_unknown'], JSON_UNESCAPED_UNICODE ) );
	eq( count( (array) $GLOBALS['mjc_stub_violations'] ), 0, 'column violations: ' . json_encode( $GLOBALS['mjc_stub_violations'], JSON_UNESCAPED_UNICODE ) );
	global $userA_id;
	wp_set_current_user( $userA_id );
	$h1 = MJC_Shortcodes::render_dashboard();
	$h2 = MJC_Shortcodes::render_dashboard();
	eq( $h1, $h2, 'dashboard render not stable' );
} );

echo "\nRESULT: pass=$pass fail=$fail\n";
echo $fail > 0 ? "RESULT: FAIL\n" : "RESULT: OK\n";
