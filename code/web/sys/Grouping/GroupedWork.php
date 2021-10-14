<?php

class GroupedWork extends DataObject {
	public $__table = 'grouped_work';    // table name
	public $id;
	public $permanent_id;
	public $full_title;
	public $author;
	public $grouping_category;
	public $date_updated;
	public $referenceCover;

	/**
	 * @param bool $updatePrimaryIdentifiers Updating primrary identifiers will force regrouping and is a bit slower
	 */
	public function forceReindex($updatePrimaryIdentifiers = false)
	{
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
} 