<?php
/**
 * Plugin Name: Blockify Icon
 * Plugin URI:  https://blockifywp.com/blocks/icon
 * Description: Lightweight, customizable icon block for WordPress.
 * Author:      Blockify
 * Author URI:  https://blockifywp.com/
 * Version:     0.0.1
 * License:     GPLv2-or-Later
 * Text Domain: blockify
 */

declare( strict_types=1 );

namespace Blockify\Icon;

use DOMElement;
use WP_Theme_JSON_Resolver;
use function add_action;
use function add_filter;
use function register_block_type;
use function str_replace;

const NS = __NAMESPACE__ . '\\';
const DS = DIRECTORY_SEPARATOR;

add_action( 'after_setup_theme', NS . 'register' );
/**
 * Registers the block.
 *
 * @since 0.0.1
 *
 * @since 1.0.0
 *
 * @return void
 */
function register() {
	register_block_type( __DIR__ . '/build' );
}

add_filter( 'render_block_blockify/icon', NS . 'render_icon_block', 10, 2 );
/**
 * Modifies front end HTML output of block.
 *
 * @since 0.0.2
 *
 * @param string $content
 * @param array  $block
 *
 * @return string
 */
function render_icon_block( string $content, array $block ): string {
	if ( ! $content ) {
		return $content;
	}

	$dom = dom( $content );

	/**
	 * @var DOMElement $div
	 */
	$div       = $dom->getElementsByTagName( 'div' )->item( 0 );
	$container = $div->firstChild;
	$classes   = $div->getAttribute( 'class' );
	$classes   .= ' ' . $container->getAttribute( 'class' );

	if ( isset( $block['attrs']['layout']['justifyContent'] ) ) {
		$classes .= ' items-justified-' . $block['attrs']['layout']['justifyContent'];
	}

	$div->setAttribute( 'class', trim( $classes ) );
	$div->setAttribute( 'style', $container->getAttribute( 'style' ) );

	$mask = $container->firstChild;

	if ( ! $mask ) {
		return $content;
	}

	$style        = $mask->getAttribute( 'style' );
	$css          = css_string_to_array( $style );
	$theme_json   = WP_Theme_JSON_Resolver::get_merged_data( '' );
	$palette      = $theme_json->get_settings()['color']['palette'];
	$mask_classes = $mask->getAttribute( 'class' );

	if ( isset( $css['background'] ) ) {
		$hex = $css['background'];

		foreach ( $palette as $color ) {
			if ( isset( $color['color'] ) && $hex === $color['color'] ) {
				$mask_classes .= ' has-' . $color['slug'] . '-background-color';
			}
		}
	}

	$mask->setAttribute( 'class', $mask_classes );
	$div->appendChild( $mask );
	$div->removeChild( $container );

	return str_replace( 'fill="currentColor"', ' ', $dom->saveHTML() );
}


use function defined;
use function libxml_clear_errors;
use function libxml_use_internal_errors;
use function mb_convert_encoding;
use DOMDocument;

/**
 * Returns a formatted DOMDocument object from a given string.
 *
 * @since 0.0.2
 *
 * @param string $html
 *
 * @return string
 */
function dom( string $html ): DOMDocument {
	$dom = new DOMDocument();

	if ( ! $html ) {
		return $dom;
	}

	$libxml_previous_state   = libxml_use_internal_errors( true );
	$dom->preserveWhiteSpace = true;

	if ( defined( 'LIBXML_HTML_NOIMPLIED' ) && defined( 'LIBXML_HTML_NODEFDTD' ) ) {
		$options = LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD;
	} else if ( defined( 'LIBXML_HTML_NOIMPLIED' ) ) {
		$options = LIBXML_HTML_NOIMPLIED;
	} else if ( defined( 'LIBXML_HTML_NODEFDTD' ) ) {
		$options = LIBXML_HTML_NODEFDTD;
	} else {
		$options = 0;
	}

	$dom->loadHTML( mb_convert_encoding( $html, 'HTML-ENTITIES', 'UTF-8' ), $options );

	$dom->formatOutput = true;

	libxml_clear_errors();
	libxml_use_internal_errors( $libxml_previous_state );

	return $dom;
}
