<?php

/**
 * Stores information about subjects for processing links to catalog and EBSCO, etc.
 *
 * @category VuFind-Plus-2014
 * @author Mark Noble <mark@marmot.org>
 * Date: 2/22/2016
 * Time: 8:55 PM
 */
class ArchivePrivateCollection extends DB_DataObject{
	public $__table = 'archive_private_collections';
	public $id;
	public $privateCollections;

}