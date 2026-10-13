<?php
/**
 * wpdb stub backed by an in-memory table store.
 *
 * Contract with the smoke suite:
 *  - CREATE TABLE via dbDelta registers the authoritative schema (column names + numeric/str types).
 *  - INSERT/UPDATE touching unknown columns records a violation (invalid SQL in the plugin).
 *  - Statements that cannot be understood are recorded in $GLOBALS['mjc_stub_sql_unknown'].
 *  - SELECT aggregates support the patterns the plugin actually uses.
 */

class wpdb {
	public $prefix = 'wp_';
	public $users = 'wp_users';
	public $usermeta = 'wp_usermeta';
	public $insert_id = 0;
	public $rows_affected = 0;
	public $num_rows = 0;
	public $tables = array();
	public $last_error = '';

	private $last_result = array();
	private $result_idx = 0;

	public function __construct() {
		$GLOBALS['mjc_stub_sql_unknown'] = isset( $GLOBALS['mjc_stub_sql_unknown'] ) ? $GLOBALS['mjc_stub_sql_unknown'] : array();
		$GLOBALS['mjc_stub_violations'] = array();
	}

	public function get_charset_collate() {
		return 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
	}

	public function esc_like( $text ) {
		return addcslashes( (string) $text, '_%\\' );
	}

	public function insert( $table, $data, $format = null ) {
		$cols = array();
		$vals = array();
		foreach ( (array) $data as $c => $v ) {
			$cols[] = '`' . str_replace( '`', '', (string) $c ) . '`';
			$vals[] = is_int( $v ) || is_float( $v ) ? (string) $v : "'" . addslashes( (string) $v ) . "'";
		}
		$sql = 'INSERT INTO `' . $table . '` (' . implode( ', ', $cols ) . ') VALUES (' . implode( ', ', $vals ) . ')';
		$r = $this->query( $sql );
		return false === $r ? false : $r;
	}

	public function update( $table, $data, $where, $format = null, $where_format = null ) {
		// جدول کاربران وردپرس: به‌روزرسانی مستقیم user_login و ... روی فروشگاه کاربران استاب.
		if ( 'wp_users' === $table || $this->users === $table ) {
			$affected = 0;
			$wid      = isset( $where['ID'] ) ? (int) $where['ID'] : 0;
			if ( $wid && isset( $GLOBALS['mjc_stub']->users[ $wid ] ) ) {
				$u = $GLOBALS['mjc_stub']->users[ $wid ];
				foreach ( (array) $data as $c => $v ) {
					$u->$c = $v;
				}
				mjc_stub_sync_user( $u );
				$affected = 1;
			}
			$this->rows_affected = $affected;
			return $affected;
		}
		$set = array();
		foreach ( (array) $data as $c => $v ) {
			$set[] = '`' . str_replace( '`', '', (string) $c ) . '` = ' . ( is_int( $v ) || is_float( $v ) ? (string) $v : "'" . addslashes( (string) $v ) . "'" );
		}
		$conds = array();
		foreach ( (array) $where as $c => $v ) {
			$conds[] = '`' . str_replace( '`', '', (string) $c ) . '` = ' . ( is_int( $v ) || is_float( $v ) ? (string) $v : "'" . addslashes( (string) $v ) . "'" );
		}
		$sql = 'UPDATE `' . $table . '` SET ' . implode( ', ', $set ) . ( $conds ? ' WHERE ' . implode( ' AND ', $conds ) : '' );
		return $this->query( $sql );
	}

	public function delete( $table, $where, $where_format = null ) {
		$conds = array();
		foreach ( (array) $where as $c => $v ) {
			$conds[] = '`' . str_replace( '`', '', (string) $c ) . '` = ' . ( is_int( $v ) || is_float( $v ) ? (string) $v : "'" . addslashes( (string) $v ) . "'" );
		}
		$sql = 'DELETE FROM `' . $table . '`' . ( $conds ? ' WHERE ' . implode( ' AND ', $conds ) : '' );
		return $this->query( $sql );
	}

	public function prepare( $query, ...$args ) {
		if ( count( $args ) === 1 && is_array( $args[0] ) ) {
			$args = $args[0];
		}
		$i = 0;
		return preg_replace_callback( '/%[sdfF]/', function ( $m ) use ( &$i, $args ) {
			$v = array_key_exists( $i, $args ) ? $args[ $i ] : '';
			$i++;
			if ( '%d' === $m[0] ) {
				return (string) (int) $v;
			}
			if ( '%f' === $m[0] || '%F' === $m[0] ) {
				return (string) (float) $v;
			}
			return "'" . addslashes( (string) $v ) . "'";
		}, $query );
	}

	public function query( $sql ) {
		$sql = trim( (string) $sql );
		$this->last_error = '';

		if ( preg_match( '/^CREATE TABLE IF NOT EXISTS\s+(\S+)/i', $sql, $m ) ) {
			$t = trim( $m[1], '`' );
			$this->ensure_table( $t );
			return 0;
		}

		if ( preg_match( '/^DESCRIBE\s+(\S+)/i', $sql, $m ) ) {
			$t = trim( $m[1], '`' );
			$this->ensure_table( $t );
			$rows = array();
			foreach ( array_keys( $this->tables[ $t ]['cols'] ) as $c ) {
				$rows[] = (object) array( 'Field' => $c );
			}
			$this->last_result = $rows;
			$this->num_rows = count( $rows );
			return $this->num_rows;
		}

		if ( preg_match( '/^SELECT\s+(.+?)\s+FROM\s+(\S+)(.*)$/is', $sql, $m ) ) {
			return $this->do_select( trim( $m[1] ), trim( $m[2], '`' ), $sql, trim( $m[3] ) );
		}

		if ( preg_match( '/^INSERT\s+(?:IGNORE\s+)?INTO\s+(\S+)\s*\(([^)]*)\)\s*VALUES\s*\((.*)\)\s*$/is', $sql, $m ) ) {
			return $this->do_insert( trim( $m[1], '`' ), $m[2], $m[3] );
		}

		if ( preg_match( '/^UPDATE\s+(\S+)\s+SET\s+(.+?)(?:\s+WHERE\s+(.+))?$/is', $sql, $m ) ) {
			return $this->do_update( trim( $m[1], '`' ), $m[2], isset( $m[3] ) ? trim( $m[3] ) : '' );
		}

		if ( preg_match( '/^DELETE\s+FROM\s+(\S+)(?:\s+WHERE\s+(.+))?$/is', $sql, $m ) ) {
			return $this->do_delete( trim( $m[1], '`' ), isset( $m[2] ) ? trim( $m[2] ) : '' );
		}

		if ( preg_match( '/^TRUNCATE\s+(?:TABLE\s+)?(\S+)/i', $sql, $m ) ) {
			$t = trim( $m[1], '`' );
			$this->ensure_table( $t );
			$this->tables[ $t ]['rows'] = array();
			return 0;
		}

		$GLOBALS['mjc_stub_sql_unknown'][] = $sql;
		return false;
	}

	public function get_var( $sql = null, $x = 0, $y = 0 ) {
		$rows = $this->get_results( $sql );
		if ( ! $rows ) {
			return null;
		}
		$r = $rows[0];
		$cols = array_values( get_object_vars( $r ) );
		return isset( $cols[ $y ] ) ? $cols[ $y ] : null;
	}

	public function get_row( $sql = null, $output = OBJECT, $y = 0 ) {
		$rows = $this->get_results( $sql );
		return isset( $rows[ $y ] ) ? $rows[ $y ] : null;
	}

	public function get_col( $sql = null, $x = 0 ) {
		$out = array();
		foreach ( $this->get_results( $sql ) as $r ) {
			$cols = array_values( get_object_vars( $r ) );
			if ( isset( $cols[ $x ] ) ) {
				$out[] = $cols[ $x ];
			}
		}
		return $out;
	}

	public function get_results( $sql = null, $output = OBJECT ) {
		$this->query( $sql );
		$out = $this->last_result;
		if ( ARRAY_A === $output ) {
			return array_map( function ( $r ) {
				return (array) $r;
			}, $out );
		}
		if ( ARRAY_N === $output ) {
			return array_map( function ( $r ) {
				return array_values( (array) $r );
			}, $out );
		}
		return $out;
	}

	/* ------------------------------------------------------------------ */

	private function ensure_table( $table ) {
		if ( ! isset( $this->tables[ $table ] ) ) {
			$this->tables[ $table ] = array( 'cols' => array( 'id' => 'num' ), 'rows' => array(), 'auto' => 1 );
		}
	}

	private function violation( $msg ) {
		$GLOBALS['mjc_stub_violations'][] = $msg;
		$this->last_error = $msg;
	}

	private function parse_from( $tail ) {
		$tail = ' ' . trim( (string) $tail ); // \s-prefixed matchers need a leading space.
		$where = '';
		$order = '';
		$limit = '';
		$offset = 0;
		$by_group = 0;

		// LIMIT first (it can contain commas/numbers, keep it out of other scans).
		if ( preg_match( '/\sLIMIT\s+(\d+)(?:\s*,\s*(\d+))?(\s|$)/i', $tail, $m, PREG_OFFSET_CAPTURE ) ) {
			$limit = $m[1][0];
			$by_group = isset( $m[2] ) && '' !== $m[2][0] ? (int) $m[2][0] : 0;
			$tail = substr( $tail, 0, $m[0][1] ) . ' ';
		}
		if ( preg_match( '/\sLIMIT\s+(\d+)\s+OFFSET\s+(\d+)(\s|$)/i', $tail, $m, PREG_OFFSET_CAPTURE ) ) {
			$limit = $m[1][0];
			$offset = (int) $m[2][0];
			$tail = substr( $tail, 0, $m[0][1] ) . ' ';
		}
		if ( preg_match( '/\sOFFSET\s+(\d+)(\s|$)/i', $tail, $m, PREG_OFFSET_CAPTURE ) ) {
			$offset = (int) $m[1][0];
			$tail = substr( $tail, 0, $m[0][1] ) . ' ';
		}

		// GROUP BY (before ORDER BY so "GROUP BY x ORDER BY y" splits correctly).
		$group = '';
		if ( preg_match( '/\sGROUP\s+BY\s+(.+?)(?=\s+HAVING\s|\s+ORDER\s+BY\s|\s+LIMIT\s|$)/is', $tail, $m, PREG_OFFSET_CAPTURE ) ) {
			$group = trim( $m[1][0] );
			$tail = substr( $tail, 0, $m[0][1] ) . ' ';
		}
		if ( preg_match( '/\sORDER\s+BY\s+(.+?)(?=\s+LIMIT\s|$)/is', $tail, $m, PREG_OFFSET_CAPTURE ) ) {
			$order = trim( $m[1][0] );
			$tail = substr( $tail, 0, $m[0][1] ) . ' ';
		}
		if ( preg_match( '/\sWHERE\s+(.+)$/is', $tail, $m, PREG_OFFSET_CAPTURE ) ) {
			$where = trim( $m[1][0] );
			$tail = substr( $tail, 0, $m[0][1] );
		}
		return array( $where, $order, $limit, $offset, $group, $by_group );
	}

	private function parse_where( $expr ) {
		$expr = trim( (string) $expr );
		if ( '' === $expr ) {
			return array( 'and', array() );
		}
		$parts = $this->split_top( $expr, 'OR' );
		if ( count( $parts ) > 1 ) {
			$conds = array();
			foreach ( $parts as $p ) {
				list( $op, $sub ) = $this->parse_where( $p );
				if ( 'noop' === $op ) {
					continue;
				}
				$conds[] = array( $op, $sub );
			}
			return array( 'or', $conds );
		}
		$parts = $this->split_top( $expr, 'AND' );
		if ( count( $parts ) > 1 ) {
			$conds = array();
			foreach ( $parts as $p ) {
				list( $op, $sub ) = $this->parse_where( $p );
				if ( 'noop' === $op ) {
					continue;
				}
				$conds[] = array( $op, $sub );
			}
			return array( 'and', $conds );
		}
		if ( strlen( $expr ) > 1 && '(' === $expr[0] && ')' === substr( $expr, -1 ) ) {
			return $this->parse_where( substr( $expr, 1, -1 ) );
		}
		if ( '1=1' === preg_replace( '/\s+/', '', $expr ) ) {
			return array( 'noop', array() );
		}
		return array( 'cond', $this->parse_condition( $expr ) );
	}

	private function split_top( $expr, $word ) {
		$out = array();
		$depth = 0;
		$len = strlen( $expr );
		$start = 0;
		for ( $i = 0; $i < $len; $i++ ) {
			$c = $expr[ $i ];
			if ( '(' === $c ) {
				$depth++;
			} elseif ( ')' === $c ) {
				$depth--;
			} elseif ( 0 === $depth && ( ' ' === $c || "\t" === $c || "\n" === $c ) ) {
				$wlen  = strlen( $word );
				$chunk = substr( $expr, $i, $wlen + 2 );
				if ( strlen( $chunk ) === $wlen + 2
					&& 0 === strcasecmp( substr( $chunk, 1, $wlen ), $word )
					&& ! preg_match( '/[A-Za-z]/', substr( $chunk, 1 + $wlen, 1 ) ) ) {
					$out[] = trim( substr( $expr, $start, $i - $start ) );
					$i += 1 + $wlen;
					$start = $i + 1;
				}
			}
		}
		$out[] = trim( substr( $expr, $start ) );
		$out = array_values( array_filter( $out, function ( $s ) {
			return '' !== $s;
		} ) );
		return $out;
	}

	private function parse_condition( $expr ) {
		$expr = trim( $expr );
		if ( preg_match( '/^(.+?)\s+IS\s+NOT\s+NULL$/i', $expr, $m ) ) {
			return array( trim( $m[1] ), 'IS NOT', null );
		}
		if ( preg_match( '/^(.+?)\s+IS\s+NULL$/i', $expr, $m ) ) {
			return array( trim( $m[1] ), 'IS', null );
		}
		if ( preg_match( '/^(.+?)\s+NOT\s+IN\s*\((.+)\)$/is', $expr, $m ) ) {
			return array( trim( $m[1] ), 'NOT IN', $this->parse_value_list( $m[2] ) );
		}
		if ( preg_match( '/^(.+?)\s+IN\s*\((.+)\)$/is', $expr, $m ) ) {
			return array( trim( $m[1] ), 'IN', $this->parse_value_list( $m[2] ) );
		}
		if ( preg_match( '/^(.+?)\s+NOT\s+LIKE\s+(.+)$/i', $expr, $m ) ) {
			return array( trim( $m[1] ), 'NOT LIKE', $this->parse_value( trim( $m[2] ) ) );
		}
		if ( preg_match( '/^(.+?)\s+LIKE\s+(.+)$/i', $expr, $m ) ) {
			return array( trim( $m[1] ), 'LIKE', $this->parse_value( trim( $m[2] ) ) );
		}
		if ( preg_match( '/^(.+?)\s*>=\s*(.+)$/', $expr, $m ) ) {
			return array( trim( $m[1] ), '>=', $this->parse_value( trim( $m[2] ) ) );
		}
		if ( preg_match( '/^(.+?)\s*<=\s*(.+)$/', $expr, $m ) ) {
			return array( trim( $m[1] ), '<=', $this->parse_value( trim( $m[2] ) ) );
		}
		if ( preg_match( '/^(.+?)\s*<>\s*(.+)$/', $expr, $m ) ) {
			return array( trim( $m[1] ), '<>', $this->parse_value( trim( $m[2] ) ) );
		}
		if ( preg_match( '/^(.+?)\s*!=\s*(.+)$/', $expr, $m ) ) {
			return array( trim( $m[1] ), '!=', $this->parse_value( trim( $m[2] ) ) );
		}
		if ( preg_match( '/^(.+?)\s*>\s*(.+)$/', $expr, $m ) ) {
			return array( trim( $m[1] ), '>', $this->parse_value( trim( $m[2] ) ) );
		}
		if ( preg_match( '/^(.+?)\s*<\s*(.+)$/', $expr, $m ) ) {
			return array( trim( $m[1] ), '<', $this->parse_value( trim( $m[2] ) ) );
		}
		if ( preg_match( '/^(.+?)\s*=\s*(.+)$/', $expr, $m ) ) {
			return array( trim( $m[1] ), '=', $this->parse_value( trim( $m[2] ) ) );
		}
		$GLOBALS['mjc_stub_sql_unknown'][] = 'WHERE-cond: ' . $expr;
		return array( $expr, '?', null );
	}

	private function parse_value_list( $raw ) {
		$parts = $this->split_values( $raw );
		$out = array();
		foreach ( $parts as $p ) {
			$out[] = $this->parse_value( trim( $p ) );
		}
		return $out;
	}

	private function split_values( $raw ) {
		$out = array();
		$depth = 0;
		$cur = '';
		$in = '';
		$len = strlen( $raw );
		for ( $i = 0; $i < $len; $i++ ) {
			$c = $raw[ $i ];
			if ( '' !== $in ) {
				$cur .= $c;
				if ( '\\' === $c && $i + 1 < $len ) {
					$cur .= $raw[ ++$i ];
					continue;
				}
				if ( $c === $in ) {
					$in = '';
				}
				continue;
			}
			if ( "'" === $c || '"' === $c ) {
				$in = $c;
				$cur .= $c;
				continue;
			}
			if ( '(' === $c ) {
				$depth++;
				$cur .= $c;
				continue;
			}
			if ( ')' === $c ) {
				$depth--;
				$cur .= $c;
				continue;
			}
			if ( ',' === $c && 0 === $depth ) {
				$out[] = $cur;
				$cur = '';
				continue;
			}
			$cur .= $c;
		}
		if ( '' !== trim( $cur ) ) {
			$out[] = $cur;
		}
		return $out;
	}

	private function parse_value( $v ) {
		$v = trim( $v );
		if ( preg_match( "/^'(.*)'$/s", $v, $m ) ) {
			return stripcslashes( $m[1] );
		}
		if ( is_numeric( $v ) ) {
			return 0 === strpos( $v, '.' ) || false !== strpos( $v, '.' ) ? (float) $v : (int) $v;
		}
		if ( 'NULL' === strtoupper( $v ) ) {
			return null;
		}
		$GLOBALS['mjc_stub_sql_unknown'][] = 'value: ' . $v;
		return $v;
	}

	private function eval_condition( $cond, $row ) {
		if ( '?' === $cond[1] ) {
			return true;
		}
		list( $field, $op, $val ) = $cond;
		$field = trim( $field, '`' );
		// Strip table aliases: t.col / wp_mjc_x.col.
		if ( false !== strpos( $field, '.' ) ) {
			$field = substr( $field, strrpos( $field, '.' ) + 1 );
		}
		$actual = array_key_exists( $field, $row ) ? $row[ $field ] : null;
		switch ( $op ) {
			case '=':
				return is_numeric( $val ) && is_numeric( $actual ) ? (float) $actual === (float) $val : (string) $actual === (string) $val;
			case '<>':
			case '!=':
				return is_numeric( $val ) && is_numeric( $actual ) ? (float) $actual !== (float) $val : (string) $actual !== (string) $val;
			case '>':
				return (float) $actual > (float) $val;
			case '<':
				return (float) $actual < (float) $val;
			case '>=':
				return (float) $actual >= (float) $val;
			case '<=':
				return (float) $actual <= (float) $val;
			case 'IS':
				return null === $actual || '' === $actual;
			case 'IS NOT':
				return null !== $actual && '' !== $actual;
			case 'LIKE':
			case 'NOT LIKE':
				$pat = '/' . str_replace( array( '%', '_' ), array( '.*', '.' ), preg_quote( (string) $val, '/' ) ) . '/us';
				$hit = 1 === preg_match( $pat, (string) $actual );
				return 'LIKE' === $op ? $hit : ! $hit;
			case 'IN':
				foreach ( (array) $val as $v ) {
					if ( is_numeric( $v ) && is_numeric( $actual ) ? (float) $actual === (float) $v : (string) $actual === (string) $v ) {
						return true;
					}
				}
				return false;
			case 'NOT IN':
				foreach ( (array) $val as $v ) {
					if ( is_numeric( $v ) && is_numeric( $actual ) ? (float) $actual === (float) $v : (string) $actual === (string) $v ) {
						return false;
					}
				}
				return true;
		}
		return true;
	}

	private function eval_where( $node, $row ) {
		if ( ! is_array( $node ) || ! isset( $node[0] ) ) {
			return true;
		}
		list( $op, $sub ) = $node;
		if ( 'and' === $op ) {
			foreach ( $sub as $s ) {
				if ( ! $this->eval_where( $s, $row ) ) {
					return false;
				}
			}
			return true;
		}
		if ( 'or' === $op ) {
			foreach ( $sub as $s ) {
				if ( $this->eval_where( $s, $row ) ) {
					return true;
				}
			}
			return false;
		}
		if ( 'cond' === $op ) {
			return $this->eval_condition( $sub, $row );
		}
		if ( 'noop' === $op ) {
			return true;
		}
		return true;
	}

	private function col_list( $cols_raw, $table ) {
		$cols_raw = trim( $cols_raw );
		if ( '*' === $cols_raw || $table . '.*' === $cols_raw || '`' . $table . '`.*' === $cols_raw ) {
			return array();
		}
		// Alias.* → expand to all columns.
		if ( preg_match( '/^[a-zA-Z0-9_`]+\.\*$/', $cols_raw ) ) {
			return array();
		}
		$out = array();
		foreach ( explode( ',', $cols_raw ) as $c ) {
			$c = trim( $c );
			if ( '' === $c ) {
				continue;
			}
			// alias.col → keep last part.
			if ( false !== strpos( $c, '.' ) && ! preg_match( '/\(/', $c ) ) {
				$c = substr( $c, strrpos( $c, '.' ) + 1 );
			}
			$c = trim( $c, '`' );
			$out[] = $c;
		}
		return $out;
	}

	private function row_simplify( $row, $cols_raw ) {
		$raw = trim( $cols_raw );
		// COUNT(*) / COUNT(x) → count.
		if ( preg_match( '/^COUNT\s*\(\s*(\*|[a-zA-Z0-9_`.]+)\s*\)$/i', $raw, $m ) ) {
			return array( (object) array( 'count' => count( $row ) ) );
		}
		// Single aggregate without alias.
		if ( preg_match( '/^(\w+)\s*\(\s*(\*|[a-zA-Z0-9_`.]+|[\w`.]+\s*[-+]\s*[\w`.]+)\s*\)$/i', $raw, $m ) ) {
			$fn = strtoupper( $m[1] );
			$vals = array();
			foreach ( $row as $r ) {
				$r = (array) $r;
				$v = array_values( $r );
				$vals[] = isset( $v[0] ) ? $v[0] : 0;
			}
			if ( 'SUM' === $fn || 'COALESCE' === $fn || 'AVG' === $fn ) {
				$sum = array_sum( array_map( 'floatval', $vals ) );
				return array( (object) array( 'v' => $sum ) );
			}
			if ( 'MAX' === $fn ) {
				return array( (object) array( 'v' => $vals ? max( $vals ) : 0 ) );
			}
			if ( 'MIN' === $fn ) {
				return array( (object) array( 'v' => $vals ? min( $vals ) : 0 ) );
			}
		}
		return $row;
	}

	private function do_select( $cols_raw, $table, $full_sql, $tail ) {
		$this->ensure_table( $table );
		list( $where, $order, $limit, $offset, $group, $by_group ) = $this->parse_from( $tail );
		list( $wop, $wsub ) = $this->parse_where( $where );

		$rows = array();
		foreach ( $this->tables[ $table ]['rows'] as $r ) {
			$row = (array) $r;
			if ( ! $this->eval_where( array( $wop, $wsub ), $row ) ) {
				continue;
			}
			$rows[] = $row;
		}

		// GROUP BY first-key (used for SUM(x) GROUP BY user_id).
		if ( $group ) {
			$gcol = trim( preg_replace( '/\s+/', ' ', $group ) );
			$gcol = trim( $gcol, '`' );
			if ( false !== strpos( $gcol, '.' ) ) {
				$gcol = substr( $gcol, strrpos( $gcol, '.' ) + 1 );
			}
			$buckets = array();
			foreach ( $rows as $row ) {
				$k = array_key_exists( $gcol, $row ) ? (string) $row[ $gcol ] : '';
				$buckets[ $k ][] = $row;
			}
			$agg = strtoupper( trim( preg_replace( '/\s+/', ' ', $cols_raw ) ) );
			$out = array();
			foreach ( $buckets as $k => $b ) {
				if ( preg_match( '/SUM\s*\(/i', $agg ) ) {
					$s = 0;
					foreach ( $b as $row ) {
						foreach ( array( 'amount', 'points_balance', 'balance' ) as $cand ) {
							if ( array_key_exists( $cand, $row ) ) {
								$s += (float) $row[ $cand ];
								break;
							}
						}
					}
					$obj = new stdClass();
					$obj->$gcol = is_numeric( $k ) ? (int) $k : $k;
					$obj->total = $s;
					$out[] = $obj;
				} else {
					$out[] = (object) $b[0];
				}
			}
			$this->last_result = $out;
			$this->num_rows = count( $out );
			return $this->num_rows;
		}

		if ( $order ) {
			$orders = array();
			foreach ( explode( ',', $order ) as $piece ) {
				$piece = trim( $piece );
				$dir = 'ASC';
				if ( preg_match( '/^(.+)\s+(ASC|DESC)$/i', $piece, $m ) ) {
					$piece = trim( $m[1] );
					$dir = strtoupper( $m[2] );
				}
				$col = trim( $piece, '`' );
				if ( false !== strpos( $col, '.' ) ) {
					$col = substr( $col, strrpos( $col, '.' ) + 1 );
				}
				$orders[] = array( $col, $dir );
			}
			usort( $rows, function ( $a, $b ) use ( $orders ) {
				foreach ( $orders as $o ) {
					list( $col, $dir ) = $o;
					$va = array_key_exists( $col, $a ) ? $a[ $col ] : null;
					$vb = array_key_exists( $col, $b ) ? $b[ $col ] : null;
					if ( $va === $vb ) {
						continue;
					}
					$cmp = ( is_numeric( $va ) && is_numeric( $vb ) ) ? ( (float) $va <=> (float) $vb ) : ( (string) $va <=> (string) $vb );
					return 'DESC' === $dir ? -$cmp : $cmp;
				}
				return 0;
			} );
		}

		// DISTINCT.
		if ( 0 === stripos( $cols_raw, 'DISTINCT ' ) ) {
			$cols_raw = substr( $cols_raw, 9 );
			$cols = $this->col_list( $cols_raw, $table );
			$seen = array();
			$uniq = array();
			foreach ( $rows as $row ) {
				$key = array();
				foreach ( $cols as $c ) {
					$key[] = isset( $row[ $c ] ) ? (string) $row[ $c ] : '';
				}
				$k = implode( '|', $key );
				if ( isset( $seen[ $k ] ) ) {
					continue;
				}
				$seen[ $k ] = true;
				$uniq[] = $row;
			}
			$rows = $uniq;
		}

		// Column projection.
		$cols = $this->col_list( $cols_raw, $table );
		$is_count = (bool) preg_match( '/^COUNT\s*\(/i', trim( $cols_raw ) );
		$proj = array();
		foreach ( $rows as $row ) {
			if ( ! $cols ) {
				$proj[] = (object) $row;
			} else {
				$o = new stdClass();
				foreach ( $cols as $c ) {
					$alias = $c;
					// col AS alias.
					if ( preg_match( '/^(.+?)\s+AS\s+(.+)$/i', $c, $m ) ) {
						$c = trim( trim( $m[1] ), '`' );
						$alias = trim( trim( $m[2] ), '`' );
					}
					if ( false !== strpos( $c, '.' ) ) {
						$c = substr( $c, strrpos( $c, '.' ) + 1 );
					}
					$o->$alias = array_key_exists( $c, $row ) ? $row[ $c ] : null;
				}
				$proj[] = $o;
			}
		}

		// Single-aggregate queries: COUNT(*), COALESCE(SUM(..),0) etc.
		if ( preg_match( '/\(/', $cols_raw ) && ! $group ) {
			$proj = $this->row_simplify( $proj, $cols_raw );
			if ( ! $proj && preg_match( '/^(SUM|COALESCE|MAX|MIN|COUNT)\s*\(/i', trim( $cols_raw ) ) ) {
				if ( $is_count ) {
					$proj[] = (object) array( 'count' => 0 );
				}
			}
		}

		if ( '' !== $limit || $offset ) {
			$lim = '' !== $limit ? (int) $limit : count( $proj );
			$proj = array_slice( $proj, $offset, $by_group ? $by_group : $lim );
		}

		$this->last_result = $proj;
		$this->num_rows = count( $proj );
		return $this->num_rows;
	}

	private function do_insert( $table, $cols_raw, $vals_raw ) {
		$this->ensure_table( $table );
		$cols = array();
		foreach ( explode( ',', $cols_raw ) as $c ) {
			$cols[] = trim( trim( $c ), '`' );
		}
		$vals = $this->split_values( $vals_raw );
		$parsed = array();
		foreach ( $vals as $v ) {
			$parsed[] = $this->parse_value( trim( $v ) );
		}
		if ( count( $cols ) !== count( $parsed ) ) {
			$this->violation( 'INSERT column/value mismatch in: ' . $table );
			return false;
		}
		$row = array();
		foreach ( $cols as $i => $c ) {
			if ( ! isset( $this->tables[ $table ]['cols'][ $c ] ) && ! isset( $this->tables[ $table ]['cols'][ strtolower( $c ) ] ) ) {
				$this->violation( "invalid column '$c' in INSERT $table" );
				return false;
			}
			$row[ $c ] = $parsed[ $i ];
		}
		if ( ! array_key_exists( 'id', $row ) ) {
			$row['id'] = $this->tables[ $table ]['auto']++;
		} else {
			$this->tables[ $table ]['auto'] = max( $this->tables[ $table ]['auto'], (int) $row['id'] + 1 );
		}
		foreach ( $this->tables[ $table ]['cols'] as $c => $type ) {
			if ( ! array_key_exists( $c, $row ) ) {
				$row[ $c ] = ( 'str' === $type ) ? '' : 0;
			}
		}
		$this->tables[ $table ]['rows'][] = (object) $row;
		$this->insert_id = (int) $row['id'];
		return 1;
	}

	private function do_update( $table, $set_raw, $where ) {
		$this->ensure_table( $table );
		$set = array();
		foreach ( $this->split_values( $set_raw ) as $piece ) {
			if ( ! preg_match( '/^([^=]+)=(.+)$/s', trim( $piece ), $m ) ) {
				$GLOBALS['mjc_stub_sql_unknown'][] = 'SET: ' . $piece;
				continue;
			}
			$col = trim( trim( $m[1] ), '`' );
			if ( ! isset( $this->tables[ $table ]['cols'][ $col ] ) ) {
				$this->violation( "invalid column '$col' in UPDATE $table" );
				return false;
			}
			$set[ $col ] = $this->parse_value( trim( $m[2] ) );
		}
		list( $wop, $wsub ) = $this->parse_where( $where );
		$n = 0;
		foreach ( $this->tables[ $table ]['rows'] as $i => $row ) {
			if ( ! $this->eval_where( array( $wop, $wsub ), (array) $row ) ) {
				continue;
			}
			foreach ( $set as $c => $v ) {
				$this->tables[ $table ]['rows'][ $i ]->{ $c } = $v;
			}
			$n++;
		}
		$this->rows_affected = $n;
		return $n;
	}

	private function do_delete( $table, $where ) {
		$this->ensure_table( $table );
		list( $wop, $wsub ) = $this->parse_where( $where );
		$kept = array();
		$n = 0;
		foreach ( $this->tables[ $table ]['rows'] as $row ) {
			if ( $this->eval_where( array( $wop, $wsub ), (array) $row ) ) {
				$n++;
				continue;
			}
			$kept[] = $row;
		}
		$this->tables[ $table ]['rows'] = $kept;
		$this->rows_affected = $n;
		return $n;
	}
}

class MJC_Stub_DBDelta {
	public function __invoke( $sql ) {
		$queries = is_array( $sql ) ? $sql : explode( ';', (string) $sql );
		foreach ( $queries as $q ) {
			$q = trim( $q );
			if ( ! preg_match( '/^CREATE TABLE/i', $q ) ) {
				continue;
			}
			if ( ! preg_match( '/CREATE TABLE (?:IF NOT EXISTS )?(\S+)/i', $q, $m ) ) {
				continue;
			}
			$table = trim( $m[1], '`' );
			if ( ! isset( $GLOBALS['wpdb']->tables[ $table ] ) ) {
				$GLOBALS['wpdb']->tables[ $table ] = array( 'cols' => array( 'id' => 'num' ), 'rows' => array(), 'auto' => 1 );
			}
			$open = strpos( $q, '(' );
			$close = strrpos( $q, ')' );
			if ( false === $open || false === $close ) {
				continue;
			}
			$body = substr( $q, $open + 1, $close - $open - 1 );
			foreach ( explode( "\n", $body ) as $line ) {
				$line = trim( $line );
				if ( '' === $line || 0 === strpos( $line, 'PRIMARY' ) || 0 === strpos( $line, 'KEY' ) || 0 === strpos( $line, 'UNIQUE' ) || 0 === strpos( $line, 'INDEX' ) ) {
					continue;
				}
				$colname = null;
				$coltype = 'str';
				if ( preg_match( '/^`([^`]+)`\s+([A-Za-z]+)/', $line, $cm ) ) {
					$colname = $cm[1];
					$coltype = $cm[2];
				} elseif ( preg_match( '/^([A-Za-z_][A-Za-z0-9_]*)\s+([A-Za-z]+)/', $line, $cm ) ) {
					$colname = $cm[1];
					$coltype = $cm[2];
				}
				if ( null !== $colname ) {
					$is_num = (bool) preg_match( '/^(TINYINT|SMALLINT|MEDIUMINT|INT|BIGINT|DECIMAL|FLOAT|DOUBLE|REAL)/i', $coltype );
					$GLOBALS['wpdb']->tables[ $table ]['cols'][ $colname ] = $is_num ? 'num' : 'str';
				}
			}
		}
	}
}

function dbDelta( $queries = '', $execute = true ) {
	$d = new MJC_Stub_DBDelta();
	$d( $queries );
	return array();
}
