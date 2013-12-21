<?php
/*
Plugin Name: Noembed - oEmbed everything
Plugin URI:  https://github.com/johnbillion/noembed
Description: Add support for Noembed.com-supported embeds
Author:      John Blackbourn
Version:     1.0
Author URI:  https://johnblackbourn.com/
Text Domain: noembed
Domain Path: /languages/
License:     GPL v2 or later

Copyright © 2013 John Blackbourn

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

defined( 'ABSPATH' ) or exit;

class Noembed {

	protected static $providers_endpoint = 'https://noembed.com/providers';
	protected static $oembed_endpoint    = 'https://noembed.com/embed';

	protected function __construct() {

		add_filter( 'oembed_providers', array( $this, 'filter_oembed_providers' ) );

	}

	public function filter_oembed_providers( array $providers ) {
		$noembed_providers = self::get_providers();

		if ( empty( $noembed_providers ) )
			return $providers;

		foreach ( $noembed_providers as $provider ) {
			$key = '#' . str_replace( '#', '\\#', $provider ) . '#';
			$providers[$key] = array(
				self::$oembed_endpoint,
				true
			);
		}

		return $providers;
	}

	public static function get_providers() {
		$providers = get_site_transient( 'noembed_providers' );

		if ( false === $providers ) {
			if ( $providers = self::fetch_providers() )
				set_site_transient( 'noembed_providers', $providers, DAY_IN_SECONDS );
		}

		return $providers;
	}

	public static function fetch_providers() {
		$result = wp_remote_get( self::$providers_endpoint );

		if ( is_wp_error( $result ) )
			return false;
		if ( 200 != wp_remote_retrieve_response_code( $result ) )
			return false;
		if ( ! $json = json_decode( wp_remote_retrieve_body( $result ) ) )
			return false;

		$providers = array();

		foreach ( $json as $provider )
			$providers = array_merge( $providers, $provider->patterns );

		return $providers;
	}

	public static function init() {
		static $instance = null;

		if ( !$instance )
			$instance = new Noembed;

		return $instance;
	}

}

Noembed::init();
