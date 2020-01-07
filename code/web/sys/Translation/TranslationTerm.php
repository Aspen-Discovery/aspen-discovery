<?php

/**
 * Class TranslationTerm
 *
 * A term or phrase that is being translated.  The term can have parameters to it indicated as %1%, %2%, etc
 * The terms are automatically generated if not found in the table during the translation process.
 */
class TranslationTerm extends DataObject
{
	public $__table = 'translation_terms';
	public $id;
	public $term;
	public $defaultText;
	public $parameterNotes;
	public $samplePageUrl;
}