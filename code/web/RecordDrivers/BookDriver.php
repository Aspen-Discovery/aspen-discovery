<?php

/**
 * Record Driver for display of LargeImages from Islandora
 *
 * @category VuFind-Plus-2014
 * @author Mark Noble <mark@marmot.org>
 * Date: 12/9/2015
 * Time: 1:47 PM
 */
require_once ROOT_DIR . '/RecordDrivers/CompoundDriver.php';
class BookDriver extends CompoundDriver {

	public function getViewAction() {
		return 'Book';
	}

	public function getFormat() {
		$genre = $this->getModsValue('genre', 'mods');
		if ($genre != null && strlen($genre) > 0){
			return ucfirst($genre);
		}
		return "Book";
	}
}