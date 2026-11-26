<?php
/**
 * Site Icon REST API endpoint & wp_head vector icon output for WPPhoto.
 *
 * Endpoint: POST /wp-json/wpphoto/v1/site-icon
 * Requires `manage_options` capability + WP REST nonce.
 *
 * @package WPPhoto
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WPPhoto_REST_Site_Icon', false ) ) {

	/**
	 * Handles site icon uploads, attachment creation, site_icon update, and head tags.
	 */
	class WPPhoto_REST_Site_Icon {

		const REST_NAMESPACE = 'wpphoto/v1';
		const REST_ROUTE     = '/site-icon';
		const META_OPTION    = 'wpphoto_site_icon_meta';
		const MAX_PNG_BYTES  = 2097152; // 2 MB.

		/**
		 * Register WordPress hooks.
		 *
		 * @return void
		 */
		public static function register_hooks() {
			add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
			add_action( 'wp_head', array( __CLASS__, 'render_head_tags' ), 99 );
			add_action( 'admin_head', array( __CLASS__, 'render_head_tags' ), 99 );
		}

		/**
		 * Register REST route POST /wp-json/wpphoto/v1/site-icon.
		 *
		 * @return void
		 */
		public static function register_routes() {
			register_rest_route(
				self::REST_NAMESPACE,
				self::REST_ROUTE,
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( __CLASS__, 'handle_set_site_icon' ),
					'permission_callback' => array( __CLASS__, 'check_permissions' ),
					'args'                => array(
						'png_data_url' => array(
							'type'              => 'string',
							'required'          => false,
							'sanitize_callback' => 'trim',
						),
						'svg_markup'   => array(
							'type'     => 'string',
							'required' => false,
						),
						'theme_color'  => array(
							'type'              => 'string',
							'required'          => false,
							'sanitize_callback' => 'sanitize_hex_color',
						),
						'filename'     => array(
							'type'              => 'string',
							'required'          => false,
							'sanitize_callback' => 'sanitize_file_name',
						),
					),
				)
			);
		}

		/**
		 * Require `manage_options` capability.
		 *
		 * @return bool|WP_Error
		 */
		public static function check_permissions() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return new WP_Error(
					'wpphoto_forbidden',
					__( '需要站点管理员权限（manage_options）才能更新站点图标。', 'wpphoto' ),
					array( 'status' => 403 )
				);
			}
			return true;
		}

		/**
		 * Handle POST /wp-json/wpphoto/v1/site-icon.
		 *
		 * Accepts either multipart PNG file (`png_file` / `file`) or base64
		 * `png_data_url`, optionally accompanied by `svg_markup` and `theme_color`.
		 *
		 * @param WP_REST_Request $request REST request object.
		 * @return WP_REST_Response|WP_Error
		 */
		public static function handle_set_site_icon( WP_REST_Request $request ) {
			$png_bytes = self::extract_png_bytes( $request );
			if ( is_wp_error( $png_bytes ) ) {
				return $png_bytes;
			}

			$svg_raw   = $request->get_param( 'svg_markup' );
			$clean_svg = '';
			if ( is_string( $svg_raw ) && '' !== trim( $svg_raw ) ) {
				$sanitized = WPPhoto_SVG_Sanitizer::sanitize_or_error( $svg_raw );
				if ( is_wp_error( $sanitized ) ) {
					return $sanitized;
				}
				$clean_svg = $sanitized;
			}

			$theme_color = $request->get_param( 'theme_color' );
			$theme_color = is_string( $theme_color ) ? sanitize_hex_color( $theme_color ) : '';
			if ( ! is_string( $theme_color ) ) {
				$theme_color = '';
			}

			$filename = $request->get_param( 'filename' );
			$filename = is_string( $filename ) && '' !== $filename ? sanitize_file_name( $filename ) : 'site-icon-512.png';
			if ( '.png' !== strtolower( substr( $filename, -4 ) ) ) {
				$filename .= '.png';
			}

			$upload = wp_upload_bits( $filename, null, $png_bytes );
			if ( ! empty( $upload['error'] ) || empty( $upload['file'] ) ) {
				return new WP_Error(
					'wpphoto_upload_failed',
					! empty( $upload['error'] ) ? (string) $upload['error'] : __( '站点图标写入媒体库目录失败。', 'wpphoto' ),
					array( 'status' => 500 )
				);
			}

			$attachment = array(
				'post_mime_type' => 'image/png',
				'post_title'     => __( '站点图标（WPPhoto）', 'wpphoto' ),
				'post_content'   => '',
				'post_status'    => 'inherit',
			);

			$attachment_id = wp_insert_attachment( $attachment, $upload['file'] );
			if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
				return new WP_Error(
					'wpphoto_attachment_failed',
					__( '创建站点图标媒体库附件失败。', 'wpphoto' ),
					array( 'status' => 500 )
				);
			}

			if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
				require_once ABSPATH . 'wp-admin/includes/image.php';
			}
			$metadata = wp_generate_attachment_metadata( $attachment_id, $upload['file'] );
			if ( is_array( $metadata ) ) {
				wp_update_attachment_metadata( $attachment_id, $metadata );
			}

			update_option( 'site_icon', (int) $attachment_id );

			$meta = array(
				'attachment_id' => (int) $attachment_id,
				'svg_markup'    => $clean_svg,
				'theme_color'   => $theme_color,
				'updated_at'    => gmdate( 'c' ),
			);
			update_option( self::META_OPTION, $meta, false );

			return rest_ensure_response(
				array(
					'ok'            => true,
					'attachment_id' => (int) $attachment_id,
					'site_icon_url' => esc_url_raw( (string) $upload['url'] ),
					'has_svg'       => '' !== $clean_svg,
					'theme_color'   => $theme_color,
				)
			);
		}

		/**
		 * Output complementary vector favicon.svg and theme-color tags in wp_head / admin_head
		 * without overriding WordPress native `wp_site_icon()`.
		 *
		 * @return void
		 */
		public static function render_head_tags() {
			$meta = get_option( self::META_OPTION, array() );
			if ( ! is_array( $meta ) || empty( $meta['attachment_id'] ) ) {
				return;
			}

			$current_site_icon = (int) get_option( 'site_icon', 0 );
			if ( $current_site_icon <= 0 || (int) $meta['attachment_id'] !== $current_site_icon ) {
				return;
			}

			if ( ! empty( $meta['svg_markup'] ) && is_string( $meta['svg_markup'] ) ) {
				$clean_svg = WPPhoto_SVG_Sanitizer::sanitize( $meta['svg_markup'] );
				if ( false !== $clean_svg ) {
					$data_uri = 'data:image/svg+xml;charset=utf-8,' . rawurlencode( $clean_svg );
					echo '<link rel="icon" type="image/svg+xml" href="' . esc_attr( $data_uri ) . '">' . "\n";
				}
			}

			if ( ! empty( $meta['theme_color'] ) && is_string( $meta['theme_color'] ) ) {
				$color = sanitize_hex_color( $meta['theme_color'] );
				if ( is_string( $color ) && '' !== $color ) {
					echo '<meta name="theme-color" content="' . esc_attr( $color ) . '">' . "\n";
				}
			}
		}

		/**
		 * Extract and validate PNG binary payload from multipart upload or base64 data URL.
		 *
		 * @param WP_REST_Request $request Request object.
		 * @return string|WP_Error
		 */
		private static function extract_png_bytes( WP_REST_Request $request ) {
			$png_bytes = '';
			$files     = $request->get_file_params();

			$file_entry = null;
			if ( isset( $files['png_file'] ) && is_array( $files['png_file'] ) ) {
				$file_entry = $files['png_file'];
			} elseif ( isset( $files['file'] ) && is_array( $files['file'] ) ) {
				$file_entry = $files['file'];
			}

			if ( is_array( $file_entry ) && ! empty( $file_entry['tmp_name'] ) && is_uploaded_file( $file_entry['tmp_name'] ) ) {
				$raw = file_get_contents( $file_entry['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
				if ( is_string( $raw ) ) {
					$png_bytes = $raw;
				}
			} else {
				$data_url = $request->get_param( 'png_data_url' );
				if ( is_string( $data_url ) && 0 === strpos( $data_url, 'data:image/png;base64,' ) ) {
					$b64     = substr( $data_url, strlen( 'data:image/png;base64,' ) );
					$decoded = base64_decode( $b64, true );
					if ( is_string( $decoded ) ) {
						$png_bytes = $decoded;
					}
				}
			}

			if ( '' === $png_bytes ) {
				return new WP_Error(
					'wpphoto_missing_png',
					__( '请提供有效的 PNG 图标文件或 base64 PNG 数据。', 'wpphoto' ),
					array( 'status' => 400 )
				);
			}

			if ( strlen( $png_bytes ) > self::MAX_PNG_BYTES ) {
				return new WP_Error(
					'wpphoto_png_too_large',
					__( '站点图标 PNG 文件不能超过 2 MB。', 'wpphoto' ),
					array( 'status' => 400 )
				);
			}

			// Verify PNG magic signature (8 bytes).
			if ( 0 !== strpos( $png_bytes, "\x89PNG\r\n\x1a\n" ) ) {
				return new WP_Error(
					'wpphoto_invalid_png',
					__( '上传文件不是合法的 PNG 图像格式。', 'wpphoto' ),
					array( 'status' => 400 )
				);
			}

			if ( function_exists( 'getimagesizefromstring' ) ) {
				$info = @getimagesizefromstring( $png_bytes ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				if ( ! is_array( $info ) || empty( $info[0] ) || empty( $info[1] ) || IMAGETYPE_PNG !== $info[2] ) {
					return new WP_Error(
						'wpphoto_invalid_png_dimensions',
						__( '无法解析 PNG 图像尺寸，请重新生成后再试。', 'wpphoto' ),
						array( 'status' => 400 )
					);
				}
			}

			return $png_bytes;
		}
	}
}
