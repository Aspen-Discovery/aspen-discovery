<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class SubBrowseCategories extends DataObject {
	public $__table = 'browse_category_subcategories';
	public $id;
	public $weight;
	public $browseCategoryId; // ID of the Main or Parent browse category
	public $subCategoryId;    // ID of the browse Category which is the Sub-Category or Child browse category
	public $_source; //Source of the sub browse category, loaded at runtime, will be browseCategory, userList, or savedSearch

	function getUniquenessFields(): array {
		return [
			'browseCategoryId',
			'subCategoryId',
		];
	}

	static function getObjectStructure($context = ''): array {
		$browseCategoryList = self::listBrowseCategories();
		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id of the sub-category row within the database',
			],
			'browseCategoryId' => [
				'property' => 'browseCategoryId',
				'type' => 'label',
				'label' => 'Browse Category',
				'description' => 'The parent browse category',
			],
			'subCategoryId' => [
				'property' => 'subCategoryId',
				'type' => 'enum',
				'values' => $browseCategoryList,
				'label' => 'Sub-Category',
				'description' => 'The sub-category of the parent browse category',
			],
			'weight' => [
				'property' => 'weight',
				'type' => 'numeric',
				'label' => 'Weight',
				'weight' => 'Defines the order of the sub-categories .  Lower weights are displayed to the left of the screen.',
				'required' => true,
			],
		];
	}

	static function listBrowseCategories() {
		$browseCategoryList = [];
		require_once ROOT_DIR . '/sys/Browse/BrowseCategory.php';

		$browseCategories = new BrowseCategory();
		$browseCategories->orderBy('label');
		if (!UserAccount::userHasPermission('Administer All Browse Categories')) {
			if (UserAccount::userHasPermission('Administer Selected Browse Category Groups')) {
				//Get a list of groups the user can edit
				require_once ROOT_DIR . '/sys/Browse/BrowseCategoryGroupUser.php';
				$browseCategoryGroupUser = new BrowseCategoryGroupUser();
				$browseCategoryGroupUser->userId = UserAccount::getActiveUserId();
				$allowedGroups = $browseCategoryGroupUser->fetchAll('browseCategoryGroupId');
				if (count($allowedGroups) == 0) {
					return [];
				}
				$activeBrowseCategories = [];
				foreach ($allowedGroups as $groupId) {
					require_once ROOT_DIR . '/sys/Browse/BrowseCategoryGroupEntry.php';
					$browseCategoryGroupEntry = new BrowseCategoryGroupEntry();
					$browseCategoryGroupEntry->browseCategoryGroupId = $groupId;
					$activeBrowseCategories = array_merge($activeBrowseCategories, $browseCategoryGroupEntry->fetchAll('browseCategoryId'));
				}

				$browseCategories->whereAddIn('id', $activeBrowseCategories, false);
			} else {
				$library = Library::getPatronHomeLibrary(UserAccount::getActiveUserObj());
				$libraryId = $library == null ? -1 : $library->libraryId;
				$browseCategories->whereAdd("sharing = 'everyone'");
				$browseCategories->whereAdd("sharing = 'library' AND libraryId = " . $libraryId, 'OR');
			}
			$browseCategories->find();

			while ($browseCategories->fetch()) {
				if ($browseCategories->canActiveUserEdit()) {
					$browseCategoryList[$browseCategories->id] = $browseCategories->label . ' (' . $browseCategories->textId . ')' . " - $browseCategories->id";
				}
			}

		} elseif (UserAccount::userHasPermission('Administer All Browse Categories')) {
			$browseCategories->find();

			while ($browseCategories->fetch()) {
				$browseCategoryList[$browseCategories->id] = $browseCategories->label . ' (' . $browseCategories->textId . ')' . " - $browseCategories->id";
			}
		}
		return $browseCategoryList;
	}

	function getEditLink($context): string {
		return '/Admin/BrowseCategories?objectAction=edit&id=' . $this->subCategoryId;
	}

	public function toArray($includeRuntimeProperties = true, $encryptFields = false): array {
		$return = parent::toArray($includeRuntimeProperties, $encryptFields);
		unset($return['browseCategoryId']);
		unset($return['subCategoryId']);
		unset($return['source']);

		return $return;
	}

	public function getLinksForJSON(): array {
		$links = parent::getLinksForJSON();
		//Add the subcategory
		$browseCategory = new BrowseCategory();
		$browseCategory->id = $this->subCategoryId;
		if ($browseCategory->find(true)) {
			$links['subCategory'] = $browseCategory->toArray();
		}
		return $links;
	}

	public function loadEmbeddedLinksFromJSON($jsonData, $mappings, $overrideExisting = 'keepExisting') {
		parent::loadEmbeddedLinksFromJSON($jsonData, $mappings, $overrideExisting);
		if (isset($jsonData)) {
			if (isset($jsonData['subCategory'])) {
				require_once ROOT_DIR . '/sys/Browse/BrowseCategory.php';
				$subCategoryObj = new BrowseCategory();
				$subCategoryObj->loadFromJSON($jsonData['subCategory'], $mappings, $overrideExisting);
				$this->subCategoryId = $subCategoryObj->id;
			}
		}
	}

	function isDismissed(): bool {
		if (UserAccount::isLoggedIn()) {
			$user = UserAccount::getActiveUserObj();
			require_once ROOT_DIR . '/sys/Browse/BrowseCategoryDismissal.php';
			$browseCategoryDismissal = new BrowseCategoryDismissal();
			$browseCategoryDismissal->browseCategoryId = $this->subCategoryId;
			$browseCategoryDismissal->userId = $user->id;
			if ($browseCategoryDismissal->find(true)) {
				return true;
			}
		}
		return false;
	}

	function getNumSubCategories() {
		$subCategory = new SubBrowseCategories();
		$subCategory->browseCategoryId = $this->browseCategoryId;
		return $subCategory->count();
	}
}