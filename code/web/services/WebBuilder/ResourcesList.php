<?php
require_once ROOT_DIR . '/sys/WebBuilder/WebResource.php';

class WebBuilder_ResourcesList extends Action
{

	function launch()
	{
		//Get all the resources.
		$resourcesByCategory = [];
		$featuredResources = [];
		$resource = new WebResource();
		$resource->orderBy('name');
		$resource->find();
		while ($resource->fetch()){
			$clonedResource = clone $resource;
			if ($resource->featured){
				$featuredResources[] = $clonedResource;
			}
			foreach ($resource->getCategories() as $category){
				if (!array_key_exists($category->name, $resourcesByCategory)){
					$resourcesByCategory[$category->name] = [];
				}
				$resourcesByCategory[$category->name][] = $clonedResource;
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