<?php
/**
 * Minimal admin menu registration stub for WPPhoto v0.1.0-alpha.
 *
 * Per wenpai-admin-ui Tier A governance gate, full admin PHP page templates
 * MUST NOT be written before `prototypes/wpphoto/APPROVAL.md` is signed off.
 * This stub registers the Media submenu entry (`upload.php?page=wpphoto`)
 * and renders only a thin kit-shell gate notice until APPROVAL.md is recorded.
 *
 * @package WPPhoto
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WPPhoto_Admin_Stub', false ) ) {

	/**
	 * Thin admin menu registration stub gated behind APPROVAL.md.
	 */
	class WPPhoto_Admin_Stub {

		const MENU_SLUG = 'wpphoto';

		/**
		 * Hook suffix returned by add_media_page().
		 *
		 * @var string
		 */
		private static $hook_suffix = '';

		/**
		 * Register admin_menu and enqueue hooks.
		 *
		 * @return void
		 */
		public static function register_hooks() {
			add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		}

		/**
		 * Register submenu under Media (`upload.php?page=wpphoto`).
		 *
		 * @return void
		 */
		public static function register_menu() {
			$display           = WPPhoto_WPCY_Bridge::display_meta();
			self::$hook_suffix = (string) add_media_page(
				$display['brand'],
				$display['brand'],
				'manage_options',
				self::MENU_SLUG,
				array( __CLASS__, 'render_stub_page' )
			);
		}

		/**
		 * Enqueue vendored wenpai-admin-ui kit assets only on the WPPhoto screen.
		 *
		 * @param string $hook Current admin screen hook suffix.
		 * @return void
		 */
		public static function enqueue_assets( $hook ) {
			if ( '' === self::$hook_suffix || $hook !== self::$hook_suffix ) {
				return;
			}
			if ( class_exists( 'Wenpai_Admin_Loader', false ) ) {
				Wenpai_Admin_Loader::enqueue( array( 'page' => self::MENU_SLUG ) );
			}
		}

		/**
		 * Render minimal shell stub awaiting Tier A prototype APPROVAL.md.
		 *
		 * @return void
		 */
		public static function render_stub_page() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}
			$display = WPPhoto_WPCY_Bridge::display_meta();
			if ( ! class_exists( 'Wenpai_Admin_Shell', false ) || ! class_exists( 'Wenpai_Admin_UI', false ) ) {
				echo '<div class="wrap"><h1>' . esc_html( $display['brand'] ) . '</h1></div>';
				return;
			}

			$shell = array(
				'slug'         => self::MENU_SLUG,
				'name'         => $display['brand'],
				'home_url'     => admin_url( 'upload.php?page=' . self::MENU_SLUG ),
				'current'      => 'overview',
				'version'      => $display['plugin_name'] . ' ' . WPPHOTO_VERSION,
				'feedback_url' => $display['feedback_url'],
				'links'        => array(
					array( 'label' => '使用文档', 'url' => $display['docs_url'] ),
					array( 'label' => '产品主页', 'url' => $display['help_url'] ),
				),
			);

			Wenpai_Admin_Shell::open( $shell );
			Wenpai_Admin_Shell::page_head(
				$display['brand'],
				'核心非 UI 架构已就绪，完整 Tier A 后台界面待原型 APPROVAL.md 签批后落地。'
			);
			Wenpai_Admin_UI::notice(
				array(
					'tone' => 'info',
					'text' => 'WPPhoto v0.1.0-alpha 仅包含公共互操作 API、SVG 双闸净化器、站点图标 REST 接口与 WPCY 桥接；完整 4 Tab 后台模板遵循 wenpai-admin-ui 门禁，将在 prototypes/wpphoto/APPROVAL.md 签批后交付。',
				)
			);
			Wenpai_Admin_Shell::close( $shell );
		}
	}
}
