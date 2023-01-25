<?php


class AspenLiDABuild extends DataObject {
	public $__table = 'aspen_lida_build';
	public $id;
	public $buildId;
	public $status;
	public $appId;
	public $name;
	public $version;
	public $buildVersion;
	public $channel;
	public $gitCommitHash;
	public $buildMessage;
	public $error;
	public $errorMessage;
	public $createdAt;
	public $completedAt;
	public $updatedAt;
	public $platform;
	public $artifact;
	public $isSupported;
	public $isEASUpdate;
	public $updateId;
	public $patch;
	public $updateCreated;
	public $isSubmitted;
	public $storeIdentifier;
	public $storeUrl;

	public static function getObjectStructure($context = ''): array {
		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'name' => [
				'property' => 'name',
				'type' => 'text',
				'label' => 'Application Name',
				'description' => 'The name of the application',
				'readOnly' => true,
			],
			'version' => [
				'property' => 'version',
				'type' => 'text',
				'label' => 'Version',
				'description' => 'The version of the app',
				'readOnly' => true,
			],
			'buildVersion' => [
				'property' => 'buildVersion',
				'type' => 'text',
				'label' => 'Build',
				'description' => 'The build version of the app',
				'readOnly' => true,
			],
			'channel' => [
				'property' => 'channel',
				'type' => 'text',
				'label' => 'Channel',
				'description' => 'The channel or build profile used',
				'readOnly' => true,
			],
			'platform' => [
				'property' => 'platform',
				'type' => 'text',
				'label' => 'Platform',
				'description' => 'The platform the build was created for',
				'readOnly' => true,
			],
			'buildId' => [
				'property' => 'buildId',
				'type' => 'text',
				'label' => 'Build Id',
				'description' => 'Provided by Expo to identify the build',
				'hideInLists' => true,
				'readOnly' => true,
			],
			'createdAt' => [
				'property' => 'createdAt',
				'type' => 'timestamp',
				'label' => 'Created at',
				'description' => 'When the build was created',
				'hideInLists' => true,
				'readOnly' => true,
			],
			'completedAt' => [
				'property' => 'completedAt',
				'type' => 'timestamp',
				'label' => 'Completed at',
				'description' => 'When the build was completed',
				'readOnly' => true,
			],
			'gitCommitHash' => [
				'property' => 'gitCommitHash',
				'type' => 'text',
				'label' => 'Git Commit Hash',
				'description' => 'The git commit hash that the build was created from',
				'hideInLists' => true,
				'readOnly' => true,
			],
			'isSupported' => [
				'property' => 'isSupported',
				'type' => 'checkbox',
				'label' => 'Supported?',
				'description' => 'Whether or not build is still supported',
			],
			'isEASUpdate' => [
				'property' => 'isEASUpdate',
				'type' => 'checkbox',
				'label' => 'Patch Update?',
				'description' => 'Whether or not this was a patch update using EAS Update',
			],
			'isSubmitted' => [
				'property' => 'isSubmitted',
				'type' => 'checkbox',
				'label' => 'Submitted to app store?',
				'description' => 'Whether or not build has been submitted to the applicable app store',
			],
			'storeIdentifier' => [
				'property' => 'storeIdentifier',
				'type' => 'text',
				'label' => 'Store Identifier',
				'description' => "The identifying id for the app to it's applicable app store",
				'hideInLists' => true,
				'readOnly' => true,
			],
			'storeUrl' => [
				'property' => 'storeUrl',
				'type' => 'text',
				'label' => 'Store Url',
				'description' => 'The url to access the app listing',
				'hideInLists' => true,
				'readOnly' => true,
			],
			'updateId' => [
				'property' => 'updateId',
				'type' => 'text',
				'label' => 'Patch Id',
				'description' => 'Provided by Expo to identify the update',
				'hideInLists' => true,
				'readOnly' => true,
			],
			'patch' => [
				'property' => 'patch',
				'type' => 'text',
				'label' => 'Patch',
				'description' => 'The patch version',
				'readOnly' => true,
			],
			'updateCreated' => [
				'property' => 'updateCreated',
				'type' => 'timestamp',
				'label' => 'Patch released at',
				'description' => 'When the patch was released',
				'hideInLists' => true,
				'readOnly' => true,
			],
			'buildMessage' => [
				'property' => 'buildMessage',
				'type' => 'textarea',
				'label' => 'Patch Comment',
				'description' => 'Comment provided when creating the patch',
				'hideInLists' => true,
				'readOnly' => true,
			],
		];
	}

	public function getNumericColumnNames(): array {
		return [
			'id',
			'error',
			'isSupported',
			'isEASUpdate',
			'isSubmitted'
		];
	}

	public function getUniquenessFields(): array {
		return [
			'buildId',
			'updateId',
		];
	}

	public function getBuildInformation(): array {
		return $this->toArray();
	}

}