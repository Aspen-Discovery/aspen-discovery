<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';
class MaterialsRequestFormats extends DataObject
{
	public $__table = 'materials_request_formats';
	public $id;
	public $libraryId;
	public $format;
	public $formatLabel;
	public $authorLabel;
	public $specialFields;   // SET Data type, possible values : 'Abridged/Unabridged', 'Article Field', 'Eaudio format', 'Ebook format', 'Season'
	public $activeForNewRequests;
	public $weight;

	static $materialsRequestFormatsSpecialFieldOptions = array(
		'Abridged/Unabridged', 'Article Field', 'Eaudio format', 'Ebook format', 'Season'
	);


	static function getObjectStructure() : array {
		return array(
			'id' => array('property' => 'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id'),
			'weight' => array('property' => 'weight', 'type'=>'integer', 'label'=>'Weight', 'description'=>'The sort order', 'default' => 0),
			'format' => array('property' => 'format', 'type' => 'text', 'label' => 'Format', 'description' => 'internal value for format, please use camelCase and no spaces ie. cdAudio'),
			'formatLabel' => array('property' => 'formatLabel', 'type' => 'text', 'label' => 'Format Label', 'description' => 'Label for the format that will be displayed to users.'),
			'authorLabel' => array('property' => 'authorLabel', 'type' => 'text', 'label' => 'Author Label', 'description' => 'Label for the author field associated with this format that will be displayed to users.'),
			'specialFields' => array('property' => 'specialFields', 'type' => 'multiSelect', 'listStyle' => 'checkboxList', 'label' => 'Special Fields for Format', 'description' => 'Any Special Fields to use with this format', 'values' => self::$materialsRequestFormatsSpecialFieldOptions),
			'activeForNewRequests' => array('property' => 'activeForNewRequests', 'type' => 'checkbox', 'label' => 'Active for new requests?', 'description' => 'Whether or not the format should be active for patrons.', 'default' => 1),
		);
	}

	static function getDefaultMaterialRequestFormats($libraryId = -1) {
		$defaultFormats = array();

		$defaultFormat = new MaterialsRequestFormats();
		$defaultFormat->libraryId = $libraryId;
		$defaultFormat->format = 'book';
		$defaultFormat->formatLabel = 'Book';
		$defaultFormat->authorLabel = 'Author';
		$defaultFormat->specialFields = array(); // (Abridged/Unabridged,Article Field,Eaudio format,Ebook format,Season')
		$defaultFormat->weight = count($defaultFormats) + 1;
		$defaultFormats[] = $defaultFormat;

		$defaultFormat = new MaterialsRequestFormats();
		$defaultFormat->libraryId = $libraryId;
		$defaultFormat->format = 'largePrint';
		$defaultFormat->formatLabel = 'Large Print';
		$defaultFormat->authorLabel = 'Author';
		$defaultFormat->specialFields = array(); // (Abridged/Unabridged,Article Field,Eaudio format,Ebook format,Season')
		$defaultFormat->weight = count($defaultFormats) + 1;
		$defaultFormats[] = $defaultFormat;

		$defaultFormat = new MaterialsRequestFormats();
		$defaultFormat->libraryId = $libraryId;
		$defaultFormat->format = 'dvd';
		$defaultFormat->formatLabel = 'DVD';
		$defaultFormat->authorLabel = 'Actor / Director';
		$defaultFormat->specialFields = array('Season'); // (Abridged/Unabridged,Article Field,Eaudio format,Ebook format,Season')
		$defaultFormat->weight = count($defaultFormats) + 1;
		$defaultFormats[] = $defaultFormat;

		$defaultFormat = new MaterialsRequestFormats();
		$defaultFormat->libraryId = $libraryId;
		$defaultFormat->format = 'bluray';
		$defaultFormat->formatLabel = 'Blu-ray';
		$defaultFormat->authorLabel = 'Actor / Director';
		$defaultFormat->specialFields = array('Season'); // (Abridged/Unabridged,Article Field,Eaudio format,Ebook format,Season')
		$defaultFormat->weight = count($defaultFormats) + 1;
		$defaultFormats[] = $defaultFormat;

		$defaultFormat = new MaterialsRequestFormats();
		$defaultFormat->libraryId = $libraryId;
		$defaultFormat->format = 'cdAudio';
		$defaultFormat->formatLabel = 'CD Audio Book';
		$defaultFormat->authorLabel = 'Author';
		$defaultFormat->specialFields = array('Abridged/Unabridged'); // (Abridged/Unabridged,Article Field,Eaudio format,Ebook format,Season')
		$defaultFormat->weight = count($defaultFormats) + 1;
		$defaultFormats[] = $defaultFormat;

		$defaultFormat = new MaterialsRequestFormats();
		$defaultFormat->libraryId = $libraryId;
		$defaultFormat->format = 'cdMusic';
		$defaultFormat->formatLabel = 'Music CD';
		$defaultFormat->authorLabel = 'Artist / Composer';
		$defaultFormat->specialFields = array(); // (Abridged/Unabridged,Article Field,Eaudio format,Ebook format,Season')
		$defaultFormat->weight = count($defaultFormats) + 1;
		$defaultFormats[] = $defaultFormat;

		$defaultFormat = new MaterialsRequestFormats();
		$defaultFormat->libraryId = $libraryId;
		$defaultFormat->format = 'ebook';
		$defaultFormat->formatLabel = 'eBook';
		$defaultFormat->authorLabel = 'Author';
		$defaultFormat->specialFields = array('Ebook format'); // (Abridged/Unabridged,Article Field,Eaudio format,Ebook format,Season')
		$defaultFormat->weight = count($defaultFormats) + 1;
		$defaultFormats[] = $defaultFormat;

		$defaultFormat = new MaterialsRequestFormats();
		$defaultFormat->libraryId = $libraryId;
		$defaultFormat->format = 'eaudio';
		$defaultFormat->formatLabel = 'eAudio';
		$defaultFormat->authorLabel = 'Author';
		$defaultFormat->specialFields = array('Eaudio format','Abridged/Unabridged'); // (Abridged/Unabridged,Article Field,Eaudio format,Ebook format,Season')
		$defaultFormat->weight = count($defaultFormats) + 1;
		$defaultFormats[] = $defaultFormat;

		$defaultFormat = new MaterialsRequestFormats();
		$defaultFormat->libraryId = $libraryId;
		$defaultFormat->format = 'playaway';
		$defaultFormat->formatLabel = 'Playaway';
		$defaultFormat->authorLabel = 'Author';
		$defaultFormat->specialFields = array('Abridged/Unabridged'); // (Abridged/Unabridged,Article Field,Eaudio format,Ebook format,Season')
		$defaultFormat->weight = count($defaultFormats) + 1;
		$defaultFormats[] = $defaultFormat;

		$defaultFormat = new MaterialsRequestFormats();
		$defaultFormat->libraryId = $libraryId;
		$defaultFormat->format = 'article';
		$defaultFormat->formatLabel = 'Article';
		$defaultFormat->authorLabel = 'Author';
		$defaultFormat->specialFields = array('Article Field'); // (Abridged/Unabridged,Article Field,Eaudio format,Ebook format,Season')
		$defaultFormat->weight = count($defaultFormats) + 1;
		$defaultFormats[] = $defaultFormat;

		$defaultFormat = new MaterialsRequestFormats();
		$defaultFormat->libraryId = $libraryId;
		$defaultFormat->format = 'cassette';
		$defaultFormat->formatLabel = 'Cassette';
		$defaultFormat->authorLabel = 'Artist / Composer';
		$defaultFormat->specialFields = array('Abridged/Unabridged'); // (Abridged/Unabridged,Article Field,Eaudio format,Ebook format,Season')
		$defaultFormat->weight = count($defaultFormats) + 1;
		$defaultFormats[] = $defaultFormat;

		$defaultFormat = new MaterialsRequestFormats();
		$defaultFormat->libraryId = $libraryId;
		$defaultFormat->format = 'vhs';
		$defaultFormat->formatLabel = 'VHS';
		$defaultFormat->authorLabel = 'Actor / Director';
		$defaultFormat->specialFields = array('Season'); // (Abridged/Unabridged,Article Field,Eaudio format,Ebook format,Season')
		$defaultFormat->weight = count($defaultFormats) + 1;
		$defaultFormats[] = $defaultFormat;

		$defaultFormat = new MaterialsRequestFormats();
		$defaultFormat->libraryId = $libraryId;
		$defaultFormat->format = 'other';
		$defaultFormat->formatLabel = 'Other';
		$defaultFormat->authorLabel = 'Author';
		$defaultFormat->specialFields = array(); // (Abridged/Unabridged,Article Field,Eaudio format,Ebook format,Season')
		$defaultFormat->weight = count($defaultFormats) + 1;
		$defaultFormats[] = $defaultFormat;

		return $defaultFormats;
	}


	static function getAuthorLabelsAndSpecialFields($libraryId) {
		// Format Labels
		$formats = new MaterialsRequestFormats();
		$formats->libraryId = $libraryId;
		$usingDefaultFormats = $formats->count() == 0;

		// Get Author Labels for all Formats
		$specialFieldFormats = [];
		$formatAuthorLabels = [];
		if ($usingDefaultFormats) {
			$defaultFormats = self::getDefaultMaterialRequestFormats();
			/** @var MaterialsRequestFormats $format */
			foreach ($defaultFormats as $format) {
				// Gather default Author Labels and default special Fields
				$formatAuthorLabels[$format->format] = translate(['text'=>$format->authorLabel,'isPublicFacing'=>true]);
				if (!empty($format->specialFields)) {
					$specialFieldFormats[$format->format] = $format->specialFields;
				}
			}

		} else {
			$formats->find();
			while ($formats->fetch()) {
				$formatAuthorLabels[$formats->format] = translate(['text'=>$formats->authorLabel,'isPublicFacing'=>true]);
			}

			// Get Formats that use Special Fields
			$formats = new MaterialsRequestFormats();
			$formats->libraryId = $libraryId;
			$formats->whereAdd('specialFields IS NOT NULL');
			$formats->find();
			while ($formats->fetch()) {
				$specialFieldFormats[$formats->format] = $formats->specialFields;
			}
		}

		return array($formatAuthorLabels, $specialFieldFormats);
	}

	public function fetch() {
		$return = parent::fetch();
		if ($return) {
			$this->specialFields = empty($this->specialFields) ? null : explode(',', $this->specialFields);
		}
		return $return;
	}

	public function insert() {
		if (is_array($this->specialFields)) {
			$this->specialFields = implode(',', $this->specialFields);
		}else{
			$this->specialFields = '';
		}
		return parent::insert();
	}

	public function update() {
		if (is_array($this->specialFields)) {
			$this->specialFields = implode(',', $this->specialFields);
		}else{
			$this->specialFields = '';
		}
		$previous = new self();
		if ($previous->get($this->id)) {
			if ($this->format != $previous->format) {
				// Format value has changed; update all related materials requests
				$materialRequest = new MaterialsRequest();
				$materialRequest->format = $previous->format;
				$materialRequest->libraryId = $this->libraryId;
				if ($materialRequest->count() > 0){


					$materialRequest = new MaterialsRequest();
					$materialRequest->format = $this->format;
					$materialRequest->whereAdd("`libraryId` = {$this->libraryId} AND `format`='{$previous->format}'");

					if ($materialRequest->update(DB_DATAOBJECT_WHEREADD_ONLY)) {
						return parent::update();

					}
				} else {
					return parent::update();
				}
			} else {
				return parent::update();
			}
		}
	return false;
	}

	/**
	 * Deletes items from table which match current objects variables
	 *
	 * Returns the true on success
	 *
	 * for example
	 *
	 * Designed to be extended
	 *
	 * $object = new mytable();
	 * $object->ID=123;
	 * echo $object->delete(); // builds a conditon
	 *
	 * $object = new mytable();
	 * $object->whereAdd('age > 12');
	 * $object->limit(1);
	 * $object->orderBy('age DESC');
	 * $object->delete(true); // dont use object vars, use the conditions, limit and order.
	 *
	 * @param bool $useWhere (optional) If DB_DATAOBJECT_WHEREADD_ONLY is passed in then
	 *             we will build the condition only using the whereAdd's.  Default is to
	 *             build the condition only using the object parameters.
	 *
	 * @access public
	 * @return mixed Int (No. of rows affected) on success, false on failure, 0 on no data affected
	 */
	function delete($useWhere = false)
	{

		$materialRequest = new MaterialsRequest();
		$materialRequest->format = $this->format;
		$materialRequest->libraryId = $this->libraryId;
		if ($materialRequest->count() == 0){
			return parent::delete($useWhere);
		}
		return false;

	}

	public function hasSpecialFieldOption($option) {
		return is_array($this->specialFields) && in_array($option, $this->specialFields);
 }
}