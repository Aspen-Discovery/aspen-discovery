<?php

require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/WebsiteIndexing/WebsitePage.php';
class Websites_WebsitePages extends ObjectEditor
{

	function getBreadcrumbs(): array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#web_indexer', 'Website Indexing');
		$breadcrumbs[] = new Breadcrumb('/Websites/WebsitePages', 'Website Pages');
		return $breadcrumbs;
	}

	function canView()
	{
		return UserAccount::userHasPermission('Administer Website Indexing Settings');
	}

	function canEdit(DataObject $dataObject)
	{
		return false;
	}

	function getActiveAdminSection(): string
	{
		return 'web_indexer';
	}

	/**
	 * @inheritDoc
	 */
	function getObjectType(): string
	{
		return 'WebsitePage';
	}

	/**
	 * @inheritDoc
	 */
	function getToolName(): string
	{
		return 'WebsitePages';
	}

	/**
	 * @inheritDoc
	 */
	function getPageTitle(): string
	{
		return 'Website Pages';
	}

	/**
	 * @inheritDoc
	 */
	function getAllObjects($page, $recordsPerPage): array
	{
		$object = new WebsitePage();
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$object->orderBy($this->getSort());
		$object->find();
		$objectList = array();
		while ($object->fetch()){
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}

	/**
	 * @inheritDoc
	 */
	function getObjectStructure(): array
	{
		return WebsitePage::getObjectStructure();
	}

	/**
	 * @inheritDoc
	 */
	function getPrimaryKeyColumn(): string
	{
		return 'id';
	}

	/**
	 * @inheritDoc
	 */
	function getIdKeyColumn(): string
	{
		return 'id';
	}

	function getDefaultSort(): string
	{
		return 'url asc';
	}
}