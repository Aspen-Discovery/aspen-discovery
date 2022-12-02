<?php
require_once ROOT_DIR . '/sys/WebBuilder/WebResource.php';

class WebBuilder_ResourcesList extends Action {

	function launch() {
		global $library;
		//Get all the resources.
		$resourcesByCategory = [];
		$featuredResources = [];

		$resource = new WebResource();
		$resource->orderBy('name');
		//Limit based on the library
		$libraryWebResource = new LibraryWebResource();
		$libraryWebResource->libraryId = $library->libraryId;
		$resource->joinAdd($libraryWebResource, 'INNER', 'libraryWebResource', 'id', 'webResourceId');
		$resource->limit(0, 1000);
		$resource->find();
		$allResources = $resource->fetchAll();
		$numLoaded = 0;
		foreach ($allResources as $resource) {
			$clonedResource = clone $resource;
			if ($clonedResource->featured) {
				$featuredResources[] = $clonedResource;
			}
			foreach ($clonedResource->getCategories() as $category) {
				if (!array_key_exists($category->name, $resourcesByCategory)) {
					$resourcesByCategory[$category->name] = [];
				}
				$resourcesByCategory[$category->name][] = $clonedResource;
			}
			$numLoaded++;
		}
		ksort($resourcesByCategory);
		global $interface;
		$interface->assign('resourcesByCategory', $resourcesByCategory);
		$interface->assign('featuredResources', $featuredResources);
		$interface->assign('numLoaded', $numLoaded);

		$this->display('resourcesList.tpl', 'Research & Learn', '');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/', 'Home');
		$breadcrumbs[] = new Breadcrumb('', 'Resources', true);
		return $breadcrumbs;
	}
}