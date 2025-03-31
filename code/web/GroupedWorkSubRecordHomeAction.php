<?php


abstract class GroupedWorkSubRecordHomeAction extends Action {
	/** @var GroupedWorkSubDriver */
	protected $recordDriver;
	protected $lastSearch;
	protected $id;

	public function __construct() {
		parent::__construct(false);
		global $interface;
		if (isset($_REQUEST['searchId'])) {
			$_SESSION['searchId'] = $_REQUEST['searchId'];
			$interface->assign('searchId', $_SESSION['searchId']);
		} elseif (isset($_SESSION['searchId'])) {
			$interface->assign('searchId', $_SESSION['searchId']);
		}

		$this->id = strip_tags($_REQUEST['id']);
		$interface->assign('id', $this->id);

		$this->loadRecordDriver($this->id);
	}

	abstract function loadRecordDriver($id);

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		if (!empty($this->lastSearch)) {
			$breadcrumbs[] = new Breadcrumb($this->lastSearch, 'Catalog Search Results');
		}
		if (!empty($this->recordDriver) && $this->recordDriver->isValid()) {
			if ($this->recordDriver->getGroupedWorkDriver() != null && $this->recordDriver->getGroupedWorkDriver()->isValid()) {
				$breadcrumbs[] = new Breadcrumb($this->recordDriver->getGroupedWorkDriver()->getRecordUrl(), $this->recordDriver->getGroupedWorkDriver()->getTitle(), false);
			}
			$breadcrumbs[] = new Breadcrumb('', $this->recordDriver->getPrimaryFormat(), false);
		}
		return $breadcrumbs;
	}

	function loadCitations() {
		global $interface;

		$citationCount = 0;
		if (!(empty($this->recordDriver))) {
			$formats = $this->recordDriver->getCitationFormats();
			foreach ($formats as $current) {
				$interface->assign(strtolower($current), $this->recordDriver->getCitation($current));
				$citationCount++;
			}
		}
		$interface->assign('citationCount', $citationCount);
	}
}