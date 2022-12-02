<?php

class Breadcrumb {
	public $link;
	public $label;
	public $translate;

	public function __construct($link, $label, $translate = true) {
		$this->link = $link;
		$this->label = $label;
		$this->translate = $translate;
	}
}