<?php

namespace JustCoded\WP\Composer\Helpers;


class Array_Helper {

	public static function get_value($array, $key, $default = null) {
		if (isset($array[$key])) {
			return $array[$key];
		} else {
			return $default;
		}
	}
}