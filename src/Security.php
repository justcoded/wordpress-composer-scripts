<?php

namespace JustCoded\WP\Composer;

use Composer\IO\IOInterface;
use Composer\Script\Event;
use JustCoded\WP\Composer\Helpers\Array_Helper;
use JustCoded\WP\Composer\Helpers\File_System_Helper;
use JustCoded\WP\Composer\Helpers\Scripts_Helper;

class Security {

	/**
	 * Add http authentication for the wp-admin folder
	 *
	 * Usage:
	 *      wp:secure -u="username" -p="password" [-r="/real/server/path/to/site"] [-s]
	 *
	 * Options:
	 *      -u          User name (Should be at least 2 characters)
	 *      -p          Password (Should be at least 2 characters)
	 *      -r          Full server path to the site directory. (Useful on some shared hostings)
	 *      -s          Silent execution
	 *
	 * @param Event $event Composer event.
	 * @return bool
	 */
	public static function admin_http_auth( Event $event ) {
		$io       = $event->getIO();
		$composer = $event->getComposer();
		$path     = dirname( $composer->getConfig()->get( 'vendor-dir' ) );

		$args      = Scripts_Helper::parse_arguments( $event->getArguments() );
		$user      = Array_Helper::get_value( $args, 'u', '' );
		$pass      = Array_Helper::get_value( $args, 'p', '' );
		$root_path = Array_Helper::get_value( $args, 'r', $path );

		// If parameters are wrong - show documentation.
		if ( ! $user || ! $pass ) {
			return Scripts_Helper::command_info( $io, __METHOD__ );
		}

		// confirmation.
		$io->write( 'You are about to create an wp-admin .htaccess/.htpassw files:' );
		$io->write( "\tUser name:   $user" );
		$io->write( "\tPassword:    $pass" );
		$io->write( "\t.htaccess path:     $root_path/cms/wp-admin/" );
		$io->write( "\t.htpassw  path:     $root_path/cms/" );

		if ( empty( $args['s'] ) && ! Scripts_Helper::confirm( $io ) ) {
			return false;
		}

		// check folders writable.
		if ( ! is_writable( $path . '/cms/wp-admin' ) || ! is_writable( $path . '/cms' ) ) {
			$io->write( 'Folders "./cms", "./cms/wp-admin" are not exists or is not writable. Terminating.' );

			return false;
		}

		$htacces_content = '
		SetEnvIf Request_URI ^{dir}/cms/wp-admin/admin-ajax.php noauth=1
		Authtype Basic
		AuthName "Restricted Access"
		AuthUserFile {dir}/cms/.htpasswd
		Require valid-user
		Order Deny,Allow
		Deny from all
		Allow from env=noauth
		Satisfy any';

		$htacces_content = strtr( $htacces_content, array(
			'{dir}'   => rtrim( $root_path, '/' ),
		) );
		file_put_contents( $path . '/cms/wp-admin/.htaccess', $htacces_content );
		file_put_contents( $path . '/cms/.htpasswd', $user . ':' . crypt( $pass, base64_encode( $pass ) ) . "\n" );
		$io->write( '.htaccess was successfully generated.' );
		$io->write( '.htpassw was successfully generated.' );
	}
}