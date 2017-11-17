<?php

namespace JustCoded\WP\Composer;

use Composer\IO\IOInterface;
use Composer\Script\Event;

/**
 * Class Boilerplates
 *
 * @package JustCoded\WP\Composer
 */
class Boilerplates {

	/**
	 * Replacement in theme variables
	 *
	 * @var array
	 */
	public static $replacement = array();


	/**
	 * Initial function for theme installation
	 *
	 * @param Event $event
	 * @return bool
	 */
	public static function theme( Event $event ) {
		$io          = $event->getIO();
		$theme_title = str_replace( 't=', '', $event->getArguments()[1] );
		$name_space  = str_replace( 'ns=', '', $event->getArguments()[2] );
		$name_space  = str_replace( ' ', '', ucfirst( $name_space ) );

		if ( isset( $event->getArguments()[3] ) ) {
			$path_to_theme_directory = str_replace( 'd=', '', $event->getArguments()[3] );
		} else {
			$path_to_theme_directory = '';
		}
		$silent_installation = false;
		if ( isset( $event->getArguments()[4] ) ) {
			$silent_installation = true;
		}

		$theme_dir  = strtolower( str_replace( ' ', '', $event->getArguments()[0] ) );
		$theme_slug = str_replace( '-', '', $theme_dir );
		if ( empty( $theme_dir ) ) {
			$theme_dir = 'default';
		}
		if ( '' !== $path_to_theme_directory ) {
			$dst = $path_to_theme_directory . '/' . $theme_dir;
		} else {
			$dst = 'wp-content/themes/' . $theme_dir;
		}

		$answer = '';
		if ( false === $silent_installation ) {
			$question = 'You creating project "'
			            . ucfirst( $theme_title )
			            . '" on path "' . $dst
			            . '" with namespace "' . $name_space
			            . '" do you agree ? (yes/no)';
			$answer = $event->getIO()->ask( $question );
		}

		if ( 'yes' === strtolower( $answer ) || 'y' === strtolower( $answer ) || '' === $answer ) {
			$src               = 'vendor/wordpress-theme-boilerplate';
			self::$replacement = array(
				'_jmvt_name'  => $theme_title,
				'_jmvt'       => $theme_slug,
				'Boilerplate' => $name_space,
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

				foreach ( self::$replacement as $str_to_find => $str_to_replace ) {
					self::search_and_replace( $dst, $str_to_find, $str_to_replace );
				}
			}
		} else {
			exit();
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