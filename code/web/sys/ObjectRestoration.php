<?php
/** @noinspection PhpMissingFieldTypeInspection */


/**
 * Virtual DataObject used by the Object Restoration admin tool.
 *
 * Each instance represents ONE soft-deleted row coming from any managed class.
 * There is no backing database table; simply store properties in-memory so
 * ObjectEditor/templates can treat the item like a normal DataObject.
 */
class ObjectRestoration extends DataObject {
	// No physical table; placeholder to satisfy DataObject’s requirement that the property exist.
	public $__table = 'object_restoration_virtual';
	public $__primaryKey = 'compositeId';

	public $compositeId;
	public $objectType;
	public $id;
	public $title = '';
	public $userInfo = '';
	public $deletedOn = '';
	public $deletedBy = '';
	public $daysRemaining = 0;

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context])) {
			return self::$_objectStructure[$context];
		}
		$structure = [
			'compositeId' => [
				'property' => 'compositeId',
				'type' => 'label',
				'label' => 'Key',
				'hideInLists' => true,
			],
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'ID',
			],
			'title' => [
				'property' => 'title',
				'type' => 'label',
				'label' => 'Title / Name',
			],
			'userInfo' => [
				'property' => 'userInfo',
				'type' => 'label',
				'label' => 'Created By',
			],
			'objectType' => [
				'property' => 'objectType',
				'type' => 'label',
				'label' => 'Type',
			],
			'deletedOn' => [
				'property' => 'deletedOn',
				'type' => 'timestamp',
				'label' => 'Deleted',
			],
			'deletedBy' => [
				'property' => 'deletedBy',
				'type' => 'label',
				'label' => 'Deleted By',
			],
			'daysRemaining' => [
				'property' => 'daysRemaining',
				'type' => 'integer',
				'label' => 'Days Remaining',
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	public function getUniquenessFields(): array { return ['compositeId']; }
	public function find($fetchFirst = false, $requireOneMatchToReturn = false): bool { return false; }
	public function fetch(): bool|DataObject|null { return null; }
	public function insert(string $context = ''): bool { return false; }
	public function update(string $context = ''): bool { return false; }
	public function delete(bool $useWhere = false, bool $hardDelete = false) : bool|int { return 0; }
	public function canActiveUserEdit() : bool { return false; }
	public function canActiveUserDelete() : bool { return false; }

	public function getAdditionalListActions(): array {
		$actions = [];
		$basePath = '/Admin/ObjectRestorations';
		$composite = $this->compositeId;

		$actions[] = [
			'text' => 'Restore',
			'url' => "$basePath?objectAction=restore&id=$composite",
			'class' => 'btn-primary',
		];
		$actions[] = [
			'text' => 'History',
			'url' => "$basePath?objectAction=history&id=$composite",
		];
		$deleteUrl = "$basePath?objectAction=hardDeleteSingle&id=$composite";
		// Use HTML-escaped double quotes so the snippet can be embedded inside a single-quoted parameter.
		$escapedDeleteUrl = htmlspecialchars($deleteUrl, ENT_QUOTES);
		$confirmJs = "window.location.href=&quot;" . $escapedDeleteUrl . "&quot;";
		$actions[] = [
			'text' => 'Delete',
			'url' => $deleteUrl,
			'class' => 'btn-danger',
			'onclick' => "AspenDiscovery.confirm('Permanently Delete', 'Are you sure you want to permanently delete this object? This action cannot be undone.', 'Delete', 'Cancel', true, '$confirmJs', 'btn-danger'); return false;",
		];
		return $actions;
	}
}