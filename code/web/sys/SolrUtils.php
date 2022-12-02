<?php
/**
 *
 * Copyright (C) Andrew Nagy 2009.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

/**
 * Solr Utility Functions
 *
 * This class is designed to hold Solr-related support methods that may
 * be called statically.  This allows sharing of some Solr-related logic
 *
 * @author      Demian Katz
 * @access      public
 */
class SolrUtils {
	/**
	 * Capitalize boolean operators in a query string to allow case-insensitivity.
	 *
	 * @access  public
	 * @param string $query The query to capitalize.
	 * @return  string                  The capitalized query.
	 */
	public static function capitalizeBooleans($query) {
		// This lookAhead detects whether or not we are inside quotes; it
		// is used to prevent switching case of Boolean reserved words
		// inside quotes, since that can cause problems in case-sensitive
		// fields when the reserved words are actually used as search terms.
		$lookAhead = '(?=(?:[^\"]*+\"[^\"]*+\")*+[^\"]*+$)';
		$regs = [
			"/\\s+AND\\s+{$lookAhead}/i",
			"/\\s+OR\\s+{$lookAhead}/i",
			"/(\\s+NOT\\s+|^NOT\\s+){$lookAhead}/i",
			"/\\(NOT\\s+{$lookAhead}/i",
		];
		$replace = [
			' AND ',
			' OR ',
			' NOT ',
			'(NOT ',
		];
		return trim(preg_replace($regs, $replace, $query));
	}

}