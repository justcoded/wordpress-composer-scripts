<?php

namespace JustCoded\WP\Composer\Helpers;


class File_System_Helper {

	/**
	 * @param: source folder $src
	 * @param: destination folder $dst
	 */
	public static function copy_dir( $src, $dst ) {
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
}