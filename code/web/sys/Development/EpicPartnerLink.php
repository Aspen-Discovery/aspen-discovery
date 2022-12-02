<?php

class EpicPartnerLink extends DataObject {
	public $__table = 'development_epic_partner_link';
	public $id;
	public $partnerId;
	public $epicId;

	static function getObjectStructure(): array {
		$epicList = [];
		require_once ROOT_DIR . '/sys/Development/DevelopmentEpic.php';
		$epic = new DevelopmentEpic();
		$epic->whereAdd('privateStatus NOT IN (9, 10)');

		$epic->orderBy('name ASC');
		$epic->find();
		while ($epic->fetch()) {
			$epicList[$epic->id] = $epic->name;
		}

		$partnerList = [];
		require_once ROOT_DIR . '/sys/Greenhouse/AspenSite.php';
		$partner = new AspenSite();
		$partner->siteType = 0;
		$partner->orderBy('name asc');
		$partner->find();
		while ($partner->fetch()) {
			$partnerList[$partner->id] = $partner->name;
		}

		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'partnerId' => [
				'property' => 'partnerId',
				'type' => 'enum',
				'values' => $partnerList,
				'label' => 'Partner',
				'description' => 'The partner who requested the task',
				'required' => true,
			],
			'epicId' => [
				'property' => 'epicId',
				'type' => 'enum',
				'values' => $epicList,
				'label' => 'Epic',
				'description' => 'The epic requested by the partner',
				'required' => true,
			],
		];
	}
}