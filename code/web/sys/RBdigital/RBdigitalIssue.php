<?php


class RBdigitalIssue extends DataObject {
	public $__table = 'rbdigital_magazine_issue';

	public $id;
	public $magazineId;
	public $issueId;
	public $imageUrl;
	public $publishedOn;
	public $coverDate;
}