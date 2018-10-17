<?php

namespace JustCoded\WP\Composer\Helpers;


use Composer\IO\IOInterface;

/**
 * Class Scripts_Helper
 *
 * @package JustCoded\WP\Composer\Helpers
 */
class Scripts_Helper {

	/**
	 * Help to parse arguments to key/value
	 *
	 * @param array $arguments Composer arguments.
	 *
	 * @return array
	 */
	public static function parse_arguments( $arguments = array() ) {
		$args_ready = array();
		$prev_key   = null;
		for ( $i = 0; $i < count( $arguments ); $i ++ ) {
			$arg = $arguments[ $i ];
			if ( empty( $arg ) ) {
				continue;
			}

			if ( preg_match( '/^--?([a-z]([a-z0-9\-]*))(\=.*)?$/', $arg, $match ) ) {
				// we have = sign.
				if ( ! empty( $match[3] ) ) {
					$args_ready[ $match[1] ] = substr( $match[3], 1 );
					$prev_key                = null;
				} else {
					// we set found key as "true" and remember key index. Maybe we will have value in next passed argument.
					$args_ready[ $match[1] ] = true;
					$prev_key                = $match[1];
				}
			} else {
				// if prev_key exists this means argument is a value for previous key.
				if ( $prev_key ) {
					$args_ready[ $prev_key ] = $arg;
					$prev_key                = null;
				} else {
					$args_ready[] = $arg;
				}
			}
		}

		return $args_ready;
	}

	/**
	 * Print command info from method comment
	 *
	 * @param IOInterface $io Composer IO object.
	 * @param string      $method Full method name.
	 */
	public static function command_info( IOInterface $io, $method ) {
		list( $class_name, $method_name ) = explode( '::', $method, 2 );

		$reflection = new \ReflectionClass( $class_name );

		$method  = $reflection->getMethod( $method_name );
		$comment = $method->getDocComment();
		$comment = substr( $comment, 3, - 2 );
		$lines   = explode( PHP_EOL, $comment );
		foreach ( $lines as $key => $line ) {
			if ( preg_match( '/[^\*]*\*[\t\s]*\@/', $line ) ) {
				unset( $lines[ $key ] );
				continue;
			}

			$lines[ $key ] = preg_replace( '/[^\*]*\*\s?/', '', $line );
		}

		$comment = implode( PHP_EOL, $lines );
		$io->write( $comment );
	}

	/**
	 * Ask a confirmation to continue
	 *
	 * @param IOInterface $io Composer IO object.
	 * @param string      $question Question to ask.
	 * @param string      $exit_message Exit message.
	 *
	 * @return bool
	 */
	public static function confirm( IOInterface $io, $question = 'Do you want to continue (yes/no)? ', $exit_message = 'Terminating.' ) {
		$answer = $io->ask( $question );
		if ( $answer && false === strpos( strtolower( $answer ), 'y' ) ) {
			$io->write( $exit_message );

			return false;
		}

		return true;
	}

	public static function ask( IOInterface $io, $base_url, $theme_dir, $exit_message = 'No themes found!' ) {

		if ( ! $io instanceof IOInterface ) {
			return false;
		}

		$question = 'Please, choose theme you want to make a child from:' . PHP_EOL;


		$src = $base_url . 'vendor/justcoded/wordpress-child-theme-boilerplate';
		$dst = $base_url . $theme_dir;

		if ( ! $themes = File_System_Helper::get_folders_names( $dst ) ) {
			$io->write( $exit_message );
			return false;
		}

		$last_key = 0;

		foreach ( $themes as $key => $theme ) {
			$question .= "[{$key}] --- {$theme}" . PHP_EOL;

			$last_key = $key;
		}

		$question .= 'Wire a number from 0 to ' . $last_key . ': ';

		return $themes[ (int) $io->ask( $question ) ];
	}
}
