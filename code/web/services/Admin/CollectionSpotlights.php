<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/LocalEnrichment/CollectionSpotlight.php';
require_once ROOT_DIR . '/sys/LocalEnrichment/CollectionSpotlightList.php';
require_once ROOT_DIR . '/sys/DataObjectUtil.php';

class Admin_CollectionSpotlights extends ObjectEditor {
	function getObjectType(): string {
		return 'CollectionSpotlight';
	}

	function getToolName(): string {
		return 'CollectionSpotlights';
	}

	function getPageTitle(): string {
		return 'Collection Spotlights';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$list = [];

		$object = new CollectionSpotlight();
		if (!UserAccount::userHasPermission('Administer All Collection Spotlights')) {
			$homeLibrary = Library::getPatronHomeLibrary();
			$object->whereAdd('libraryId = ' . $homeLibrary->libraryId . ' OR libraryId = -1');
		}
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$object->find();
		while ($object->fetch()) {
			$list[$object->id] = clone $object;
		}

		return $list;
	}

	function getDefaultSort(): string {
		return 'name asc';
	}

	function getObjectStructure($context = ''): array {
		return CollectionSpotlight::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function canAddNew(): bool {
		// Collection Spotlights should be added from search results.
		return false;
	}

	function canDelete(): bool {
		return true;
	}

	function launch(): void {
		global $interface;

		$interface->assign('canAddNew', $this->canAddNew());
		$interface->assign('canCopy', $this->canCopy());
		$interface->assign('canDelete', $this->canDelete());
		$interface->assign('showReturnToList', $this->showReturnToList());

		$objectAction = $_REQUEST['objectAction'] ?? 'list';

		if ($objectAction == 'delete' && isset($_REQUEST['id'])) {
			parent::launch();
			exit();
		}

		$availableSpotlights = [];
		$collectionSpotlight = new CollectionSpotlight();
		if (!UserAccount::userHasPermission('Administer All Collection Spotlights')) {
			$homeLibrary = Library::getPatronHomeLibrary();
			$collectionSpotlight->whereAdd('libraryId = ' . $homeLibrary->libraryId . ' OR libraryId = -1');
		}
		$collectionSpotlight->orderBy('name ASC');
		$collectionSpotlight->find();
		while ($collectionSpotlight->fetch()) {
			$availableSpotlights[$collectionSpotlight->id] = clone($collectionSpotlight);
		}
		$interface->assign('availableSpotlights', $availableSpotlights);

		// Get the selected spotlight.
		if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
			$spotlight = $availableSpotlights[$_REQUEST['id']];
			$interface->assign('object', $spotlight);
		} else {
			$spotlight = null;
		}

		if ($objectAction === 'save') {
			if (!isset($spotlight)) {
				$spotlight = new CollectionSpotlight();
			}
			$fieldLocks = $this->getFieldLocks();
			if (UserAccount::userHasPermission('Lock Administration Fields')) {
				$fieldLocks = null;
			}
			$structure = $collectionSpotlight->getObjectStructure();
			DataObjectUtil::updateFromUI($spotlight, $structure, $fieldLocks);
			// Validate (includes validateName, which skips current id).
			$validationResults = DataObjectUtil::validateObject($structure, $spotlight);
			if (!$validationResults['validatedOk']) {
				$interface->assign('object', $spotlight);
				$interface->assign('errors', $validationResults['errors']);
				$objectAction = 'edit';
			} else {
				// Commit the update; CollectionSpotlight->update() handles saving lists.
				$saveOk = $spotlight->update();
				$interface->assign('object', $spotlight);
				if (!$saveOk) {
					$interface->assign('errors', ['An error occurred saving the collection spotlight.']);
					$objectAction = 'edit';
				} else {
					$objectAction = 'view';
				}
			}

		}

		if ($objectAction == 'edit' || $objectAction == 'add') {
			if (isset($_REQUEST['id'])) {
				$interface->assign('spotlightId', $_REQUEST['id']);
				$interface->assign('id', $_REQUEST['id']);
			}
			$interface->assign('initializationJs', $this->getInitializationJs());
			$editForm = DataObjectUtil::getEditForm($collectionSpotlight->getObjectStructure());
			$interface->assign('editForm', $editForm);
			$interface->setTemplate('collectionSpotlightEdit.tpl');
		} elseif ($objectAction === 'view') {
			// Set some default sizes for the iframe we embed on the view page.
			switch ($spotlight->style) {
				case 'horizontal':
					$width = 650;
					$height = ($spotlight->coverSize == 'medium') ? 350 : 300;
					if ($spotlight->getNumLists() > 1) {
						$height += 40;
					}
					break;
				case 'vertical' :
					$width = ($spotlight->coverSize == 'medium') ? 275 : 175;
					$height = ($spotlight->coverSize == 'medium') ? 700 : 400;
					break;
				case 'text-list' :
					$width = 500;
					$height = 200;
					break;
				case 'single' :
				case 'single-with-next' :
				default:
					$width = ($spotlight->coverSize == 'medium') ? 300 : 225;
					$height = ($spotlight->coverSize == 'medium') ? 350 : 275;
					break;
			}
			$interface->assign('width', $width);
			$interface->assign('height', $height);
			$interface->setTemplate('collectionSpotlight.tpl');
		} else {
			parent::launch();
			exit();
		}

		$this->display($interface->getTemplate(), 'Collection Spotlights');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#local_enrichment', 'Local Enrichment');
		$breadcrumbs[] = new Breadcrumb('/Admin/CollectionSpotlights', 'Collection Spotlights');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'local_enrichment';
	}

	function canView(): bool {
		return UserAccount::userHasPermission([
			'Administer All Collection Spotlights',
			'Administer Library Collection Spotlights',
		]);
	}

	function canBatchEdit(): bool {
		return UserAccount::userHasPermission([
			'Administer All Collection Spotlights',
		]);
	}

	function getInitializationJs(): string {
		return 'AspenDiscovery.Admin.updateCollectionSpotlightFields();';
	}
}