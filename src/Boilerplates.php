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
	 * New theme generator based on Justcoded Theme Boilerplate (https://github.com/justcoded/wordpress-theme-boilerplate)
	 *
	 * Usage:
	 *      wp:theme <folder-name> [-t="My Theme"] [-ns="ClientName"] [-dir="wp-content/themes"] [-s, silent install]
	 *
	 * Options:
	 *      -t          Theme name, displayed in WordPress admin panel
	 *      -ns         Namespace to be used for theme classes
	 *      -dir        Themes base directory. Default to 'wp-content/themes'
	 *      -s          Silent install, setup theme without confirmation message
	 *
	 * @param Event $event Composer event.
	 * @return bool
	 */
	public static function theme( Event $event ) {
		$io = $event->getIO();
		// Get arguments from composer command line.
		$args = Scripts_Helper::parse_arguments( $event->getArguments() );
		if ( empty( $args[0] ) ) {
			return Scripts_Helper::command_info( $io, __METHOD__ );
		}

		// Prepare data.
		$theme       = $args[0];
		$theme_title = Array_Helper::get_value( $args, 't', ucfirst( $theme ) );
		$name_space  = Array_Helper::get_value( $args, 'ns', ucfirst( str_replace( '-', '_', $theme ) ) );
		$name_space  = str_replace( ' ', '', $name_space );
		$dir         = Array_Helper::get_value( $args, 'dir', 'wp-content/themes' );
		$dir         = trim( $dir, '/' ) . '/' . $theme;

		// If there are no '-s' silent argument - get a question to user.
		$io->write( 'You are about to create a new theme:' );
		$io->write( "\tPath:        $dir" );
		$io->write( "\tTitle:       $theme_title" );
		$io->write( "\tNamespace:   $name_space\\Theme\\*" );

		if ( empty( $args['s'] ) && ! Scripts_Helper::confirm( $io ) ) {
			return false;
		}

		// Replacement array.
		$textdomain  = str_replace( '-', '_', $theme );
		$prefix      = str_replace( '-', '_', $theme ) . '_';
		$replacement = array(
			'JustCoded Theme Boilerplate' => $theme_title,
			'Boilerplate\\'               => $name_space . '\\',
			'boilerplate_'                => $prefix,
			"'boilerplate'"               => "'{$textdomain}'",
		);

		// Run copy and replace.
		$composer = $event->getComposer();
		$root_dir = dirname( $composer->getConfig()->get( 'vendor-dir' ) );
		$src      = $root_dir . '/vendor/justcoded/wordpress-theme-boilerplate';
		$dst      = $root_dir . '/' . $dir;
		if ( opendir( $src ) ) {
			File_System_Helper::copy_dir( $src, $dst );
			File_System_Helper::search_and_replace( $dst, $replacement );
		} else {
			$io->write( 'There are was an error before start copying theme files' );
		}
		$io->write( 'Theme has been created!' );
	}
}
