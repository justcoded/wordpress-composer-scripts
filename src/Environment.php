<?php

namespace JustCoded\WP\Composer;

use Composer\IO\IOInterface;
use Composer\Script\Event;

/**
 * Class Environment
 *
 * Contains hooks which help user to setup environemnt:
 * - create .env, .htaccess files
 * - configure .env file
 *
 * @package JustCoded\WordPress\Composer
 */
class Environment {

	/**
	 * Copy .env and .htaccess from example versions
	 *
	 * @param Event $event Composer script event object.
	 */
	public static function post_install( Event $event ) {
		$composer = $event->getComposer();
		$root_dir = dirname( $composer->getConfig()->get( 'vendor-dir' ) );
		$io       = $event->getIO();

		if ( ! is_file( "$root_dir/.env" ) && is_file( "$root_dir/.env.example" ) ) {
			copy( "$root_dir/.env.example", "$root_dir/.env" );
			$io->write( "\t.env file created." );
		}
		if ( ! is_file( "$root_dir/.htaccess" ) && is_file( "$root_dir/.htaccess.example" ) ) {
			copy( "$root_dir/.htaccess.example", "$root_dir/.htaccess" );
			$io->write( "\t.htaccess file created." );
		}
		// special delay to be sure all info has been printed to bash.
		sleep( 1 );
	}

	/**
	 * Copy deployment instructions as main readme file
	 * (on after create project)
	 *
	 * @param Event $event Composer script event object.
	 */
	public static function deployment_readme( Event $event ) {
		$composer = $event->getComposer();
		$root_dir = dirname( $composer->getConfig()->get( 'vendor-dir' ) );

		if ( is_file( "$root_dir/DEPLOYMENT.md" ) && is_file( "$root_dir/README.md" ) ) {
			$readme = file_get_contents( "$root_dir/README.md" );
			if ( false !== strpos( $readme, 'Project Template by JustCoded' ) ) {
				unlink( "$root_dir/README.md" );
				rename( "$root_dir/DEPLOYMENT.md", "$root_dir/README.md" );
				$event->getIO()->write( "\tREADME.md file created with deployment instructions." );
			}
		}
		// special delay to be sure all info has been printed to bash.
		sleep( 1 );
	}

	/**
	 * Generate new secure DB prefix environment variable
	 *
	 * @param Event $event Composer script event object.
	 */
	public static function wpdb_prefix( Event $event ) {
		$composer = $event->getComposer();
		$root_dir = dirname( $composer->getConfig()->get( 'vendor-dir' ) );
		$io       = $event->getIO();

		$io->write( 'Generating new WPDB prefix environment variable...' );

		do {
			$hash = md5( microtime() );
		} while ( ! preg_match( '/^[a-z]/', $hash ) );
		$db_prefix = substr( $hash, 0, 5 ) . '_';
		$replace   = array(
			'/^[\s\t]*DB_PREFIX\s*=.*$/m' => 'DB_PREFIX=' . $db_prefix,
		);

		static::update_file( $io, "$root_dir/.env.example", $replace )
				&& $io->write( "\t.env.example has been updated." );
		static::update_file( $io, "$root_dir/.env", $replace, false )
				&& $io->write( "\t.env has been updated." );
		// special delay to be sure all info has been printed to bash.
		sleep( 1 );
	}

	/**
	 * Generate new WP salts constants (environment variables)
	 *
	 * @param Event $event Composer script event object.
	 */
	public static function salts( Event $event ) {
		$composer = $event->getComposer();
		$root_dir = dirname( $composer->getConfig()->get( 'vendor-dir' ) );
		$io       = $event->getIO();

		$io->write( 'Generating new auth sault environment variables...' );

		$salt_names = [
			'AUTH_KEY',
			'SECURE_AUTH_KEY',
			'LOGGED_IN_KEY',
			'NONCE_KEY',
			'AUTH_SALT',
			'SECURE_AUTH_SALT',
			'LOGGED_IN_SALT',
			'NONCE_SALT',
		];

		$replace = [];
		foreach ( $salt_names as $salt_name ) {
			$salt = static::wp_generate_password( 64, true, true );
			$replace[ '/^[\s\t]*' . $salt_name . 's*=.*$/m' ] = "$salt_name='$salt'";
		}

		static::update_file( $io, "$root_dir/.env.example", $replace )
				&& $io->write( "\t.env.example has been updated." );
		static::update_file( $io, "$root_dir/.env", $replace, false )
				&& $io->write( "\t.env has been updated." );
		// special delay to be sure all info has been printed to bash.
		sleep( 1 );
	}

	/**
	 * Replace some lines based on patterns inside some file and write new content ro disk
	 *
	 * @param IOInterface $io Composer IO object.
	 * @param string      $file Filename to update.
	 * @param array       $patterns Search/replace patterns.
	 * @param bool        $warning Show warning if file not found.
	 *
	 * @return bool|int
	 */
	protected static function update_file( IOInterface $io, $file, $patterns, $warning = true ) {
		if ( ! is_writable( $file ) ) {
			$warning && $io->writeError( 'Unable to locate or write ' . basename( $file ) . ' file: ' . $file );

			return false;
		}

		$content = file_get_contents( $file );
		foreach ( $patterns as $search => $replace ) {
			if ( preg_match( $search, $content ) ) {
				$content = preg_replace( $search, $replace, $content );
			} else {
				$content .= "\n" . $replace;
			}
		}

		return file_put_contents( $file, $content );
	}

	/**
	 * Generates a random password drawn from the defined set of characters.
	 * (copy of WordPress function, wp_rand() replaced with random_int())
	 *
	 * @param int  $length              Optional. The length of password to generate. Default 12.
	 * @param bool $special_chars       Optional. Whether to include standard special characters.
	 *                                  Default true.
	 * @param bool $extra_special_chars Optional. Whether to include other special characters.
	 *                                  Used when generating secret keys and salts. Default false.
	 * @return string The random password.
	 */
	protected static function wp_generate_password(
		$length = 12,
		$special_chars = true,
		$extra_special_chars = false
	) {
		$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		if ( $special_chars ) {
			$chars .= '!@#$%^&*()';
		}
		if ( $extra_special_chars ) {
			$chars .= '-_ []{}<>~`+=,.;:/?|';
		}

		srand();
		$password = '';
		for ( $i = 0; $i < $length; $i ++ ) {
			$password .= substr( $chars, random_int( 0, strlen( $chars ) - 1 ), 1 );
		}

		return $password;
	}

}
