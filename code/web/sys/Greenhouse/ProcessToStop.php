<?php

class ProcessToStop extends DataObject {
	public $__table = 'processes_to_stop';
	public $id;
	public $processId;
	public $processName;
	public $stopAttempted;
	public $stopResults;
}