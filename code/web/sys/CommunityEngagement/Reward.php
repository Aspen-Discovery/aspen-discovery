<?php /** @noinspection PhpMissingFieldTypeInspection */

class Reward extends DataObject {
	public $__table = 'ce_reward';
	public $id;
	public $name;
	public $displayName;
	public $description;
	public $rewardType;
	public $badgeImage;
	public $awardAutomatically;

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context]) && self::$_objectStructure[$context] !== null) {
			return self::$_objectStructure[$context];
		}
		global $serverName;
		$rewardType = self::getRewardType();
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
				'maxLength' => 50,
				'description' => 'A name for the campaign',
				'required' => true,
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
				'onchange' => 'AspenDiscovery.CommunityEngagement.updateRewardFields()',
			],
			'displayName' => [
				'property' => 'displayName',
				'type' => 'checkbox',
				'label' => 'Display Name',
				'description' => 'Whether or not to display the reward name to patrons',
				'default' => true,
			],
			'awardAutomatically' => [
				'property' => 'awardAutomatically',
				'type' => 'checkbox',
				'label' => 'Award Automatically',
				'description' => 'Whether or not to give this award automatically upon campaign or milestone completion',
				'default' => false,
			],
			'badgeImage' => [
				'property' => 'badgeImage',
				'type' => 'image',
				'label' => 'Image for Digital Badge',
				'description' => 'The image to use for the digital badge',
				'displayUrl' => '/CommunityEngagement/ViewImage?size=full&id=',
				'required' => false,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
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

	public function getBadgeImageUrl($size = 'full') {
		if (!empty($this->badgeImage)) {
			require_once ROOT_DIR . '/sys/Storage/StorageManager.php';
			$storageManager = StorageManager::getInstance();
			return $storageManager->getImageUrl($this->badgeImage, 'reward', null, $size);
		}
		return null;
	}

	public function uploadImage() {
		if (!empty($this->badgeImage)) {
			require_once ROOT_DIR . '/sys/Storage/StorageManager.php';
			$storageManager = StorageManager::getInstance();
			$imageFile = $storageManager->getImagePath('reward', null, 'full') . '/' . $this->badgeImage;
		}
	}

	public function insert(string $context = '') : int|bool {
		// Handle badge image upload
		$this->processBadgeUpload();
		
		$this->uploadImage();
		$this->saveTextBlockTranslations('description');
		
		return parent::insert();
	}

	public function update(string $context = '') : bool|int {
		// Handle badge image upload
		$this->processBadgeUpload();
		
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

	private function processBadgeUpload() {
		if (empty($this->badgeImage)) {
			return;
		}
		
		try {
			require_once ROOT_DIR . '/sys/Storage/StorageManager.php';
			$storageManager = StorageManager::getInstance();
			
			// Check if this is a new upload (temporary file path)
			if (strpos($this->badgeImage, '/tmp/') === 0 || strpos($this->badgeImage, sys_get_temp_dir()) === 0) {
				$originalFilename = basename($this->badgeImage);
				$fileExtension = pathinfo($originalFilename, PATHINFO_EXTENSION);
				
				if (empty($fileExtension)) {
					throw new AspenError("Invalid file - no extension found");
				}
				
				if (empty($this->id)) {
					return; // Process after insert when id is available
				}
				
				$standardizedFilename = "Reward_badge_" . $this->id . "." . $fileExtension;
				$imagePath = $storageManager->getImagePath('reward', null, 'full');
				$finalPath = $imagePath . '/' . $standardizedFilename;
				
				// Handle filename conflicts
				$counter = 1;
				$baseName = "Reward_badge_" . $this->id;
				while (file_exists($finalPath)) {
					$standardizedFilename = $baseName . "_" . $counter . "." . $fileExtension;
					$finalPath = $imagePath . '/' . $standardizedFilename;
					$counter++;
					
					if ($counter > 1000) {
						throw new AspenError("Too many filename conflicts for Reward {$this->id}");
					}
				}
				
				if (!move_uploaded_file($this->badgeImage, $finalPath) && !rename($this->badgeImage, $finalPath)) {
					throw new AspenError("Failed to move uploaded file to: " . $finalPath);
				}
				
				$this->badgeImage = $standardizedFilename;
			}
			
		} catch (AspenError $e) {
			global $logger;
			$logger->log("Badge upload failed for Reward {$this->id}: " . $e->getMessage(), Logger::LOG_ERROR);
			$this->badgeImage = '';
		}
	}

}