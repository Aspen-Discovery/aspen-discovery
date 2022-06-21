<?php


class OMDBSetting extends DataObject
{
	public $__table = 'omdb_settings';    // table name
	public $id;
	public $apiKey;
	public $fetchCoversWithoutDates;

	public static function getObjectStructure() : array
	{
		return [
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id'),
			'apiKey' => array('property' => 'apiKey', 'type' => 'storedPassword', 'label' => 'API Key', 'description' => 'The Key for the API', 'maxLength' => '10', 'hideInLists' => true),
			'fetchCoversWithoutDates' => ['property' => 'fetchCoversWithoutDates', 'type'=>'checkbox', 'label'=>'Fetch Covers Without Dates', 'description'=>'If Unchecked, covers must match the date and title of the cover for the cover to be shown.  This can cause fewer covers to be shown', 'default'=> 1],
		];
	}

	/**
	 * @return int|bool
	 */
	public function update()
	{
		$result = parent::update();
		if (in_array('fetchCoversWithoutDates', $this->_changedFields)){
			require_once ROOT_DIR . '/sys/Covers/BookCoverInfo.php';
			$covers = new BookCoverInfo();
			$covers->reloadOMDBCovers();
		}
		return $result;
	}
}