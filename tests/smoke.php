<?php
/**
 * Standalone PHP CLI smoke test for WPPhoto (no WordPress installation required).
 *
 * Usage:
 *   php tests/smoke.php
 *
 * Verifies:
 * 1. WPPhoto_SVG_Sanitizer blocks XSS vectors (<script>, onload=, javascript: href,
 *    <foreignObject>, <iframe>, <object>, <embed>, CSS javascript:, data:text/html, XXE).
 * 2. WPPhoto_SVG_Sanitizer preserves clean valid vector SVG markup.
 * 3. Public PHP interop API functions (`includes/api.php`) work cleanly.
 *
 * @package WPPhoto
 */

define( 'WPPHOTO_TESTING', true );

require_once dirname( __DIR__ ) . '/includes/api.php';
require_once dirname( __DIR__ ) . '/includes/class-wpphoto-svg-sanitizer.php';

$passed = 0;
$failed = 0;

/**
 * Simple assertion helper.
 *
 * @param bool   $cond    Condition.
 * @param string $message Assertion label.
 * @return void
 */
function wpphoto_smoke_assert( $cond, $message ) {
	global $passed, $failed;
	if ( $cond ) {
		$passed++;
		echo "PASS: {$message}\n";
	} else {
		$failed++;
		fwrite( STDERR, "FAIL: {$message}\n" );
	}
}

// 1. Block <script> tag.
$xss_script = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><script>alert(1)</script><circle cx="12" cy="12" r="8"/></svg>';
$res_script = WPPhoto_SVG_Sanitizer::sanitize( $xss_script );
wpphoto_smoke_assert(
	is_string( $res_script )
		&& false === stripos( $res_script, '<script' )
		&& false === stripos( $res_script, 'alert(1)' )
		&& false !== strpos( $res_script, '<circle' ),
	'Sanitizer strips <script> payload while keeping safe <circle>'
);

// 2. Block onload= / onclick= event attributes.
$xss_event = '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)" viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="20" onclick="evil()"/></svg>';
$res_event = WPPhoto_SVG_Sanitizer::sanitize( $xss_event );
wpphoto_smoke_assert(
	is_string( $res_event )
		&& false === stripos( $res_event, 'onload' )
		&& false === stripos( $res_event, 'onclick' )
		&& false !== strpos( $res_event, '<rect' ),
	'Sanitizer strips onload= and onclick= event handlers'
);

// 3. Block javascript: in href / xlink:href.
$xss_href = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 24 24"><use href="javascript:alert(1)" xlink:href="javascript:alert(2)"/><use href="#safe-icon"/></svg>';
$res_href = WPPhoto_SVG_Sanitizer::sanitize( $xss_href );
wpphoto_smoke_assert(
	is_string( $res_href )
		&& false === stripos( $res_href, 'javascript:' )
		&& false !== strpos( $res_href, '#safe-icon' ),
	'Sanitizer blocks javascript: href/xlink:href and preserves internal #fragment'
);

// 4. Block <foreignObject>, <iframe>, <object>, <embed>.
$xss_embed = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">'
	. '<foreignObject width="100" height="100"><body xmlns="http://www.w3.org/1999/xhtml"><iframe src="https://evil.test"></iframe></body></foreignObject>'
	. '<iframe src="javascript:alert(1)"></iframe>'
	. '<object data="evil.swf"></object>'
	. '<embed src="evil.swf"/>'
	. '<path d="M4 12h16"/>'
	. '</svg>';
$res_embed = WPPhoto_SVG_Sanitizer::sanitize( $xss_embed );
wpphoto_smoke_assert(
	is_string( $res_embed )
		&& false === stripos( $res_embed, 'foreignObject' )
		&& false === stripos( $res_embed, '<iframe' )
		&& false === stripos( $res_embed, '<object' )
		&& false === stripos( $res_embed, '<embed' )
		&& false !== strpos( $res_embed, 'M4 12h16' ),
	'Sanitizer blocks <foreignObject>, <iframe>, <object>, and <embed>'
);

// 5. Block data:text/html and unsafe style CSS expressions (both valid XML and malformed XML).
$xss_style = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">'
	. '<rect x="0" y="0" width="24" height="24" style="background:url(data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==)" fill="url(javascript:alert(1))"/>'
	. '</svg>';
$res_style = WPPhoto_SVG_Sanitizer::sanitize( $xss_style );
wpphoto_smoke_assert(
	is_string( $res_style )
		&& false === stripos( $res_style, 'data:text/html' )
		&& false === stripos( $res_style, 'javascript:' )
		&& false !== strpos( $res_style, '<rect' ),
	'Sanitizer blocks data:text/html and javascript: in style/fill attributes'
);

$xss_malformed = '<svg xmlns="http://www.w3.org/2000/svg"><rect style="background:url(data:text/html,<script>alert(1)</script>)"/></svg>';
wpphoto_smoke_assert(
	false === WPPhoto_SVG_Sanitizer::sanitize( $xss_malformed ),
	'Sanitizer rejects malformed XML containing raw <script> inside attribute'
);

// 6. Block DOCTYPE / ENTITY XXE payload.
$xss_xxe = '<?xml version="1.0"?><!DOCTYPE svg [<!ENTITY xxe SYSTEM "file:///etc/passwd">]><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M2 2h20"/></svg>';
$res_xxe = WPPhoto_SVG_Sanitizer::sanitize( $xss_xxe );
wpphoto_smoke_assert(
	is_string( $res_xxe )
		&& false === stripos( $res_xxe, 'DOCTYPE' )
		&& false === stripos( $res_xxe, 'ENTITY' )
		&& false !== strpos( $res_xxe, 'M2 2h20' ),
	'Sanitizer strips DOCTYPE/ENTITY declarations before parsing'
);

// 7. Reject non-SVG root.
$bad_root = '<html><body><script>alert(1)</script></body></html>';
wpphoto_smoke_assert(
	false === WPPhoto_SVG_Sanitizer::sanitize( $bad_root ),
	'Sanitizer rejects non-<svg> root document'
);

// 8. Preserve valid gradient + path + circle SVG.
$valid_svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="64" height="64">'
	. '<defs><linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">'
	. '<stop offset="0%" stop-color="#3858e9"/><stop offset="100%" stop-color="#2c47c5"/>'
	. '</linearGradient></defs>'
	. '<rect x="2" y="2" width="60" height="60" rx="14" fill="url(#bg)"/>'
	. '<path d="M20 32h24" stroke="#ffffff" stroke-width="4" stroke-linecap="round"/>'
	. '</svg>';
$res_valid = WPPhoto_SVG_Sanitizer::sanitize( $valid_svg );
wpphoto_smoke_assert(
	is_string( $res_valid )
		&& false !== strpos( $res_valid, 'linearGradient' )
		&& false !== strpos( $res_valid, 'url(#bg)' )
		&& false !== strpos( $res_valid, 'M20 32h24' )
		&& false !== strpos( $res_valid, 'viewBox="0 0 64 64"' ),
	'Sanitizer preserves valid SVG gradients, shapes, and attributes'
);

// 9. Public interop API smoke checks.
wpphoto_smoke_assert( true === wpphoto_is_available(), 'wpphoto_is_available() returns true' );
wpphoto_smoke_assert( true === wpphoto_supports( 'site_icon' ), 'wpphoto_supports("site_icon") returns true' );
wpphoto_smoke_assert( true === wpphoto_supports( 'svg_sanitize' ), 'wpphoto_supports("svg_sanitize") returns true' );
wpphoto_smoke_assert( false === wpphoto_supports( 'unknown_feature_xyz' ), 'wpphoto_supports() returns false for unknown feature' );
wpphoto_smoke_assert( false === wpphoto_is_wpcy_active(), 'wpphoto_is_wpcy_active() returns false in standalone CLI' );
wpphoto_smoke_assert( false === wpphoto_is_toolbox_active(), 'wpphoto_is_toolbox_active() returns false when WPCY inactive' );
wpphoto_smoke_assert( is_bool( wpphoto_host_supports_webp() ), 'wpphoto_host_supports_webp() returns boolean' );

echo "\nSummary: {$passed} passed, {$failed} failed.\n";
exit( $failed > 0 ? 1 : 0 );
