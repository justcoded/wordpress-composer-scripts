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
	 *
	 * @return bool
	 */
	public static function theme( Event $event ) {
		$io = $event->getIO();
		// Get arguments from composer command line.
		$args = Scripts_Helper::parse_arguments( $event->getArguments() );
		if ( empty( $args[0] ) ) {
			$current_method = explode( '::', __METHOD__ );

			return Scripts_Helper::command_info( $io, $current_method[1], __CLASS__ );
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

		if ( empty( $args['s'] ) ) {
			$answer = $io->ask( 'Do you want to continue (yes/no)? ' );
			if ( $answer && false === strpos( strtolower( $answer ), 'y' ) ) {
				$io->write( 'Terminating.' );

				return false;
			}
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
			$event->getIO()->write( 'There are was an error before start copying theme files' );
		}
		$event->getIO()->write( 'Theme has been created!' );
	}


	/**
	 * make base auth for wp-admin exclude admin-ajax.php
	 *
	 * @param Event $event
	 *
	 */
	public static function secure( Event $event ) {

		$composer = $event->getComposer();
		$path = dirname( $composer->getConfig()->get( 'vendor-dir' ) );
		$args = Scripts_Helper::parse_arguments( $event->getArguments() );

		$user      = Array_Helper::get_value( $args, 'u', '' );
		$pass      = Array_Helper::get_value( $args, 'p', '' );
		$root_path  = Array_Helper::get_value( $args, 'r', '' );
		if ( ! $root_path ) {
			$root_path = $path;
		}

		if ( strlen( $user ) < 2 ) {
			$event->getIO()->write(
				'ERROR. Login should be at least 2 english letters or numbers. Quit initialization.'
			);
			exit( 0 );
		}
		if ( strlen( $pass ) < 2 ) {
			$event->getIO()->write(
				'ERROR. Password should be at least 2 english letters or numbers. Quit initialization.'
			);
			exit( 0 );
		}

		$welcome = $event->getIO()->ask(
			'Enter Http Authentification intro message (default: Restricted Area)'
		);
		if ( $welcome ) {
			$welcome = ( trim( $welcome ) ? trim( $welcome ) : 'Restricted Area' );
		} else {
			$welcome = 'Welcome';
		}

		$htacces_text = "
		SetEnvIf Request_URI ^{sub_dir}cms/wp-admin/admin-ajax.php noauth=1
		Authtype Basic
		AuthName \"{welcome}\"
		AuthUserFile {htpassdir}/cms/.htpasswd
		Require valid-user
		Order Deny,Allow
		Deny from all
		Allow from env=noauth
		Satisfy any";

		$base_htaccess = file_get_contents( $path . '/.htaccess' );
		preg_match( "/RewriteBase\s(.*)/i", $base_htaccess, $m );
		$subdir       = ( $m[1] ) ? $m[1] : '/';
		$htacces_text = strtr( $htacces_text, array(
			'{sub_dir}'   => $subdir,
			'{welcome}'   => $welcome,
			'{htpassdir}' => $root_path,
		) );
		if ( ! is_dir( $path . '/cms/wp-admin' ) ) {
			mkdir( $path . '/cms/wp-admin', 0755, true );
		}
		file_put_contents( $path . '/cms/wp-admin/.htaccess', $htacces_text );
		file_put_contents( $path . '/cms/.htpasswd', $user . ':' . crypt( $pass, base64_encode( $pass ) ) . "\n" );
		$event->getIO()->write(
			'htpassw was successfully generated.'
		);
	}
}
