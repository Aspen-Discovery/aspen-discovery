<?php /** @noinspection PhpMissingFieldTypeInspection */

class AspenRelease extends DataObject {
	public $__table = 'aspen_release';
	public $id;
	public $name;
	public $releaseDateTest;
	public $releaseDate;

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context])) {
			return self::$_objectStructure[$context];
		}
		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'name' => [
				'property' => 'name',
				'type' => 'text',
				'label' => 'Name',
				'description' => 'The name of the release',
				'maxLength' => 10,
				'required' => true,
				'canBatchUpdate' => false,
			],
			'releaseDateTest' => [
				'property' => 'releaseDateTest',
				'type' => 'date',
				'label' => 'Release Date to Test Servers',
				'description' => 'The official release to Test and Implementation servers',
			],
			'releaseDate' => [
				'property' => 'releaseDate',
				'type' => 'date',
				'label' => 'Release Date to Production Servers',
				'description' => 'The official release to live servers',
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	static function getReleasesList() : array {
		$today = new DateTime('now');
		$today = $today->format('Y-m-d');
		$activeVersion = false;
		$release = new AspenRelease();
		$release->orderBy('releaseDate ASC');
		$release->find();
		$releases = [];
		while($release->fetch()) {
			if(!$activeVersion && $release->releaseDate < $today) {
				$activeVersion = $release->name;
			} else {
				if(version_compare($release->name, $activeVersion, '>=') && $release->releaseDate < $today) {
					$activeVersion = $release->name;
				}
			}

			$releases[$release->name]['id'] = $release->id;
			$releases[$release->name]['version'] = $release->name;
			$releases[$release->name]['name'] = $release->name;
			$releases[$release->name]['date'] = $release->releaseDate;
			$releases[$release->name]['dateTesting'] = $release->releaseDateTest;
			$releases[$release->name]['isActive'] = false;
		}

		if($activeVersion) {
			$releases[$activeVersion]['isActive'] = true;
			$releases[$activeVersion]['name'] .= ' (Active)';
		}

		usort($releases, function ($a, $b) {
			return $a['isActive'] != true;
		});

		return $releases;
	}
}