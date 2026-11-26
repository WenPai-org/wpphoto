<?php
/**
 * Strict PHP DOMDocument SVG whitelist sanitizer for WPPhoto.
 *
 * Blocks XSS vectors including <script>, <iframe>, <object>, <embed>,
 * <foreignObject>, on* event handlers, javascript:/vbscript:/data:text/html
 * URIs, unsafe CSS expressions, and XML entity / DOCTYPE attacks.
 *
 * Safe to run standalone in CLI tests without WordPress loaded.
 *
 * @package WPPhoto
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'WPPHOTO_TESTING' ) ) {
	exit;
}

if ( ! class_exists( 'WPPhoto_SVG_Sanitizer', false ) ) {

	/**
	 * Whitelist-based SVG sanitizer backed by PHP DOMDocument.
	 */
	class WPPhoto_SVG_Sanitizer {

		/**
		 * Strict whitelist of allowed SVG element tag names (lowercase).
		 *
		 * Explicitly excludes: script, iframe, object, embed, foreignObject,
		 * style, animate, set, animateMotion, animateTransform, handler,
		 * listener, link, meta, base, form, input, image, audio, video, canvas.
		 *
		 * @var array<string, true>
		 */
		private static $allowed_tags = array(
			'svg'            => true,
			'g'              => true,
			'defs'           => true,
			'symbol'         => true,
			'use'            => true,
			'title'          => true,
			'desc'           => true,
			'path'           => true,
			'circle'         => true,
			'ellipse'        => true,
			'rect'           => true,
			'line'           => true,
			'polyline'       => true,
			'polygon'        => true,
			'lineargradient' => true,
			'radialgradient' => true,
			'stop'           => true,
			'clippath'       => true,
			'mask'           => true,
			'pattern'        => true,
			'text'           => true,
			'tspan'          => true,
		);

		/**
		 * Strict whitelist of allowed SVG attribute names (lowercase).
		 *
		 * @var array<string, true>
		 */
		private static $allowed_attrs = array(
			'id'                  => true,
			'class'               => true,
			'viewbox'             => true,
			'xmlns'               => true,
			'xmlns:xlink'         => true,
			'width'               => true,
			'height'              => true,
			'x'                   => true,
			'y'                   => true,
			'x1'                  => true,
			'y1'                  => true,
			'x2'                  => true,
			'y2'                  => true,
			'cx'                  => true,
			'cy'                  => true,
			'r'                   => true,
			'rx'                  => true,
			'ry'                  => true,
			'd'                   => true,
			'points'              => true,
			'fill'                => true,
			'fill-opacity'        => true,
			'fill-rule'           => true,
			'stroke'              => true,
			'stroke-width'        => true,
			'stroke-linecap'      => true,
			'stroke-linejoin'     => true,
			'stroke-miterlimit'   => true,
			'stroke-dasharray'    => true,
			'stroke-dashoffset'   => true,
			'stroke-opacity'      => true,
			'opacity'             => true,
			'transform'           => true,
			'offset'              => true,
			'stop-color'          => true,
			'stop-opacity'        => true,
			'gradientunits'       => true,
			'gradienttransform'   => true,
			'spreadmethod'        => true,
			'clip-path'           => true,
			'clip-rule'           => true,
			'mask'                => true,
			'preserveaspectratio' => true,
			'role'                => true,
			'aria-label'          => true,
			'aria-hidden'         => true,
			'focusable'           => true,
			'font-family'         => true,
			'font-size'           => true,
			'font-weight'         => true,
			'text-anchor'         => true,
			'dominant-baseline'   => true,
			'dx'                  => true,
			'dy'                  => true,
			'href'                => true,
			'xlink:href'          => true,
			'style'               => true,
		);

		/**
		 * Sanitize raw SVG markup using a strict DOMDocument allowlist.
		 *
		 * @param string $svg Raw SVG string.
		 * @return string|false Clean SVG markup, or false on invalid input.
		 */
		public static function sanitize( $svg ) {
			if ( ! is_string( $svg ) ) {
				return false;
			}
			$svg = trim( $svg );
			if ( '' === $svg || strlen( $svg ) > 524288 ) {
				return false;
			}
			if ( ! class_exists( 'DOMDocument', false ) ) {
				return false;
			}

			// Strip BOM, XML processing instructions, DOCTYPE (including internal subsets),
			// and ENTITY declarations before parsing to eliminate XXE and stylesheet injection vectors.
			$svg = preg_replace( '/^\xEF\xBB\xBF/', '', $svg );
			$svg = preg_replace( '/<\?.*?\?>/s', '', (string) $svg );
			$svg = preg_replace( '/<!DOCTYPE\s+[^>\[]+(?:\[[^\]]*\])?\s*>/is', '', (string) $svg );
			$svg = preg_replace( '/<!ENTITY[^>]*>/is', '', (string) $svg );
			$svg = trim( (string) $svg );
			if ( '' === $svg ) {
				return false;
			}

			$prev_errors = libxml_use_internal_errors( true );
			$dom         = new DOMDocument( '1.0', 'UTF-8' );
			$dom->formatOutput = false;

			$flags  = LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING;
			$loaded = $dom->loadXML( $svg, $flags );
			libxml_clear_errors();
			libxml_use_internal_errors( $prev_errors );

			if ( ! $loaded || ! $dom->documentElement ) {
				return false;
			}

			$root = $dom->documentElement;
			if ( 'svg' !== strtolower( $root->localName ) ) {
				return false;
			}

			self::clean_node( $root );

			if ( ! $root->hasAttribute( 'xmlns' ) ) {
				$root->setAttribute( 'xmlns', 'http://www.w3.org/2000/svg' );
			}

			$clean = $dom->saveXML( $root );
			if ( ! is_string( $clean ) || '' === trim( $clean ) ) {
				return false;
			}

			return trim( $clean );
		}

		/**
		 * WordPress-friendly wrapper returning WP_Error on failure.
		 *
		 * @param string $svg Raw SVG string.
		 * @return string|WP_Error
		 */
		public static function sanitize_or_error( $svg ) {
			$clean = self::sanitize( $svg );
			if ( false === $clean ) {
				if ( class_exists( 'WP_Error', false ) ) {
					return new WP_Error(
						'wpphoto_invalid_svg',
						'无效的 SVG 内容或包含不允许的结构。',
						array( 'status' => 400 )
					);
				}
				return '';
			}
			return $clean;
		}

		/**
		 * Recursively clean an element and its children against the allowlist.
		 *
		 * @param DOMElement $element Current DOM element.
		 * @return void
		 */
		private static function clean_node( DOMElement $element ) {
			self::clean_attributes( $element );

			// Iterate backwards because removing child nodes mutates childNodes live.
			for ( $i = $element->childNodes->length - 1; $i >= 0; $i-- ) {
				$child = $element->childNodes->item( $i );
				if ( null === $child ) {
					continue;
				}

				if ( XML_COMMENT_NODE === $child->nodeType || XML_PI_NODE === $child->nodeType ) {
					$element->removeChild( $child );
					continue;
				}

				if ( XML_CDATA_SECTION_NODE === $child->nodeType ) {
					$element->removeChild( $child );
					continue;
				}

				if ( XML_ELEMENT_NODE === $child->nodeType && $child instanceof DOMElement ) {
					$tag = strtolower( $child->localName );
					if ( ! isset( self::$allowed_tags[ $tag ] ) ) {
						$element->removeChild( $child );
						continue;
					}
					self::clean_node( $child );
				}
			}
		}

		/**
		 * Clean attributes on a single allowed DOMElement.
		 *
		 * @param DOMElement $element Current DOM element.
		 * @return void
		 */
		private static function clean_attributes( DOMElement $element ) {
			if ( ! $element->hasAttributes() ) {
				return;
			}

			$to_remove = array();
			foreach ( $element->attributes as $attr ) {
				$name  = strtolower( $attr->nodeName );
				$local = strtolower( $attr->localName );
				$value = (string) $attr->nodeValue;

				// Always block event handler attributes (on*).
				if ( 0 === strpos( $local, 'on' ) || 0 === strpos( $name, 'on' ) ) {
					$to_remove[] = $attr->nodeName;
					continue;
				}

				// Enforce strict attribute allowlist.
				if ( ! isset( self::$allowed_attrs[ $name ] ) && ! isset( self::$allowed_attrs[ $local ] ) ) {
					$to_remove[] = $attr->nodeName;
					continue;
				}

				// Restrict href / xlink:href strictly to internal fragment references (#id).
				if ( 'href' === $local || 'xlink:href' === $name ) {
					if ( ! self::is_safe_fragment_href( $value ) ) {
						$to_remove[] = $attr->nodeName;
					}
					continue;
				}

				// Restrict style attribute against CSS script/data/external URL vectors.
				if ( 'style' === $local ) {
					if ( ! self::is_safe_inline_style( $value ) ) {
						$to_remove[] = $attr->nodeName;
					}
					continue;
				}

				// Reject dangerous URI schemes anywhere in attribute values.
				if ( self::has_dangerous_uri( $value ) ) {
					$to_remove[] = $attr->nodeName;
				}
			}

			foreach ( $to_remove as $attr_name ) {
				$element->removeAttribute( $attr_name );
			}
		}

		/**
		 * Validate href / xlink:href value (internal fragment #id only).
		 *
		 * @param string $value Attribute value.
		 * @return bool
		 */
		private static function is_safe_fragment_href( $value ) {
			$trimmed = trim( $value );
			return (bool) preg_match( '/^#[A-Za-z_][A-Za-z0-9_.:-]*$/', $trimmed );
		}

		/**
		 * Validate an inline style value inside SVG attributes.
		 *
		 * @param string $value Style value.
		 * @return bool
		 */
		private static function is_safe_inline_style( $value ) {
			if ( self::has_dangerous_uri( $value ) ) {
				return false;
			}
			$normalized = strtolower( preg_replace( '/[\x00-\x20]+/', '', $value ) );
			if ( false !== strpos( $normalized, 'expression(' )
				|| false !== strpos( $normalized, '@import' )
				|| false !== strpos( $normalized, 'behavior:' )
				|| false !== strpos( $normalized, '-moz-binding' )
			) {
				return false;
			}
			// Allow url(#localId) only; block all external or data url(...) references.
			if ( preg_match_all( '/url\(\s*([^)]+)\)/i', $value, $matches ) ) {
				foreach ( $matches[1] as $raw_target ) {
					$target = trim( $raw_target, " \t\n\r\0\x0B\"'" );
					if ( ! self::is_safe_fragment_href( $target ) ) {
						return false;
					}
				}
			}
			return true;
		}

		/**
		 * Check whether a string contains javascript:, vbscript:, or data:text/html schemes.
		 *
		 * @param string $value Raw attribute value.
		 * @return bool
		 */
		private static function has_dangerous_uri( $value ) {
			$decoded    = html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			$normalized = strtolower( preg_replace( '/[\x00-\x20]+/', '', $decoded ) );

			if ( false !== strpos( $normalized, 'javascript:' )
				|| false !== strpos( $normalized, 'vbscript:' )
				|| false !== strpos( $normalized, 'data:text/html' )
				|| false !== strpos( $normalized, 'data:application/xhtml' )
				|| false !== strpos( $normalized, 'data:image/svg+xml' )
			) {
				return true;
			}

			return false;
		}
	}
}
