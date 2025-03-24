<?php

class WebBuilder_CustomWebResourcePage extends Action {
	/** @var CustomWebResourcePage */
	private $customWebResourcePage;

	function __construct() {
		parent::__construct();

		require_once ROOT_DIR . '/sys/WebBuilder/CustomWebResourcePage.php';

		global $interface;

		$id = strip_tags($_REQUEST['id']);
		$this->customWebResourcePage = new CustomWebResourcePage();
		$this->customWebResourcePage->id = $id;

		if (!$this->customWebResourcePage->find(true)) {
			$interface->assign('module', 'Error');
			$interface->assign('action', 'Handle404');
			require_once ROOT_DIR . "/services/Error/Handle404.php";
			$actionClass = new Error_Handle404();
			$actionClass->launch();
			die();
		} elseif (!$this->canView()) {
			$interface->assign('module', 'Error');
			$interface->assign('action', 'Handle401');
			$interface->assign('followupModule', 'WebBuilder');
			$interface->assign('followupAction', 'CustomWebResourcePage');
			$interface->assign('id', $id);
			require_once ROOT_DIR . "/services/Error/Handle401.php";
			$actionClass = new Error_Handle401();
			$actionClass->launch();
			die();
		}
	}

	function launch() {
		global $interface;
		global $activeLanguage;
		global $library;

		require_once ROOT_DIR . '/sys/WebBuilder/CustomWebResourcePageAudience.php';
		require_once ROOT_DIR . '/sys/WebBuilder/CustomWebResourcePageCategory.php';

		//Get audiences to show resources for
		$audiencesForPage = new CustomWebResourcePageAudience();
		$audiencesForPage->customResourcePageId = $this->customWebResourcePage->id;
		$audiencesForPage->find();
		$audienceIds = [];
		while ($audiencesForPage->fetch()) {
			if (!array_key_exists($audiencesForPage ->audienceId, $audienceIds)) {
				$audienceIds[$audiencesForPage ->audienceId] = $audiencesForPage ->audienceId;
			}
		}

		//Get categories to show resources for
		$categoriesForPage = new CustomWebResourcePageCategory();
		$categoriesForPage->customResourcePageId = $this->customWebResourcePage->id;
		$categoriesForPage->find();
		$categoryIds = [];
		while ($categoriesForPage->fetch()) {
			if (!array_key_exists($categoriesForPage ->categoryId, $categoryIds)) {
				$categoryIds[$categoriesForPage ->categoryId] = $categoriesForPage ->categoryId;
			}
		}

		if (!empty($categoryIds) || !empty($audienceIds)) {
			require_once ROOT_DIR . '/sys/WebBuilder/WebResource.php';
			require_once ROOT_DIR . '/sys/WebBuilder/CustomWebResourcePage.php';
			require_once ROOT_DIR . '/RecordDrivers/WebResourceRecordDriver.php';

			$audienceWebResourceIds = [];
			$categoryWebResourceIds = [];
			$webResources = [];

			if (!empty($audienceIds)) {
				foreach ($audienceIds as $audienceId) {
					$resourcesForAudience = new WebResourceAudience();
					$resourcesForAudience->audienceId = $audienceId;
					$resourcesForAudience->find();
					while ($resourcesForAudience->fetch()) {
						$webResourceLibrary = new libraryWebResource();
						$webResourceLibrary->webResourceId = $resourcesForAudience->webResourceId;
						$webResourceLibrary->libraryId = $library->libraryId;
						if ($webResourceLibrary->find()) {
							if (!array_key_exists("WebResource:" . $resourcesForAudience->webResourceId, $audienceWebResourceIds)) {
								$audienceWebResourceIds["\"WebResource:" . $resourcesForAudience->webResourceId . "\""] = "WebResource:" . $resourcesForAudience->webResourceId;
							}
						}
					}
				}
			}
			if (!empty($categoryIds)) {
				foreach ($categoryIds as $categoryId) {
					$resourcesForCategory = new WebResourceCategory();
					$resourcesForCategory->categoryId = $categoryId;
					$resourcesForCategory->find();
					while ($resourcesForCategory->fetch()) {
						$webResourceLibrary = new libraryWebResource();
						$webResourceLibrary->webResourceId = $resourcesForCategory->webResourceId;
						$webResourceLibrary->libraryId = $library->libraryId;
						if ($webResourceLibrary->find()) {
							if (!array_key_exists("WebResource:" . $resourcesForCategory->webResourceId, $categoryWebResourceIds)) {
								$categoryWebResourceIds["\"WebResource:" . $resourcesForCategory->webResourceId . "\""] = "WebResource:" . $resourcesForCategory->webResourceId;
							}
						}
					}
				}
			}

			//Narrow down the array to resources that include the audiences defined AND categories defined
			$webResourceIds = array_intersect($audienceWebResourceIds, $categoryWebResourceIds);

			/** @var SearchObject_AbstractGroupedWorkSearcher $searchObject */
			$searchObject = SearchObjectFactory::initSearchObject('Websites');
			$resourcesToShow = $searchObject->getRecords(array_keys($webResourceIds));

			foreach ($resourcesToShow as $curResource) {
				$webResourceRecordDriver = new WebResourceRecordDriver($curResource->getFields());
				$webResources[$webResourceRecordDriver->getId()] = [
					'id' => $webResourceRecordDriver->getId(),
					'title' => $webResourceRecordDriver->getTitle(),
					'description' => $webResourceRecordDriver->getDescription(),
					'link' => $webResourceRecordDriver->getLinkUrl(),
					'bookCoverUrl' => $webResourceRecordDriver->getBookCoverUrl('medium'),
				];
			}

			$title = $this->customWebResourcePage->title;
			$interface->assign('id', $this->customWebResourcePage->id);
			$interface->assign('title', $title);
			$interface->assign('description', $this->customWebResourcePage->getTextBlockTranslation('customWebResourceDescription', $activeLanguage->code));
			$interface->assign('webResources', $webResources);


			$this->display('resourcesFiltered.tpl', $title, '', false);

		} else {
			global $interface;
			$interface->assign('module', 'Error');
			$interface->assign('action', 'Handle404');
			require_once ROOT_DIR . "/services/Error/Handle404.php";
			$actionClass = new Error_Handle404();
			$actionClass->launch();
			die();
		}
	}

	function canView(): bool {
		return $this->customWebResourcePage->canView();
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/', 'Home');
		if ($this->customWebResourcePage != null) {
			$breadcrumbs[] = new Breadcrumb('', $this->customWebResourcePage->title, true);
			if (UserAccount::userHasPermission([
				'Administer All Custom Web Resource Pages',
				'Administer Library Custom Web Resource Pages',
			])) {
				$breadcrumbs[] = new Breadcrumb('/WebBuilder/CustomWebResourcePages?id=' . $this->customWebResourcePage->id . '&objectAction=edit', 'Edit', true);
			}
		}
		return $breadcrumbs;
	}
}