<?php


class WebBuilder_GrapesPage extends Action {
	/** @var GrapesPage */
	private $grapesPage;

	function __construct() {
		parent::__construct();

		require_once ROOT_DIR . '/sys/WebBuilder/GrapesPage.php';

		global $interface;

		$id = strip_tags($_REQUEST['id']);
		$this->grapesPage = new GrapesPage();
		$this->grapesPage->id = $id;

		if (!$this->grapesPage->find(true)) {
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
			$interface->assign('followupAction', 'GrapesPage');
			$interface->assign('id', $id);
			require_once ROOT_DIR . "/services/Error/Handle401.php";
			$actionClass = new Error_Handle401();
			$actionClass->launch();
			die();
		}
	}

	function launch() {
		global $interface;

		$title = $this->grapesPage->title;
		$interface->assign('id', $this->grapesPage->id);
		$interface->assign('contents', $this->grapesPage->getFormattedContents());
		
		// $interface->assign('templateContent', $this->grapesPage->templateContent);
		// $interface->assign('title', $title);

		$templates = $this->grapesPage->getTemplates();
		if (!empty($templates)) {
			$templateContent = reset($templates)->templateContent;
		} else {
			$templateContent = ' ';
		}
		$interface->assign('templateContent', $templateContent);
		$interface->assign('title', $title);

		$this->display('grapesPage.tpl', $title, '', false);
	}

	function canView(): bool {
		return true;
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/', 'Home');
		if ($this->grapesPage != null) {
			$breadcrumbs[] = new Breadcrumb('', $this->grapesPage->title, true);
			if (UserAccount::userHasPermission([
				'Administer All Basic Pages',
				'Administer Library Basic Pages',
			])) {
				$breadcrumbs[] = new Breadcrumb('/WebBuilder/GrapesPages?id=' . $this->grapesPage->id . '&objectAction=createFromBlankPage', 'Edit', true);
			}
		}
		return $breadcrumbs;
	}
}