<?php

require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Development/DevelopmentTask.php';

class Development_Tasks extends ObjectEditor {
	function getObjectType(): string {
		return 'DevelopmentTask';
	}

	function getToolName(): string {
		return 'Tasks';
	}

	function getModule(): string {
		return 'Development';
	}

	function getPageTitle(): string {
		return 'Development Tasks';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$object = new DevelopmentTask();
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$this->applyFilters($object);
		$object->orderBy($this->getSort());
		$object->find();
		$objectList = [];
		while ($object->fetch()) {
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}

	function getDefaultSort(): string {
		return 'id desc';
	}

	function getObjectStructure($context = ''): array {
		return DevelopmentTask::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function canAddNew() {
		return true;
	}

	function canDelete() {
		return true;
	}

	function getAdditionalObjectActions($existingObject): array {
		return [];
	}

	function getInstructions(): string {
		return '';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/Home', 'Greenhouse Home');
		$breadcrumbs[] = new Breadcrumb('/Development/Tasks', 'Tasks');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'development';
	}

	function canView(): bool {
		if (UserAccount::isLoggedIn()) {
			if (UserAccount::getActiveUserObj()->source == 'admin' && UserAccount::getActiveUserObj()->cat_username == 'aspen_admin') {
				return true;
			}
		}
		return false;
	}

	public function display($mainContentTemplate, $pageTitle, $sidebarTemplate = 'Development/development-sidebar.tpl', $translateTitle = true) {
		parent::display($mainContentTemplate, $pageTitle, $sidebarTemplate, $translateTitle);
	}

	/** @noinspection PhpUnused */
	function createTaskFromTicket() {
		global $interface;
		$interface->assign('instructions', $this->getInstructions());

		$structure = $this->getObjectStructure('createTaskFromTicket');

		//Update the structure with data from the ticket
		require_once ROOT_DIR . '/sys/Support/Ticket.php';
		$ticket = new Ticket();
		$ticketId = $_REQUEST['ticketId'];
		$ticket->id = $ticketId;
		if ($ticket->find(true)) {
			$newTask = new DevelopmentTask();
			$newTask->name = $ticket->title;
			$newTask->description = $ticket->description;
			//Set the proper type
			$ticketType = $ticket->queue;
			if ($ticketType == 'Bugs') {
				$newTask->taskType = 1;
			} else if ($ticketType == 'Development') {
				$newTask->taskType = 2;
			} else if ($ticketType == 'Support') {
				$newTask->taskType = 5;
			}
			//Link to the ticket
			require_once ROOT_DIR . '/sys/Development/TaskTicketLink.php';
			$ticketTaskLink = new TaskTicketLink();
			$ticketTaskLink->ticketId = $ticketId;
			$newTask->setRelatedTickets([$ticketTaskLink]);

			//Link to the partner
			require_once ROOT_DIR . '/sys/Development/TaskPartnerLink.php';
			if (!empty($ticket->requestingPartner)) {
				$requestingPartnerLink = new TaskPartnerLink();
				$requestingPartnerLink->partnerId = $ticket->requestingPartner;
				$newTask->setRequestingPartners([$requestingPartnerLink]);
			}

			//Link to the appropriate components
			require_once ROOT_DIR . '/sys/Development/ComponentTaskLink.php';
			$components = $ticket->getRelatedComponents();
			$relatedComponents = [];
			foreach ($components as $component) {
				$componentTaskLink = new ComponentTaskLink();
				$componentTaskLink->componentId = $component->componentId;
				$relatedComponents[] = $componentTaskLink;
			}
			$newTask->setRelatedComponents($relatedComponents);


			$interface->assign('object', $newTask);

			//Check to see if the request should be multipart/form-data
			$contentType = $this->getFormContentType($structure);
			$interface->assign('contentType', $contentType);

			$interface->assign('additionalObjectActions', $this->getAdditionalObjectActions($newTask));
			$interface->setTemplate('../Admin/objectEditor.tpl');
		} else {
			$interface->setTemplate('../Admin/invalidObject.tpl');
		}
	}
}