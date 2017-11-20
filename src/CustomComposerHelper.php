<?php

namespace JustCoded\WP\Composer;

use Composer\IO\IOInterface;
use Composer\Script\Event;

/**
 * Works with composer data
 *
 * Class CustomComposerHelper
 *
 * @package JustCoded\WP\Composer
 */
class CustomComposerHelper {

	/**
	 * Help to pass arguments to array
	 *
	 * @param array $arguments
	 *
	 * @return array
	 */
	public static function arguments_cleaner( $arguments = array() ) {

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
					$args_ready['silent'] = false;
					$args_ready['theme_slug'] = $one_arg_pair[0];
				}
			}
		}

		return $args_ready;
	}


	/**
	 * @param $dir
	 * @param $stringsearch
	 * @param $stringreplace
	 *
	 * @return array
	 */
	public static function search_and_replace( $dir, $stringsearch, $stringreplace ) {

		$listDir = array();
		if ( $handler = opendir( $dir ) ) {
			while ( ( $sub = readdir( $handler ) ) !== false ) {
				if ( $sub != "." && $sub != ".." && $sub != "Thumb.db" ) {
					if ( is_file( $dir . "/" . $sub ) ) {
						if ( substr_count( $sub, '.php' ) || substr_count( $sub, '.css' ) ) {
							$getfilecontents = file_get_contents( $dir . "/" . $sub );
							if ( substr_count( $getfilecontents, $stringsearch ) > 0 ) {
								$replacer = str_replace( $stringsearch, $stringreplace, $getfilecontents );
								// Let's make sure the file exists and is writable first.
								if ( is_writable( $dir . "/" . $sub ) ) {
									if ( ! $handle = fopen( $dir . "/" . $sub, 'w' ) ) {
										echo "Cannot open file (" . $dir . "/" . $sub . ")";
										exit;
									}
									// Write $somecontent to our opened file.
									if ( fwrite( $handle, $replacer ) === false ) {
										echo "Cannot write to file (" . $dir . "/" . $sub . ")";
										exit;
									}
									fclose( $handle );
								}
							}
						}
						$listDir[] = $sub;
					} elseif ( is_dir( $dir . "/" . $sub ) ) {
						$listDir[ $sub ] = self::search_and_replace( $dir . "/" . $sub, $stringsearch, $stringreplace );
					}
				}
			}
			closedir( $handler );
		}

		return $listDir;
	}


	/**
	 * @param: source folder $src
	 * @param: destination folder $dst
	 */
	public static function recursive_copy( $src, $dst ) {
		$dir = opendir( $src );
		@mkdir( $dst );
		while ( false !== ( $file = readdir( $dir ) ) ) {

			if ( ( '.' !== $file ) && ( '..' !== $file ) ) {
				if ( is_dir( $src . '/' . $file ) ) {
					self::recursive_copy( $src . '/' . $file, $dst . '/' . $file );
				} else {
					copy( $src . '/' . $file, $dst . '/' . $file );
				}
			}
		}

		closedir( $dir );
	}

}
