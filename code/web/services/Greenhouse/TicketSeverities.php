<?php

require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Support/TicketSeverityFeed.php';

class Greenhouse_TicketSeverities extends ObjectEditor
{

	function getBreadcrumbs(): array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/Home', 'Greenhouse Home');
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/TicketSeverities', 'Ticket Severities');
		return $breadcrumbs;
	}

	function canView()
	{
		if (UserAccount::isLoggedIn()){
			if (UserAccount::getActiveUserObj()->source == 'admin' && UserAccount::getActiveUserObj()->cat_username == 'aspen_admin'){
				return true;
			}
		}
		return false;
	}

	function getActiveAdminSection(): string
	{
		return 'greenhouse';
	}

	function getObjectType(): string
	{
		return 'TicketSeverityFeed';
	}

	function getModule(): string
	{
		return 'Greenhouse';
	}

	function getToolName(): string
	{
		return 'TicketSeverities';
	}

	function getPageTitle(): string
	{
		return 'Ticket Severities';
	}

	function getAllObjects($page, $recordsPerPage): array
	{
		$object = new TicketSeverityFeed();
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$this->applyFilters($object);
		$object->orderBy($this->getSort());
		$object->find();
		$objectList = array();
		while ($object->fetch()) {
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}

	function getObjectStructure(): array
	{
		return TicketSeverityFeed::getObjectStructure();
	}

	function getPrimaryKeyColumn(): string
	{
		return 'id';
	}

	function getIdKeyColumn(): string
	{
		return 'id';
	}

	function getDefaultSort(): string
	{
		return 'name';
	}

	public function display($mainContentTemplate, $pageTitle, $sidebarTemplate = 'Development/development-sidebar.tpl', $translateTitle = true)
	{
		parent::display($mainContentTemplate, $pageTitle, $sidebarTemplate, $translateTitle);
	}
}