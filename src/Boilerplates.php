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
		$io          = $event->getIO();
		// Get arguments from composer command line.
		$args = Scripts_Helper::parse_arguments( $event->getArguments() );
		if (empty($args[0])) {
			return Scripts_Helper::command_info($io, __METHOD__);
		}

		// prepare data
		$theme = $args[0];
		$theme_title = Array_Helper::get_value($args,'t', ucfirst($theme));
		$name_space = Array_Helper::get_value($args,'ns', ucfirst(str_replace($theme,'-', '_')));
		$dir = Array_Helper::get_value($args,'d', 'wp-content/themes');


		$dst = rtrim($dir, '/') . '/' . $theme;

		// If there are no '-s' silent argument - get a question to user.
		if ( empty($args['s']) ) {
			$question = 'You creating project "'
			            . ucfirst( $theme_title )
			            . '" on path "' . $dst
			            . '" with namespace "' . $name_space
			            . '" do you agree ? (yes/no)';
			$answer = $event->getIO()->ask( $question );
			if (false === strpos(strtolower($answer), 'y')) {
				$io->write('Terminating');
				return;
			}
		}
		// Replacement array.
		$prefix = str_replace( '-', '_', $theme ) . '_';
		$replacement = array(
			'JustCoded Theme Boilerplate'  => $theme_title,
			'boilerplate_'       => $prefix,
			'Boilerplate\\' => $name_space . '\\',
		);
		// Depending on user answer continue or stop working.
			$src               = 'vendor/wordpress-theme-boilerplate';
			if ( is_dir( $src ) ) {
				$dir = opendir( $src );
				File_System_Helper::copy_dir( $src, $dst );
				foreach ( $replacement as $str_to_find => $str_to_replace ) {
					File_System_Helper::search_and_replace( $dst, $str_to_find, $str_to_replace );
				}
			} else {
				// TODO: print error!
			}
			$event->getIO()->write( 'The task has been created!' );
		} else {
			// Stop the execution of the script if the user entered something other than 'yes' or 'y'.
			exit();
		}
	}
}
