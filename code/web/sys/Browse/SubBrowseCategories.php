<?php

/**
 *  Class for managing sub-categories of Browse Categories
 *
 * @category Pika
 * @author Pascal Brammeier <pascal@marmot.org>
 * Date: 6/3/2015
 *
 */
require_once ROOT_DIR . '/sys/DB/DataObject.php';

class SubBrowseCategories extends DataObject {
	public $__table = 'browse_category_subcategories';
	public
		$id,
		$weight,
		$browseCategoryId, // ID of the Main or Parent browse category
		$subCategoryId;    // ID of the browse Category which is the Sub-Category or Child browse category

	static function getObjectStructure(){
		$browseCategoryList = self::listBrowseCategories();
		$structure = array(
			'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id of the sub-category row within the database'),
			'browseCategoryId' => array('property'=>'browseCategoryId', 'type'=>'label', 'label'=>'Browse Category', 'description'=>'The parent browse category'),
//			'browseCategoryId' => array('property'=>'browseCategoryId', 'type'=>'enum', 'values'=>$browseCategoryList, 'label'=>'Browse Category', 'description'=>'The parent browse category'),
			'subCategoryId'    => array('property'=>'subCategoryId', 'type'=>'enum', 'values'=>$browseCategoryList, 'label'=>'Sub-Category', 'description'=>'The sub-category of the parent browse category'),
			'weight' => array('property' => 'weight', 'type' => 'numeric', 'label' => 'Weight', 'weight' => 'Defines the order of the sub-categories .  Lower weights are displayed to the left of the screen.', 'required'=> true),

		);
		return $structure;
	}

	static function listBrowseCategories(){
		$browseCategoryList = array();
		require_once ROOT_DIR . '/sys/Browse/BrowseCategory.php';

		$browseCategories = new BrowseCategory();
		$browseCategories->orderBy('label');
		$browseCategories->selectAdd();
		$browseCategories->selectAdd('id, CONCAT(`label`, " (", `textID`, ")") AS `option`');
		$browseCategoryList = $browseCategories->fetchAll('id', 'option');

		return $browseCategoryList;
	}

}