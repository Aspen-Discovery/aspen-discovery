<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

/**
 * Represents a group of mutually exclusive permissions.
 *
 * @property int $id The primary key.
 * @property string $groupKey Unique identifier for the permission group.
 * @property string $sectionName The section under which this group appears.
 * @property string $label The display label for the dropdown.
 * @property string $description Helper text shown under the dropdown label.
 */
class PermissionGroup extends DataObject {
	public $__table = 'permission_groups';
	public $__primaryKey = 'id';

	public $id;
	public $groupKey;
	public $sectionName;
	public $label;
	public $description;

	static function getObjectStructure($context = ''): array {
		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'ID',
			],
			'groupKey' => [
				'property' => 'groupKey',
				'type' => 'text',
				'label' => 'Group Key',
				'description' => 'Unique key for this permission group.',
			],
			'sectionName' => [
				'property' => 'sectionName',
				'type' => 'text',
				'label' => 'Section Name',
				'description' => 'Name of the permission section to which this group belongs.',
			],
			'label' => [
				'property' => 'label',
				'type' => 'text',
				'label' => 'Label',
				'description' => 'Display label for the dropdown.',
			],
			'description' => [
				'property' => 'description',
				'type' => 'textarea',
				'label' => 'Description',
				'description' => 'Helper text displayed under the dropdown label.',
			],
		];
	}
}