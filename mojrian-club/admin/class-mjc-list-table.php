<?php
/**
 * جدول لیست عمومی ادمین (WP_List_Table پیکربندی‌پذیر).
 *
 * @package mojrian-club
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * کلاس جدول لیست.
 */
class MJC_List_Table extends WP_List_Table {

	/**
	 * پیکربندی ستون‌ها.
	 *
	 * @var array
	 */
	public $columns_cfg = array();

	/**
	 * ردیف‌های صفحه‌ی جاری.
	 *
	 * @var array
	 */
	public $rows_data = array();

	/**
	 * فرمت‌دهنده‌ی هر ردیف.
	 *
	 * @var callable
	 */
	public $row_formatter = null;

	/**
	 * عنوان مفرد/جمع.
	 *
	 * @var string
	 */
	public $singular = 'item';

	/**
	 * @var string
	 */
	public $plural = 'items';

	/**
	 * سازنده.
	 */
	public function __construct() {
		parent::__construct( array(
			'singular' => 'mjc_item',
			'plural'   => 'mjc_items',
			'ajax'     => false,
		) );
	}

	/**
	 * ستون‌ها.
	 *
	 * @return array
	 */
	public function get_columns() {
		return $this->columns_cfg;
	}

	/**
	 * اکشن‌های گروهی.
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		return isset( $this->bulk_actions ) ? $this->bulk_actions : array();
	}

	/**
	 * آماده‌سازی.
	 */
	public function prepare_items() {
		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = array();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->items           = $this->rows_data;
	}

	/**
	 * محتوای هر ستون.
	 *
	 * @param object $item ردیف.
	 * @param string $column_name نام ستون.
	 * @return string
	 */
	public function column_default( $item, $column_name ) {
		if ( is_callable( $this->row_formatter ) ) {
			$cells = call_user_func( $this->row_formatter, $item );
			return isset( $cells[ $column_name ] ) ? $cells[ $column_name ] : '';
		}
		return isset( $item->$column_name ) ? esc_html( $item->$column_name ) : '';
	}

	/**
	 * ستون چک‌باکس.
	 *
	 * @param object $item ردیف.
	 * @return string
	 */
	public function column_cb( $item ) {
		$id = isset( $item->id ) ? $item->id : ( isset( $item->ID ) ? $item->ID : 0 );
		return sprintf( '<input type="checkbox" name="mjc_bulk_ids[]" value="%d" />', (int) $id );
	}
}
