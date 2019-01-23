<?php
/**
 * Record Driver to handle loading data for Hoopla Records
 *
 * @category Pika
 * @author Mark Noble <mark@marmot.org>
 * Date: 12/18/14
 * Time: 10:50 AM
 */

require_once ROOT_DIR . '/RecordDrivers/ExternalEContentDriver.php';
class SideLoadedRecord extends ExternalEContentDriver {
	/**
	 * Constructor.  We build the object using data from the Side-loaded records stored on disk.
	 * Will be similar to a MarcRecord with slightly different functionality
	 *
	 * @param array|File_MARC_Record|string $record
	 * @param  GroupedWork $groupedWork;
	 * @access  public
	 */
	public function __construct($record, $groupedWork = null) {
		parent::__construct($record, $groupedWork);
	}

	function getRecordUrl(){
		global $configArray;
		$recordId = $this->getUniqueID();

		/** @var IndexingProfile[] $indexingProfiles */
		global $indexingProfiles;
		$indexingProfile = $indexingProfiles[$this->profileType];

		return $configArray['Site']['path'] . "/{$indexingProfile->recordUrlComponent}/$recordId";
	}

	public function getMoreDetailsOptions(){
		global $interface;

		$isbn = $this->getCleanISBN();

		//Load table of contents
		$tableOfContents = $this->getTOC();
		$interface->assign('tableOfContents', $tableOfContents);

		//Get Related Records to make sure we initialize items
		$recordInfo = $this->getGroupedWorkDriver()->getRelatedRecord($this->getIdWithSource());

		//Get copies for the record
		$this->assignCopiesInformation();

		$interface->assign('items', $recordInfo['itemSummary']);

		//Load more details options
		$moreDetailsOptions = $this->getBaseMoreDetailsOptions($isbn);

		$moreDetailsOptions['copies'] = array(
				'label' => 'Copies',
				'body' => $interface->fetch('ExternalEContent/view-items.tpl'),
				'openByDefault' => true
		);

		$moreDetailsOptions['moreDetails'] = array(
				'label' => 'More Details',
				'body' => $interface->fetch('ExternalEContent/view-more-details.tpl'),
		);

		$this->loadSubjects();
		$moreDetailsOptions['subjects'] = array(
				'label' => 'Subjects',
				'body' => $interface->fetch('Record/view-subjects.tpl'),
		);
		$moreDetailsOptions['citations'] = array(
				'label' => 'Citations',
				'body' => $interface->fetch('Record/cite.tpl'),
		);
		if ($interface->getVariable('showStaffView')){
			$moreDetailsOptions['staff'] = array(
					'label' => 'Staff View',
					'body' => $interface->fetch($this->getStaffView()),
			);
		}

		return $this->filterAndSortMoreDetailsOptions($moreDetailsOptions);
	}

	protected function getRecordType(){
		return $this->profileType;
	}
}