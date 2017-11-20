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
	 * Initial function for theme installation
	 *
	 * @param Event $event
	 * @return bool
	 */
	public static function theme( Event $event ) {
		$io          = $event->getIO();
		// Get arguments from composer command line.
		$arguments = $event->getArguments();
		// Parse arguments.
		$args_ready = CustomComposerHelper::arguments_cleaner( $arguments );
		$theme_title = $args_ready['title'];
		$name_space  = $args_ready['namespace'];
		$name_space  = str_replace( ' ', '', ucfirst( $name_space ) );
		$path_to_theme_directory = $args_ready['dir'];
		// Get theme dir path.
		$theme_dir  = $args_ready['theme_slug'];
		if ( '' !== $path_to_theme_directory ) {
			$dst = $path_to_theme_directory . '/' . $theme_dir;
		} else {
			$dst = 'wp-content/themes/' . $theme_dir;
		}
		$answer = '';
		// If there are no '-s' silent argument - get a question to user.
		if ( isset( $args_ready['silent'] ) && false === $args_ready['silent'] ) {
			$question = 'You creating project "'
			            . ucfirst( $theme_title )
			            . '" on path "' . $dst
			            . '" with namespace "' . $name_space
			            . '" do you agree ? (yes/no)';
			$answer = $event->getIO()->ask( $question );
		}
		// Replacement array.
		$prefix = str_replace( '-', '_', $args_ready['theme_slug'] );
		$replacement = array(
			'JustCoded Theme Boilerplate'  => $theme_title,
			'_jmvt'       => $prefix,
			'Boilerplate' => $name_space,
		);
		// Depending on user answer continue or stop working.
		if ( 'yes' === strtolower( $answer ) || 'y' === strtolower( $answer ) || '' === $answer ) {
			$src               = 'vendor/wordpress-theme-boilerplate';
			if ( is_dir( $src ) ) {
				$dir = opendir( $src );
				CustomComposerHelper::recursive_copy( $src, $dst );
				foreach ( $replacement as $str_to_find => $str_to_replace ) {
					CustomComposerHelper::search_and_replace( $dst, $str_to_find, $str_to_replace );
				}
			}
			$event->getIO()->write( 'The task has been created!' );
		} else {
			// Stop the execution of the script if the user entered something other than 'yes' or 'y'.
			exit();
		}
	}
}
