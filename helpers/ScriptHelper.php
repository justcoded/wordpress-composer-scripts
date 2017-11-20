<?php

namespace JustCoded\WP\Composer;

use Composer\IO\IOInterface;
use Composer\Script\Event;

/**
 * Works with composer data
 *
 * Class ScriptHelper
 *
 * @package JustCoded\WP\Composer
 */
class ScriptHelper {

	/**
	 * Help to pass arguments to array
	 *
	 * @param array $arguments
	 *
	 * @return array
	 */
	public static function parse_arguments( $arguments = array() ) {
		$args_ready = array();
		foreach ( $arguments as $key => $arg_str ) {
			$one_arg_pair = explode( '=', $arg_str );
			if ( count( $one_arg_pair ) > 1 ) {
				$key_of_param = 'default';
				switch ( $one_arg_pair[0] ) {
					case '-t' :
						$key_of_param = 'title';
						break;
					case '-ns' :
						$key_of_param = 'namespace';
						break;
					case '-dir' :
						$key_of_param = 'dir';
						break;
				}
				$args_ready[ $key_of_param ] = $one_arg_pair[1];
			} else {
				if ( '-s' === $one_arg_pair[0] ) {
					$args_ready['silent'] = true;
				} else {
					$args_ready['silent']     = false;
					$args_ready['theme_slug'] = $one_arg_pair[0];
				}
			}
		}

		return $args_ready;
	}
}
