<?php

namespace JustCoded\WP\Composer\Helpers;


use Composer\IO\IOInterface;

/**
 * Class Scripts_Helper
 *
 * @package JustCoded\WP\Composer\Helpers
 */
class Scripts_Helper {

	/**
	 * Help to parse arguments to key/value
	 *
	 * @param array $arguments
	 *
	 * @return array
	 */
	public static function parse_arguments( $arguments = array() ) {
		$args_ready = array();
		foreach ( $arguments as $key => $arg_str ) {
			preg_match( '/(^-[^=? ?]*)(.)/', $arg_str, $matches );
			if ( empty( $matches ) ) {
				$args_ready[] = $arg_str;
			} else {
				if ( '-' !== $matches[1] ) {
					$matches[1] = str_replace( '-', '', $matches[1] );
					$args_ready[$matches[1]] = explode( $matches[2], $arg_str )[1];
				}
			}
		}

		return $args_ready;
	}

	public static function command_info( IOInterface $io, $method ) {
		// TODO: get doc comment from $method/class and print as command info
		$io->write( 'There are was an error on: ' );
	}

}