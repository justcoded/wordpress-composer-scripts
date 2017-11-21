<?php

namespace JustCoded\WP\Composer\Helpers;

/**
 * Class Array_Helper
 *
 * @package JustCoded\WP\Composer\Helpers
 */
class Array_Helper {

	/**
	 * @param      $array
	 * @param      $key
	 * @param null $default
	 *
	 * @return null
	 */
	public static function get_value( $array, $key, $default = null ) {
		if ( isset( $array[ $key ] ) ) {
			return $array[ $key ];
		} else {
			return $default;
		}
	}
}
