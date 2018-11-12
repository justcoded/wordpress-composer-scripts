<?php

namespace JustCoded\WP\Composer\Helpers;

use Symfony\Component\Config\Definition\Exception\Exception;

/**
 * Class File_System_Helper
 *
 * @package JustCoded\WP\Composer\Helpers
 */
class File_System_Helper {

	/**
	 * @param: source folder $src
	 * @param: destination folder $dst
	 */
	public static function copy_dir( $src, $dst ) {
		$dir = opendir( $src );
		mkdir( $dst, 0777, true );
		while ( false !== ( $file = readdir( $dir ) ) ) {
			if ( ( '.' === $file ) || ( '..' === $file ) || ( '.git' === $file ) ) {
				continue;
			}
			if ( is_dir( $src . '/' . $file ) ) {
				self::copy_dir( $src . '/' . $file, $dst . '/' . $file );
			} else {
				copy( $src . '/' . $file, $dst . '/' . $file );
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
	public static function search_and_replace( $dir, $replacement ) {
		if ( ! $handler = opendir( $dir ) ) {
			return [];
		}
		$list_dir = array();
		while ( ( $sub = readdir( $handler ) ) !== false ) {
			if ( '.' === $sub || '..' === $sub || 'Thumb.db' === $sub ) {
				continue;
			}
			if ( is_file( $dir . '/' . $sub ) && ( substr_count( $sub, '.php' ) || substr_count( $sub, '.css' ) ) ) {
				$contents = file_get_contents( $dir . '/' . $sub );
				foreach ( $replacement as $string_search => $string_replace ) {
					if ( substr_count( $contents, $string_search ) > 0 ) {
						$contents = str_replace( $string_search, $string_replace, $contents );
					}
				}
				// Let's make sure the file exists and is writable first.
				if ( is_writable( $dir . '/' . $sub ) ) {
					$handle = fopen( $dir . '/' . $sub, 'w' );
					fwrite( $handle, $contents );
					fclose( $handle );
				}
				$list_dir[] = $sub;
			} elseif ( is_dir( $dir . '/' . $sub ) ) {
				$list_dir[ $sub ] = self::search_and_replace( $dir . '/' . $sub, $replacement );
			}
		}

		return $list_dir;
	}

	public static function get_folders_names( $dir ) {
		if ( ! $handler = opendir( $dir ) ) {
			return [];
		}

		$list_dir = [];

		while ( ( $sub = readdir( $handler ) ) !== false ) {

			$full_path = $dir . '/' . $sub;

			if ( '.' === $sub || '..' === $sub || 'Thumb.db' === $sub || ! is_dir( $full_path ) ) {
				continue;
			}

			$list_dir[] = $sub;
		}

		return $list_dir;
	}

	/**
	 * Method finds parent theme namespace.
	 *
	 * @param $dir - Directory to search
	 *
	 * @throws \Exception - Exception with error message.
	 *
	 * @return array|bool - false on fail and theme namespace on success.
	 */
	public static function find_theme_namespace( $dir ) {
		if ( ! $handler = opendir( $dir ) ) {
			throw new \Exception( 'Can not open ' . $dir );
		}

		$theme_file = $dir . '/app/Theme.php';

		if ( file_exists( $theme_file ) && is_readable( $theme_file ) ) {
			foreach ( file( $theme_file ) as $line ) {
				if ( false !== strpos( $line, 'namespace' ) ) {
					preg_match( '/^namespace\s([a-zA-Z]+)*/ms', $line, $matches );
					return $matches[1];
				}

			}
			throw new \Exception( 'Parent theme namespace couldn\'t be found' );
		}
		throw new \Exception( 'File Theme.php either can\'t be found or is not readable' );
	}
}
