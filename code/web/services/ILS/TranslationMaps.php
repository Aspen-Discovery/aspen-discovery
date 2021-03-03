<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Indexing/TranslationMap.php';
class ILS_TranslationMaps extends ObjectEditor {
	function launch(){
		global $interface;
		$objectAction = isset($_REQUEST['objectAction']) ? $_REQUEST['objectAction'] : null;
		if ($objectAction == 'loadFromFile'){
			$id = $_REQUEST['id'];
			$translationMap = new TranslationMap();
			if ($translationMap->get($id)) {
				$interface->assign('mapName', $translationMap->name);
				$interface->assign('additionalObjectActions', $this->getAdditionalObjectActions($translationMap));
			}
			$interface->assign('id', $id);
			$this->display('../ILS/importTranslationMapData.tpl', "Import Translation Map Data");
			exit();
		}elseif($objectAction == 'doAppend' || $objectAction == 'doReload'){
			$id = $_REQUEST['id'];

			$translationMapData = $_REQUEST['translationMapData'];
			//Truncate the current data
			$translationMap = new TranslationMap();
			$translationMap->id = $id;
			if ($translationMap->find(true)){
				$newValues = array();
				if ($objectAction == 'doReload'){
					/** @var TranslationMapValue $value */
					/** @noinspection PhpUndefinedFieldInspection */
					foreach($translationMap->translationMapValues as $value){
						$value->delete();
					}
					/** @noinspection PhpUndefinedFieldInspection */
					$translationMap->translationMapValues = array();
					$translationMap->update();
				}else{
					/** @noinspection PhpUndefinedFieldInspection */
					foreach($translationMap->translationMapValues as $value){
						$newValues[$value->value] = $value;
					}
				}

				//Parse the new data
				$data = preg_split('/\\r\\n|\\r|\\n/', $translationMapData);

				foreach ($data as $dataRow){
					if (strlen(trim($dataRow)) != 0 && $dataRow[0] != '#'){
						$dataFields = preg_split('/[,=]/', $dataRow, 2);
						$value = trim(str_replace('"', '',$dataFields[0]));
						if (array_key_exists($value, $newValues)){
							$translationMapValue = $newValues[$value];
						}else{
							$translationMapValue = new TranslationMapValue();
						}
						$translationMapValue->value = $value;
						$translationMapValue->translation = trim(str_replace('"', '',$dataFields[1]));
						$translationMapValue->translationMapId = $id;

						$newValues[$translationMapValue->value] = $translationMapValue;
					}
				}
				/** @noinspection PhpUndefinedFieldInspection */
				$translationMap->translationMapValues = $newValues;
				$translationMap->update();
			}else{
				$interface->assign('error', "Sorry we could not find a translation map with that id");
			}

			//Show the results
			$_REQUEST['objectAction'] = 'edit';
		}else if ($objectAction == 'viewAsINI'){
			$id = $_REQUEST['id'];
			$translationMap = new TranslationMap();
			$translationMap->id = $id;
			if ($translationMap->find(true)){
				$interface->assign('id', $id);
				$interface->assign('additionalObjectActions', $this->getAdditionalObjectActions($translationMap));
				/** @noinspection PhpUndefinedFieldInspection */
				$interface->assign('translationMapValues', $translationMap->translationMapValues);
				$this->display('../ILS/viewTranslationMapAsIni.tpl', 'View Translation Map Data');
				exit();
			}else{
				$interface->assign('error', "Sorry we could not find a translation map with that id");
			}
		}else if ($objectAction == 'downloadAsINI'){
			$id = $_REQUEST['id'];
			$translationMap = new TranslationMap();
			$translationMap->id = $id;
			if ($translationMap->find(true)){
				$interface->assign('id', $id);

				$translationMapAsCsv = '';
				/** @var TranslationMapValue $mapValue */
				foreach ($translationMap->translationMapValues as $mapValue){
					$translationMapAsCsv .= $mapValue->value . ' = ' . $mapValue->translation . "\r\n";
				}
				header('Content-Description: File Transfer');
				header('Content-Type: application/octet-stream');
				header("Content-Disposition: attachment; filename={$translationMap->name}.ini");
				header('Content-Transfer-Encoding: utf-8');
				header('Expires: 0');
				header('Cache-Control: must-revalidate');
				header('Pragma: public');

				header('Content-Length: ' . strlen($translationMapAsCsv));
				ob_clean();
				flush();
				echo $translationMapAsCsv;
				exit();
			}else{
				$interface->assign('error', "Sorry we could not find a translation map with that id");
			}
		}
		parent::launch();
	}
	function getObjectType(){
		return 'TranslationMap';
	}
    function getModule()
    {
        return "ILS";
    }
	function getToolName(){
		return 'TranslationMaps';
	}
	function getPageTitle(){
		return 'Translation Maps';
	}
	function getAllObjects($page, $recordsPerPage){
		$list = array();

		$object = new TranslationMap();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$object->find();
		while ($object->fetch()){
			$list[$object->id] = clone $object;
		}

		return $list;
	}
	function getDefaultSort()
	{
		return 'name asc';
	}
	function getObjectStructure(){
		return TranslationMap::getObjectStructure();
	}
	function getPrimaryKeyColumn(){
		return 'id';
	}
	function getIdKeyColumn(){
		return 'id';
	}
	function canAddNew(){
		return true;
	}
	function canDelete(){
		return true;
	}

	/**
	 * @param TranslationMap $existingObject
	 * @return array
	 */
	function getAdditionalObjectActions($existingObject){
		$actions = array();
		if ($existingObject && $existingObject->id != ''){
			$actions[] = array(
				'text' => 'Load From CSV/INI',
				'url'  => '/ILS/TranslationMaps?objectAction=loadFromFile&id=' . $existingObject->id,
			);
			$actions[] = array(
				'text' => 'View as INI',
				'url'  => '/ILS/TranslationMaps?objectAction=viewAsINI&id=' . $existingObject->id,
			);
			$actions[] = array(
				'text' => 'Download as INI',
				'url'  => '/ILS/TranslationMaps?objectAction=downloadAsINI&id=' . $existingObject->id,
			);
		}

		return $actions;
	}

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#ils_integration', 'ILS Integration');
		if (!empty($this->activeObject) && $this->activeObject instanceof TranslationMap){
			$breadcrumbs[] = new Breadcrumb('/ILS/IndexingProfiles?objectAction=edit&id=' . $this->activeObject->indexingProfileId, 'Indexing Profile');
		}
		$breadcrumbs[] = new Breadcrumb('/ILS/TranslationMaps', 'Translation Maps');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'ils_integration';
	}

	function canView()
	{
		return UserAccount::userHasPermission('Administer Translation Maps');
	}
}