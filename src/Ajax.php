<?php
/**
 * WP Ajax Abstract Class.
 *
 * The core base class.
 *
 * You may copy, distribute and modify the software as long as you track
 * changes/dates in source files. Any modifications to or software including
 * (via compiler) GPL-licensed code must also be made available under the GPL
 * along with build & install instructions.
 *
 * PHP Version 7.2
 *
 * @package   WPS\WP\Ajax
 * @author    Travis Smith <t@wpsmith.net>
 * @copyright 2018-2019 Travis Smith
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License v2
 * @link      https://github.com/akamai/wp-akamai
 * @since     0.2.0
 */

namespace WPS\WP\Ajax;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( __NAMESPACE__ . '\Ajax' ) ) {
	/**
	 * Ajax class.
	 *
	 * @package WPS\WP\Ajax
	 */
	class Ajax {

		/**
		 * WP Nounce
		 *
		 * @var string
		 */
		protected $nonce = '';

		/**
		 * WP AJAX Name
		 *
		 * @var string
		 */
		protected $name = '';

		/**
		 * Whether to hook AJAX callback into front-end.
		 *
		 * @var string
		 */
		protected $nopriv = false;

		/**
		 * Hook for scripts.
		 * Could be: wp_enqueue_scripts or login_enqueue_scripts or admin_enqueue_scripts
		 *
		 * @var string
		 */
		protected $script_hook = 'admin_enqueue_scripts';

		/**
		 * Whether to keep the WP Heartbeat script.
		 *
		 * @var string
		 */
		protected $heartbeat = true;

		/**
		 * AJAX callback function name.
		 *
		 * @var string
		 */
		protected $callback;

		/**
		 * Array of args for registering a script.
		 *
		 * @var array
		 */
		protected $script = array();

		/**
		 * Constructor
		 *
		 * @param string $name Name (lower-case, without spaces, use underscore) of the WP Action
		 * @param callable $callback Callback to be called for Ajax processing.
		 * @param array $script Array of script information: url, src (path), data (localized info).
		 *
		 * @return void.
		 */
		public function __construct( $name, $callback, $script = array() ) {
			$this->name     = str_replace( ' ', '_', strtolower( $name ) );
			$this->script   = $script;
			$this->callback = $callback;

			// if not doing ajax, load script
			if ( ! self::doing_ajax() ) {
				$this->maybe_do_action( 'plugins_loaded', array( $this, 'script' ) );
			}

			// Hook up AJAX Action
			$this->maybe_do_action( 'plugins_loaded', array( $this, 'init' ) );

			// Hook into secured callback
			add_action( "{$this->name}_wp_ajax_action", $this->callback );
		}

		/**
		 * Sets object parameters.
		 *
		 * Available parameters: nopriv, script_hook, callback
		 *
		 * @param string $param Parameter name.
		 * @param mixed $value Value of parameter.
		 *
		 * @return bool.
		 */
		public function set( $param, $value ) {
			switch ( $param ) {
				case 'nopriv':
					$this->nopriv = (bool) $value;

					return true;
					break;
				case 'heartbeat':
					$this->heartbeat = (bool) $value;

					return true;
					break;
				case 'script_hook':
					$hooks = array(
						'wp_enqueue_scripts',
						'login_enqueue_scripts',
						'admin_enqueue_scripts',
					);
					if ( in_array( $value, $hooks ) ) {
						$this->script_hook = strtolower( $value );

						return true;
					}

					return false;
					break;
				case 'callback':
					if ( is_callable( $value ) ) {
						if ( ! did_action( "{$this->name}_wp_ajax_action" ) ) {
							remove_action( "{$this->name}_wp_ajax_action", $this->callback );
							$this->callback = $value;
							add_action( "{$this->name}_wp_ajax_action", $this->callback );

							return true;
						}
					}
					break;
			}

			return false;

		}

		/**
		 * Gets object parameter.
		 *
		 * Available parameters: nopriv, script_hook, callback
		 *
		 * @param string $param Parameter name.
		 *
		 * @return mixed        Value of parameter.
		 */
		public function get( $param ) {
			switch ( $param ) {
				case 'nopriv':
					return (bool) $this->nopriv;
				case 'heartbeat':
					return (bool) $this->heartbeat;
				case 'script_hook':
					return (string) $this->script_hook;
				case 'callback':
					return (string) $this->callback;
				default:
					return null;
			}
		}

		/**
		 * Hooks up AJAX Action
		 *
		 * @return void.
		 */
		public function init() {
			add_action( "wp_ajax_{$this->name}_action", array( $this, 'callback' ) );
			if ( $this->nopriv ) {
				add_action( "wp_ajax_nopriv_{$this->name}_action", array( $this, 'callback' ) );
			}
		}

		public static function doing_ajax() {
			return ( defined( 'DOING_AJAX' ) && DOING_AJAX );
		}

		/**
		 * Hooks action or executes action.
		 *
		 * @param string       WordPress action to be checked with did_action().
		 * @param string|array Function name/array to be called.
		 *
		 * @return void.
		 * @author Travis Smith <t@wpsmith.net>
		 *
		 * @since  1.0.0
		 */
		private function maybe_do_action( $hook, $action ) {
			if ( ! is_callable( $action ) ) {
				return;
			}

			if ( ! did_action( $hook ) ) {
				add_action( $hook, $action );
			} else {
				call_user_func( $action );
			}
		}

		/**
		 * Performs script operations: register, localize, and enqueue.
		 *
		 * @return void.
		 */
		public function script() {
			// Register Script
			$this->maybe_do_action( 'wp_loaded', array( $this, 'register' ) );

			// Make sure we hook scripts in proper place & prevent user error.
			if ( $this->nopriv && 'admin_enqueue_scripts' === $this->script_hook ) {
				$this->script_hook = 'wp_enqueue_scripts';
			}

			// Go SCRIPT!
			$this->maybe_do_action( $this->script_hook, array( $this, 'localize' ), 9 );
			$this->maybe_do_action( $this->script_hook, array( $this, 'enqueue' ) );

			if ( ! $this->heartbeat ) {
				$this->maybe_do_action( 'admin_enqueue_scripts', array( $this, 'no_heartbeat' ) );
			}
		}

		/**
		 * Properly Registers AJAX script.
		 *
		 * @return void.
		 */
		public function register() {
			wp_register_script(
				$this->name,
				$this->script['url'],
				$this->script['deps'],
				filemtime( $this->script['src'] ),
				true
			);
		}

		/**
		 * Properly Enqueues AJAX script.
		 *
		 * @return void.
		 */
		public function enqueue() {
			wp_enqueue_script( $this->name );
		}

		protected function get_action() {
			return "{$this->name}_action";
		}

		protected function get_defaults() {
			$defaults              = array(
				'ajaxurl'     => admin_url( 'admin-ajax.php' ),
				'_ajax_nonce' => $this->nonce,
				'action'      => $this->get_action(),
			);
			$defaults['screen_id'] = is_admin() ? get_current_screen()->id : '';

			return $defaults;
		}

		/**
		 * Properly provides localized data for the action.
		 *
		 * @return void.
		 */
		public function localize() {
			// Get JS object name
			$object      = isset( $this->script['data_object'] ) ? $this->script['data_object'] : $this->name;
			$this->nonce = wp_create_nonce( $this->get_action() );

			$defaults = $this->get_defaults();
			$data     = isset( $this->script['data'] ) ? wp_parse_args( $this->script['data'], $defaults ) : $defaults;
			wp_localize_script( $this->name, "$object", $data );
		}

		public function no_heartbeat() {
			wp_deregister_script( 'heartbeat' );
			wp_register_script( 'heartbeat', false );
		}

		/**
		 * Does proper AJAX security check & then calls "{$this->name}_wp_ajax_action" action.
		 *
		 * @return void.
		 */
		public function callback() {
			$data = array_merge( array_map( 'esc_attr', $_GET ), $_POST );

			if ( ! check_ajax_referer( $data['action'], "_ajax_nonce", false ) ) {
				wp_send_json_error();
			}

			do_action( "{$this->name}_wp_ajax_action", $data );
		}

	}
}