<?php

namespace JustCoded\WP\Composer;

use Composer\Script\Event;
use JustCoded\WP\Composer\Helpers\Array_Helper;
use JustCoded\WP\Composer\Helpers\Scripts_Helper;
use JustCoded\WP\Composer\Helpers\Replace_Trait;
use PHP_CodeSniffer\Tokenizers\PHP;

class DB_Replace {

	const PER_PAGE = 5000;
	public static $skipRules = array();

	public function __construct() {
		self::load_wp();

		/* include host-update components */
		include $_SERVER['PWD'] . '/extensions/wp-host-update/app/components/Router.php';
		include $_SERVER['PWD'] . '/extensions/wp-host-update/app/components/Controller.php';
		include $_SERVER['PWD'] . '/extensions/wp-host-update/app/components/ReplaceHelper.php';
		include $_SERVER['PWD'] . '/extensions/wp-host-update/app/controllers/PageController.php';
		include $_SERVER['PWD'] . '/extensions/wp-host-update/app/controllers/ProcessController.php';
		include $_SERVER['PWD'] . '/extensions/wp-host-update/app/inc/functions.php';
	}

	/**
	 * Replace string in DB
	 *
	 * Usage:
	 *      wp:dbUpdate -- --search='old string' --replace='new string' --method --tables --ms_old_url --ms_new_url
	 *
	 * Options:
	 *      --search    Old string
	 *      --replace   New string
	 *      --method    Method replace only content columns/string data columns or replace all data columns full/simple (default simple)
	 *      --tables    List of tables separated by comma (default all)
	 *      --multisite_old_url Multisite Old URL
	 *      --multisite_new_url Multisite New URL
	 *
	 */
	public static function db_replace( Event $event ) {

		$args = Scripts_Helper::parse_arguments( $event->getArguments() );
		$io   = $event->getIO();

		$options = self::check_options( $args, $io );

		new self();
		self::update_tables( $options );
	}

	/**
	 * @param array $replace_param
	 */
	protected static function update_tables( $replace_param = array() ) {
		self::load_wp();
		global $wpdb;
		$process_controller = new \ProcessController();

		if ( 'all' == $replace_param['tables_choice'] ) {
			$tables                         = $wpdb->get_col( "SHOW TABLES LIKE '" . $wpdb->prefix . "%'" );
			$replace_param['tables_custom'] = $tables;
		} else {
			$tables                         = explode( ',', $replace_param['tables_choice'] );
			$replace_param['tables_custom'] = $tables;
		}

		if ( ! empty( $tables ) && is_array( $tables ) ) {
			foreach ( $tables as $table_key => $table ) {
				$process_controller->runSelectQuery( $table, $replace_param );
				self::show_status( $table_key + 1, count( $replace_param['tables_custom'] ), 100 );
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
	 * Check options
	 * @param $args
	 * @param $io
	 *
	 * @return array|void
	 */
	protected static function check_options( $args, $io ) {
		$search = Array_Helper::get_value( $args, 'search', '' );

		if ( empty( $search ) ) {
			return Scripts_Helper::command_info( $io, __METHOD__ );
		}

		$params = array(
			'tables_choice'  => Array_Helper::get_value( $args, 'tables', 'all' ),
			'replace_method' => Array_Helper::get_value( $args, 'method', 'simple' ),
		);

		$params['to_replace'][] = array(
			$search,
			Array_Helper::get_value( $args, 'replace', '' ),
		);

		$params['domain_replace']['old_domain'] = Array_Helper::get_value( $args, 'ms_old_url', '' );
		$params['domain_replace']['new_domain'] = Array_Helper::get_value( $args, 'ms_new_url', '' );

		return $params;
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