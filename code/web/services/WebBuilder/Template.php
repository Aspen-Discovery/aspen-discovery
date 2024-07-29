<?php
require_once ROOT_DIR . '/code/web/Action.php';

class WebBuilder_Template extends Action {
	/** @var GrapesTemplate */
	private $template;

	function __construct() {
		parent::__construct();

		require_once ROOT_DIR . '/sys/WebBuilder/GrapesTemplate.php';

		global $interface;

		$id = strip_tags($_REQUEST['id']);
		$this->template = new GrapesTemplate();
		$this->template->id = $id;

		if (!$this->template->find(true)) {
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
			$interface->assign('followupAction', 'Template');
			$interface->assign('id', $id);
			require_once ROOT_DIR . "/services/Error/Handle401.php";
			$actionClass = new Error_Handle401();
			$actionClass->launch();
			die();
		}
	}

	function launch() {
		global $interface;
		// $template = new GrapesTemplate();

		$title = $this->template->title;
		$interface->assign('id', $this->template->id);
		$interface->assign('contents', $this->template->getFormattedContents());
		$interface->assign('title', $title);

		$this->display('template.tpl', $title, '', false);
		// header("Location: /WebBuilder/Template?objectAction=view&id={$template->id}");
	}

	function saveAsTemplate(){
        $newGrapesTemplate = json_decode(file_get_contents("php://input"), true);
        $html = $newGrapesTemplate['html'];
		$css = $newGrapesTemplate['css'];
		$projectData = $newGrapesTemplate['projectData'];
        $template = new GrapesTemplate();
        $template->htmlData = $html;
		$template->cssData = $css;
		$template->templateContent = $projectData;
        $template->insert();
    }

	function canView(): bool {
		return true;
	}
	function getAdditionalObjectActions($id): array {
		$objectActions = [];
		if (!empty($existingObject) && $existingObject instanceof GrapesTemplate && !empty($existingObject->id)){
			$objectActions[] = [
				'text' => 'Open Editor',
				//'url' => '/WebBuilder/GrapesJSEditor?objectAction=edit&id=' . $existingObject->templateId,
				'url' => '/WebBuilder/GrapesJSTemplates?id=' . $id,
			];
		}
		return $objectActions;
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/', 'Home');
		if ($this->template != null) {
			$breadcrumbs[] = new Breadcrumb('', $this->template->title, true);
			if (UserAccount::userHasPermission([
				'Administer All Grapes Pages',
				'Administer Library Grapes Pages',
			])) {
				$breadcrumbs[] = new Breadcrumb('/WebBuilder/Templates?id=' . $this->template->id . '&objectAction=edit', 'Edit', true);
			}
		}
		return $breadcrumbs;
	}
}