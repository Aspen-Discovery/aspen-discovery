<?php

class WebBuilder_ResourceCategory extends Action
{

	function launch() : void
	{
		global $interface;
		global $activeLanguage;
		global $library;

		require_once ROOT_DIR . '/sys/WebBuilder/WebBuilderCategory.php';
		$category = new WebBuilderCategory();
		$category->id = $_GET["id"];
		if ($category->find(true)) {
			require_once ROOT_DIR . '/sys/WebBuilder/WebResource.php';
			require_once ROOT_DIR . '/RecordDrivers/WebResourceRecordDriver.php';
			$resourcesForCategory = new WebResourceCategory();
			$resourcesForCategory->categoryId = $category->id;
			$resourcesForCategory->find();
			$webResources = [];
			$webResourceIds = [];
			while ($resourcesForCategory->fetch()) {
				$webResourceLibrary = new libraryWebResource();
				$webResourceLibrary->webResourceId = $resourcesForCategory->webResourceId;
				$webResourceLibrary->libraryId = $library->libraryId;
				if ($webResourceLibrary->find()) {
					if (!array_key_exists("WebResource:" . $resourcesForCategory->webResourceId, $webResourceIds)) {
						$webResourceIds["\"WebResource:" . $resourcesForCategory->webResourceId . "\""] = "WebResource:" . $resourcesForCategory->webResourceId;
					}
				}
			}
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

			uasort($webResources, function ($a, $b) {
				return $a['title'] <=> $b['title'];
			});

			$interface->assign('webResources', $webResources);
			$interface->assign('title', $category->name);
			$interface->assign('description', $category->getTextBlockTranslation('customWebBuilderCategoryDescription', $activeLanguage->code));

			$this->display('resourcesFiltered.tpl', $category->name, '', false);

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

	function getBreadcrumbs(): array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Home', 'Home');
		return $breadcrumbs;
	}
}
