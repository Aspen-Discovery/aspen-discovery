<?php


class GroupedWorkDisplayInfo extends DataObject {
	public $__table = 'grouped_work_display_info';
	public $id;
	public $permanent_id;
	public $title;
	public $author;
	public $seriesName;
	public $seriesDisplayOrder;
	public $addedBy;
	public $dateAdded;

	public function insert($context = '') {
		if (empty($this->seriesDisplayOrder)) {
			$this->seriesDisplayOrder = 0;
		}
		require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
		$groupedWork = new GroupedWork();
		$groupedWork->permanent_id = $this->permanent_id;
		if ($groupedWork->find(true)) {
			$groupedWork->forceReindex(true);
		}
		return parent::insert();
	}

	public function update($context = '') {
		if (empty($this->seriesDisplayOrder)) {
			$this->seriesDisplayOrder = 0;
		}
		require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
		$groupedWork = new GroupedWork();
		$groupedWork->permanent_id = $this->permanent_id;
		if ($groupedWork->find(true)) {
			$groupedWork->forceReindex(true);
		}
		return parent::update();
	}

	public function delete($useWhere = false) {
		require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
		$groupedWork = new GroupedWork();
		$groupedWork->permanent_id = $this->permanent_id;
		if ($groupedWork->find(true)) {
			$groupedWork->forceReindex(true);
		}
		return parent::delete($useWhere);
	}
}