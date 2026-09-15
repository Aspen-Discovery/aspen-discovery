<?php /** @noinspection PhpMissingFieldTypeInspection */


class OpenArchivesRecord extends DataObject {
	public $__table = 'open_archives_record';
	public $id;
	public $sourceCollection;
	public $permanentUrl;
	/** @noinspection PhpUnused */
	public $lastSeen;
	/** Cover image URL, if resolved on demand via the platform's REST API. Shared across all cover sizes. */
	public $coverUrl;
}