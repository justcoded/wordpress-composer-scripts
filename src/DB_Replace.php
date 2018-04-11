<?php

namespace JustCoded\WP\Composer;

use Composer\Script\Event;
use JustCoded\WP\Composer\Helpers\Array_Helper;
use JustCoded\WP\Composer\Helpers\Process_Controller;
use JustCoded\WP\Composer\Helpers\Scripts_Helper;
use PHP_CodeSniffer\Tokenizers\PHP;

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
class DB_Replace {

	public static function db_replace( Event $event ) {
		$args    = Scripts_Helper::parse_arguments( $event->getArguments() );
		$options = self::checkOptions( $args );

		if ( ! $options || empty( $options['search'] ) ) {
			self::printError();
		}

		$replace_param = self::prepareOptions( $options );
		self::initReplace( $replace_param );
	}

	/**
	 * Print error MSG
	 *
	 * @throws \ReflectionException
	 */
	protected static function printError() {
		echo PHP_EOL;
		$rc = new \ReflectionClass( get_class() );
		print_r( '========================================================' );
		echo PHP_EOL;
		print_r( '|| Something wrong. Read documentation and try again. ||' );
		echo PHP_EOL;
		print_r( '========================================================' );
		echo PHP_EOL;
		print_r( $rc->getDocComment() );
		echo PHP_EOL;
		die;
	}

	/**
	 * @param array $replace_param
	 */
	protected static function initReplace( $replace_param = array() ) {
		self::loadWp();
		global $wpdb;

		$controller = new Process_Controller();

		if ( 'all' == $replace_param['tables_choice'] ) {
			$tables                         = $wpdb->get_col( "SHOW TABLES LIKE '" . $wpdb->prefix . "%'" );
			$replace_param['tables_custom'] = $tables;
		} else {
			$tables                         = explode( ',', $replace_param['tables_choice'] );
			$replace_param['tables_custom'] = $tables;
		}

		if ( ! empty( $tables ) && is_array( $tables ) ) {
			foreach ( $tables as $table_key => $table ) {
				$controller->actionIndex( $table_key, $replace_param );
			}
		}
	}

	/**
	 * Load wordpress
	 */
	protected static function loadWp() {

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
	protected static function prepareOptions( $options ) {

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
	protected static function checkOptions( $args ) {
		$params       = array();
		$list_options = array( 'search', 'replace', 'tables', 'method' );

		foreach ( $args as $key => $arg ) {
			if ( ! in_array( $key, $list_options ) || empty( $key ) ) {
				return false;
			}
		}

		$params['search']        = Array_Helper::get_value( $args, 'search', '' );
		$params['replace']       = Array_Helper::get_value( $args, 'replace', '' );
		$params['method']        = Array_Helper::get_value( $args, 'method', 'simple' );
		$params['tables_choice'] = Array_Helper::get_value( $args, 'tables', 'all' );

		return $params;
	}
}