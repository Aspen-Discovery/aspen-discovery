<?php
/**
 * Provides information for mapping fixed bib fields and variable item fields to MARC records when using the Sierra Export.
 *
 * User: mnoble
 * Date: 4/16/2018
 * Time: 12:17 PM
 */

class SierraExportFieldMapping extends DataObject {
	public $__table = 'sierra_export_field_mapping';    // table name
	public $id;
	public $indexingProfileId;
	public /** @noinspection PhpUnused */
		$fixedFieldDestinationField;
	public /** @noinspection PhpUnused */
		$bcode3DestinationSubfield;
	public /** @noinspection PhpUnused */
		$materialTypeSubfield;
	public /** @noinspection PhpUnused */
		$bibLevelLocationsSubfield;
	public /** @noinspection PhpUnused */
		$callNumberExportFieldTag;
	public /** @noinspection PhpUnused */
		$callNumberPrestampExportSubfield;
	public /** @noinspection PhpUnused */
		$callNumberExportSubfield;
	public /** @noinspection PhpUnused */
		$callNumberCutterExportSubfield;
	public /** @noinspection PhpUnused */
		$callNumberPoststampExportSubfield;
	public /** @noinspection PhpUnused */
		$itemPublicNoteExportSubfield;
	public /** @noinspection PhpUnused */
		$volumeExportFieldTag;
	public /** @noinspection PhpUnused */
		$urlExportFieldTag;
	public /** @noinspection PhpUnused */
		$eContentExportFieldTag;

	static function getObjectStructure(): array {
		$indexingProfiles = [];
		require_once ROOT_DIR . '/sys/Indexing/IndexingProfile.php';
		$indexingProfile = new IndexingProfile();
		$indexingProfile->orderBy('name');
		$indexingProfile->find();
		while ($indexingProfile->fetch()) {
			$indexingProfiles[$indexingProfile->id] = $indexingProfile->name;
		}
		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id within the database',
			],
			'indexingProfileId' => [
				'property' => 'indexingProfileId',
				'type' => 'enum',
				'values' => $indexingProfiles,
				'label' => 'Indexing Profile Id',
				'description' => 'The Indexing Profile this map is associated with',
			],
			'fixedFieldDestinationField' => [
				'property' => 'fixedFieldDestinationField',
				'type' => 'text',
				'label' => 'Fixed Field Destination Tag',
				'maxLength' => 3,
				'description' => 'The MARC field where fixed field data should be stored',
				'forcesReindex' => true,
			],
			'bcode3DestinationSubfield' => [
				'property' => 'bcode3DestinationSubfield',
				'type' => 'text',
				'label' => 'BCode3 Destination Subfield',
				'maxLength' => 1,
				'description' => 'Subfield for where BCode3 should be stored',
				'forcesReindex' => true,
			],
			'materialTypeSubfield' => [
				'property' => 'materialTypeSubfield',
				'type' => 'text',
				'label' => 'Material Type Destination Subfield',
				'maxLength' => 1,
				'description' => 'Subfield for where Material Type should be stored',
				'forcesReindex' => true,
			],
			'bibLevelLocationsSubfield' => [
				'property' => 'bibLevelLocationsSubfield',
				'type' => 'text',
				'label' => 'Bib Level Locations Destination Subfield',
				'maxLength' => 1,
				'description' => 'Subfield for where Bib Level Location information should be stored',
				'forcesReindex' => true,
			],
			'callNumberExportFieldTag' => [
				'property' => 'callNumberExportFieldTag',
				'type' => 'text',
				'label' => 'Call Number Export Field Tag',
				'maxLength' => 1,
				'description' => 'The Item Variable field tag where call number is exported',
				'forcesReindex' => true,
			],
			'callNumberPrestampExportSubfield' => [
				'property' => 'callNumberPrestampExportSubfield',
				'type' => 'text',
				'label' => 'Call Number Prestamp Export Subfield',
				'maxLength' => 1,
				'description' => 'The subfield where the call number prestamp is exported',
				'forcesReindex' => true,
			],
			'callNumberExportSubfield' => [
				'property' => 'callNumberExportSubfield',
				'type' => 'text',
				'label' => 'Call Number Export Subfield',
				'maxLength' => 1,
				'description' => 'The subfield where the call number is exported',
				'forcesReindex' => true,
			],
			'callNumberCutterExportSubfield' => [
				'property' => 'callNumberCutterExportSubfield',
				'type' => 'text',
				'label' => 'Call Number Cutter Export Subfield',
				'maxLength' => 1,
				'description' => 'The subfield where the call number cutter is exported',
				'forcesReindex' => true,
			],
			'callNumberPoststampExportSubfield' => [
				'property' => 'callNumberPoststampExportSubfield',
				'type' => 'text',
				'label' => 'Call Number Poststamp Export Subfield',
				'maxLength' => 5,
				'description' => 'The subfield where the call number poststamp is exported.  Multiple can be specified.  I.e. eS is both e and S',
				'forcesReindex' => true,
			],
			'itemPublicNoteExportSubfield' => [
				'property' => 'itemPublicNoteExportSubfield',
				'type' => 'text',
				'label' => 'Item Public Note Subfield',
				'maxLength' => 1,
				'description' => 'The subfield where the item public note is exported.',
				'forcesReindex' => true,
			],
			'volumeExportFieldTag' => [
				'property' => 'volumeExportFieldTag',
				'type' => 'text',
				'label' => 'Volume Export Field Tag',
				'maxLength' => 1,
				'description' => 'The Item Variable field tag where volume is exported',
				'forcesReindex' => true,
			],
			'urlExportFieldTag' => [
				'property' => 'urlExportFieldTag',
				'type' => 'text',
				'label' => 'URL Export Field Tag',
				'maxLength' => 1,
				'description' => 'The Item Variable field tag where the url is exported',
				'forcesReindex' => true,
			],
			'eContentExportFieldTag' => [
				'property' => 'eContentExportFieldTag',
				'type' => 'text',
				'label' => 'eContent Export Field Tag',
				'maxLength' => 1,
				'description' => 'The Item Variable field tag where eContent information (Marmot Only) is exported',
				'forcesReindex' => true,
			],
		];
	}
}