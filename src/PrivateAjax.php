<?php
/**
 * WP Ajax Private Class.
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

if ( ! class_exists( __NAMESPACE__ . '\PrivateAjax' ) ) {
	/**
	 * Class PrivateAjax.
	 *
	 * @package WPS\WP\Ajax
	 */
	class PrivateAjax extends Ajax {

		/**
		 * Hook for scripts.
		 * Could be: wp_enqueue_scripts or login_enqueue_scripts or admin_enqueue_scripts
		 *
		 * @var string
		 */
		protected $script_hook = 'wp_enqueue_scripts';

		/**
		 * Whether to keep the WP Heartbeat script.
		 *
		 * @var string
		 */
		protected $heartbeat = false;

	}
}