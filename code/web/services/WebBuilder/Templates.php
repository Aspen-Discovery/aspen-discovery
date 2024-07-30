<?php
require_once ROOT_DIR . '/sys/WebBuilder/GrapesTemplate.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
class WebBuilder_Templates  extends ObjectEditor{

	function getObjectType(): string {
		return 'GrapesTemplate';
	}

	function getToolName(): string {
		return 'Templates';
	}

	function getModule(): string {
		return 'WebBuilder';
	}

	function getPageTitle(): string {
		return 'Templates';
	}

	function getDefaultSort(): string {
		return 'templateName';
	}

	function getObjectStructure($context = ''): array {
		return GrapesTemplate::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getAllObjects($page, $recordsPerPage): array {
    	$object = new GrapesTemplate();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$userHasExistingObjects = true;
		$objectList = [];
		if ($userHasExistingObjects) {
			$object->find();
			while ($object->fetch()) {
				$objectList[$object->id] = clone $object;
			}
		}
		return $objectList;
	}

	function getTemplateById($id) {
    	$template = new GrapesTemplate();
    	$template->find();
    	while ($template->fetch()){
			if ($template->id == $id) {
				return clone $template;
    		}
    	}
		return null;
	}

	function getTemplateByName($templateName) {
    	$template = new GrapesTemplate();
    	$template->find();
    	while ($template->fetch()){
        	if ($template->templateName == $templateName) {
            	return clone $template;
			}
		}
		return null;
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

	public function canAddNew(){
    	return true;
	}

	public function canCopy() {
		return true;
	}

	public function canDelete() {
    	return true;
	}

	public function canExportToCSV() {
    	return false;
	}

	function getActiveAdminSection(): string {
		return 'web_builder';
	}

	function getAdditionalObjectActions($existingObject): array {
		$objectActions = [];
		if (!empty($existingObject) && $existingObject instanceof GrapesTemplate && !empty($existingObject->id)){
			$objectActions[] = [
				'text' => 'Open Editor',
				'url' => '/WebBuilder/GrapesJSTemplates?objectAction=edit&id=' . $existingObject->id,
			];
		}
		return $objectActions;
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#web_builder', 'Web Builder');
		$breadcrumbs[] = new Breadcrumb('/WebBuilder/Templates', 'Templates');
		return $breadcrumbs;
	}
}