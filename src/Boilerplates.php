<?php

namespace JustCoded\WP\Composer;

use Composer\IO\IOInterface;
use Composer\Script\Event;
use JustCoded\WP\Composer\Helpers\Array_Helper;
use JustCoded\WP\Composer\Helpers\File_System_Helper;
use JustCoded\WP\Composer\Helpers\Scripts_Helper;


/**
 * Class Boilerplates
 *
 * @package JustCoded\WP\Composer
 */
class Boilerplates {

	/**
	 * Initial function for theme installation
	 *
	 * @param Event $event
	 * @return bool
	 */
	public static function theme( Event $event ) {
		$io = $event->getIO();
		// Get arguments from composer command line.
		$args = Scripts_Helper::parse_arguments( $event->getArguments() );
		if ( empty( $args[0] ) ) {
			return Scripts_Helper::command_info( $io, __METHOD__, __CLASS__ );
		}
		// prepare data.
		$theme       = $args[0];
		$theme_title = Array_Helper::get_value( $args, 't', ucfirst( $theme ) );
		$name_space  = Array_Helper::get_value( $args, 'ns', ucfirst( str_replace( '-', '_', $theme ) ) );
		$name_space  = str_replace( ' ', '', $name_space );
		$dir         = Array_Helper::get_value( $args, 'dir', 'wp-content/themes' );
		$dst = rtrim( $dir, '/' ) . '/' . $theme;
		// If there are no '-s' silent argument - get a question to user.
		if ( empty( $args['s'] ) ) {
			$question = 'You creating project "'
			            . ucfirst( $theme_title )
			            . '" on path "' . $dst
			            . '" with namespace "' . $name_space
			            . '" do you agree ? (yes/no)';
			$answer   = $event->getIO()->ask( $question );
			if ( $answer && false === strpos( strtolower( $answer ), 'y' ) ) {
				$io->write( 'Terminating' );

				return;
			}
		}
		// Replacement array.
		$prefix      = str_replace( '-', '_', $theme ) . '_';
		$replacement = array(
			'JustCoded Theme Boilerplate' => $theme_title,
			'boilerplate_'                => $prefix,
			'Boilerplate\\'               => $name_space . '\\',
			'_jmvt_name'                  => $theme_title,
		);
		// Depending on user answer continue or stop working.
		$composer = $event->getComposer();
		$root_dir = dirname( $composer->getConfig()->get( 'vendor-dir' ) );
		$src = $root_dir . '/vendor/wordpress-theme-boilerplate';
		$dst = $root_dir . '/' . $dst;
		if ( opendir( $src ) ) {
			File_System_Helper::copy_dir( $src, $dst );
			File_System_Helper::search_and_replace( $dst, $replacement );
		} else {
			$event->getIO()->write( 'There are was an error before start copying theme files' );
		}
		$event->getIO()->write( 'The task has been created!' );
	}
}
