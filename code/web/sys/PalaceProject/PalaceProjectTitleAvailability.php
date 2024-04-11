<?php

class PalaceProjectTitleAvailability extends DataObject {
	public $__table = 'palace_project_title_availability';
	public $id;
	public $titleId;
	public $collectionId;
	public $lastSeen;
	public $deleted;
}