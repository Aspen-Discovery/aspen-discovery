<?php /** @noinspection PhpMissingFieldTypeInspection */


class Obituary extends DataObject {
	public $__table = 'obituary'; // table name
	public $__primaryKey = 'obituaryId';
	public $obituaryId;
	public $personId;
	public $source;
	public $date;
	/** @noinspection PhpUnused */
	public $dateDay;
	/** @noinspection PhpUnused */
	public $dateMonth;
	/** @noinspection PhpUnused */
	public $dateYear;
	public $sourcePage;
	public $contents;
	/** @noinspection PhpUnused */
	public $picture;

	function id() {
		return $this->obituaryId;
	}

	function label() : string {
		return $this->source . ' ' . $this->sourcePage . ' ' . $this->date;
	}

	function getNumericColumnNames(): array {
		return [
			'dateDay',
			'dateMonth',
			'dateYear',
		];
	}

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context]) && self::$_objectStructure[$context] !== null) {
			return self::$_objectStructure[$context];
		}
		$structure = [
			[
				'property' => 'obituaryId',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id of the obituary in the database',
				'storeDb' => true,
			],
			[
				'property' => 'personId',
				'type' => 'hidden',
				'label' => 'Person Id',
				'description' => 'The id of the person this obituary is for',
				'storeDb' => true,
			],
			//array('property'=>'person', 'type'=>'method', 'label'=>'Person', 'description'=>'The person this obituary is for', 'storeDb' => false),
			[
				'property' => 'source',
				'type' => 'text',
				'maxLength' => 100,
				'label' => 'Source',
				'description' => 'The source of the obituary',
				'storeDb' => true,
			],
			[
				'property' => 'sourcePage',
				'type' => 'text',
				'maxLength' => 25,
				'label' => 'Source Page',
				'description' => 'The page where the obituary was found',
				'storeDb' => true,
			],
			[
				'property' => 'date',
				'type' => 'partialDate',
				'label' => 'Date',
				'description' => 'The date of the obituary.',
				'storeDb' => true,
				'propNameMonth' => 'dateMonth',
				'propNameDay' => 'dateDay',
				'propNameYear' => 'dateYear',
			],
			[
				'property' => 'contents',
				'type' => 'textarea',
				'rows' => 10,
				'cols' => 80,
				'label' => 'Full Text of the Obituary',
				'description' => 'The full text of the obituary.',
				'storeDb' => true,
				'hideInLists' => true,
			],
			[
				'property' => 'picture',
				'type' => 'image',
				'thumbWidth' => 65,
				'mediumWidth' => 250,
				'label' => 'Picture',
				'description' => 'A scanned image of the obituary.',
				'storeDb' => true,
				'storeSolr' => false,
				'hideInLists' => true,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	public function getPictureUrl($size = 'original') {
		if (!empty($this->picture)) {
			require_once ROOT_DIR . '/sys/Storage/StorageManager.php';
			$storageManager = StorageManager::getInstance();
			return $storageManager->getImageUrl($this->picture, 'genealogy', 'obituary', $size);
		}
		return null;
	}

	private function processPictureUpload() {
		if (empty($this->picture)) {
			return;
		}
		
		try {
			require_once ROOT_DIR . '/sys/Storage/StorageManager.php';
			$storageManager = StorageManager::getInstance();
			
			// Check if this is a new upload (temporary file path)
			if (strpos($this->picture, '/tmp/') === 0 || strpos($this->picture, sys_get_temp_dir()) === 0) {
				$originalFilename = basename($this->picture);
				$fileExtension = pathinfo($originalFilename, PATHINFO_EXTENSION);
				
				if (empty($fileExtension)) {
					throw new AspenError("Invalid file - no extension found");
				}
				
				if (empty($this->obituaryId)) {
					return; // Process after insert when obituaryId is available
				}
				
				$standardizedFilename = "Obituary_picture_" . $this->obituaryId . "." . $fileExtension;
				$imagePath = $storageManager->getImagePath('genealogy', 'obituary', 'original');
				$finalPath = $imagePath . '/' . $standardizedFilename;
				
				// Handle filename conflicts
				$counter = 1;
				$baseName = "Obituary_picture_" . $this->obituaryId;
				while (file_exists($finalPath)) {
					$standardizedFilename = $baseName . "_" . $counter . "." . $fileExtension;
					$finalPath = $imagePath . '/' . $standardizedFilename;
					$counter++;
					
					if ($counter > 1000) {
						throw new AspenError("Too many filename conflicts for Obituary {$this->obituaryId}");
					}
				}
				
				if (!move_uploaded_file($this->picture, $finalPath) && !rename($this->picture, $finalPath)) {
					throw new AspenError("Failed to move uploaded file to: " . $finalPath);
				}
				
				$this->picture = $standardizedFilename;
			}
			
			// Generate thumbnail
			if (!empty($this->picture)) {
				$this->generatePictureThumbnail($storageManager);
			}
			
		} catch (AspenError $e) {
			global $logger;
			$logger->log("Picture upload failed for Obituary {$this->obituaryId}: " . $e->getMessage(), Logger::LOG_ERROR);
			$this->picture = '';
		}
	}

	private function generatePictureThumbnail($storageManager) {
		try {
			$imagePath = $storageManager->getImagePath('genealogy', 'obituary', 'original');
			$originalFile = $imagePath . '/' . $this->picture;
			
			if (!file_exists($originalFile)) {
				throw new AspenError("Original file not found: " . $originalFile);
			}
			
			$thumbnailPath = $storageManager->getImagePath('genealogy', 'obituary', 'thumbnail');
			$thumbnailFile = $thumbnailPath . '/' . $this->picture;
			
			if (!file_exists($thumbnailFile)) {
				require_once ROOT_DIR . '/sys/Covers/CoverImageUtils.php';
				if (!resizeImage($originalFile, $thumbnailFile, 150, 150)) {
					throw new AspenError("Failed to create thumbnail for Obituary {$this->obituaryId}");
				}
			}
			
		} catch (AspenError $e) {
			global $logger;
			$logger->log("Thumbnail generation failed for Obituary {$this->obituaryId}: " . $e->getMessage(), Logger::LOG_WARNING);
		}
	}

	public function insert(string $context = '') : int|bool {
		// Handle picture upload
		$this->processPictureUpload();
		
		$ret = parent::insert();
		//Load the person this is for, and update solr
		if ($this->personId) {
			require_once ROOT_DIR . '/sys/Genealogy/Person.php';
			$person = new Person();
			$person->personId = $this->personId;
			$person->find(true);
			$person->saveToSolr();
		}
		return $ret;
	}

	public function update(string $context = '') : int|bool {
		// Handle picture upload
		$this->processPictureUpload();
		
		$ret = parent::update();
		//Load the person this is for, and update solr
		if ($this->personId) {
			require_once ROOT_DIR . '/sys/Genealogy/Person.php';
			$person = new Person();
			$person->personId = $this->personId;
			$person->find(true);
			$person->saveToSolr();
		}
		return $ret;
	}

	public function delete(bool $useWhere = false, bool $hardDelete = false) : bool|int {
		$personId = $this->personId;
		$ret = parent::delete($useWhere, $hardDelete);
		//Load the person this is for, and update solr
		if ($personId) {
			require_once ROOT_DIR . '/sys/Genealogy/Person.php';
			$person = new Person();
			$person->personId = $this->personId;
			$person->find(true);
			$person->saveToSolr();
		}
		return $ret;
	}
}