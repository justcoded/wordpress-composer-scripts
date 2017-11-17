<?php

namespace JustCoded\WP\Composer;

use Composer\IO\IOInterface;
use Composer\Script\Event;

class Boilerplates {

	/**
	 *  Arguments array
	 * @var $args
	 */
	public static $args = array();


	/**
	 * @param Event $event
	 *
	 * @return bool
	 */
	public static function theme( Event $event ) {
		$io = $event->getIO();

		$theme_name = str_replace( 't=', '', $event->getArguments()[1] );
		$theme_slug = str_replace( ' ', '', $theme_name );
		$theme_dir  = str_replace( ' ', '', $event->getArguments()[0] );
		if ( empty( $theme_dir ) ) {
			$theme_dir = 'wp-content/themes';
		}

		$src  = 'vendor/justcoded/wordpress-theme-boilerplate';
		$dst  = $theme_dir . '/' . $theme_slug;
		self::$args = array(
			'full_path_to_new_theme' => $dst,
			'theme_slug'             => $theme_slug,
			'theme_name'             => $theme_name,
		);

		if ( is_dir( $src ) ) {
			$dir = opendir( $src );
			if ( ! is_dir( $dst ) ) {
				if ( ! mkdir( $dst, 0777, true ) ) {
					$io->write( "\n ERROR. Unable to create directory $theme_dir" );

					return false;
				}
			}

			self::recurse_copy( $src, $dst );
		}

	}


	/**
	 * @param: source folder $src
	 * @param: destination folder $dst
	 */
	public static function recurse_copy( $src, $dst ) {
		$dir = opendir( $src );
		@mkdir( $dst );
		while ( false !== ( $file = readdir( $dir ) ) ) {
//			if ( false == ( $file = readdir( $dir ) ) ) {
//				self::renaming_files_scripts();
//			}
			if ( ( '.' !== $file ) && ( '..' !== $file ) ) {
				if ( is_dir( $src . '/' . $file ) ) {
					self::recurse_copy( $src . '/' . $file, $dst . '/' . $file );
				} else {
					copy( $src . '/' . $file, $dst . '/' . $file );
				}
			}
		}
		closedir( $dir );
	}


//	/**
//	 * @param: root folder to renaming files $dest
//	 */
//	public static function renaming_files_scripts() {
//
//		$args = self::$args;
//		$dest       = $args['full_path_to_new_theme'];
//		$theme_slug = $args['theme_slug'];
//		foreach ( glob( $dest . '/*.php' ) as $filename ) {
//			$file = file_get_contents( $filename );
//			file_put_contents(
//				$filename,
//				preg_replace(
//					"/(Boilerplate)/",
//					ucfirst( $theme_slug ), $file
//				)
//			);
//		}
//	}

}