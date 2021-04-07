<?php
require_once ROOT_DIR . '/sys/WebBuilder/WebResource.php';

class WebBuilder_ResourcesList extends Action
{

	function launch()
	{
		global $library;
		//Get all the resources.
		$resourcesByCategory = [];
		$featuredResources = [];
		$resource = new WebResource();
		$resource->orderBy('name');
		$resource->find();
		while ($resource->fetch()){
			//Limit based on the library
			$clonedResource = clone $resource;
			if (array_key_exists($library->libraryId, $clonedResource->getLibraries())){
				if ($clonedResource->featured) {
					$featuredResources[] = $clonedResource;
				}
				foreach ($clonedResource->getCategories() as $category) {
					if (!array_key_exists($category->name, $resourcesByCategory)) {
						$resourcesByCategory[$category->name] = [];
					}
					$resourcesByCategory[$category->name][] = $clonedResource;
				}
			}
		}
		ksort($resourcesByCategory);
		global $interface;
		$interface->assign('resourcesByCategory', $resourcesByCategory);
		$interface->assign('featuredResources', $featuredResources);

		$this->display('resourcesList.tpl', 'Research & Learn', '');
	}

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/', 'Home');
		$breadcrumbs[] = new Breadcrumb('', 'Resources', true);
		return $breadcrumbs;
	}
}