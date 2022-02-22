<?php
/**
 * Version: 1.7.3
 * Author: Alto-Palo
 * Author URI: https://github.com/awvenezia
 * 
 * @package NlsnWPFP
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Nlsn_Custom_Update_Checker' ) ) {
	
	/**
	 * Nlsn_Custom_Update_Checker
	 */
	class Nlsn_Custom_Update_Checker {
		
		/**
		 * Variable plugin_slug
		 *
		 * @var mixed
		 */
		public $plugin_slug;        
		/**
		 * Variable version
		 *
		 * @var mixed
		 */
		public $version;        
		/**
		 * Variable cache_key
		 *
		 * @var mixed
		 */
		public $cache_key;      
		/**
		 * Cache_allowed
		 *
		 * @var mixed
		 */
		public $cache_allowed;
		
		/**
		 * Constructor __construct
		 *
		 * @return void
		 */
		public function __construct() {

			$this->plugin_slug   = NLSN_PLUGIN_SLUG;;
			$this->version       = '1.7.3';
			$this->cache_key     = 'custom_upd';
			$this->cache_allowed = false;

			add_filter( 'plugins_api', array( $this, 'update_info' ), 20, 3 );
			add_filter( 'site_transient_update_plugins', array( $this, 'update' ) );
			add_action( 'upgrader_process_complete', array( $this, 'purge' ), 10, 2 );

		}
		
		/**
		 * Function request
		 *
		 * @return mixed
		 */
		public function request() {

			$remote = get_transient( $this->cache_key );

			if ( false === $remote || ! $this->cache_allowed ) {

				if (function_exists('vip_safe_wp_remote_get')) {
					$remote = vip_safe_wp_remote_get(
						'https://raw.githubusercontent.com/awvenezia/wp-favorite-posts/main/info.json',
						array(
							'timeout' => 3,
							'headers' => array(
								'Accept' => 'application/json',
							),
						)
					);
				} else {
					$remote = wp_safe_remote_get(
						'https://raw.githubusercontent.com/awvenezia/wp-favorite-posts/main/info.json',
						array(
							'timeout' => 3,
							'headers' => array(
								'Accept' => 'application/json',
							),
						)
					);
				}

				if (
					is_wp_error( $remote )
					|| 200 !== wp_remote_retrieve_response_code( $remote )
					|| empty( wp_remote_retrieve_body( $remote ) )
				) {
					return false;
				}

				set_transient( $this->cache_key, $remote, DAY_IN_SECONDS );

			}

			$remote = json_decode( wp_remote_retrieve_body( $remote ) );

			return $remote;

		}

		
		/**
		 * Function update_info
		 *
		 * @param  mixed $res Result.
		 * @param  mixed $action Action.
		 * @param  mixed $args Arguments.
		 * @return mixed
		 */
		public function update_info( $res, $action, $args ) {

			// do nothing if you're not getting plugin information right now.
			if ( 'plugin_information' !== $action ) {
				return false;
			}

			// do nothing if it is not our plugin.
			if ( $this->plugin_slug !== $args->slug ) {
				return false;
			}

			// get updates.
			$remote = $this->request();

			if ( ! $remote ) {
				return false;
			}

			$res = new stdClass();

			$res->name           = $remote->name;
			$res->slug           = $remote->slug;
			$res->version        = $remote->version;
			$res->tested         = $remote->tested;
			$res->requires       = $remote->requires;
			$res->author         = $remote->author;
			$res->author_profile = $remote->author_profile;
			$res->download_link  = $remote->download_url;
			$res->trunk          = $remote->download_url;
			$res->requires_php   = $remote->requires_php;
			$res->last_updated   = $remote->last_updated;

			$res->sections = array(
				'description'  => $remote->sections->description,
				'installation' => $remote->sections->installation,
				'changelog'    => $remote->sections->changelog,
			);

			if ( ! empty( $remote->banners ) ) {
				$res->banners = array(
					'low'  => $remote->banners->low,
					'high' => $remote->banners->high,
				);
			}

			return $res;

		}
		
		/**
		 * Function update
		 *
		 * @param  mixed $transient Transient Object.
		 * @return mixed
		 */
		public function update( $transient ) {

			if ( empty( $transient->checked ) ) {
				return $transient;
			}

			$remote = $this->request();

			if (
				$remote
				&& version_compare( $this->version, $remote->version, '<' )
				&& version_compare( $remote->requires, get_bloginfo( 'version' ), '<' )
				&& version_compare( $remote->requires_php, PHP_VERSION, '<' )
			) {
				$res              = new stdClass();
				$res->slug        = $this->plugin_slug;
				$res->plugin      = NLSN_PLUGIN_PATH;
				$res->new_version = $remote->version;
				$res->tested      = $remote->tested;
				$res->package     = $remote->download_url;

				$transient->response[ $res->plugin ] = $res;

			}

			return $transient;

		}
		
		/**
		 * Function purge
		 *
		 * @return void
		 */
		public function purge() {
			$options = nlsn_get_options();
			if (
				$this->cache_allowed
				&& 'update' === $options['action']
				&& 'plugin' === $options['type']
			) {
				// just clean the cache when new plugin version is installed.
				delete_transient( $this->cache_key );
			}

		}


	}

	new Nlsn_Custom_Update_Checker();

}
