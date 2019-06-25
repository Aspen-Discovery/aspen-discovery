<?php


class Translation extends DataObject
{
	public $__table = 'translations';
	public $id;
	public $termId;
	public $languageId;
	public $translation;
	public $translated;

	public function getNumericColumnNames()
	{
		return ['termId', 'languageId'];
	}
}