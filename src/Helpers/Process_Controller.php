<?php

namespace JustCoded\WP\Composer\Helpers;

class Process_Controller extends Base_Controller {

	use Replace_Trait;

	const PER_PAGE = 5000;
	protected $skipRules;

	public function actionIndex( $table_key, $replace_param ) {
		set_time_limit( 0 );
		$metric_start = microtime( true );
		global $wpdb;

		$tables           = $replace_param['tables_custom'];
		$step             = $table_key;
		$to_replace       = $replace_param['search_replace'];
		$current_table    = $tables[ $step ];
		$clean_table_name = strtolower( preg_replace( "/^$wpdb->prefix(\d+\_)?/", '', $current_table ) );

		$replace_method = ( $replace_param['replace_method'] == 'full' ) ? 'full' : 'simple';
		if ( $replace_method == 'simple' ) {
			$this->prepareSkipRules();
		}
		$updated_rows = 0;
		$primary_keys = $wpdb->get_results( "SHOW KEYS FROM `$current_table` WHERE Key_name = 'PRIMARY'" );

		$select_count_query = "SELECT COUNT(*) as rowscnt FROM $current_table";
		$rows_cnt           = $wpdb->get_var( $select_count_query );
		$select_query       = "SELECT * FROM $current_table";
		$select_limit       = self::PER_PAGE;
		$select_offset      = 0;

		while ( $select_offset < $rows_cnt ) {
			$db_rows = $wpdb->get_results( "$select_query LIMIT $select_limit OFFSET $select_offset" );

			foreach ( $db_rows as $row ) {
				$update_query  = "UPDATE $current_table SET ";
				$update_values = array();
				$i             = 1;
				foreach ( $row as $key => $value ) {

					if ( $primary_keys[0]->Column_name == $key ) {
						$where = " WHERE $key=$value";
						$i ++;
						continue;
					}

					if ( $replace_method == 'simple' && $this->canSkipColumn( $clean_table_name, $key, $value ) ) {
						continue;
					}

					$new_value = self::recursiveReplace( $value, $to_replace );

					if ( strcmp( $new_value, $value ) == 0 ) {
						continue;
					}
					$update_values[] = $key . "='" . self::sql_add_slashes( $new_value ) . "'";
					$i ++;
				}

				if ( empty( $update_values ) ) {
					continue;
				}

				$update_query .= implode( ',', $update_values );
				$wpdb->query( $update_query . $where );
				$updated_rows ++;
			}
			$select_offset += $select_limit;
		}
		$metric_end = microtime( true );
		$this->show_status( $table_key + 1, count( $tables ), 100 );
	}

	/**
	 * Show a status bar in the console
	 *
	 * @param   int $done how many items are completed
	 * @param   int $total how many items are to be done total
	 * @param   int $size optional size of the status bar
	 */
	protected function show_status( $done, $total, $size = 30 ) {
		static $start_time;

		// if we go over our bound, just ignore it
		if ( $done > $total ) {
			return;
		}

		if ( empty( $start_time ) ) {
			$start_time = time();
		}
		$now = time();

		$perc = (double) ( $done / $total );

		$bar = floor( $perc * $size );

		$status_bar = "\r[";
		$status_bar .= str_repeat( "=", $bar );
		if ( $bar < $size ) {
			$status_bar .= ">";
			$status_bar .= str_repeat( " ", $size - $bar );
		} else {
			$status_bar .= "=";
		}

		$disp = number_format( $perc * 100, 0 );

		$status_bar .= "] $disp%  $done/$total";

		$rate = ( $now - $start_time ) / $done;
		$left = $total - $done;
		$eta  = round( $rate * $left, 2 );

		$elapsed = $now - $start_time;

		$status_bar .= " remaining: " . number_format( $eta ) . " sec.  elapsed: " . number_format( $elapsed ) . " sec.";

		echo "$status_bar  ";

		flush();

		// when done, send a newline
		if ( $done == $total ) {
			echo "\n";
		}
	}


	protected function prepareBlogReplace( $input ) {
		if ( empty( $input ) || ! is_array( $input ) ) {
			return array();
		}
		foreach ( $input as $key => $replace ) {
			$replace[0]    = str_replace( '*.', '', $replace[0] );
			$replace[1]    = str_replace( '*.', '', $replace[1] );
			$input[ $key ] = $replace;
		}

		return $input;
	}

	protected function canSkipColumn( $table, $column, $value ) {
		if ( is_numeric( $value ) || '' === $value || is_null( $value ) ) {
			return true;
		}
		if ( isset( $this->skipRules['tables_columns'][ $table ][ $column ] ) ) {
			return true;
		}
		if ( preg_match( $this->skipRules['table_name']['^'], $table ) ||
		     preg_match( $this->skipRules['table_name']['$'], $table )
		) {
			return true;
		}
		if ( preg_match( $this->skipRules['column_name']['^'], $column ) ||
		     preg_match( $this->skipRules['column_name']['$'], $column )
		) {
			return true;
		}

		return false;
	}

	protected function prepareSkipRules() {
		$table_names                         = array(
			'^' => array(
				'term_relationships',
			),
			'$' => array(
				'_log',
				'_logs',
			),
		);
		$this->skipRules['tables_columns']   = array(
			'posts'         => array(
				'post_password'  => 1,
				'to_ping'        => 1,
				'pinged'         => 1,
				'post_type'      => 1,
				'post_mime_type' => 1,
			),
			'options'       => array(
				'option_name' => 1,
				'autoload'    => 1,
			),
			'comments'      => array(
				'comment_type' => 1,
			),
			'term_taxonomy' => array(
				'taxonomy' => 1,
			),
		);
		$column_names                        = array(
			'^' => array(
				'id',
				'meta_key',
				'status',
				'date_',
				'created',
				'hash',
				'md5',
			),
			'$' => array(
				'_id',
				'_status',
				'_date',
				'_date_gmt',
				'_modified',
				'_modified_gmt',
				'_md5',
				'_hash',
			),
		);
		$this->skipRules['table_name']['^']  = '/^(' . implode( '|', $table_names['^'] ) . ')/i';
		$this->skipRules['table_name']['$']  = '/(' . implode( '|', $table_names['$'] ) . ')$/i';
		$this->skipRules['column_name']['^'] = '/^(' . implode( '|', $column_names['^'] ) . ')/i';
		$this->skipRules['column_name']['$'] = '/(' . implode( '|', $column_names['$'] ) . ')$/i';
	}
}