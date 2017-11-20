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
		$arguments = $event->getArguments();
		$args_ready = CustomComposerHelper::arguments_cleaner( $arguments );
		$theme_title = $args_ready['title'];
		$name_space  = $args_ready['namespace'];
		$name_space  = str_replace( ' ', '', ucfirst( $name_space ) );
		$path_to_theme_directory = $args_ready['dir'];
		$theme_dir  = $args_ready['theme_slug'];
		if ( empty( $theme_dir ) ) {
			$theme_dir = 'default';
		}
		if ( '' !== $path_to_theme_directory ) {
			$dst = $path_to_theme_directory . '/' . $theme_dir;
		} else {
			$dst = 'wp-content/themes/' . $theme_dir;
		}
		$answer = '';
		if ( isset( $args_ready['silent'] ) && false === $args_ready['silent'] ) {
			$question = 'You creating project "'
			            . ucfirst( $theme_title )
			            . '" on path "' . $dst
			            . '" with namespace "' . $name_space
			            . '" do you agree ? (yes/no)';
			$answer = $event->getIO()->ask( $question );
		}
		$replacement = array(
			'_jmvt_name'  => $theme_title,
			'_jmvt'       => $args_ready['theme_slug'],
			'Boilerplate' => $name_space,
		);
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
			exit();
		}
	}
}
