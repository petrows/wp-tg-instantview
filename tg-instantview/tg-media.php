<?php
/**
 * Media processing helpers for Telegram Instant View output.
 *
 * @package TG-InstantView
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return the effective media mode.
 *
 * @param array|null $options Prepared plugin options, or null to load them.
 * @return string One of keep, remove or link.
 */
function tgiv_get_media_mode( $options = null ) {
	if ( ! is_array( $options ) ) {
		$options = tgiv_options();
	}

	if ( empty( $options['tgiv_process_media'] ) ) {
		return 'keep';
	}

	if (
		isset( $options['tgiv_media_action'] )
		&& in_array( $options['tgiv_media_action'], array( 'remove', 'link' ), true )
	) {
		return $options['tgiv_media_action'];
	}

	return 'remove';
}

/**
 * Normalize a media URL for use in a replacement link.
 *
 * @param string $url Raw URL.
 * @return string Normalized URL, or an empty string for unsupported schemes.
 */
function tgiv_normalize_media_url( $url ) {
	$url = html_entity_decode( trim( (string) $url ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );

	if ( '' === $url || preg_match( '/^(?:data|blob|javascript):/i', $url ) ) {
		return '';
	}

	if ( 0 === strpos( $url, '//' ) ) {
		$url = ( is_ssl() ? 'https:' : 'http:' ) . $url;
	} elseif ( ! preg_match( '#^https?://#i', $url ) ) {
		$url = home_url( '/' . ltrim( $url, '/' ) );
	}

	return esc_url_raw( $url, array( 'http', 'https' ) );
}

/**
 * Get the first URL from a srcset attribute.
 *
 * @param string $srcset Source set.
 * @return string First URL in the set.
 */
function tgiv_media_url_from_srcset( $srcset ) {
	$candidates = explode( ',', (string) $srcset );
	$first      = trim( $candidates[0] );

	if ( '' === $first ) {
		return '';
	}

	$parts = preg_split( '/\s+/', $first );
	return isset( $parts[0] ) ? $parts[0] : '';
}

/**
 * Get the best source URL from a DOM media node.
 *
 * @param DOMElement $node Media element.
 * @return string Media URL.
 */
function tgiv_media_url_from_dom_node( $node ) {
	$tag        = strtolower( $node->tagName );
	$attributes = array( 'src', 'data-src', 'data-lazy-src' );

	if ( 'object' === $tag ) {
		array_unshift( $attributes, 'data' );
	}

	foreach ( $attributes as $attribute ) {
		if ( $node->hasAttribute( $attribute ) ) {
			$url = tgiv_normalize_media_url( $node->getAttribute( $attribute ) );
			if ( $url ) {
				return $url;
			}
		}
	}

	foreach ( array( 'srcset', 'data-srcset' ) as $attribute ) {
		if ( $node->hasAttribute( $attribute ) ) {
			$url = tgiv_normalize_media_url(
				tgiv_media_url_from_srcset( $node->getAttribute( $attribute ) )
			);
			if ( $url ) {
				return $url;
			}
		}
	}

	foreach ( $node->getElementsByTagName( '*' ) as $child ) {
		if ( ! $child instanceof DOMElement ) {
			continue;
		}

		foreach ( array( 'src', 'data-src', 'data-lazy-src' ) as $attribute ) {
			if ( $child->hasAttribute( $attribute ) ) {
				$url = tgiv_normalize_media_url( $child->getAttribute( $attribute ) );
				if ( $url ) {
					return $url;
				}
			}
		}

		if ( $child->hasAttribute( 'srcset' ) ) {
			$url = tgiv_normalize_media_url(
				tgiv_media_url_from_srcset( $child->getAttribute( 'srcset' ) )
			);
			if ( $url ) {
				return $url;
			}
		}
	}

	return '';
}

/**
 * Build a readable replacement link label.
 *
 * @param string      $tag  Media element name.
 * @param DOMElement $node Media element.
 * @return string Link label.
 */
function tgiv_media_link_label( $tag, $node ) {
	$labels = array(
		'img'     => 'Open image',
		'picture' => 'Open image',
		'iframe'  => 'Open embedded media',
		'video'   => 'Watch video',
		'audio'   => 'Listen to audio',
		'object'  => 'Open embedded media',
		'embed'   => 'Open embedded media',
	);
	$label  = isset( $labels[ $tag ] ) ? $labels[ $tag ] : 'Open media';
	$detail = '';

	foreach ( array( 'alt', 'title', 'aria-label' ) as $attribute ) {
		if ( $node->hasAttribute( $attribute ) ) {
			$detail = trim( $node->getAttribute( $attribute ) );
			if ( $detail ) {
				break;
			}
		}
	}

	if ( ! $detail && 'picture' === $tag ) {
		$images = $node->getElementsByTagName( 'img' );
		if ( $images->length && $images->item( 0 )->hasAttribute( 'alt' ) ) {
			$detail = trim( $images->item( 0 )->getAttribute( 'alt' ) );
		}
	}

	return $detail ? $label . ': ' . $detail : $label;
}

/**
 * Determine whether a node still belongs to the processing root.
 *
 * @param DOMNode $node Node to inspect.
 * @param DOMNode $root Root node.
 * @return bool True when the node is still attached below root.
 */
function tgiv_media_node_is_attached( $node, $root ) {
	$current = $node;

	while ( $current ) {
		if ( $current->isSameNode( $root ) ) {
			return true;
		}
		$current = $current->parentNode;
	}

	return false;
}

/**
 * Select the node to remove, including its media figure when present.
 *
 * @param DOMNode $node Media node.
 * @param DOMNode $root Processing root.
 * @return DOMNode Node to remove.
 */
function tgiv_media_removal_target( $node, $root ) {
	$current = $node;

	while ( $current->parentNode && ! $current->parentNode->isSameNode( $root ) ) {
		$parent = $current->parentNode;
		if ( $parent instanceof DOMElement && 'figure' === strtolower( $parent->tagName ) ) {
			return $parent;
		}
		$current = $parent;
	}

	return $node;
}

/**
 * Process media with DOMDocument.
 *
 * @param string $html Rendered post HTML.
 * @param string $mode remove or link.
 * @return string|false Processed HTML, or false when DOM parsing failed.
 */
function tgiv_process_media_dom( $html, $mode ) {
	if ( ! class_exists( 'DOMDocument' ) ) {
		return false;
	}

	$document = new DOMDocument( '1.0', 'UTF-8' );
	$previous = libxml_use_internal_errors( true );
	$loaded   = $document->loadHTML(
		'<?xml encoding="utf-8" ?><div id="tgiv-media-root">' . $html . '</div>',
		LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET
	);
	libxml_clear_errors();
	libxml_use_internal_errors( $previous );

	if ( ! $loaded ) {
		return false;
	}

	$xpath = new DOMXPath( $document );
	$roots = $xpath->query( '//*[@id="tgiv-media-root"]' );
	if ( ! $roots || 0 === $roots->length ) {
		return false;
	}
	$root = $roots->item( 0 );
	$nodes = $xpath->query(
		'.//picture | .//iframe | .//video | .//audio | .//object | .//embed | .//img[not(ancestor::picture)]',
		$root
	);

	if ( ! $nodes ) {
		return false;
	}

	$media_nodes = array();
	foreach ( $nodes as $node ) {
		$media_nodes[] = $node;
	}

	foreach ( $media_nodes as $node ) {
		if (
			! $node instanceof DOMElement
			|| ! $node->parentNode
			|| ! tgiv_media_node_is_attached( $node, $root )
		) {
			continue;
		}

		if ( 'remove' === $mode ) {
			$target = tgiv_media_removal_target( $node, $root );
			if ( $target->parentNode ) {
				$target->parentNode->removeChild( $target );
			}
			continue;
		}

		$url = tgiv_media_url_from_dom_node( $node );
		if ( ! $url ) {
			$node->parentNode->removeChild( $node );
			continue;
		}

		$tag   = strtolower( $node->tagName );
		$link  = $document->createElement( 'a' );
		$label = tgiv_media_link_label( $tag, $node );
		$link->setAttribute( 'class', 'tgiv-media-link tgiv-media-link--' . $tag );
		$link->setAttribute( 'href', $url );
		$link->setAttribute( 'rel', 'noopener noreferrer' );
		$link->appendChild( $document->createTextNode( $label ) );

		$target = $node;
		if (
			$node->parentNode instanceof DOMElement
			&& 'a' === strtolower( $node->parentNode->tagName )
		) {
			$target = $node->parentNode;
		}

		if ( $target->parentNode ) {
			$target->parentNode->replaceChild( $link, $target );
		}
	}

	$output = '';
	foreach ( $root->childNodes as $child ) {
		$fragment = $document->saveHTML( $child );
		if ( false !== $fragment ) {
			$output .= $fragment;
		}
	}

	return $output;
}

/**
 * Extract a source URL from an HTML media fragment for the fallback parser.
 *
 * @param string $html Media HTML.
 * @return string Media URL.
 */
function tgiv_media_url_from_html( $html ) {
	foreach ( array( 'src', 'data-src', 'data-lazy-src', 'data' ) as $attribute ) {
		$pattern = '/\s' . preg_quote( $attribute, '/' ) . '\s*=\s*(["\'])(.*?)\1/is';
		if ( preg_match( $pattern, $html, $match ) ) {
			$url = tgiv_normalize_media_url( $match[2] );
			if ( $url ) {
				return $url;
			}
		}
	}

	if ( preg_match( '/\ssrcset\s*=\s*(["\'])(.*?)\1/is', $html, $match ) ) {
		return tgiv_normalize_media_url( tgiv_media_url_from_srcset( $match[2] ) );
	}

	return '';
}

/**
 * Build escaped replacement link markup.
 *
 * @param string $url   Media URL.
 * @param string $tag   Media element name.
 * @param string $label Optional link label.
 * @return string Replacement HTML.
 */
function tgiv_media_link_html( $url, $tag = 'media', $label = '' ) {
	$url = tgiv_normalize_media_url( $url );
	if ( ! $url ) {
		return '';
	}

	if ( ! $label ) {
		$labels = array(
			'img'     => 'Open image',
			'picture' => 'Open image',
			'iframe'  => 'Open embedded media',
			'video'   => 'Watch video',
			'audio'   => 'Listen to audio',
			'object'  => 'Open embedded media',
			'embed'   => 'Open embedded media',
		);
		$label  = isset( $labels[ $tag ] ) ? $labels[ $tag ] : 'Open media';
	}

	return sprintf(
		'<a class="tgiv-media-link tgiv-media-link--%1$s" href="%2$s" rel="noopener noreferrer">%3$s</a>',
		esc_attr( sanitize_html_class( $tag ) ),
		esc_url( $url ),
		esc_html( $label )
	);
}

/**
 * Process media with a regex fallback when the DOM extension is unavailable.
 *
 * @param string $html Rendered post HTML.
 * @param string $mode remove or link.
 * @return string Processed HTML.
 */
function tgiv_process_media_fallback( $html, $mode ) {
	$paired_pattern = '#<(iframe|video|audio|object|picture)\b[^>]*>[\s\S]*?</\1\s*>#i';
	$void_pattern   = '#<(img|embed)\b[^>]*\/?>#i';

	if ( 'remove' === $mode ) {
		$html = preg_replace( $paired_pattern, '', $html );
		return preg_replace( $void_pattern, '', $html );
	}

	$html = preg_replace_callback(
		$paired_pattern,
		function ( $match ) {
			$tag = strtolower( $match[1] );
			return tgiv_media_link_html( tgiv_media_url_from_html( $match[0] ), $tag );
		},
		$html
	);

	return preg_replace_callback(
		$void_pattern,
		function ( $match ) {
			$tag = strtolower( $match[1] );
			return tgiv_media_link_html( tgiv_media_url_from_html( $match[0] ), $tag );
		},
		$html
	);
}

/**
 * Apply the configured media behavior to rendered post HTML.
 *
 * @param string $html Rendered post HTML.
 * @param string $mode keep, remove or link.
 * @return string Processed HTML.
 */
function tgiv_process_media_html( $html, $mode = 'keep' ) {
	if ( 'keep' === $mode || ! in_array( $mode, array( 'remove', 'link' ), true ) ) {
		return $html;
	}

	$processed = tgiv_process_media_dom( $html, $mode );
	if ( false !== $processed ) {
		return $processed;
	}

	return tgiv_process_media_fallback( $html, $mode );
}

/**
 * Render the featured image according to the configured mode.
 *
 * Open Graph metadata remains unchanged, so Telegram can still use the image
 * in the link preview card.
 *
 * @param string $mode keep, remove or link.
 * @return string Featured media HTML.
 */
function tgiv_get_featured_media_html( $mode = 'keep' ) {
	if ( 'remove' === $mode ) {
		return '';
	}

	if ( 'link' !== $mode ) {
		return get_the_post_thumbnail();
	}

	$url = get_the_post_thumbnail_url( null, 'full' );
	if ( ! $url ) {
		return '';
	}

	$label         = 'Open featured image';
	$attachment_id = get_post_thumbnail_id();
	if ( $attachment_id ) {
		$alt = trim( (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) );
		if ( $alt ) {
			$label .= ': ' . $alt;
		}
	}

	return tgiv_media_link_html( $url, 'img', $label );
}
