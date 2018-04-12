<?php

namespace JustCoded\WP\Composer;

use Composer\Script\Event;
use JustCoded\WP\Composer\Helpers\Array_Helper;
use JustCoded\WP\Composer\Helpers\Process_Controller;
use JustCoded\WP\Composer\Helpers\Scripts_Helper;
use JustCoded\WP\Composer\Helpers\Replace_Trait;
use PHP_CodeSniffer\Tokenizers\PHP;

class DB_Replace {

	use Replace_Trait;

	const PER_PAGE = 5000;
	public static $skipRules = array();

	/**
	 * Replace srting in DB
	 *
	 * Usage:
	 *      wp:dbUpdate -- --search='old string' --replace='new string' --method --tables
	 *
	 * Options:
	 *      --search    Old string
	 *      --replace   New string
	 *      --method    Method replace only content columns/string data columns or replace all data columns full/simple (default simple)
	 *      --tables    List of tables separated by comma (default all)
	 *
	 */
	public static function db_replace( Event $event ) {
		$args = Scripts_Helper::parse_arguments( $event->getArguments() );
		$io   = $event->getIO();
		$options = self::check_options( $args, $io );
		$replace_param = self::prepare_options( $options );
		self::update_tables( $replace_param );
	}

	/**
	 * @param array $replace_param
	 */
	protected static function update_tables( $replace_param = array() ) {
		self::load_wp();
		global $wpdb;

		if ( 'all' == $replace_param['tables_choice'] ) {
			$tables                         = $wpdb->get_col( "SHOW TABLES LIKE '" . $wpdb->prefix . "%'" );
			$replace_param['tables_custom'] = $tables;
		} else {
			$tables                         = explode( ',', $replace_param['tables_choice'] );
			$replace_param['tables_custom'] = $tables;
		}

		if ( ! empty( $tables ) && is_array( $tables ) ) {
			foreach ( $tables as $table_key => $table ) {
				self::replace_strings( $table_key, $replace_param );
			}
		}
	}

	/**
	 * Load wordpress
	 */
	protected static function load_wp() {

		if ( ! defined( 'ABSPATH' ) ) {
			define( 'ABSPATH', $_SERVER['PWD'] . '/cms/' );
		}

		if ( file_exists( ABSPATH . '/wp-load.php' ) ) {
			require_once( ABSPATH . '/wp-load.php' );
		}
	}

	/**
	 * Update options array for use in script
	 *
	 * @param $options
	 *
	 * @return array
	 */
	protected static function prepare_options( $options ) {

		$replace_param = array(
			'tables_choice'  => $options['tables_choice'],
			'replace_method' => $options['method'],
		);

		$replace_param['search_replace'][] = array(
			$options['search'],
			$options['replace'],
		);

		return $replace_param;
	}

	/**
	 * Check options
	 *
	 * @param $args
	 *
	 * @return array|bool
	 */
	protected static function check_options( $args, $io ) {
		$params = array();
		/* list of keys */
		$list_options = array( 'search', 'replace', 'tables', 'method' );

		/* check if in the args isset not use param */
		foreach ( $args as $key => $arg ) {
			if ( ! in_array( $key, $list_options ) || empty( $key ) ) {
				$io->writeError( 'Undefined parameter.' );
				die;
			}
		}

		$params['search']        = Array_Helper::get_value( $args, 'search', '' );
		$params['replace']       = Array_Helper::get_value( $args, 'replace', '' );
		$params['method']        = Array_Helper::get_value( $args, 'method', 'simple' );
		$params['tables_choice'] = Array_Helper::get_value( $args, 'tables', 'all' );

		if ( empty( $params['search'] ) ) {
			$io->writeError( 'Please specify string for replace' );
			die;
		}

		return $params;
	}

	/**
	 * Replace one string to another
	 *
	 * @param $table_key
	 * @param $replace_param
	 */
	protected static function replace_strings( $table_key, $replace_param ) {
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
			self::prepare_skip_rules();
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

					if ( $replace_method == 'simple' && self::can_skip_column( $clean_table_name, $key, $value ) ) {
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
		self::show_status( $table_key + 1, count( $tables ), 100 );
	}

	protected static function can_skip_column( $table, $column, $value ) {
		if ( is_numeric( $value ) || '' === $value || is_null( $value ) ) {
			return true;
		}
		if ( isset( self::$skipRules['tables_columns'][ $table ][ $column ] ) ) {
			return true;
		}
		if ( preg_match( self::$skipRules['table_name']['^'], $table ) ||
		     preg_match( self::$skipRules['table_name']['$'], $table )
		) {
			return true;
		}
		if ( preg_match( self::$skipRules['column_name']['^'], $column ) ||
		     preg_match( self::$skipRules['column_name']['$'], $column )
		) {
			return true;
		}

		return false;
	}

	protected static function prepare_skip_rules() {
		$table_names                         = array(
			'^' => array(
				'term_relationships',
			),
			'$' => array(
				'_log',
				'_logs',
			),
		);
		self::$skipRules['tables_columns']   = array(
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
		self::$skipRules['table_name']['^']  = '/^(' . implode( '|', $table_names['^'] ) . ')/i';
		self::$skipRules['table_name']['$']  = '/(' . implode( '|', $table_names['$'] ) . ')$/i';
		self::$skipRules['column_name']['^'] = '/^(' . implode( '|', $column_names['^'] ) . ')/i';
		self::$skipRules['column_name']['$'] = '/(' . implode( '|', $column_names['$'] ) . ')$/i';
	}

	/**
	 * Show a status bar in the console
	 *
	 * @param   int $done how many items are completed
	 * @param   int $total how many items are to be done total
	 * @param   int $size optional size of the status bar
	 */
	protected static function show_status( $done, $total, $size = 30 ) {
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

}