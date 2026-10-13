<?php
// mbstring polyfills (php-wasm may lack ext-mbstring).
if ( ! function_exists( 'mb_strlen' ) ) {
	function mb_strlen( $s, $enc = null ) { return strlen( (string) $s ); }
}
if ( ! function_exists( 'mb_substr' ) ) {
	function mb_substr( $s, $start, $len = null, $enc = null ) {
		return null === $len ? substr( (string) $s, $start ) : substr( (string) $s, $start, $len );
	}
}
if ( ! function_exists( 'mb_strpos' ) ) {
	function mb_strpos( $h, $n, $o = 0, $enc = null ) { return strpos( (string) $h, (string) $n, $o ); }
}
if ( ! function_exists( 'mb_stripos' ) ) {
	function mb_stripos( $h, $n, $o = 0, $enc = null ) { return stripos( (string) $h, (string) $n, $o ); }
}
if ( ! function_exists( 'mb_strtolower' ) ) {
	function mb_strtolower( $s, $enc = null ) { return strtolower( (string) $s ); }
}
if ( ! function_exists( 'mb_strtoupper' ) ) {
	function mb_strtoupper( $s, $enc = null ) { return strtoupper( (string) $s ); }
}
if ( ! function_exists( 'mb_convert_encoding' ) ) {
	function mb_convert_encoding( $s, $to, $from = null ) { return (string) $s; }
}

// Path constants (plugin files guard on ABSPATH).
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/wp/' );
}
if ( ! defined( 'WPINC' ) ) {
	define( 'WPINC', 'wp-includes' );
}
if ( ! defined( 'WP_CONTENT_DIR' ) ) {
	define( 'WP_CONTENT_DIR', '/uploads' );
}
if ( ! defined( 'WP_CONTENT_URL' ) ) {
	define( 'WP_CONTENT_URL', 'http://test.local/wp-content' );
}
if ( ! defined( 'WP_PLUGIN_DIR' ) ) {
	define( 'WP_PLUGIN_DIR', '/uploads/plugins' );
}
if ( ! defined( 'KB_IN_BYTES' ) ) {
	define( 'KB_IN_BYTES', 1024 );
	define( 'MB_IN_BYTES', 1024 * 1024 );
	define( 'GB_IN_BYTES', 1024 * 1024 * 1024 );
}
if ( ! defined( 'DOING_AUTOSAVE' ) ) {
	define( 'DOING_AUTOSAVE', false );
}
if ( ! defined( 'OBJECT' ) ) {
	define( 'OBJECT', 'OBJECT' );
	define( 'OBJECT_K', 'OBJECT_K' );
	define( 'ARRAY_A', 'ARRAY_A' );
	define( 'ARRAY_N', 'ARRAY_N' );
}
if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
	define( 'MINUTE_IN_SECONDS', 60 );
	define( 'HOUR_IN_SECONDS', 3600 );
	define( 'DAY_IN_SECONDS', 86400 );
	define( 'WEEK_IN_SECONDS', 604800 );
	define( 'MONTH_IN_SECONDS', 2592000 );
	define( 'YEAR_IN_SECONDS', 31536000 );
}

/**
 * Minimal WordPress stubs for php-wasm smoke tests (Mojrian Club).
 * Rule: define ONLY WP core surface the plugin actually uses. No MJC_* helpers.
 */

$GLOBALS['mjc_stub'] = (object) array(
	'actions'      => array(),
	'filters'      => array(),
	'options'      => array(),
	'transients'   => array(),
	'users'        => array(),
	'user_meta'    => array(),
	'posts'        => array(),
	'post_meta'    => array(),
	'next_user_id' => 1,
	'next_post_id' => 1,
	'next_term_id' => 1,
	'roles'        => array(),
	'current_user' => 0,
	'is_admin'     => true,
	'mails'        => array(),
	'redirects'    => array(),
	'enqueued'     => array(),
	'inline_css'   => array(),
	'shortcodes'   => array(),
	'menus'        => array(),
	'menu_pages'   => array(),
	'meta_boxes'   => array(),
	'activation'   => null,
	'deactivation' => null,
	'auth_cookies' => array(),
	'created_pages'=> array(),
);

class MJC_Stub_Redirect extends Exception {}
class MJC_Stub_Die extends Exception {}

class WP_Error {
	public $errors = array();
	public $error_data = array();
	public function __construct( $code = '', $message = '', $data = '' ) {
		if ( $code ) {
			$this->errors[ $code ][] = $message;
			if ( $data ) {
				$this->error_data[ $code ] = $data;
			}
		}
	}
	public function get_error_code() {
		$codes = array_keys( $this->errors );
		return $codes ? $codes[0] : '';
	}
	public function get_error_message( $code = '' ) {
		if ( ! $code ) {
			$code = $this->get_error_code();
		}
		return isset( $this->errors[ $code ][0] ) ? $this->errors[ $code ][0] : '';
	}
}

class MJC_Stub_Role {
	public $name;
	public $capabilities = array();
	public function __construct( $name, $caps = array() ) {
		$this->name         = $name;
		$this->capabilities = $caps;
	}
	public function add_cap( $cap ) {
		$this->capabilities[ $cap ] = true;
	}
	public function remove_cap( $cap ) {
		unset( $this->capabilities[ $cap ] );
	}
	public function has_cap( $cap ) {
		return ! empty( $this->capabilities[ $cap ] );
	}
}

function mjc_stub_sync_user( $user ) {
	$GLOBALS['mjc_stub']->users[ (int) $user->ID ] = $user;
}

class MJC_Stub_User {
	public $ID = 0;
	public $user_login = '';
	public $user_email = '';
	public $user_pass = '';
	public $display_name = '';
	public $user_nicename = '';
	public $roles = array();
	public $caps = array();
	public function __construct( $data = array() ) {
		foreach ( (array) $data as $k => $v ) {
			$this->$k = $v;
		}
		if ( ! $this->display_name ) {
			$this->display_name = $this->user_login;
		}
		if ( ! $this->user_nicename ) {
			$this->user_nicename = $this->user_login;
		}
	}
	public function set_role( $role ) {
		$this->roles = $role ? array( $role ) : array();
		mjc_stub_sync_user( $this );
	}
	public function add_role( $role ) {
		if ( $role && ! in_array( $role, $this->roles, true ) ) {
			$this->roles[] = $role;
		}
		mjc_stub_sync_user( $this );
	}
	public function remove_role( $role ) {
		$this->roles = array_values( array_diff( $this->roles, array( $role ) ) );
		mjc_stub_sync_user( $this );
	}
	public function add_cap( $cap ) {
		$this->caps[ $cap ] = true;
		mjc_stub_sync_user( $this );
	}
	public function remove_cap( $cap ) {
		unset( $this->caps[ $cap ] );
		mjc_stub_sync_user( $this );
	}
	public function has_cap( $cap ) {
		if ( in_array( 'administrator', $this->roles, true ) ) {
			return true;
		}
		if ( ! empty( $this->caps[ $cap ] ) ) {
			return true;
		}
		foreach ( $this->roles as $r ) {
			$R = $GLOBALS['mjc_stub']->roles;
			if ( isset( $R[ $r ] ) && $R[ $r ]->has_cap( $cap ) ) {
				return true;
			}
		}
		return false;
	}
	public function exists() {
		return $this->ID > 0;
	}
}

function mjc_stub_create_user( $login, $roles = array(), $email = '' ) {
	$id   = $GLOBALS['mjc_stub']->next_user_id++;
	$user = new MJC_Stub_User( array(
		'ID'           => $id,
		'user_login'   => $login,
		'user_email'   => $email ? $email : $login . '@test.local',
		'user_pass'    => 'pass',
		'roles'        => (array) $roles,
	) );
	mjc_stub_sync_user( $user );
	return $user;
}

/* ------------------------------------------------------------------ *
 * Users / caps
 * ------------------------------------------------------------------ */

function get_userdata( $id ) {
	$id = (int) $id;
	return isset( $GLOBALS['mjc_stub']->users[ $id ] ) ? $GLOBALS['mjc_stub']->users[ $id ] : false;
}

function get_user_by( $field, $value ) {
	foreach ( $GLOBALS['mjc_stub']->users as $u ) {
		if ( 'id' === $field && (int) $u->ID === (int) $value ) {
			return $u;
		}
		if ( 'login' === $field && $u->user_login === $value ) {
			return $u;
		}
		if ( 'email' === $field && $u->user_email === $value ) {
			return $u;
		}
	}
	return false;
}

function user_can( $user_id, $cap ) {
	// مطابق وردپرس: WP_User|int — شیء کاربر نباید به int تبدیل شود.
	$u = is_object( $user_id ) ? $user_id : get_userdata( $user_id );
	return $u ? $u->has_cap( $cap ) : false;
}

function current_user_can( $cap ) {
	return user_can( $GLOBALS['mjc_stub']->current_user, $cap );
}

function wp_get_current_user() {
	$u = get_userdata( $GLOBALS['mjc_stub']->current_user );
	return $u ? $u : new MJC_Stub_User( array( 'ID' => 0 ) );
}

function get_current_user_id() {
	return (int) $GLOBALS['mjc_stub']->current_user;
}

function wp_set_current_user( $id ) {
	$GLOBALS['mjc_stub']->current_user = (int) $id;
	return get_userdata( $id );
}

function is_user_logged_in() {
	return $GLOBALS['mjc_stub']->current_user > 0;
}

function get_users( $args = array() ) {
	$args = wp_parse_args( $args, array(
		'capability' => '',
		'role'       => '',
		'role__in'   => array(),
		'fields'     => 'all',
		'number'     => 0,
		'orderby'    => 'ID',
		'order'      => 'ASC',
	) );
	$out = array();
	foreach ( $GLOBALS['mjc_stub']->users as $u ) {
		if ( $args['capability'] && ! $u->has_cap( $args['capability'] ) ) {
			continue;
		}
		if ( $args['role'] && ! in_array( $args['role'], (array) $u->roles, true ) ) {
			continue;
		}
		if ( $args['role__in'] && ! array_intersect( (array) $args['role__in'], (array) $u->roles ) ) {
			continue;
		}
		$out[] = $u;
	}
	usort( $out, function ( $a, $b ) use ( $args ) {
		$c = ( 'DESC' === strtoupper( $args['order'] ) ) ? -1 : 1;
		return $c * ( (int) $a->ID <=> (int) $b->ID );
	} );
	if ( $args['number'] > 0 ) {
		$out = array_slice( $out, 0, (int) $args['number'] );
	}
	if ( 'ID' === $args['fields'] || 'ids' === $args['fields'] ) {
		return array_map( function ( $u ) {
			return (int) $u->ID;
		}, $out );
	}
	return $out;
}

// WP core roles always exist.
$GLOBALS['mjc_stub']->roles['administrator'] = new MJC_Stub_Role( 'Administrator', array( 'manage_options' => true, 'read' => true ) );
$GLOBALS['mjc_stub']->roles['subscriber']    = new MJC_Stub_Role( 'Subscriber', array( 'read' => true ) );

function add_role( $slug, $name, $caps = array() ) {
	$GLOBALS['mjc_stub']->roles[ $slug ] = new MJC_Stub_Role( $name, $caps );
	return $GLOBALS['mjc_stub']->roles[ $slug ];
}

function remove_role( $slug ) {
	unset( $GLOBALS['mjc_stub']->roles[ $slug ] );
}

function get_role( $slug ) {
	return isset( $GLOBALS['mjc_stub']->roles[ $slug ] ) ? $GLOBALS['mjc_stub']->roles[ $slug ] : null;
}

function email_exists( $email ) {
	foreach ( $GLOBALS['mjc_stub']->users as $u ) {
		if ( $u->user_email === $email ) {
			return (int) $u->ID;
		}
	}
	return false;
}

function username_exists( $username ) {
	foreach ( $GLOBALS['mjc_stub']->users as $u ) {
		if ( $u->user_login === $username ) {
			return (int) $u->ID;
		}
	}
	return false;
}

function wp_insert_user( $data ) {
	$data = (array) $data;
	if ( ! empty( $data['ID'] ) && get_userdata( $data['ID'] ) ) {
		return wp_update_user( $data );
	}
	$user = mjc_stub_create_user(
		isset( $data['user_login'] ) ? $data['user_login'] : 'user' . $GLOBALS['mjc_stub']->next_user_id,
		isset( $data['role'] ) ? array( $data['role'] ) : array(),
		isset( $data['user_email'] ) ? $data['user_email'] : ''
	);
	if ( isset( $data['user_pass'] ) ) {
		$user->user_pass = $data['user_pass'];
	}
	if ( isset( $data['display_name'] ) ) {
		$user->display_name = $data['display_name'];
	}
	mjc_stub_sync_user( $user );
	return (int) $user->ID;
}

function wp_update_user( $data ) {
	$data = (array) $data;
	$user = get_userdata( isset( $data['ID'] ) ? $data['ID'] : 0 );
	if ( ! $user ) {
		return new WP_Error( 'invalid_id', 'کاربر یافت نشد.' );
	}
	foreach ( array( 'user_login', 'user_email', 'user_pass', 'display_name', 'first_name', 'last_name' ) as $k ) {
		if ( isset( $data[ $k ] ) ) {
			$user->$k = $data[ $k ];
		}
	}
	if ( isset( $data['role'] ) ) {
		$user->set_role( $data['role'] );
	}
	mjc_stub_sync_user( $user );
	return (int) $user->ID;
}

function get_user_meta( $user_id, $key = '', $single = false ) {
	$store = $GLOBALS['mjc_stub']->user_meta;
	$user_id = (int) $user_id;
	if ( '' === $key ) {
		return isset( $store[ $user_id ] ) ? $store[ $user_id ] : array();
	}
	$val = isset( $store[ $user_id ][ $key ] ) ? $store[ $user_id ][ $key ] : '';
	if ( $single ) {
		return is_array( $val ) && $val ? $val[0] : $val;
	}
	return is_array( $val ) ? $val : ( '' === $val ? array() : array( $val ) );
}

function update_user_meta( $user_id, $key, $value ) {
	$GLOBALS['mjc_stub']->user_meta[ (int) $user_id ][ $key ] = $value;
	return true;
}

function sanitize_user( $username, $strict = false ) {
	$username = (string) $username;
	if ( $strict ) {
		$username = preg_replace( '/[^a-zA-Z0-9 _.\-@]/', '', $username );
	} else {
		$username = preg_replace( '/[%<>\[\]]/', '', $username );
	}
	return $username;
}

function wp_cache_delete( $key, $group = '' ) {
	return true;
}

function clean_user_cache( $user ) {
	return true;
}

function wp_generate_password( $length = 12, $special = true, $extra = '' ) {
	return substr( md5( uniqid( 'pw', true ) ), 0, (int) $length );
}

function wp_set_auth_cookie( $user_id, $remember = false, $secure = '' ) {
	$GLOBALS['mjc_stub']->auth_cookies[] = (int) $user_id;
}

function wp_authenticate_username_password( $user, $username, $password ) {
	if ( $user instanceof WP_User ) {
		return $user;
	}
	$username = trim( (string) $username );
	$password = (string) $password;
	if ( '' === $username || '' === $password ) {
		return new WP_Error( 'empty_username', 'نام کاربری یا رمز عبور خالی است.' );
	}
	$u = get_user_by( 'login', $username );
	if ( ! $u ) {
		return new WP_Error( 'invalid_username', 'نام کاربری نادرست است.' );
	}
	if ( isset( $u->user_pass ) && $u->user_pass !== $password ) {
		return new WP_Error( 'incorrect_password', 'رمز عبور نادرست است.' );
	}
	return $u;
}

function wp_signon( $credentials = array(), $secure = '' ) {
	$creds = (array) $credentials;
	$user  = false;
	if ( ! empty( $creds['user_login'] ) ) {
		$user = get_user_by( 'login', $creds['user_login'] );
		if ( ! $user ) {
			$user = get_user_by( 'email', $creds['user_login'] );
		}
	}
	if ( ! $user ) {
		return new WP_Error( 'invalid_username', 'نام کاربری نادرست است.' );
	}
	if ( isset( $creds['user_password'] ) && $user->user_pass !== $creds['user_password'] ) {
		return new WP_Error( 'incorrect_password', 'رمز عبور نادرست است.' );
	}
	wp_set_current_user( $user->ID );
	wp_set_auth_cookie( $user->ID );
	return $user;
}

/* ------------------------------------------------------------------ *
 * Hooks
 * ------------------------------------------------------------------ */

function add_action( $tag, $cb, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['mjc_stub']->actions[ $tag ][] = array( 'cb' => $cb, 'prio' => (int) $priority );
}

function add_filter( $tag, $cb, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['mjc_stub']->filters[ $tag ][] = array( 'cb' => $cb, 'prio' => (int) $priority );
}

function do_action( $tag, ...$args ) {
	$list = isset( $GLOBALS['mjc_stub']->actions[ $tag ] ) ? $GLOBALS['mjc_stub']->actions[ $tag ] : array();
	usort( $list, function ( $a, $b ) {
		return $a['prio'] <=> $b['prio'];
	} );
	foreach ( $list as $item ) {
		call_user_func_array( $item['cb'], $args );
	}
}

function apply_filters( $tag, $value, ...$args ) {
	$list = isset( $GLOBALS['mjc_stub']->filters[ $tag ] ) ? $GLOBALS['mjc_stub']->filters[ $tag ] : array();
	usort( $list, function ( $a, $b ) {
		return $a['prio'] <=> $b['prio'];
	} );
	foreach ( $list as $item ) {
		$value = call_user_func_array( $item['cb'], array_merge( array( $value ), $args ) );
	}
	return $value;
}

function has_action( $tag, $cb = false ) {
	return ! empty( $GLOBALS['mjc_stub']->actions[ $tag ] );
}

function register_activation_hook( $file, $cb ) {
	$GLOBALS['mjc_stub']->activation = $cb;
}

function register_deactivation_hook( $file, $cb ) {
	$GLOBALS['mjc_stub']->deactivation = $cb;
}

function register_uninstall_hook( $file, $cb ) {
	// no-op.
}

function add_shortcode( $tag, $cb ) {
	$GLOBALS['mjc_stub']->shortcodes[ $tag ] = $cb;
}

function do_shortcode( $content ) {
	return $content;
}

/* ------------------------------------------------------------------ *
 * Options / transients
 * ------------------------------------------------------------------ */

function get_option( $key, $default = false ) {
	return array_key_exists( $key, $GLOBALS['mjc_stub']->options ) ? $GLOBALS['mjc_stub']->options[ $key ] : $default;
}

function update_option( $key, $value, $autoload = null ) {
	$GLOBALS['mjc_stub']->options[ $key ] = $value;
	return true;
}

function add_option( $key, $value = '', $deprecated = '', $autoload = 'yes' ) {
	if ( array_key_exists( $key, $GLOBALS['mjc_stub']->options ) ) {
		return false;
	}
	$GLOBALS['mjc_stub']->options[ $key ] = $value;
	return true;
}

function delete_option( $key ) {
	unset( $GLOBALS['mjc_stub']->options[ $key ] );
	return true;
}

function get_transient( $key ) {
	return array_key_exists( $key, $GLOBALS['mjc_stub']->transients ) ? $GLOBALS['mjc_stub']->transients[ $key ] : false;
}

function set_transient( $key, $value, $ttl = 0 ) {
	$GLOBALS['mjc_stub']->transients[ $key ] = $value;
	return true;
}

function current_time( $type, $gmt = 0 ) {
	return 'timestamp' === $type ? time() : gmdate( 'Y-m-d H:i:s' );
}

/* ------------------------------------------------------------------ *
 * Escaping / sanitizing
 * ------------------------------------------------------------------ */

function __( $text, $domain = 'default' ) {
	return $text;
}

function _e( $text, $domain = 'default' ) {
	echo $text;
}

function esc_html__( $text, $domain = 'default' ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES );
}

function esc_attr__( $text, $domain = 'default' ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES );
}

function esc_html( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES );
}

function esc_attr( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES );
}

function esc_textarea( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES );
}

function esc_url( $url, $protocols = null, $_context = 'display' ) {
	return (string) $url;
}

function esc_url_raw( $url, $protocols = null ) {
	return (string) $url;
}

function esc_sql( $data ) {
	return is_array( $data ) ? array_map( 'esc_sql', $data ) : addslashes( (string) $data );
}

function wp_kses_post( $content ) {
	return strip_tags( (string) $content );
}

function wp_strip_all_tags( $text, $remove_breaks = false ) {
	return trim( strip_tags( (string) $text ) );
}

function wp_unslash( $value ) {
	if ( is_array( $value ) ) {
		return array_map( 'wp_unslash', $value );
	}
	return is_string( $value ) ? stripslashes( $value ) : $value;
}

function sanitize_key( $key ) {
	return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) );
}

function sanitize_text_field( $str ) {
	return trim( preg_replace( '/[\r\n\t ]+/', ' ', strip_tags( (string) $str ) ) );
}

function sanitize_textarea_field( $str ) {
	return trim( strip_tags( (string) $str ) );
}

function sanitize_title( $title, $fallback = '', $context = 'save' ) {
	$t = strtolower( trim( (string) $title ) );
	$t = preg_replace( '/[\s_]+/u', '-', $t );
	$t = preg_replace( '/[^\p{L}\p{N}\-]+/u', '-', $t );
	$t = preg_replace( '/-+/', '-', $t );
	return trim( $t, '-' );
}

function sanitize_email( $email ) {
	return filter_var( (string) $email, FILTER_SANITIZE_EMAIL );
}

function sanitize_file_name( $filename ) {
	return preg_replace( '/[^A-Za-z0-9._\-\x80-\xff]/u', '', (string) $filename );
}

function sanitize_hex_color( $color ) {
	return preg_match( '/^#([A-Fa-f0-9]{3}){1,2}$/', (string) $color ) ? $color : '';
}

function wp_parse_args( $args, $defaults = array() ) {
	if ( is_object( $args ) ) {
		$args = get_object_vars( $args );
	} elseif ( is_string( $args ) ) {
		parse_str( $args, $args );
	}
	return array_merge( (array) $defaults, (array) $args );
}

function wp_json_encode( $data, $flags = 0, $depth = 512 ) {
	return json_encode( $data, $flags | JSON_UNESCAPED_UNICODE, $depth );
}

function wp_trim_words( $text, $num_words = 55, $more = null ) {
	$words = preg_split( '/\s+/u', trim( (string) $text ) );
	$num_words = (int) $num_words;
	if ( count( $words ) > $num_words ) {
		return implode( ' ', array_slice( $words, 0, $num_words ) ) . ( null === $more ? '&nbsp;&hellip;' : $more );
	}
	return implode( ' ', $words );
}

function wpautop( $pee, $br = true ) {
	return $pee;
}

function checked( $checked, $current = true, $echo = true ) {
	$result = ( (string) $checked === (string) $current ) ? ' checked="checked"' : '';
	if ( $echo ) {
		echo $result;
	}
	return $result;
}

function selected( $selected, $current = true, $echo = true ) {
	$result = ( (string) $selected === (string) $current ) ? ' selected="selected"' : '';
	if ( $echo ) {
		echo $result;
	}
	return $result;
}

function is_wp_error( $thing ) {
	return $thing instanceof WP_Error;
}

function wp_mail( $to, $subject, $message, $headers = '', $attachments = array() ) {
	$GLOBALS['mjc_stub']->mails[] = compact( 'to', 'subject', 'message' );
	return true;
}

function wp_rand( $min = 0, $max = 0 ) {
	return random_int( (int) $min, $max ? (int) $max : mt_getrandmax() );
}

/* ------------------------------------------------------------------ *
 * URLs / paths
 * ------------------------------------------------------------------ */

function home_url( $path = '', $scheme = null ) {
	return 'http://test.local' . ( $path ? '/' . ltrim( $path, '/' ) : '' );
}

function site_url( $path = '', $scheme = null ) {
	return home_url( $path );
}

function admin_url( $path = '', $scheme = 'admin' ) {
	return 'http://test.local/wp-admin/' . ltrim( (string) $path, '/' );
}

function plugins_url( $path = '', $plugin = '' ) {
	$base = 'http://test.local/wp-content/plugins/mojrian-club';
	return $path ? $base . '/' . ltrim( $path, '/' ) : $base;
}

function plugin_basename( $file ) {
	$file = str_replace( '\\', '/', (string) $file );
	if ( 0 === strpos( $file, '/plugin/' ) ) {
		return 'mojrian-club/' . substr( $file, 8 );
	}
	return basename( $file );
}

function plugin_dir_path( $file ) {
	return trailingslashit( dirname( str_replace( '\\', '/', (string) $file ) ) );
}

function plugin_dir_url( $file ) {
	return plugins_url( '', $file ) . '/';
}

function trailingslashit( $string ) {
	return rtrim( (string) $string, '/\\' ) . '/';
}

function untrailingslashit( $string ) {
	return rtrim( (string) $string, '/\\' );
}

function add_query_arg( ...$args ) {
	if ( is_array( $args[0] ) ) {
		$params = $args[0];
		$url    = isset( $args[1] ) ? $args[1] : ( isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '' );
	} else {
		$params = array( $args[0] => $args[1] );
		$url    = isset( $args[2] ) ? $args[2] : ( isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '' );
	}
	$parts = explode( '#', (string) $url, 2 );
	$frag  = isset( $parts[1] ) ? '#' . $parts[1] : '';
	$q     = explode( '?', $parts[0], 2 );
	$base  = $q[0];
	parse_str( isset( $q[1] ) ? $q[1] : '', $query );
	foreach ( $params as $k => $v ) {
		if ( false === $v ) {
			unset( $query[ $k ] );
		} else {
			$query[ $k ] = $v;
		}
	}
	$qs = http_build_query( $query );
	return $base . ( $qs ? '?' . $qs : '' ) . $frag;
}

function remove_query_arg( $keys, $url = false ) {
	if ( false === $url ) {
		$url = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';
	}
	foreach ( (array) $keys as $k ) {
		$url = add_query_arg( array( $k => false ), $url );
	}
	return $url;
}

function wp_login_url( $redirect = '' ) {
	return 'http://test.local/wp-login.php';
}

function wp_logout_url( $redirect = '' ) {
	return 'http://test.local/wp-login.php?action=logout';
}

function wp_lostpassword_url( $redirect = '' ) {
	return 'http://test.local/wp-login.php?action=lostpassword';
}

function wp_nonce_url( $actionurl, $action = -1, $name = '_wpnonce' ) {
	return add_query_arg( array( $name => wp_create_nonce( $action ) ), $actionurl );
}

function wp_nonce_field( $action = -1, $name = '_wpnonce', $referer = true, $echo = true ) {
	$field = '<input type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( wp_create_nonce( $action ) ) . '" />';
	if ( $echo ) {
		echo $field;
	}
	return $field;
}

function wp_create_nonce( $action = -1 ) {
	return substr( md5( 'nonce-salt|' . $action . '|' . $GLOBALS['mjc_stub']->current_user ), 0, 12 );
}

function wp_verify_nonce( $nonce, $action = -1 ) {
	$nonce = (string) $nonce;
	if ( $nonce && hash_equals( wp_create_nonce( $action ), $nonce ) ) {
		return 1;
	}
	return false;
}

function wp_get_referer() {
	return isset( $GLOBALS['mjc_stub']->referer ) ? $GLOBALS['mjc_stub']->referer : 'http://test.local/referer';
}

function wp_safe_redirect( $location, $status = 302, $x_redirect_by = 'WordPress' ) {
	$GLOBALS['mjc_stub']->redirects[] = (string) $location;
	throw new MJC_Stub_Redirect( (string) $location );
}

function wp_redirect( $location, $status = 302 ) {
	$GLOBALS['mjc_stub']->redirects[] = (string) $location;
	throw new MJC_Stub_Redirect( (string) $location );
}

function wp_die( $message = '', $title = '', $args = array() ) {
	$msg = is_scalar( $message ) ? (string) $message : wp_json_encode( $message );
	throw new MJC_Stub_Die( $msg );
}

function status_header( $code, $description = '' ) {}

function nocache_headers() {}

function wp_mkdir_p( $dir ) {
	if ( is_dir( $dir ) ) {
		return true;
	}
	return @mkdir( $dir, 0777, true );
}

/* ------------------------------------------------------------------ *
 * Conditionals
 * ------------------------------------------------------------------ */

function is_admin() {
	return ! empty( $GLOBALS['mjc_stub']->is_admin );
}

function is_multisite() {
	return false;
}

function is_ssl() {
	return false;
}

function is_page( $page = '' ) {
	return false;
}

function is_singular( $post_types = '' ) {
	return false;
}

function is_front_page() {
	return false;
}

function get_locale() {
	return 'fa_IR';
}

function get_bloginfo( $show = '', $filter = 'raw' ) {
	return 'test';
}

/* ------------------------------------------------------------------ *
 * Posts / terms / media
 * ------------------------------------------------------------------ */

function wp_insert_post( $arr, $wp_error = false ) {
	$arr = (array) $arr;
	$id  = $GLOBALS['mjc_stub']->next_post_id++;
	$post = (object) array(
		'ID'           => $id,
		'post_title'   => isset( $arr['post_title'] ) ? $arr['post_title'] : '',
		'post_name'    => isset( $arr['post_name'] ) && '' !== $arr['post_name'] ? $arr['post_name'] : ( isset( $arr['post_title'] ) ? sanitize_title( $arr['post_title'] ) : '' ),
		'post_content' => isset( $arr['post_content'] ) ? $arr['post_content'] : '',
		'post_status'  => isset( $arr['post_status'] ) ? $arr['post_status'] : 'publish',
		'post_type'    => isset( $arr['post_type'] ) ? $arr['post_type'] : 'post',
		'post_parent'  => isset( $arr['post_parent'] ) ? (int) $arr['post_parent'] : 0,
		'guid'         => isset( $arr['guid'] ) ? $arr['guid'] : '',
		'meta_input'   => isset( $arr['meta_input'] ) ? (array) $arr['meta_input'] : array(),
	);
	$GLOBALS['mjc_stub']->posts[ $id ] = $post;
	foreach ( $post->meta_input as $k => $v ) {
		update_post_meta( $id, $k, $v );
	}
	return $id;
}

function get_post( $post = null ) {
	if ( is_object( $post ) ) {
		return $post;
	}
	if ( is_array( $post ) ) {
		return (object) $post;
	}
	$id = (int) $post;
	return isset( $GLOBALS['mjc_stub']->posts[ $id ] ) ? $GLOBALS['mjc_stub']->posts[ $id ] : null;
}

function get_page_by_path( $page_path, $output = OBJECT, $post_type = 'page' ) {
	if ( '' === (string) $page_path ) {
		return null;
	}
	foreach ( $GLOBALS['mjc_stub']->posts as $p ) {
		if ( $p->post_name === $page_path && $p->post_type === $post_type ) {
			return $p;
		}
	}
	return null;
}

function get_permalink( $post = 0 ) {
	$p = get_post( $post );
	return 'http://test.local/?p=' . ( $p ? $p->ID : 0 );
}

function get_the_ID() {
	return isset( $GLOBALS['mjc_stub']->current_post ) ? (int) $GLOBALS['mjc_stub']->current_post : 0;
}

function get_the_title( $post = 0 ) {
	$p = get_post( $post ? $post : get_the_ID() );
	return $p ? $p->post_title : '';
}

function get_posts( $args = array() ) {
	$defaults = array( 'post_type' => 'post', 'post_status' => 'publish' );
	$args  = wp_parse_args( $args, $defaults );
	$limit = 5;
	if ( isset( $args['posts_per_page'] ) && -1 === (int) $args['posts_per_page'] ) {
		$limit = -1;
	} elseif ( isset( $args['posts_per_page'] ) && $args['posts_per_page'] > 0 ) {
		$limit = (int) $args['posts_per_page'];
	} elseif ( isset( $args['numberposts'] ) && -1 === (int) $args['numberposts'] ) {
		$limit = -1;
	} elseif ( isset( $args['numberposts'] ) && $args['numberposts'] > 0 ) {
		$limit = (int) $args['numberposts'];
	}
	$out   = array();
	foreach ( $GLOBALS['mjc_stub']->posts as $p ) {
		if ( isset( $args['post_type'] ) && 'any' !== $args['post_type'] && $p->post_type !== $args['post_type'] ) {
			continue;
		}
		if ( isset( $args['post_status'] ) && 'any' !== $args['post_status'] && $p->post_status !== $args['post_status'] ) {
			continue;
		}
		$out[] = $p;
		if ( $limit > 0 && count( $out ) >= $limit ) {
			break;
		}
	}
	if ( isset( $args['fields'] ) && 'ids' === $args['fields'] ) {
		return array_map( function ( $p ) {
			return (int) $p->ID;
		}, $out );
	}
	return $out;
}

function wp_delete_post( $postid, $force = false ) {
	unset( $GLOBALS['mjc_stub']->posts[ (int) $postid ] );
	return true;
}

function update_post_meta( $post_id, $key, $value, $prev = '' ) {
	$GLOBALS['mjc_stub']->post_meta[ (int) $post_id ][ $key ] = $value;
	return true;
}

function get_post_meta( $post_id, $key = '', $single = false ) {
	$id   = (int) $post_id;
	$meta = isset( $GLOBALS['mjc_stub']->post_meta[ $id ] ) ? $GLOBALS['mjc_stub']->post_meta[ $id ] : array();
	if ( '' === $key ) {
		return $meta;
	}
	$val = isset( $meta[ $key ] ) ? $meta[ $key ] : '';
	if ( $single ) {
		return $val;
	}
	return '' === $val ? array() : array( $val );
}

function delete_post_meta( $post_id, $key, $value = '' ) {
	unset( $GLOBALS['mjc_stub']->post_meta[ (int) $post_id ][ $key ] );
	return true;
}

function register_post_type( $type, $args = array() ) {
	$GLOBALS['mjc_stub']->post_types[ $type ] = $args;
	return true;
}

function register_taxonomy( $taxonomy, $object_type, $args = array() ) {
	$GLOBALS['mjc_stub']->taxonomies[ $taxonomy ] = $args;
	return true;
}

function wp_insert_term( $term, $taxonomy, $args = array() ) {
	$id = $GLOBALS['mjc_stub']->next_term_id++;
	return array( 'term_id' => $id, 'term_taxonomy_id' => $id );
}

function get_terms( $args = array() ) {
	return array();
}

function wp_set_object_terms( $object_id, $terms, $taxonomy, $append = false ) {
	return array();
}

function get_the_terms( $post, $taxonomy ) {
	return array();
}

function wp_upload_dir( $time = null, $create_dir = true, $refresh_cache = false ) {
	$dir = '/uploads';
	wp_mkdir_p( $dir );
	return array(
		'path'    => $dir,
		'url'     => 'http://test.local/wp-content/uploads',
		'subdir'  => '',
		'basedir' => $dir,
		'baseurl' => 'http://test.local/wp-content/uploads',
		'error'   => false,
	);
}

function media_handle_upload( $file_id, $post_id, $post_data = array(), $overrides = array() ) {
	return wp_insert_attachment( array( 'post_title' => 'upload' ), '/uploads/tmp' );
}

function wp_insert_attachment( $args, $file = false, $parent = 0, $wp_error = false ) {
	$args = (array) $args;
	$args['post_type'] = 'attachment';
	$args['guid']      = isset( $args['guid'] ) ? $args['guid'] : (string) $file;
	$id = wp_insert_post( $args );
	return $id;
}

function wp_generate_attachment_metadata( $attachment_id, $file ) {
	return array();
}

function wp_update_attachment_metadata( $attachment_id, $data ) {
	return true;
}

function wp_delete_attachment( $post_id, $force = false ) {
	unset( $GLOBALS['mjc_stub']->posts[ (int) $post_id ] );
	return true;
}

function set_post_thumbnail( $post, $thumbnail_id ) {
	return update_post_meta( $post, '_thumbnail_id', (int) $thumbnail_id );
}

function get_post_thumbnail_id( $post = null ) {
	$p = get_post( $post ? $post : get_the_ID() );
	return $p ? (int) get_post_meta( $p->ID, '_thumbnail_id', true ) : 0;
}

function has_post_thumbnail( $post = null ) {
	return (bool) get_post_thumbnail_id( $post );
}

function get_the_post_thumbnail( $post = null, $size = 'post-thumbnail', $attr = '' ) {
	return has_post_thumbnail( $post ) ? '<img />' : '';
}

function get_the_post_thumbnail_url( $post = null, $size = 'post-thumbnail' ) {
	$att = get_post_thumbnail_id( $post );
	return $att ? wp_get_attachment_image_url( $att, $size ) : false;
}

function wp_get_attachment_image( $attachment_id, $size = 'thumbnail', $icon = false, $attr = '' ) {
	return '<img />';
}

function wp_get_attachment_image_url( $attachment_id, $size = 'thumbnail' ) {
	$p = get_post( $attachment_id );
	return $p ? ( 'http://test.local/wp-content/uploads/' . basename( (string) $p->guid ) ) : false;
}

function wp_get_attachment_url( $attachment_id ) {
	return wp_get_attachment_image_url( $attachment_id );
}

/* ------------------------------------------------------------------ *
 * Admin chrome / scripts
 * ------------------------------------------------------------------ */

function add_menu_page( $page_title, $menu_title, $capability, $slug, $callback = '', $icon_url = '', $position = null ) {
	$GLOBALS['mjc_stub']->menus[] = array(
		'type'      => 'main',
		'page_title' => $page_title,
		'menu_title' => $menu_title,
		'cap'       => $capability,
		'slug'      => $slug,
	);
	$GLOBALS['mjc_stub']->menu_pages[ $slug ] = $callback;
	return 'toplevel_' . $slug;
}

function add_submenu_page( $parent, $page_title, $menu_title, $capability, $slug, $callback = '' ) {
	$GLOBALS['mjc_stub']->menus[] = array(
		'type'      => 'sub',
		'parent'    => $parent,
		'page_title' => $page_title,
		'menu_title' => $menu_title,
		'cap'       => $capability,
		'slug'      => $slug,
	);
	$GLOBALS['mjc_stub']->menu_pages[ $slug ] = $callback;
	return 'submenu_' . $slug;
}

function add_meta_box( $id, $title, $callback, $screen = null, $context = 'advanced', $priority = 'default', $callback_args = null ) {
	$GLOBALS['mjc_stub']->meta_boxes[ $id ] = array( 'title' => $title, 'callback' => $callback );
}

function wp_enqueue_style( $handle, $src = '', $deps = array(), $ver = false, $media = 'all' ) {
	$GLOBALS['mjc_stub']->enqueued[ $handle ] = $src;
}

function wp_enqueue_script( $handle, $src = '', $deps = array(), $ver = false, $in_footer = false ) {
	$GLOBALS['mjc_stub']->enqueued[ $handle ] = $src;
}

function wp_localize_script( $handle, $name, $data ) {
	return true;
}

function wp_add_inline_style( $handle, $css ) {
	$GLOBALS['mjc_stub']->inline_css[ $handle ] = $css;
	return true;
}

function wp_register_style( $handle, $src = '', $deps = array(), $ver = false, $media = 'all' ) {
	return true;
}

function wp_register_script( $handle, $src = '', $deps = array(), $ver = false, $in_footer = false ) {
	return true;
}

function load_plugin_textdomain( $domain, $deprecated = false, $plugin_rel_path = false ) {
	return true;
}

function flush_rewrite_rules( $hard = true ) {
	$GLOBALS['mjc_stub']->flushed = true;
	// وضعیت پست‌تایپ‌های ثبت‌شده در لحظه‌ی flush — برای تست ترتیب (flush باید بعد از ثبت CPTها باشد).
	$GLOBALS['mjc_stub']->flush_history[] = ( isset( $GLOBALS['mjc_stub']->post_types ) && is_array( $GLOBALS['mjc_stub']->post_types ) ) ? array_keys( $GLOBALS['mjc_stub']->post_types ) : array();
}

function settings_errors() {}

function get_settings_errors() {
	return array();
}

function add_settings_error( $setting, $code, $message, $type = 'error' ) {}

function register_setting( $group, $name, $args = array() ) {}

function add_settings_section( $id, $title, $cb, $page ) {}

function add_settings_field( $id, $title, $cb, $page, $section = 'default', $args = array() ) {}

function settings_fields( $group ) {}

function do_settings_sections( $page ) {}

function submit_button( $text = '', $type = '', $name = '', $wrap = true, $other = null ) {}

function get_header( $name = '', $args = array() ) {}

function get_footer( $name = '', $args = array() ) {}

function have_posts() {
	return false;
}

function the_post() {}

function the_title( $before = '', $after = '', $echo = true ) {}

function the_content( $more_link_text = '', $strip_teaser = false ) {}

function post_class( $class = '', $post = null ) {}

function wp_reset_query() {}

function wp_reset_postdata() {}

function get_search_query( $escaped = true ) {
	return '';
}
