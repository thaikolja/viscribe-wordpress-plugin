<?php

/*
 * @name:           Viscribe
 * @description     Uses AI to rename images during upload for SEO-friendly filenames.
 * @author          Kolja Nolte <kolja.nolte@gmail.com>
 * @copyright       2025-2026 (C) Kolja Nolte
 * @see             https://docs.kolja-nolte.com/viscribe
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Released under the GNU General Public License v2 or later.
 * See: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package Viscribe\Services\Twig
 * @license GPL-2.0-or-later
 */

/**
 * Compile-time translation node for Twig.
 *
 * @package Viscribe\Services\Twig
 */

declare( strict_types=1 );

namespace Viscribe\Services\Twig;

use Twig\Compiler;
use Twig\Node\Node;

/**
 * Compiles {% trans %}...{% endtrans %} into \__( '...', 'viscribe' ).
 *
 * The literal string appears in compiled PHP templates, making it
 * discoverable by WordPress .pot generation tools.
 */
class TransNode extends Node {

	/**
	 * Compile the translation node to PHP.
	 *
	 * @param Compiler $compiler The Twig compiler instance.
	 *
	 * @return void
	 */
	public function compile( Compiler $compiler ): void {
		$body = $this->getNode( 'body' );

		// Get the raw text content from the body node.
		$text = $this->extract_text( $body );

		if ( '' === $text ) {
			return;
		}

		$compiler
			->raw( "<?php echo \\\__( '" )
			->raw( str_replace( "'", "\\'", $text ) )
			->raw( "', 'viscribe' ); ?>\n" );
	}

	/**
	 * Extract plain text from a Twig node tree.
	 *
	 * @param Node $node The node to extract text from.
	 *
	 * @return string The extracted text.
	 */
	private function extract_text( Node $node ): string {
		$text = '';

		for ( $i = 0; $i < $node->count(); $i ++ ) {
			$child = $node->getNode( (string) $i );
			if ( $child instanceof \Twig\Node\TextNode ) {
				$text .= $child->getAttribute( 'data' );
			} elseif ( $child instanceof Node ) {
				$text .= $this->extract_text( $child );
			}
		}

		return trim( $text );
	}
}
