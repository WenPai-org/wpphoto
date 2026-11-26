<?php
/**
 * WPPhoto public PHP interop API (per PLUGIN-INTEROP.md).
 *
 * Stable cross-plugin contract for WPCY 4.0, WPSSS, and ecosystem plugins.
 * Safe to load in standalone CLI smoke tests without WordPress bootstrapped.
 *
 * @package WPPhoto
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'WPPHOTO_TESTING' ) ) {
	exit;
}

if ( ! function_exists( 'wpphoto_is_available' ) ) {
	/**
	 * Report whether WPPhoto core plugin is loaded and available.
	 *
	 * @return bool
	 */
	function wpphoto_is_available() {
		$available = true;
		if ( function_exists( 'apply_filters' ) ) {
			$available = (bool) apply_filters( 'wpphoto_is_available', $available );
		}
		return $available;
	}
}

if ( ! function_exists( 'wpphoto_is_wpcy_active' ) ) {
	/**
	 * Detect whether WP-China-Yes (WPCY 4.0+) is active on this site.
	 *
	 * @return bool
	 */
	function wpphoto_is_wpcy_active() {
		$active = defined( 'CHINA_YES_VERSION' )
			|| class_exists( '\\WenPai\\ChinaYes\\Core\\Plugin', false );

		if ( function_exists( 'apply_filters' ) ) {
			$active = (bool) apply_filters( 'wpphoto_is_wpcy_active', $active );
		}
		return $active;
	}
}

if ( ! function_exists( 'wpphoto_is_toolbox_active' ) ) {
	/**
	 * Detect whether the embedded 6-in-1 offline toolbox is unlocked.
	 *
	 * Dual-activation contract (PLUGIN-INTEROP.md §7):
	 * - Standalone WPPhoto: links cleanly to free motucloud.com web tools (?from=wpphoto).
	 * - WPCY + WPPhoto active together: unlocks the embedded in-admin 6-in-1 offline toolbox.
	 *
	 * @return bool
	 */
	function wpphoto_is_toolbox_active() {
		$active = wpphoto_is_wpcy_active();
		if ( function_exists( 'apply_filters' ) ) {
			$active = (bool) apply_filters( 'wpphoto_is_toolbox_active', $active );
		}
		return $active;
	}
}

if ( ! function_exists( 'wpphoto_host_supports_webp' ) ) {
	/**
	 * Detect whether the current PHP host supports generating WebP thumbnails.
	 *
	 * Uses `wp_image_editor_supports()` in WordPress runtime and falls back to
	 * checking PHP GD / Imagick WebP capabilities in CLI/standalone contexts.
	 *
	 * @return bool
	 */
	function wpphoto_host_supports_webp() {
		$supported = false;

		if ( function_exists( 'wp_image_editor_supports' ) ) {
			$supported = (bool) wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) );
		} else {
			if ( function_exists( 'gd_info' ) ) {
				$info = gd_info();
				if ( is_array( $info ) && ! empty( $info['WebP Support'] ) ) {
					$supported = true;
				}
			}
			if ( ! $supported && class_exists( 'Imagick', false ) && method_exists( 'Imagick', 'queryFormats' ) ) {
				$formats = Imagick::queryFormats( 'WEBP' );
				if ( is_array( $formats ) && ! empty( $formats ) ) {
					$supported = true;
				}
			}
		}

		if ( function_exists( 'apply_filters' ) ) {
			$supported = (bool) apply_filters( 'wpphoto_host_supports_webp', $supported );
		}
		return $supported;
	}
}

if ( ! function_exists( 'wpphoto_is_wpsss_active' ) ) {
	/**
	 * Detect whether WPSSS (文派智储) is active for storage synergy.
	 *
	 * @return bool
	 */
	function wpphoto_is_wpsss_active() {
		$active = defined( 'WPSSS_VERSION' )
			|| function_exists( 'wpsss_is_available' )
			|| class_exists( 'WPSSS_Ops', false );

		if ( function_exists( 'apply_filters' ) ) {
			$active = (bool) apply_filters( 'wpphoto_is_wpsss_active', $active );
		}
		return $active;
	}
}

if ( ! function_exists( 'wpphoto_has_pro' ) ) {
	/**
	 * Reserved Pro entitlement hook (PRD §6; license-server v2 integration slot).
	 *
	 * @return bool
	 */
	function wpphoto_has_pro() {
		$has_pro = false;
		if ( function_exists( 'apply_filters' ) ) {
			$has_pro = (bool) apply_filters( 'wpphoto_has_pro', $has_pro );
		}
		return $has_pro;
	}
}

if ( ! function_exists( 'wpphoto_supports' ) ) {
	/**
	 * Check whether WPPhoto supports a named feature capability.
	 *
	 * @param string $feature Feature identifier.
	 * @return bool
	 */
	function wpphoto_supports( $feature ) {
		$feature = is_string( $feature ) ? strtolower( trim( $feature ) ) : '';
		if ( '' === $feature ) {
			return false;
		}

		switch ( $feature ) {
			case 'site_icon':
			case 'favicon_studio':
			case 'svg_sanitize':
			case 'motuicons':
			case 'wpcy_bridge':
			case 'wpsss_synergy':
				$supported = true;
				break;

			case 'offline_toolbox':
				$supported = wpphoto_is_toolbox_active();
				break;

			case 'webp_host':
				$supported = wpphoto_host_supports_webp();
				break;

			default:
				$supported = false;
				break;
		}

		if ( function_exists( 'apply_filters' ) ) {
			$supported = (bool) apply_filters( 'wpphoto_supports', $supported, $feature );
		}
		return $supported;
	}
}
