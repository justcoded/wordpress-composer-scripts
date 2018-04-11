<?php

namespace JustCoded\WP\Composer\Helpers;

abstract class Base_Controller {
	public $wpdb;

	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;
	}

	public function responseStart( $type = 'html' ) {
		if ( headers_sent() ) {
			return;
		}
		switch ( $type ) {
			case 'json':
				header( 'Content-Type: application/json' );
				break;
			default:
				header( 'Content-Type: text/html; charset=utf-8' );
		}
	}

	public function responseJson( $data ) {
		$this->responseStart( 'json' );
		echo json_encode( $data );
	}

	abstract public function actionIndex( $table, $data );
}