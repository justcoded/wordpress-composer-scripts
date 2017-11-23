<?php

namespace JustCoded\WP\Composer;

use Composer\IO\IOInterface;
use Composer\Script\Event;
use JustCoded\WP\Composer\Helpers\Array_Helper;
use JustCoded\WP\Composer\Helpers\File_System_Helper;
use JustCoded\WP\Composer\Helpers\Scripts_Helper;


/**
 * Class Secure
 *
 * @package JustCoded\WP\Composer
 */
class Security {

	/**
	 * make base auth for wp-admin exclude admin-ajax.php
	 *
	 * @param Event $event
	 *
	 */
	public static function wp_admin_base_auth( Event $event ) {

		$composer = $event->getComposer();
		$path     = dirname( $composer->getConfig()->get( 'vendor-dir' ) );
		$args     = Scripts_Helper::parse_arguments( $event->getArguments() );

		$user      = Array_Helper::get_value( $args, 'u', '' );
		$pass      = Array_Helper::get_value( $args, 'p', '' );
		$root_path = Array_Helper::get_value( $args, 'r', '' );

		$event->getIO()->write( $root_path );

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

		$base_htaccess = file_get_contents( $path . '/cms/.htaccess' );
		preg_match( "/RewriteBase\s(.*)/i", $base_htaccess, $m );
		$subdir       = ( $m[1] ) ? $m[1] : '/';
		$htacces_text = strtr( $htacces_text, array(
			'{sub_dir}'   => $subdir,
			'{welcome}'   => $welcome,
			'{htpassdir}' => $path,
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