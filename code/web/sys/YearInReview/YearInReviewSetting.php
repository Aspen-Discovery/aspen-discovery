<?php /** @noinspection PhpMissingFieldTypeInspection */
require_once ROOT_DIR . '/sys/LibraryLocation/LibraryYearInReview.php';

class YearInReviewSetting extends DataObject {
	public $__table = 'year_in_review_settings';
	public $id;
	public $name;
	public $year;
	public $style;
	/** @noinspection PhpUnused */
	public $staffStartDate;
	/** @noinspection PhpUnused */
	public $patronStartDate;
	public $endDate;

	/** @noinspection PhpUnused */
	protected $_promoMessage;
	protected $_libraries;

	public function getNumericColumnNames(): array {
		return [
			'year',
			'staffStartDate',
			'patronStartDate',
		];
	}

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context])) {
			return self::$_objectStructure[$context];
		}

		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All System Messages'));
		$current_year = date('Y');
		$last_year = $current_year - 1;
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
				'description' => 'The name of the Year In review Settings',
			],
			'year' => [
				'property' => 'year',
				'type' => 'enum',
				'label' => 'Year',
				'values' => [
					$last_year => $last_year,
					$current_year => $current_year
				],
				'description' => 'The year for the Year in review',
			],
			//TODO: Next year, these should load dynamically based on the available styles for the year
			'style' => [
				'property' => 'style',
				'type' => 'enum',
				'label' => 'Style',
				'values' => [
					'0' => 'Modern',
					'1' => 'Festive',
					'2' => 'Holiday Wrapping',
					'3' => 'Folk',
				],
				'description' => 'The style for the Year in review',
			],
			'promoMessage' => [
				'property' => 'promoMessage',
				'type' => 'translatableTextBlock',
				'label' => 'Promo Message To Display to the patron',
				'description' => 'Provide information about the Year In Review so patrons know the functionality exists.',
				'defaultTextFile' => 'YearInReview_promoMessage.MD',
				'hideInLists' => true,
			],
			'staffStartDate' => [
				'property' => 'staffStartDate',
				'type' => 'timestamp',
				'label' => 'Start Date to Show for Staff',
				'description' => 'The first date the year in review should be shown to staff',
				'required' => true,
				'unsetLabel' => 'No start date',
			],
			'patronStartDate' => [
				'property' => 'patronStartDate',
				'type' => 'timestamp',
				'label' => 'Start Date to Show for Patrons',
				'description' => 'The first date the year in review should be shown to patrons',
				'required' => true,
				'unsetLabel' => 'No end date',
			],
			'endDate' => [
				'property' => 'endDate',
				'type' => 'timestamp',
				'label' => 'End Date to Show',
				'description' => 'The last date to show year in review',
				'readOnly' => true,
				'unsetLabel' => 'No end date',
			],
			'libraries' => [
				'property' => 'libraries',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Libraries',
				'description' => 'Define libraries that see this system message',
				'values' => $libraryList,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	public function __get($name) {
		if ($name == "libraries") {
			return $this->getLibraries();
		} else {
			return parent::__get($name);
		}
	}

	public function getLibraries(): ?array {
		if (!isset($this->_libraries) && $this->id) {
			$this->_libraries = [];
			$obj = new LibraryYearInReview();
			$obj->yearInReviewId = $this->id;
			$obj->find();
			while ($obj->fetch()) {
				$this->_libraries[$obj->libraryId] = $obj->libraryId;
			}
		}
		return $this->_libraries;
	}

	public function __set($name, $value) {
		if ($name == "libraries") {
			$this->_libraries = $value;
		} else {
			parent::__set($name, $value);
		}
	}

	/**
	 * Override the update functionality to save related objects
	 *
	 * @see DB/DB_DataObject::update()
	 */
	public function update(string $context = '') : int|bool {
		$this->__set('endDate', strtotime($this->year + 1  . '-02-01'));
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveTextBlockTranslations('promoMessage');
		}
		return $ret;
	}

	public function insert(string $context = '') : int|bool {
		$this->__set('endDate', strtotime($this->year + 1  . '-02-01'));
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveTextBlockTranslations('promoMessage');
		}
		return $ret;
	}

	public function delete(bool $useWhere = false, bool $hardDelete = false) : bool|int {
		$ret = parent::delete($useWhere, $hardDelete);
		if ($ret && !empty($this->id)) {
			$libraryYearInReview = new LibraryYearInReview();
			$libraryYearInReview->yearInReviewId = $this->id;
			$libraryYearInReview->delete(true);
		}
		return $ret;
	}

	public function saveLibraries() : void {
		if (isset ($this->_libraries) && is_array($this->_libraries)) {
			$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer Year in Review for All Libraries'));
			foreach ($libraryList as $libraryId => $displayName) {
				$obj = new LibraryYearInReview();
				$obj->yearInReviewId = $this->id;
				$obj->libraryId = $libraryId;
				if (in_array($libraryId, $this->_libraries)) {
					if (!$obj->find(true)) {
						$obj->insert();
					}
				} else {
					if ($obj->find(true)) {
						$obj->delete();
					}
				}
			}
		}
	}

	public function getSlide(User $patron, int|string $slideNumber) : array {
		$result = [
			'success' => false,
			'title' => translate([
				'text' => 'Error',
				'isPublicFacing' => true,
			]),
			'message' => translate([
				'text' => 'Unknown error loading year in review slide.',
				'isPublicFacing' => true,
			]),
		];

		//Load slide configuration for the year
		$yearInReviewResultData = $patron->getYearInReviewResultData();
		if ($yearInReviewResultData !== false) {
			$yearInReviewResult = $patron->getYearInReviewResult();
			if (!$yearInReviewResult->wrappedViewed){
				//Dismiss the user message if active
				require_once ROOT_DIR . '/sys/Account/UserMessage.php';
				$userMessage = new UserMessage();
				$userMessage->userId = $patron->id;
				$userMessage->messageType = 'yearInReview_' . $patron->getYearInReviewSetting()->year;
				$userMessage->isDismissed = 0;
				if ($userMessage->find(true)) {
					$userMessage->isDismissed = 1;
					$userMessage->update();
				}

				//User is viewing wrapped for the first time
				$yearInReviewResult->wrappedViewed = true;
				$yearInReviewResult->update();
			}
			$style = (isset($yearInReviewResultData->activeStyle) && is_numeric($yearInReviewResultData->activeStyle)) ? $yearInReviewResultData->activeStyle : $this->style;
			$configurationFile = ROOT_DIR . "/year_in_review/{$this->year}_$style.json";
			if (file_exists($configurationFile)) {
				$slideConfiguration = json_decode(file_get_contents($configurationFile));

				if ($slideNumber > 0 && $slideNumber <= $yearInReviewResultData->numSlidesToShow) {
					$slideIndex = $yearInReviewResultData->slidesToShow[$slideNumber - 1];
					$slideInfo = $slideConfiguration->slides[$slideIndex - 1];
					$result['success'] = true;
					$result['title'] = translate([
						'text' => $slideInfo->title,
						'isPublicFacing' => true,
					]);

					foreach ($slideInfo->overlay_text as $overlayText) {
						foreach ($yearInReviewResultData->userData as $field => $value) {
							if (is_string($value)){
								$overlayText->text = str_replace("{" . $field . "}", $value, $overlayText->text);
							}
						}
					}

					$result['slideConfiguration'] = $slideInfo;
					$result['numSlidesToShow'] = $yearInReviewResultData->numSlidesToShow;
					$result['modalBody'] = $this->formatSlide($slideInfo, $slideNumber);

					$modalButtons = '';
					if ($slideNumber > 1) {
						$modalButtons .= '<button type="button" class="btn btn-default" onclick="return AspenDiscovery.Account.viewYearInReview(' . $slideNumber - 1 . ')">' . translate([
								'text' => 'Previous',
								'isPublicFacing' => true,
								'inAttribute' => true,
							]) . '</button>';
					}
					if ($slideNumber < $yearInReviewResultData->numSlidesToShow) {
						$modalButtons .= '<button type="button" class="btn btn-primary" onclick="return AspenDiscovery.Account.viewYearInReview(' . $slideNumber + 1 . ')">' . translate([
								'text' => 'Next',
								'isPublicFacing' => true,
								'inAttribute' => true,
							]) . '</button>';
					}
					$result['modalButtons'] = $modalButtons;
				} else {
					$result['message'] = translate([
						'text' => 'Invalid slide number',
						'isPublicFacing' => true,
					]);
				}
			}else{
				$result['message'] = translate([
					'text' => 'Unable to find year in review configuration file',
					'isPublicFacing' => true,
				]);
			}
		}else{
			$result['message'] = translate([
				'text' => 'Unable to find year in review data',
				'isPublicFacing' => true,
			]);
		}

		return $result;
	}

	private function formatSlide(stdClass $slideInfo, int $slideNumber) : string {
		global $interface;
		$interface->assign('slideNumber', $slideNumber);
		$interface->assign('slideInfo', $slideInfo);
		return $interface->fetch('YearInReview/slide.tpl');
	}

	public function getSlideImage(User $patron, int|string $slideNumber) : bool {
		//Load slide configuration for the year
		$gotImage = true;
		$userYearInResults = $patron->getYearInReviewResultData();
		if ($userYearInResults !== false) {
			$style = (isset($userYearInResults->activeStyle) && is_numeric($userYearInResults->activeStyle)) ? $userYearInResults->activeStyle : $this->style;
			$configurationFile = ROOT_DIR . "/year_in_review/{$this->year}_$style.json";
			if (file_exists($configurationFile)) {
				$slideConfiguration = json_decode(file_get_contents($configurationFile));

				if ($slideNumber > 0 && $slideNumber <= $userYearInResults->numSlidesToShow) {
					$slideIndex = $userYearInResults->slidesToShow[$slideNumber - 1];
					$slideInfo = $slideConfiguration->slides[$slideIndex - 1];

					foreach ($slideInfo->overlay_text as $overlayText) {
						foreach ($userYearInResults->userData as $field => $value) {
							if (!is_array($value)) {
								$overlayText->text = str_replace("{" . $field . "}", $value, $overlayText->text);
							}
						}
					}

					$gotImage = $this->createSlideImage($slideInfo, $userYearInResults->userData);
				}
			}
		}

		return $gotImage;
	}

	private function createSlideImage(stdClass $slideInfo, stdClass $userData) : ?string {
		$gotImage = false;
		if (empty($slideInfo->overlay_text) && empty($slideInfo->overlay_images)) {
			//This slide is not dynamic, we just return the static contents
		}else{
			require_once ROOT_DIR . '/sys/Covers/CoverImageUtils.php';

			//Get the background image for the slide
			$backgroundImageFile = ROOT_DIR . '/year_in_review/images/' . $slideInfo->background;
			$backgroundImageFile = realpath($backgroundImageFile);
			if (str_ends_with($backgroundImageFile, '.png')) {
				$backgroundImage = imagecreatefrompng($backgroundImageFile);
			}elseif (str_ends_with($backgroundImageFile, '.gif')) {
				$backgroundImage = imagecreatefromgif($backgroundImageFile);
			}
			$backgroundImageInfo = getimagesize($backgroundImageFile);
			$backgroundWidth = $backgroundImageInfo[0];
			$backgroundHeight = $backgroundImageInfo[1];
			//Create a canvas for the slide
			$slideCanvas = imagecreatetruecolor($backgroundWidth, $backgroundHeight);
			//Display the background to the slide
			imagecopy($slideCanvas, $backgroundImage, 0, 0, 0, 0, $backgroundWidth, $backgroundHeight);

			if (empty($overlayText->font)) {
				$font = ROOT_DIR . '/fonts/JosefinSans-Bold.ttf';
			}else{
				$font = ROOT_DIR . "/fonts/$overlayText->font.ttf";
			}

			$white = imagecolorallocate($slideCanvas, 255, 255, 255);
			$black = imagecolorallocate($slideCanvas, 0, 0, 0);
			$green = imagecolorallocate($slideCanvas, 53, 114, 72); // #357248

			//Add overlay text to the image
			foreach ($slideInfo->overlay_text as $overlayText) {
				$overlayWidth = $overlayText->width;
				if (str_ends_with($overlayWidth,'%')) {
					$percent = str_replace('%', '', $overlayWidth) / 100;
					$textWidth = $backgroundWidth * $percent;
				}else{
					$textWidth = $overlayWidth;
				}
				$fontSize = $overlayText->font_size;
				if (str_ends_with($fontSize,'em')) {
					$fontSize = str_replace('em', '', $fontSize) * 16;
				}
				$left = $overlayText->left;
				if (str_ends_with($left,'%')) {
					$percent = str_replace('%', '', $left) / 100;
					$left = $backgroundWidth * $percent;
				}elseif (str_ends_with($left,'px')) {
					$left = str_replace('px', '', $left);
				}
				$top = $overlayText->top;
				if (str_ends_with($top,'%')) {
					$percent = str_replace('%', '', $top) / 100;
					$top = $backgroundWidth * $percent;
				}elseif (str_ends_with($top,'px')) {
					$top = str_replace('px', '', $top);
				}

				if ($overlayText->color == 'white') {
					$color = $white;
				}elseif ($overlayText->color == 'green') {
					$color = $green;	
				}else{
					$color = $black;
				}

				if (!empty($overlayText->allCaps)) {
					$overlayText->text = strtoupper($overlayText->text);
				}

				[
					,
					$lines,
				] = wrapTextForDisplay($font, $overlayText->text, $fontSize, $fontSize * .2, $textWidth);
				if ($overlayText->align == 'center') {
					addCenteredWrappedTextToImage($slideCanvas, $font, $lines, $fontSize, $fontSize * .2, $left, $top, $textWidth, $color);
				}else{
					addWrappedTextToImage($slideCanvas, $font, $lines, $fontSize, $fontSize * .2, $left, $top, $color);
				}
			}

			if (!empty($slideInfo->overlay_images)) {
				foreach ($slideInfo->overlay_images as $overlayImage) {
					require_once ROOT_DIR . '/sys/Covers/BookCoverProcessor.php';
					require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';

					$sourceImage = null;
					$recordDriver = null;
					if ($overlayImage->source == 'recommendation_0' && !empty($userData->recommendationIds[0])){
						$recordDriver = new GroupedWorkDriver($userData->recommendationIds[0]);
					}elseif ($overlayImage->source == 'recommendation_1' && !empty($userData->recommendationIds[1])){
						$recordDriver = new GroupedWorkDriver($userData->recommendationIds[1]);
					}elseif ($overlayImage->source == 'recommendation_2' && !empty($userData->recommendationIds[2])){
						$recordDriver = new GroupedWorkDriver($userData->recommendationIds[2]);
					}
					if (!empty($recordDriver)){
						$coverUrl = $recordDriver->getBookcoverUrl('medium', true);
						$coverUrl = str_replace(' ', '%20', $coverUrl);
						if (!empty($coverUrl)){
							$coverImage = imagecreatefromstring(file_get_contents($coverUrl));
							if ($coverImage !== false){
								$coverWidth = imagesx($coverImage);
								$coverHeight = imagesy($coverImage);

								$left = $overlayImage->left;
								if (str_ends_with($left,'%')) {
									$percent = str_replace('%', '', $left) / 100;
									$left = $backgroundWidth * $percent;
								}elseif (str_ends_with($left,'px')) {
									$left = str_replace('px', '', $left);
								}
								$top = $overlayImage->top;
								if (str_ends_with($top,'%')) {
									$percent = str_replace('%', '', $top) / 100;
									$top = $backgroundWidth * $percent;
								}elseif (str_ends_with($top,'px')) {
									$top = str_replace('px', '', $top);
								}

								$overlayWidth = $overlayImage->width;
								if (str_ends_with($overlayWidth,'%')) {
									$percent = str_replace('%', '', $overlayWidth) / 100;
									$newWidth = $backgroundWidth * $percent;
								}else{
									$newWidth = $overlayWidth;
								}

								$maxDimension = (int)$newWidth;
								if ($coverWidth > $coverHeight) {
									$newWidth = $maxDimension;
									$newHeight = (int)floor($coverHeight * ($maxDimension / $coverWidth));
								} else {
									$newHeight = $maxDimension;
									$newWidth = (int)floor($coverWidth * ($maxDimension / $coverHeight));
								}

								imagecopyresampled($slideCanvas, $coverImage, $left, $top, 0, 0, $newWidth, $newHeight, $coverWidth, $coverHeight);
							}
						}
					}
				}
			}

			//Output the image to the browser
			if (str_ends_with($backgroundImageFile, '.png')) {
				imagepng($slideCanvas);
			}elseif (str_ends_with($backgroundImageFile, '.gif')) {
				imagegif($slideCanvas);
			}
			imagedestroy($slideCanvas);
			$gotImage = true;
		}

		return $gotImage;
	}
}
