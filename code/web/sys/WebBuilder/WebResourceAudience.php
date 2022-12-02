<?php


class WebResourceAudience extends DataObject {
	public $__table = 'web_builder_resource_audience';
	public $id;
	public $webResourceId;
	public $audienceId;

	public function getAudience() {
		$audience = new WebBuilderAudience();
		$audience->id = $this->audienceId;
		if ($audience->find(true)) {
			return $audience;
		} else {
			return false;
		}
	}
}