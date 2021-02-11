<?php
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/File/FileUpload.php';

class WebBuilder_PDFs extends ObjectEditor
{
	function getObjectType()
	{
		return 'FileUpload';
	}

	function getToolName()
	{
		return 'PDFs';
	}

	function getModule()
	{
		return 'WebBuilder';
	}

	function getPageTitle()
	{
		return 'Uploaded PDFs';
	}

	function getAllObjects($page, $recordsPerPage)
	{
		$object = new FileUpload();
		$object->type = 'web_builder_pdf';
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$object->find();
		$objectList = array();
		while ($object->fetch()) {
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}

	function getDefaultSort()
	{
		return 'title asc';
	}

	function updateFromUI($object, $structure){
		$object->type = 'web_builder_pdf';
		return parent::updateFromUI($object, $structure);
	}

	function getObjectStructure()
	{
		$objectStructure = FileUpload::getObjectStructure();
		unset($objectStructure['type']);
		$fileProperty = $objectStructure['fullPath'];
		global $serverName;
		$dataPath = '/data/aspen-discovery/' . $serverName . '/uploads/web_builder_pdf/';
		$fileProperty['path'] = $dataPath;
		$fileProperty['validTypes'] = ['application/pdf'];
		$objectStructure['fullPath'] = $fileProperty;
		return $objectStructure;
	}

	function getPrimaryKeyColumn()
	{
		return 'id';
	}

	function getIdKeyColumn()
	{
		return 'id';
	}

	/**
	 * @param FileUpload $existingObject
	 * @return array
	 */
	function getAdditionalObjectActions($existingObject)
	{
		$objectActions = [];
		if (!empty($existingObject) && !empty($existingObject->id)){
			$objectActions[] = [
				'text' => 'View PDF',
				'url' => '/Files/' . $existingObject->id . '/ViewPDF',
			];
			$objectActions[] = [
				'text' => 'Download PDF',
				'url' => '/WebBuilder/DownloadPDF?id=' . $existingObject->id,
			];
		}
		return $objectActions;
	}

	function getInstructions()
	{
		return '';
	}

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#web_builder', 'Web Builder');
		$breadcrumbs[] = new Breadcrumb('/WebBuilder/PDFs', 'PDFs');
		return $breadcrumbs;
	}

	function canView()
	{
		return UserAccount::userHasPermission(['Administer All Web Content']);
	}

	function getActiveAdminSection()
	{
		return 'web_builder';
	}
}