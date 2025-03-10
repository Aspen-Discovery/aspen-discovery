<?php

class Reward extends DataObject {
	public $__table = 'ce_reward';
	public $id;
	public $name;
	public $displayName;
	public $description;
	public $rewardType;
	public $badgeImage;

	public static function getObjectStructure($context = ''):array {
		global $serverName;
		$rewardType = self::getRewardType();
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
				'label' => 'Name',
				'maxLength' => 50,
				'description' => 'A name for the campaign',
				'required' => true,
			],
			'displayName' => [
				'property' => 'displayName',
				'type' => 'checkbox',
				'label' => 'Display Name',
				'description' => 'Whether or not to display the reward name to patrons',
				'default' => true,
			],
			'description' => [
				'property' => 'description',
				'type' => 'translatableTextBlock',
				'label' => 'Description',
				'maxLength' => 255,
				'description' => 'A description of the campaign',
				'defaultTextFile' => 'Reward_description.MD',
				'hideInLists'=> true,
			],
			'rewardType' => [
				'property' => 'rewardType',
				'type' =>'enum',
				'label' => 'Reward Type',
				'description' => 'The type of reward',
				'values' => $rewardType,
			],
			'badgeImage' => [
				'property' => 'badgeImage',
				'type' => 'image',
				'label' => 'Image for Digital Badge',
				'description' => 'The image to use for the digital badge',
				'path' => '/data/aspen-discovery/' . $serverName . '/uploads/reward_image/full',
				'displayUrl' => '/CommunityEngagement/ViewImage?size=full&id=',
				'required' => false,
			],
		];
	}

	public function getDisplayUrl(): string {
		$size = 'full';
		if (empty($this->id)) {
			return  ' ';
		}
		return '/CommunityEngagement/ViewImage?size=' .$size . '&id=' . $this->id;
	}

	public function getShareUrl(): string {
		global $serverName;
		$size = 'full';
		return 'http://' . $serverName . '/CommunityEngagement/ViewImage?size=' . $size . '&id=' . $this->id;
	}

	public function uploadImage() {
		if (!empty($this->badgeImage)) {
			global $serverName;
			$imageFile = '/data/aspen-discovery/' . $serverName . '/uploads/reward_image/full/' . $this->badgeImage;
		}
	}

	function insert($context = ' ') {
			$this->uploadImage();
			$this->saveTextBlockTranslations('description');
		
		return parent::insert();
	}

	function update($context = ' ') {
			$this->uploadImage();
			$this->saveTextBlockTranslations('description');
		
		return parent::update();
	}

	public static function getRewardType () {
		return [
			0 => 'Physical',
			1 => 'Digital',
		];
	}

	/**
	 * @return array
	 */
	public static function getRewardList(): array {
		$reward = new Reward();
		$rewardList = [];

		if ($reward->find()) {
			while ($reward->fetch()) {
				$rewardList[$reward->id] = $reward->name;
			}
		}
		return $rewardList;
	}


}