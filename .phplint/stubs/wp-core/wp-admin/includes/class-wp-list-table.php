<?php
if ( ! class_exists( 'WP_List_Table' ) ) {
	class WP_List_Table {
		protected $items = array();
		protected $columns = array();
		protected $_column_headers = array();
		public function __construct( $args = array() ) {}
		public function prepare_items() {}
		public function get_columns() {
			return array();
		}
		public function column_default( $item, $column_name ) {
			return '';
		}
		public function display() {
			echo '<table class="wp-list-table widefat striped">';
			foreach ( (array) $this->items as $item ) {
				echo '<tr>';
				$cols = $this->get_columns();
				foreach ( array_keys( (array) $cols ) as $col ) {
					if ( 'cb' === $col ) {
						echo '<td>' . $this->column_cb( $item ) . '</td>';
					} else {
						echo '<td>' . $this->column_default( $item, $col ) . '</td>';
					}
				}
				echo '</tr>';
			}
			echo '</table>';
		}
		public function column_cb( $item ) {
			return '';
		}
		public function display_tablenav( $which ) {}
		public function get_table_classes() {
			return array();
		}
	}
}
