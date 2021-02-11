<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/sys/Administration/BlockPatronAccountLink.php'; // Database object
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';

class Admin_BlockPatronAccountLinks extends ObjectEditor
{

	/**
	 * The class name of the object which is being edited
	 */
	function getObjectType()
	{
		return 'BlockPatronAccountLink';
	}

	/**
	 * The page name of the tool (typically the plural of the object)
	 */
	function getToolName()
	{
		return 'BlockPatronAccountLinks';
	}

	/**
	 * The title of the page to be displayed
	 */
	function getPageTitle()
	{
		return 'Block Patron Account Links';
	}

	/**
	 * Load all objects into an array keyed by the primary key
	 */
	function getAllObjects($page, $recordsPerPage)
	{
		$object = new BlockPatronAccountLink();
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$this->applyFilters($object);
		$object->find();
		$objectList = array();
		while ($object->fetch()){
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}

	function getDefaultSort()
	{
		return 'id';
	}

	function canSort()
	{
		return false;
	}

	/**
	 * Define the properties which are editable for the object
	 * as well as how they should be treated while editing, and a description for the property
	 */
	function getObjectStructure()
	{
		return BlockPatronAccountLink::getObjectStructure();
	}

	/**
	 * The name of the column which defines this as unique
	 */
	function getPrimaryKeyColumn()
	{
		return 'id';
	}

	/**
	 * The id of the column which serves to join other columns
	 */
	function getIdKeyColumn()
	{
		return 'id';
	}

	function getInstructions(){
//		return '<p>To block a patron from viewing the information of another patron by linking accounts:</p>
//		<br>
// 		<ul>
// 		<li>First enter the barcode of the user you want to prevent from seeing the other account as the <b>"The following blocked barcode will not have access to the account below."</b></li>
// 		<li>Next enter the barcode of the user you want to prevent from being viewed by the other account as the <b>"The following barcode will not be accessible by the blocked barcode above."</b></li>
// 		<li>If the user should not be able to see any other accounts at all, check <b>"Check this box to prevent the blocked barcode from accessing ANY linked accounts."</b></li>
// 		<li>Now select a <b>Save Changes</b> button</li>
// 		</ul>
// 		<br>
// 		<p class="alert alert-warning">
// 		<span class="glyphicon glyphicon-warning-sign" aria-hidden="true"></span> Blocking a patron from linking accounts will not prevent a user from manually logging into other accounts.
// 		If you suspect that someone has been accessing other accounts incorrectly, you should issue new cards or change PINs for the accounts they have accessed in addition to blocking them.
//		</p>';
	}

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#primary_configuration', 'Primary Configuration');
		$breadcrumbs[] = new Breadcrumb('/Admin/BlockPatronAccountLinks', 'Block Patron Account Linking');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'primary_configuration';
	}

	function canView()
	{
		return UserAccount::userHasPermission('Block Patron Account Linking');
	}
}