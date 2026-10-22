<?php /** @noinspection PhpMissingFieldTypeInspection */

require_once ROOT_DIR . '/sys/DB/LibraryLocationLinkedObject.php';
require_once ROOT_DIR . '/sys/LocalEnrichment/PlacardTrigger.php';
require_once ROOT_DIR . '/sys/LocalEnrichment/PlacardLibrary.php';
require_once ROOT_DIR . '/sys/LocalEnrichment/PlacardLocation.php';
require_once ROOT_DIR . '/sys/LocalEnrichment/PlacardLanguage.php';

class Placard extends DB_LibraryLocationLinkedObject {
	public $__table = 'placards';
	public $id;
	public $title;
	public $body;
	public $image;
	public /** @noinspection PhpUnused */
		$altText;
	public $link;
	public $css;
	public /** @noinspection PhpUnused */
		$dismissable;
	public $startDate;
	public $endDate;
	public $sourceType;
	public $sourceId;
	public $generatedFromSource;
	public $isCustomized;
	public $deleted;
	public $dateDeleted;
	public $deletedBy;

	/** @var PlacardTrigger[] */
	protected $_triggers;
	/** @var int[] */
	protected $_languages;
	/** @var array */
	protected $_debugCandidates = [];

	protected $_libraries;
	protected $_locations;

	public function getUniquenessFields(): array {
		return ['title'];
	}

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context])) {
			return self::$_objectStructure[$context];
		}

		$placardTriggerStructure = PlacardTrigger::getObjectStructure($context);
		unset($placardTriggerStructure['placardId']);

		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Placards'));
		$locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer All Placards'));
		$languageList = Language::getLanguageList();

		$sourceOptions = [
			'none' => 'None',
			'web_resource' => 'Web Resource',
		];

		/** @noinspection HtmlRequiredAltAttribute */
		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'title' => [
				'property' => 'title',
				'type' => 'text',
				'label' => 'Title',
				'description' => 'The title of the placard',
			],
			'startDate' => [
				'property' => 'startDate',
				'type' => 'timestamp',
				'label' => 'Start Date to Show',
				'description' => 'The first date the placard should be shown, leave blank to always show',
				'unsetLabel' => 'No start date',
			],
			'endDate' => [
				'property' => 'endDate',
				'type' => 'timestamp',
				'label' => 'End Date to Show',
				'description' => 'The end date the placard should be shown, leave blank to always show',
				'unsetLabel' => 'No end date',
			],
			'dismissable' => [
				'property' => 'dismissable',
				'type' => 'checkbox',
				'label' => 'Dismissable',
				'description' => 'Whether or not a user can dismiss the placard',
			],
			'body' => [
				'property' => 'body',
				'type' => 'html',
				'label' => 'Body',
				'description' => 'The body of the placard',
				'allowableTags' => '<p><em><i><strong><b><a><ul><ol><li><h1><h2><h3><h4><h5><h6><h7><pre><code><hr><table><tbody><tr><th><td><caption><img><br><div><span><sub><sup>',
				'hideInLists' => true,
			],
			'css' => [
				'property' => 'css',
				'type' => 'textarea',
				'label' => 'CSS',
				'description' => 'Additional styling to apply to the placard',
				'hideInLists' => true,
			],
			'image' => [
				'property' => 'image',
				'type' => 'image',
				'label' => 'Image (Max Width: 800px)',
				'description' => 'Image to use for the placard',
				'note' => 'Placard image maximum height is configured under Themes for selected libraries and locations. By default, there is no height constraint.',
				'required' => false,
				'maxWidth' => 800,
				'hideInLists' => true,
			],
			'altText' => [
				'property' => 'altText',
				'type' => 'text',
				'label' => 'Alt Text',
				'description' => 'Alt Text for the image',
				'maxLength' => 500,
				'hideInLists' => true,
			],
			'link' => [
				'property' => 'link',
				'type' => 'url',
				'label' => 'Link',
				'description' => 'An optional link when clicking on the placard (or link in the placard)',
				'hideInLists' => true,
			],
			'triggers' => [
				'property' => 'triggers',
				'type' => 'oneToMany',
				'label' => 'Triggers',
				'description' => 'Trigger words that will cause the placard to display',
				'keyThis' => 'id',
				'keyOther' => 'placardId',
				'subObjectType' => 'PlacardTrigger',
				'structure' => $placardTriggerStructure,
				'sortable' => false,
				'storeDb' => true,
				'allowEdit' => false,
				'canEdit' => false,
				'canAddNew' => true,
				'canDelete' => true,
			],
			'sourceType' => [
				'property' => 'sourceType',
				'type' => 'enum',
				'values' => $sourceOptions,
				'label' => 'Source Type',
				'description' => 'Source type for the content of cell',
				'onchange' => 'return AspenDiscovery.WebBuilder.getSourceValuesForPlacard();',
			],
			'sourceId' => [
				'property' => 'sourceId',
				'type' => 'enum',
				'values' => [],
				'label' => 'Source Id',
				'description' => 'Source for the content of placard',
			],
			'languages' => [
				'property' => 'languages',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Languages',
				'description' => 'Define languages that use this placard',
				'values' => $languageList,
				'hideInLists' => true,
			],
			'libraries' => [
				'property' => 'libraries',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Libraries',
				'description' => 'Define libraries that see this placard',
				'values' => $libraryList,
				'hideInLists' => false,
			],
			'locations' => [
				'property' => 'locations',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Locations',
				'description' => 'Define locations that use this placard',
				'values' => $locationList,
				'hideInLists' => false,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	/**
	 * @return ?int[]
	 */
	public function getLibraries(): ?array {
		if (!isset($this->_libraries) && $this->id) {
			$this->_libraries = [];
			$obj = new PlacardLibrary();
			$obj->placardId = $this->id;
			$obj->find();
			while ($obj->fetch()) {
				$this->_libraries[$obj->libraryId] = $obj->libraryId;
			}
		}
		return $this->_libraries;
	}

	/**
	 * @return ?int[]
	 */
	public function getLocations(): ?array {
		if (!isset($this->_locations) && $this->id) {
			$this->_locations = [];
			$obj = new PlacardLocation();
			$obj->placardId = $this->id;
			$obj->find();
			while ($obj->fetch()) {
				$this->_locations[$obj->locationId] = $obj->locationId;
			}
		}
		return $this->_locations;
	}

	public function __get($name) {
		if ($name == "libraries") {
			return $this->getLibraries();
		} elseif ($name == "locations") {
			return $this->getLocations();
		} elseif ($name == 'triggers') {
			$this->getTriggers();
			return $this->_triggers;
		} elseif ($name == 'languages') {
			$this->getLanguages();
			return $this->_languages;
		} elseif ($name == 'debugCandidates') {
			return $this->_debugCandidates;
		} else {
			return parent::__get($name);
		}
	}

	public function __set($name, $value) {
		if ($name == "libraries") {
			$this->_libraries = $value;
		} elseif ($name == "locations") {
			$this->_locations = $value;
		} elseif ($name == 'triggers') {
			$this->_triggers = $value;
		} elseif ($name == 'languages') {
			$this->_languages = $value;
		} elseif ($name == 'debugCandidates') {
			$this->_debugCandidates = $value;
		} else {
			parent::__set($name, $value);
		}
	}

	/** @noinspection PhpUnused */
	public function __isset($name): bool {
		if ($name == 'debugCandidates') {
			return !empty($this->_debugCandidates);
		}

		if (property_exists($this, $name)) {
			return isset($this->$name);
		}
		return isset($this->_data[$name]);
	}

	/**
	 * Override the update functionality to save related objects
	 *
	 * @see DB/DB_DataObject::update()
	 */
	public function update(string $context = '', $savingOnObjectPage = true) : int|bool {
		if ($this->sourceType != 'none' && $savingOnObjectPage) {
			$this->compareLinkedObject();
		}
		if ($this->sourceType == 'none') {
			$this->__set('isCustomized', 0);
		}
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveLocations();
			$this->saveTriggers();
			$this->saveLanguages();
		}
		return $ret;
	}

	public function insert(string $context = '') : int|bool {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveLocations();
			$this->saveTriggers();
			//When inserting a placard, if nothing exists, apply to all languages
			if (empty($this->_languages)) {
				$languageList = Language::getLanguageList();
				foreach ($languageList as $languageId => $displayName) {
					$this->_languages[$languageId] = $languageId;
				}
			}
			$this->saveLanguages();
		}
		return $ret;
	}

	public function delete(bool $useWhere = false, bool $hardDelete = false) : bool|int {
		$ret = parent::delete($useWhere, $hardDelete);
		if ($ret && $hardDelete && !empty($this->id)) {
			$triggers = new PlacardTrigger();
			$triggers->placardId = $this->id;
			$triggers->delete(true);

			$placardLibrary = new PlacardLibrary();
			$placardLibrary->placardId = $this->id;
			$placardLibrary->delete(true);

			$placardLocation = new PlacardLocation();
			$placardLocation->placardId = $this->id;
			$placardLocation->delete(true);

			$placardLocation = new PlacardLanguage();
			$placardLocation->placardId = $this->id;
			$placardLocation->delete(true);
		}
		return $ret;
	}

	public function saveTriggers() : void {
		if (isset ($this->_triggers) && is_array($this->_triggers)) {
			$this->saveOneToManyOptions($this->_triggers, 'placardId');
			unset($this->_triggers);
		}
	}

	/**
	 * @return PlacardTrigger[]|null
	 */
	public function getTriggers(): ?array {
		if (!isset($this->_triggers) && $this->id) {
			$this->_triggers = [];
			$trigger = new PlacardTrigger();
			$trigger->placardId = $this->id;
			$trigger->orderBy('triggerWord');
			$trigger->find();
			while ($trigger->fetch()) {
				$this->_triggers[$trigger->id] = clone($trigger);
			}
		}
		return $this->_triggers;
	}

	/**
	 * @return int[]|null
	 */
	public function getLanguages(): ?array {
		if (!isset($this->_languages) && $this->id) {
			$this->_languages = [];
			$language = new PlacardLanguage();
			$language->placardId = $this->id;
			$language->find();
			while ($language->fetch()) {
				$this->_languages[$language->languageId] = $language->languageId;
			}
		}
		return $this->_languages;
	}

	public function saveLibraries() : void {
		if (isset ($this->_libraries) && is_array($this->_libraries)) {
			$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Placards'));
			foreach ($libraryList as $libraryId => $displayName) {
				$obj = new PlacardLibrary();
				$obj->placardId = $this->id;
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

	public function saveLocations() : void {
		if (isset ($this->_locations) && is_array($this->_locations)) {
			$locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer All Placards'));
			foreach ($locationList as $locationId => $displayName) {
				$obj = new PlacardLocation();
				$obj->placardId = $this->id;
				$obj->locationId = $locationId;
				if (in_array($locationId, $this->_locations)) {
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

	public function saveLanguages() : void {
		if (isset ($this->_languages) && is_array($this->_languages)) {
			$languageList = Language::getLanguageList();
			foreach ($languageList as $languageId => $displayName) {
				$obj = new PlacardLanguage();
				$obj->placardId = $this->id;
				$obj->languageId = $languageId;
				if (in_array($languageId, $this->_languages)) {
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

	/** @noinspection PhpUnused */
	public function bodyHasAnchor() : bool {
		return !empty($this->body) && (stripos($this->body, '<a') !== false);
	}

	public function isDismissed() : bool {
		require_once ROOT_DIR . '/sys/LocalEnrichment/PlacardDismissal.php';
		//Make sure the user has not dismissed the placard
		if (UserAccount::isLoggedIn()) {
			$placardDismissal = new PlacardDismissal();
			$placardDismissal->placardId = $this->id;
			$placardDismissal->userId = UserAccount::getActiveUserId();
			if ($placardDismissal->count() == 1) {
				//The placard has been dismissed
				return true;
			}
		}
		return false;
	}

	public function isValidForScope() : bool {
		global $library;
		global $locationSingleton;
		$location = $locationSingleton->getActiveLocation();

		if ($location != null) {
			$placardLocation = new PlacardLocation();
			$placardLocation->placardId = $this->id;
			$placardLocation->find();
			//If no locations are selected, allow at any location
			if ($placardLocation->getNumResults() > 0) {
				$placardLocation->locationId = $location->locationId;
				if ($placardLocation->find(true)) {
					return true;
				} else {
					return false;
				}
			}
		}
		$placardLibrary = new PlacardLibrary();
		$placardLibrary->placardId = $this->id;
		$placardLibrary->libraryId = $library->libraryId;
		return $placardLibrary->find(true);
	}

	public function isValidForDisplay() : bool {
		$curTime = time();
		if ($this->startDate != 0 && $this->startDate > $curTime) {
			return false;
		}
		if ($this->endDate != 0 && $this->endDate < $curTime) {
			return false;
		}
		if ($this->isDismissed()) {
			return false;
		}
		if (!$this->isValidForScope()) {
			return false;
		}
		//Check to see if the placard is valid based on the language
		global $activeLanguage;
		$validLanguages = $this->getLanguages();
		if (!in_array($activeLanguage->id, $validLanguages)) {
			return false;
		}
		return true;
	}

	public function getLinksForJSON(): array {
		$links = parent::getLinksForJSON();
		//Triggers
		$triggers = $this->getTriggers();
		$links['triggers'] = [];
		foreach ($triggers as $trigger) {
			$triggerArray = $trigger->toArray();
			unset ($triggerArray['placardId']);
			$links['triggers'][] = $triggerArray;
		}
		//Languages
		$languages = $this->getLanguages();
		$links['languages'] = [];
		foreach ($languages as $languageId) {
			$language = new Language();
			$language->id = $languageId;
			if ($language->find(true)) {
				$links['languages'][] = $language->code;
			}
		}

		return $links;
	}

	public function loadRelatedLinksFromJSON($jsonData, $mappings, string $overrideExisting = 'keepExisting'): bool {
		$result = parent::loadRelatedLinksFromJSON($jsonData, $mappings);

		if (array_key_exists('triggers', $jsonData)) {
			$triggers = [];
			foreach ($jsonData['triggers'] as $trigger) {
				$triggerObj = new PlacardTrigger();
				$triggerObj->placardId = $this->id;
				//Make sure we don't overwrite the placard id we just set
				unset($trigger['placardId']);
				$triggerObj->loadFromJSON($trigger, $mappings);
				$triggers[] = $triggerObj;
			}
			$this->_triggers = $triggers;
			$result = true;
		}
		if (array_key_exists('languages', $jsonData)) {
			$languages = [];
			$languageIds = Language::getLanguageIdsByCode();
			foreach ($jsonData['languages'] as $language) {
				if (array_key_exists($language, $languageIds)) {
					$languageId = $languageIds[$language];
					$languages[$languageId] = $languageId;
				}
			}
			$this->_languages = $languages;
			$result = true;
		}
		return $result;
	}

	public function loadCopyableSubObjects() : void {
		$this->getTriggers();
		$index = -1;
		foreach ($this->_triggers as $subObject) {
			$subObject->id = $index;
			$index--;
		}
		$this->getLanguages();
	}

	private function compareLinkedObject() : void {
		if ($this->sourceType == 'web_resource') {
			require_once ROOT_DIR . '/sys/WebBuilder/WebResource.php';
			$webResource = new WebResource();
			$webResource->id = $this->sourceId;
			if ($webResource->find(true)) {
				if ($webResource->name != $this->title || $webResource->url != $this->link || $webResource->teaser != $this->body || $webResource->logo != $this->image) {
					$this->__set('isCustomized', 1);
				} else {
					$this->__set('isCustomized', 0);
				}
			}
		}
	}

	public function supportsSoftDelete(): bool {
		return true;
	}

	/**
	 * @param string $triggerWord
	 * @return Placard|null
	 */
	public static function getPlacardForTriggerWord(string $triggerWord) : ?Placard {
		$trigger = new PlacardTrigger();
		$escapedWord = $trigger->escape($triggerWord);
		// Allow matches where the search term contains the trigger or is contained within the trigger (partial/prefix).
		$trigger->whereAdd("CASE WHEN exactMatch = 0 THEN ($escapedWord like concat('%', triggerWord, '%') OR triggerWord LIKE concat('%', $escapedWord, '%')) ELSE $escapedWord = triggerWord END");
		$trigger->selectAdd();
		$trigger->selectAdd('placard_trigger.*');
		//Pre-filter for date and scope
		$placard = new Placard();
		$now = time();
		$placard->whereAdd("startDate = 0 OR startDate <= $now");
		$placard->whereAdd("endDate = 0 OR endDate > $now");
		global $locationSingleton;
		global $library;
		$location = $locationSingleton->getActiveLocation();
		if ($location != null) {
			$placardLocation = new PlacardLocation();
			$placardLocation->locationId = $location->locationId;
			$placard->joinAdd($placardLocation, 'INNER', 'placardLocation', 'id', 'placardId');
		}else{
			$placardLibrary = new PlacardLibrary();
			$placardLibrary->libraryId = $library->libraryId;
			$placard->joinAdd($placardLibrary, 'INNER', 'placardLibrary', 'id', 'placardId');
		}

		global $activeLanguage;
		$candidates = [];
		$searchTermLower = strtolower($triggerWord);
		$placardLanguage = new PlacardLanguage();
		$placardLanguage->languageId = $activeLanguage->id;
		$placard->joinAdd($placardLanguage, 'INNER', 'placardLanguage', 'id', 'placardId');
		$trigger->joinAdd($placard, 'INNER', 'placard', 'placardId', 'id');
		$trigger->find();
		while ($trigger->fetch()) {
			$placardCandidate = new Placard();
			$placardCandidate->id = $trigger->placardId;
			if (!$placardCandidate->find(true) || $placardCandidate->isDismissed()) {
				continue;
			}

			$triggerLower = strtolower($trigger->triggerWord);
			$score = self::calculateTriggerMatchScore($searchTermLower, $triggerLower, $trigger->exactMatch);
			if (!isset($candidates[$trigger->placardId]) || $score > $candidates[$trigger->placardId]['score']) {
				$candidates[$trigger->placardId] = [
					'placard' => $placardCandidate,
					'score' => $score,
					'triggerWord' => $trigger->triggerWord,
				];
			}
		}

		if (empty($candidates)) {
			return null;
		}

		uasort($candidates, function($a, $b) {
			return $b['score'] <=> $a['score'];
		});

		$best = reset($candidates);
		$bestPlacard = $best['placard'];

		// Store debug information for display if debugging is enabled
		$debugInfo = [];
		foreach ($candidates as $placardId => $candidate) {
			$debugInfo[] = [
				'title' => $candidate['placard']->title,
				'triggerWord' => $candidate['triggerWord'],
				'score' => $candidate['score'],
				'isSelected' => ($placardId === $bestPlacard->id),
			];
		}
		$bestPlacard->debugCandidates = $debugInfo;

		return $bestPlacard;
	}

	/**
	 * Calculate a match score for a trigger word match.
	 * Higher scores indicate better/more specific matches.
	 *
	 * Match type priorities (additive bonuses):
	 * - Exact match: 3
	 * - Whole word match: 2
	 * - Partial match (trigger contains search term / prefix-subset): 1.5 + overlap ratio
	 * - Partial match (search term contains trigger): 1
	 *
	 * Longer trigger words receive a fractional bonus equal to a multiple of 0.01 times their length.
	 *
	 * @param string $searchTerm The search term (lowercased).
	 * @param string $triggerWord The trigger word (lowercased).
	 * @param int $exactMatch Whether this trigger requires exact match.
	 * @return float The match score.
	 */
	private static function calculateTriggerMatchScore(string $searchTerm, string $triggerWord, int $exactMatch): float {
		$triggerLength = strlen($triggerWord);
		if ($searchTerm === $triggerWord) {
			return 3.0 + ($triggerLength * 0.01);
		}
		if (preg_match('/\b' . preg_quote($triggerWord, '/') . '\b/', $searchTerm)) {
			return 2.0 + ($triggerLength * 0.01);
		}
		// If exact match is enabled, no partial word consideration.
		if ($exactMatch == 0) {
			// Handle cases where the search term is a prefix/subset of the trigger word (e.g., "union lead" vs "union leader").
			if (str_contains($triggerWord, $searchTerm)) {
				$overlapRatio = strlen($searchTerm) / $triggerLength;
				return 1.5 + $overlapRatio + ($triggerLength * 0.01);
			}
			if (str_contains($searchTerm, $triggerWord)) {
				return 1.0 + ($triggerLength * 0.01);
			}
		}

		return 0.0;
	}
}
