<?php


class WebResourceCategory extends DataObject {
	public $__table = 'web_builder_resource_category';
	public $id;
	public $webResourceId;
	public $categoryId;

	/**
	 * @return bool|WebBuilderCategory
	 */
	public function getCategory() {
		$category = new WebBuilderCategory();
		$category->id = $this->categoryId;
		if ($category->find(true)) {
			return $category;
		} else {
			return false;
		}
	}
}