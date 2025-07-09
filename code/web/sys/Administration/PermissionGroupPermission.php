<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

/**
 * Maps a PermissionGroup to an individual Permission.
 * Defines the many-to-many relationship between
 * permission_groups and permissions, determining which
 * permissions belong in each mutually exclusive group.
 *
 * @property int $id The primary key.
 * @property int $groupId Foreign key to permission_groups.id.
 * @property int $permissionId Foreign key to permissions.id.
 */
class PermissionGroupPermission extends DataObject {
	public $__table = 'permission_group_permissions';
	public $__primaryKey = 'id';

	public $id;
	public $groupId;
	public $permissionId;

	static function getObjectStructure($context = ''): array {
		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'ID',
			],
			'groupId' => [
				'property' => 'groupId',
				'type' => 'integer',
				'label' => 'Group ID',
				'description' => 'The permission group ID.',
			],
			'permissionId' => [
				'property' => 'permissionId',
				'type' => 'integer',
				'label' => 'Permission ID',
				'description' => 'The ID of the permission in this group.',
			],
		];
	}
}