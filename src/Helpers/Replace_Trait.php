<?php

namespace JustCoded\WP\Composer\Helpers;

trait Replace_Trait {

	public function find_wp_abspath() {
		if ( ! defined( 'WP_CONFIG_PATH' ) ) {
			throw new Exception( 'find_wp_directory() : WP_CONFIG_PATH is not defined.' );
		}
		$wp_conf_dir = dirname( WP_CONFIG_PATH );
		if ( is_file( "$wp_conf_dir/wp-settings.php" ) ) {
			return "$wp_conf_dir/";
		}
		$entries = scandir( $wp_conf_dir );
		foreach ( $entries as $entry ) {
			if ( $entry == '.' || $entry == '..' || ! is_dir( "$wp_conf_dir/$entry" ) ) {
				continue;
			}
			if ( is_file( "$wp_conf_dir/$entry/wp-settings.php" ) ) {
				return "$wp_conf_dir/$entry/";
			}
		}

		return false;
	}

	public function html_options( $options, $selected = null ) {
		if ( ! is_array( $options ) || empty( $options ) ) {
			return;
		}
		$html = '';
		foreach ( $options as $value => $label ) {
			$selected_attr = '';
			if ( ( ! is_array( $selected ) && strcmp( $selected, $value ) == 0 )
			     || ( is_array( $selected ) && in_array( $value, $selected ) ) ) {
				$selected_attr = ' selected="selected"';
			}
			$html .= '<option value="' . html_encode( $value ) . '">' . html_encode( $label ) . '</option>' . "\n";
		}

		return $html;
	}

	public function html_encode( $value ) {
		return htmlentities( $value, ENT_QUOTES, 'UTF-8' );
	}

	public function sql_add_slashes( $string = '' ) {
		$string = str_replace( '\\', '\\\\', $string );

		return str_replace( '\'', '\\\'', $string );
	}

	public function is_json( $string, $strict = false ) {
		$json = @json_decode( $string, true );
		if ( $strict == true && ! is_array( $json ) ) {
			return false;
		}

		return ! ( $json == null || $json == false );
	}

	public static function recursiveReplace( $data, $to_replace, $serialized = false, $parent_serialized = false ) {
		$is_json = false;
		if ( is_string( $data ) && ( $unserialized = @unserialize( $data ) ) !== false ) {
			// PHP currently has a bug that doesn't allow you to clone the DateInterval / DatePeriod classes.
			// We skip them here as they probably won't need data to be replaced anyway
			if ( is_object( $unserialized ) && ( $unserialized instanceof DateInterval || $unserialized instanceof DatePeriod ) ) {
				return $data;
			}
			$data = self::recursiveReplace( $unserialized, $to_replace, true, true );
		} elseif ( is_array( $data ) ) {
			$_tmp = array();
			foreach ( $data as $key => $value ) {
				$_tmp[ $key ] = self::recursiveReplace( $value, $to_replace, false, $parent_serialized );
			}
			$data = $_tmp;
			unset( $_tmp );
		} elseif ( is_object( $data ) ) {
			$_tmp = clone $data;
			foreach ( $data as $key => $value ) {
				$_tmp->$key = self::recursiveReplace( $value, $to_replace, false, $parent_serialized );
			}
			$data = $_tmp;
			unset( $_tmp );
		} elseif ( self::is_json( $data, true ) ) {
			$_tmp = array();
			$data = json_decode( $data, true );
			foreach ( $data as $key => $value ) {
				$_tmp[ $key ] = self::recursiveReplace( $value, $to_replace, false, $parent_serialized );
			}
			$data = $_tmp;
			unset( $_tmp );
			$is_json = true;
		} elseif ( is_string( $data ) ) {
			$data = self::replace( $data, $to_replace );
		}
		if ( $serialized ) {
			return serialize( $data );
		}
		if ( $is_json ) {
			return json_encode( $data );
		}

		return $data;
	}

	public static function replace( $subject, $to_replace ) {

		if ( empty( $to_replace ) || ! is_array( $to_replace ) ) {
			return $subject;
		}

		foreach ( $to_replace as $params ) {
			$subject = str_ireplace( $params[0], $params[1], $subject );
		}

		return $subject;
	}
}