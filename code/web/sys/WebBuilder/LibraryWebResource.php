<?php

class LibraryWebResource extends DataObject {
	public $__table = 'library_web_builder_resource';
	public $id;
	public $libraryId;
	public $webResourceId;

	public function getNumericColumnNames(): array {
		return [
			'id',
			'libraryId',
			'webResourceId',
		];
	}
}