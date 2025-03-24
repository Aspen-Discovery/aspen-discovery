<?php

class WebBuilder_ResourceAudience extends Action
{

	function launch() : void
	{
		global $interface;
		global $activeLanguage;
		global $library;

		require_once ROOT_DIR . '/sys/WebBuilder/WebBuilderAudience.php';
		$audience = new WebBuilderAudience();
		$audience->id = $_GET["id"];
		if ($audience->find(true)) {
			require_once ROOT_DIR . '/sys/WebBuilder/WebResource.php';
			require_once ROOT_DIR . '/RecordDrivers/WebResourceRecordDriver.php';
			$resourcesForAudience = new WebResourceAudience();
			$resourcesForAudience->audienceId = $audience->id;
			$resourcesForAudience->find();
			$webResources = [];
			$webResourceIds = [];
			while ($resourcesForAudience->fetch()) {
				$webResourceLibrary = new libraryWebResource();
				$webResourceLibrary->webResourceId = $resourcesForAudience->webResourceId;
				$webResourceLibrary->libraryId = $library->libraryId;
				if ($webResourceLibrary->find()) {
					if (!array_key_exists("WebResource:" . $resourcesForAudience->webResourceId, $webResourceIds)) {
						$webResourceIds["\"WebResource:" . $resourcesForAudience->webResourceId . "\""] = "WebResource:" . $resourcesForAudience->webResourceId;
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
			$interface->assign('title', $audience->name);
			$interface->assign('description', $audience->getTextBlockTranslation('customWebBuilderAudienceDescription', $activeLanguage->code));

			$this->display('resourcesFiltered.tpl', $audience->name, '', false);

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
