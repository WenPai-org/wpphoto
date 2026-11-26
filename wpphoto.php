<?php
/**
 * Plugin Name: WPPhoto
 * Plugin URI: https://wpcy.com/photo
 * Description: 墨图图库（WPPhoto）：浏览器端站点图标工坊、上传前轻量图像预处理、商用矢量图标库与 SVG 双闸安全净化。
 * Version: 0.1.0-alpha
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: 文派
 * Author URI: https://wpcy.com/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wpphoto
 * Update URI: https://updates.wenpai.net
 *
 * @package WPPhoto
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WPPHOTO_VERSION', '0.1.0-alpha' );
define( 'WPPHOTO_FILE', __FILE__ );
define( 'WPPHOTO_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPPHOTO_URL', plugin_dir_url( __FILE__ ) );
define( 'WPPHOTO_BASENAME', plugin_basename( __FILE__ ) );

// Load wenpai-admin-ui kit at plugin load time so its plugins_loaded@1 registry
// picks the newest kit version across all active WenPai plugins.
if ( file_exists( WPPHOTO_DIR . 'lib/wenpai-admin-ui/php/load.php' ) ) {
	require_once WPPHOTO_DIR . 'lib/wenpai-admin-ui/php/load.php';
}

require_once WPPHOTO_DIR . 'includes/api.php';
require_once WPPHOTO_DIR . 'includes/class-wpphoto-svg-sanitizer.php';
require_once WPPHOTO_DIR . 'includes/class-wpphoto-rest-site-icon.php';
require_once WPPHOTO_DIR . 'includes/class-wpphoto-wpcy-bridge.php';
require_once WPPHOTO_DIR . 'includes/class-wpphoto-admin-stub.php';

/**
 * Boot WPPhoto core services.
 *
 * @return void
 */
function wpphoto_boot() {
	WPPhoto_REST_Site_Icon::register_hooks();
	WPPhoto_WPCY_Bridge::register_hooks();
	WPPhoto_Admin_Stub::register_hooks();
}
add_action( 'plugins_loaded', 'wpphoto_boot', 15 );
