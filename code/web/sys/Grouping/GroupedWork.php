<?php

class GroupedWork extends DataObject {
	public $__table = 'grouped_work';    // table name
	public $id;
	public $permanent_id;
	public $full_title;
	public $author;
	public $grouping_category;
	public $primary_language;
	public $date_updated;
	public $referenceCover;

	/**
	 * @param bool $updatePrimaryIdentifiers Updating primary identifiers will force regrouping and is a bit slower
	 */
	public function forceReindex(bool $updatePrimaryIdentifiers = false) {
		require_once ROOT_DIR . '/sys/Indexing/GroupedWorkScheduledWorkIndex.php';
		$scheduledWork = new GroupedWorkScheduledWorkIndex();
		$scheduledWork->permanent_id = $this->permanent_id;
		$scheduledWork->indexAfter = time();
		$scheduledWork->processed = 0;
		$scheduledWork->insert();

		if ($updatePrimaryIdentifiers) {
			require_once ROOT_DIR . '/sys/Grouping/GroupedWorkPrimaryIdentifier.php';
			$groupedWorkPrimaryIdentifier = new GroupedWorkPrimaryIdentifier();
			$groupedWorkPrimaryIdentifier->grouped_work_id = $this->id;
			$groupedWorkPrimaryIdentifier->find();
			while ($groupedWorkPrimaryIdentifier->fetch()) {
				require_once ROOT_DIR . '/sys/Indexing/RecordIdentifiersToReload.php';
				$recordIdentifierToReload = new RecordIdentifiersToReload();
				$recordIdentifierToReload->type = $groupedWorkPrimaryIdentifier->type;
				$recordIdentifierToReload->identifier = $groupedWorkPrimaryIdentifier->identifier;
				$recordIdentifierToReload->insert();
			}
		}
	}

	public function getDebuggingInfo() : GroupedWorkDebugInfo {
		require_once ROOT_DIR . '/sys/Indexing/GroupedWorkDebugInfo.php';
		$indexDebugInfo = new GroupedWorkDebugInfo();
		$indexDebugInfo->permanent_id = $this->permanent_id;
		if ($indexDebugInfo->find(true)) {
			//Record has already been marked for debugging
		}else{
			//Need to create a new record
			$indexDebugInfo->processed = 0;
			$indexDebugInfo->insert();
			$this->forceReindex();
		}
		return $indexDebugInfo;
	}

	public function resetDebugging() : GroupedWorkDebugInfo {
		require_once ROOT_DIR . '/sys/Indexing/GroupedWorkDebugInfo.php';
		$indexDebugInfo = new GroupedWorkDebugInfo();
		$indexDebugInfo->permanent_id = $this->permanent_id;
		if ($indexDebugInfo->find(true)) {
			$indexDebugInfo->processed = 0;
			$indexDebugInfo->debugInfo = '';
			$indexDebugInfo->debugTime = null;
			$indexDebugInfo->update();
		}else{
			//Need to create a new record
			$indexDebugInfo->processed = 0;
			$indexDebugInfo->insert();
			$this->forceReindex();
		}
		return $indexDebugInfo;
	}
} 