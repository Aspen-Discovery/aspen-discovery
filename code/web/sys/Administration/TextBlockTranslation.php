<?php

class TextBlockTranslation  extends DataObject {
	public $__table = 'text_block_translation';

	public $id;
	public $objectType;
	public $objectId;
	public $languageId;
	public $translation;
}