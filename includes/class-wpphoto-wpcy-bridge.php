<?php
/**
 * WPCY 4.0, white-label (`wenpai_plugin_display`), WPSSS synergy, and
 * `options-general.php` site-icon shortcut integration for WPPhoto.
 *
 * @package WPPhoto
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WPPhoto_WPCY_Bridge', false ) ) {

	/**
	 * Ecosystem integration bridge for WPPhoto.
	 */
	class WPPhoto_WPCY_Bridge {

		const MOTUCLOUD_BASE = 'https://motucloud.com';

		/**
		 * Register ecosystem filter and admin hooks.
		 *
		 * @return void
		 */
		public static function register_hooks() {
			// Shared WenPai suite contract (consumed by WP-China-Yes 4.0 Services tab).
			add_filter( 'wpcy_service_status', array( __CLASS__, 'filter_wpcy_service_status' ), 10, 2 );

			// Quiet contextual shortcut under Settings -> General (Site Icon row).
			add_action( 'admin_footer-options-general.php', array( __CLASS__, 'render_general_site_icon_shortcut' ) );
		}

		/**
		 * Resolve white-label-aware plugin display metadata via `wenpai_plugin_display`.
		 *
		 * Never mutates plugin header Plugin Name or Update URI.
		 *
		 * @return array<string, string>
		 */
		public static function display_meta() {
			$defaults = array(
				'brand'        => '墨图图库',
				'plugin_name'  => 'WPPhoto',
				'help_url'     => 'https://wpcy.com/photo',
				'feedback_url' => 'https://wpcy.com/support',
				'docs_url'     => 'https://wpcy.com/document',
			);

			$filtered = function_exists( 'apply_filters' )
				? apply_filters( 'wenpai_plugin_display', $defaults, 'wpphoto' )
				: $defaults;

			if ( ! is_array( $filtered ) ) {
				return $defaults;
			}

			$out = $defaults;
			foreach ( $defaults as $key => $fallback ) {
				if ( isset( $filtered[ $key ] ) && is_string( $filtered[ $key ] ) && '' !== trim( $filtered[ $key ] ) ) {
					$out[ $key ] = trim( $filtered[ $key ] );
				}
			}
			return $out;
		}

		/**
		 * Populate `$status['motucloud']` for WPCY 4.0 Services catalog.
		 *
		 * @param array<string, mixed> $status   Existing service status map.
		 * @param array<string, mixed> $settings WPCY settings snapshot (unused).
		 * @return array<string, mixed>
		 */
		public static function filter_wpcy_service_status( $status, $settings = array() ) {
			unset( $settings );
			if ( ! is_array( $status ) ) {
				$status = array();
			}

			$has_site_icon = (int) get_option( 'site_icon', 0 ) > 0;
			$toolbox_on    = wpphoto_is_toolbox_active();
			$wpsss_on      = wpphoto_is_wpsss_active();

			$parts   = array();
			$parts[] = $has_site_icon ? '站点图标已配置' : '待设站点图标';
			$parts[] = $toolbox_on ? '站内 6 合 1 离线工具箱已激活' : '工具箱外链模式';
			if ( $wpsss_on ) {
				$parts[] = 'WPSSS 已协同';
			}

			$status['motucloud'] = array(
				'summary'      => implode( ' · ', $parts ),
				'pill'         => $has_site_icon ? '已接管' : '待配置',
				'state'        => $has_site_icon ? 'ok' : 'warn',
				'action_label' => '管理',
				'action_url'   => admin_url( 'upload.php?page=wpphoto' ),
			);

			return $status;
		}

		/**
		 * Build clean privacy-preserving MotuCloud free web tool URL (`?from=wpphoto`).
		 * Never appends customer site domain or personal data.
		 *
		 * @param string $tool Tool slug (tuya|webp|mark|resize|svg|favicon).
		 * @return string
		 */
		public static function motucloud_tool_url( $tool ) {
			$allowed = array( 'tuya', 'webp', 'mark', 'resize', 'svg', 'favicon' );
			$slug    = in_array( $tool, $allowed, true ) ? $tool : 'favicon';
			return self::MOTUCLOUD_BASE . '/' . $slug . '/?from=wpphoto';
		}

		/**
		 * Return structured WPSSS (文派智储) synergy status.
		 *
		 * @return array<string, mixed>
		 */
		public static function wpsss_status() {
			$active = wpphoto_is_wpsss_active();
			return array(
				'active'      => $active,
				'label'       => $active ? '已协同（上传前瘦身后自动入桶）' : '未安装（推荐免费插件 WPSSS 文派智储）',
				'install_url' => 'https://wpcy.com/object-storage',
			);
		}

		/**
		 * Inject a quiet contextual shortcut link next to Site Icon on `options-general.php`.
		 *
		 * Follows the restraint contract: zero popups, zero red dots, filterable off,
		 * and white-label aware via `wenpai_plugin_display`.
		 *
		 * @return void
		 */
		public static function render_general_site_icon_shortcut() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$enabled = (bool) apply_filters( 'wpphoto_shortcut_general_site_icon', true );
			if ( ! $enabled ) {
				return;
			}

			$display = self::display_meta();
			$url     = admin_url( 'upload.php?page=wpphoto&tab=favicon' );
			$label   = sprintf( '使用%s设计站点图标 →', $display['brand'] );
			?>
			<script>
			(function(){
				var row = document.querySelector('.site-icon-section, #site_icon_hidden_field');
				if (!row) { return; }
				var container = row.closest('td') || row.parentElement;
				if (!container || container.querySelector('.wpphoto-general-shortcut')) { return; }
				var p = document.createElement('p');
				p.className = 'description wpphoto-general-shortcut';
				var a = document.createElement('a');
				a.href = <?php echo wp_json_encode( esc_url_raw( $url ) ); ?>;
				a.textContent = <?php echo wp_json_encode( $label ); ?>;
				p.appendChild(a);
				container.appendChild(p);
			})();
			</script>
			<?php
		}
	}
}
