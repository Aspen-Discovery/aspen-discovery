<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/LocalEnrichment/JavaScriptSnippet.php';

class Admin_JavaScriptSnippets extends ObjectEditor
{

	function getObjectType(){
		return 'JavaScriptSnippet';
	}
	function getToolName(){
		return 'JavaScriptSnippets';
	}
	function getPageTitle(){
		return 'JavaScript Snippets';
	}
	function canDelete(){
		return UserAccount::userHasPermission(['Administer All JavaScript Snippets', 'Administer Library JavaScript Snippets']);
	}
	function getAllObjects(){
		$javascriptSnippet = new JavaScriptSnippet();
		$javascriptSnippet->orderBy('name');
		if (!UserAccount::userHasPermission('Administer All JavaScript Snippets')){
			$libraryJavaScriptSnippet = new JavaScriptSnippetLibrary();
			$library = Library::getPatronHomeLibrary(UserAccount::getActiveUserObj());
			if ($library != null){
				$libraryJavaScriptSnippet->libraryId = $library->libraryId;
				$placardsForLibrary = [];
				$libraryJavaScriptSnippet->find();
				while ($libraryJavaScriptSnippet->fetch()){
					$placardsForLibrary[] = $libraryJavaScriptSnippet->javascriptSnippetId;
				}
				$javascriptSnippet->whereAddIn('id', $placardsForLibrary, false);
			}
		}
		$javascriptSnippet->find();
		$list = array();
		while ($javascriptSnippet->fetch()){
			$list[$javascriptSnippet->id] = clone $javascriptSnippet;
		}
		return $list;
	}
	function getObjectStructure(){
		return JavaScriptSnippet::getObjectStructure();
	}
	function getPrimaryKeyColumn(){
		return 'id';
	}
	function getIdKeyColumn(){
		return 'id';
	}
	function getInstructions()
	{
		return '';
	}
	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#local_enrichment', 'Local Enrichment');
		$breadcrumbs[] = new Breadcrumb('/Admin/JavaScriptSnippets', 'JavaScript Snippets');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'local_enrichment';
	}

	function canView()
	{
		return UserAccount::userHasPermission(['Administer All JavaScript Snippets', 'Administer Library JavaScript Snippets']);
	}
}