<?php /** @noinspection PhpMissingFieldTypeInspection */

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class MaterialsRequestFormatMapping extends DataObject {
	public $__table = 'materials_request_format_mapping';
	public $id;
	public $libraryId;
	public $materialsRequestFormatId;
	public $catalogFormat;

	/** @noinspection PhpUnusedParameterInspection */
	static function getObjectStructure($context = ''): array {
		$libraryList = [];
		$tmpLibrary = new Library();
		$tmpLibrary->orderBy('displayName');
		$homeLibrary = Library::getPatronHomeLibrary();
		if (is_null($homeLibrary)) {
			//User does not have a home library, this is likely an admin account.  Use the active library
			global $library;
			$homeLibrary = $library;
		}
		$tmpLibrary->libraryId = $homeLibrary->libraryId;

		$tmpLibrary->find();
		while ($tmpLibrary->fetch()) {
			$libraryList[$tmpLibrary->libraryId] = $tmpLibrary->displayName;
		}

		//Get a list of all the materials request formats.
		$requestFormats = $homeLibrary->getMaterialsRequestFormats();

		$materialRequestFormat = new MaterialsRequestFormat();
		$materialRequestFormat->libraryId = $homeLibrary->libraryId;
		$validRequestFormats = [-1 => 'None'];

		if (!empty($requestFormats)) {
			foreach ($requestFormats as $requestFormat) {
				$validRequestFormats[$requestFormat->id] = $requestFormat->formatLabel;
			}
		}

		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'libraryId' => [
				'property' => 'libraryId',
				'type' => 'enum',
				'values' => $libraryList,
				'label' => 'Library',
				'description' => 'The id of a library',
			],
			'catalogFormat' => [
				'property' => 'catalogFormat',
				'type' => 'text',
				'label' => 'Aspen Catalog Format',
				'description' => 'The format within the Aspen catalog.',
				'readOnly' => true
			],
			'materialsRequestFormatId' => [
				'property' => 'materialsRequestFormatId',
				'type' => 'enum',
				'values' => $validRequestFormats,
				'label' => 'Materials Request Format',
				'description' => 'The format for the materials request.',
			],
		];
	}

	public static function loadActiveFormats(int $activeLibraryId) : void {
		//Automatically generate based on the data in the database.
		global $aspen_db;
		//Get the list of active formats from the database
		$loadDefaultFormatsStmt = "select trim(indexed_format.format) as format, MIN(permanent_id), count(grouped_work.id) as numWorks FROM grouped_work_record_items inner join grouped_work_records on groupedWorkRecordId = grouped_work_records.id join grouped_work_variation on grouped_work_variation.id = grouped_work_record_items.groupedWorkVariationId join indexed_format on grouped_work_variation.formatId = indexed_format.id join grouped_work on grouped_work_variation.groupedWorkId = grouped_work.id group by lower(trim(format));";
		$results = $aspen_db->query($loadDefaultFormatsStmt, PDO::FETCH_ASSOC);

		//Get the exising formats for the library
		$formatMapping = new MaterialsRequestFormatMapping();
		$formatMapping->libraryId = $activeLibraryId;
		//Load a list of active formats with the key being the format name and the value being the id
		$activeFormats = $formatMapping->fetchAll('catalogFormat', 'id');

		foreach ($results as $result) {
			//Check to see if we already have this format
			$formatExists = array_key_exists($result['format'], $activeFormats);

			if (!$formatExists) {
				$materialsRequestFormatMapping = new MaterialsRequestFormatMapping();
				$materialsRequestFormatMapping->libraryId = $activeLibraryId;
				$materialsRequestFormatMapping->catalogFormat = $result['format'];
				$materialsRequestFormatMapping->materialsRequestFormatId = -1;
				$materialsRequestFormatMapping->insert();
			}else{
				unset($activeFormats[$result['format']]);
			}
		}

		//Delete anything left over
		if (!empty($activeFormats)) {
			$formatMapping = new MaterialsRequestFormatMapping();
			$formatMapping->whereAddIn('id', $activeFormats, false);
			$formatMapping->delete(true);
		}
	}
}