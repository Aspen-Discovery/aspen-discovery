<?php

require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Support/TicketQueueFeed.php';

class Greenhouse_TicketQueues extends ObjectEditor
{

	function getBreadcrumbs(): array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/Home', 'Greenhouse Home');
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/TicketQueues', 'Ticket Queues');
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
		return 'TicketQueueFeed';
	}

	function getModule(): string
	{
		return 'Greenhouse';
	}

	function getToolName(): string
	{
		return 'TicketQueues';
	}

	function getPageTitle(): string
	{
		return 'Ticket Queues';
	}

	function getAllObjects($page, $recordsPerPage): array
	{
		$object = new TicketQueueFeed();
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
		return TicketQueueFeed::getObjectStructure();
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