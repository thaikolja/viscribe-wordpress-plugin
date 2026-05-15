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
 * Token parser for the {% trans %} tag.
 *
 * @package Viscribe\Services\Twig
 */

declare( strict_types=1 );

namespace Viscribe\Services\Twig;

use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * Parses {% trans %}...{% endtrans %} blocks.
 *
 * The text between the tags is extracted at compile time and
 * emitted as a literal in the compiled PHP template, ensuring
 * WordPress translation parsers can discover the strings.
 */
class TransTokenParser extends AbstractTokenParser {

	/**
	 * Parse the {% trans %} token.
	 *
	 * @param Token $token The current token.
	 *
	 * @return TransNode The compiled translation node.
	 */
	public function parse( Token $token ): TransNode {
		$parser = $this->parser;
		$stream = $parser->getStream();

		$stream->expect( Token::BLOCK_END_TYPE );

		$body = $this->parser->subparse( [ $this, 'decide_trans_end' ], true );

		$stream->expect( Token::BLOCK_END_TYPE );

		return new TransNode( [ 'body' => $body ], [], $token->getLine(), $this->getTag() );
	}

	/**
	 * Determine if the current token marks the end of the trans block.
	 *
	 * @param Token $token The current token.
	 *
	 * @return bool True if this is the end of the trans block.
	 */
	public function decide_trans_end( Token $token ): bool {
		return $token->test( 'endtrans' );
	}

	/**
	 * Get the tag name.
	 *
	 * @return string The tag name.
	 */
	public function getTag(): string {
		return 'trans';
	}
}
