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
	function getAllObjects(){
		$list = array();

		$object = new TranslationMap();
		$object->orderBy('name');
		$object->find();
		while ($object->fetch()){
			$list[$object->id] = clone $object;
		}

		return $list;
	}
	function getObjectStructure(){
		return TranslationMap::getObjectStructure();
	}
	function getAllowableRoles(){
		return array('opacAdmin');
	}
	function getPrimaryKeyColumn(){
		return 'id';
	}
	function getIdKeyColumn(){
		return 'id';
	}
	function canAddNew(){
		return UserAccount::userHasRole('opacAdmin');
	}
	function canDelete(){
		return UserAccount::userHasRole('opacAdmin');
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
		}

		return $actions;
	}

}