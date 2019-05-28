<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/ListWidget.php';
require_once ROOT_DIR . '/sys/ListWidgetList.php';
require_once ROOT_DIR . '/sys/DataObjectUtil.php';

/**
 * Provides a method of running SQL updates to the database.
 * Shows a list of updates that are available with a description of the
 *
 * @author Mark Noble
 *
 */
class ListWidgetsListsLinks extends Admin_Admin {

	function launch()
	{
		global $interface;
		//Figure out what mode we are in
		if (isset($_REQUEST['objectAction'])){
			$objectAction = $_REQUEST['objectAction'];
		}else{
			$objectAction = 'edit';
		}

		switch ($objectAction)
		{
			/** @noinspection PhpMissingBreakStatementInspection */
			case 'save':
				$this->launchSave();//Yes, there is not a break after this case.
			case 'edit':
				$this->launchEdit($_REQUEST['widgetId'], $_REQUEST['widgetListId']);
				break;
		}
		$interface->assign('sidebar', 'MyAccount/account-sidebar.tpl');
		$interface->display('layout.tpl');
	}


	private function launchSave()
	{
		if (!empty($_REQUEST['id']))//Save existing elements
		{
			$tmpREQUEST = $DATA = $_REQUEST;
			unset($_REQUEST);
			foreach($DATA['id'] as $key=>$val)
			{
				if($DATA['toDelete_'.$key]!=1)
				{
					$this->setRequestValues($key, $DATA['name'][$key], $DATA['listWidgetListsId'][$key], $DATA['link'][$key], $DATA['weight'][$key]);
					$this->saveElement();
				}
				else
				{
					$this->deleteLink($key);
				}
				unset($_REQUEST, $listWidgetLinks);
			}
			$_REQUEST = $tmpREQUEST;
		}

		//New Elements?
		if(!empty($_REQUEST['newLink']))
		{
			$tmpREQUEST = $DATA = $_REQUEST;
			unset($_REQUEST);
			foreach($DATA['newLink'] as $key=>$val)
			{
				if(!empty($DATA['nameNewLink'][$key]) && !empty($DATA['linkNewLink'][$key]) )
				{
					$this->setRequestValues('', $DATA['nameNewLink'][$key],$DATA['widgetListId'], $DATA['linkNewLink'][$key], $DATA['weightNewLink'][$key]);
					$this->saveElement();
					unset($_REQUEST, $listWidgetLinks);
				}
			}
			$_REQUEST = $tmpREQUEST;
		}

	}

	private function launchEdit($widgetId, $widgetListId)
	{
		global $interface;
		$interface->setPageTitle('List Widgets');

		//Get Info about the Widget
		$widget = new ListWidget();
		$widget->whereAdd('id = '.$widgetId);
		$widget->find();
		$widget->fetch();
		$interface->assign('widgetName', $widget->name);
		$interface->assign('widgetId', $widget->id);

		//Get Info about the current TAB
		$widgetList = new ListWidgetList();
		$widgetList->whereAdd('id = '.$widgetListId);
		$widgetList->find();
		$widgetList->fetch();
		$interface->assign('widgetListName', $widgetList->name);

		//Get all available links
		$availableLinks = array();
		$listWidgetLinks = new ListWidgetListsLinks();
		$listWidgetLinks->whereAdd('listWidgetListsId = '.$widgetListId);
		$listWidgetLinks->orderBy('weight ASC');
		$listWidgetLinks->find();
		while ($listWidgetLinks->fetch()){
			$availableLinks[$listWidgetLinks->id] = clone($listWidgetLinks);
		}
		$interface->assign('availableLinks', $availableLinks);
		$interface->setTemplate('listWidgetListLinks.tpl');
	}

	private function setRequestValues($id, $name, $listWidgetListsId, $link, $weight)
	{
		$_REQUEST['id'] = $id;
		$_REQUEST['name'] = $name;
		$_REQUEST['listWidgetListsId'] = $listWidgetListsId;
		$_REQUEST['link'] = $link;
		$_REQUEST['weight'] = $weight;
	}

	private function deleteLink($linkId)
	{
		$listWidgetLinks = new ListWidgetListsLinks();
		$listWidgetLinks->get($linkId);
		$listWidgetLinks->delete();
	}

	private function saveElement()
	{
		$listWidgetLinks = new ListWidgetListsLinks();
		DataObjectUtil::updateFromUI($listWidgetLinks, $listWidgetLinks->getObjectStructure());
		DataObjectUtil::saveObject($listWidgetLinks->getObjectStructure(), "ListWidgetListsLinks");
	}

	public function getAllowableRoles(){
		return array('opacAdmin');
	}

}