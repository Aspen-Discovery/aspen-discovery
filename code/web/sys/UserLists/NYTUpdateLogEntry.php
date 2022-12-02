<?php
require_once ROOT_DIR . '/sys/BaseLogEntry.php';

class NYTUpdateLogEntry extends BaseLogEntry {
	public $__table = 'nyt_update_log';   // table name
	public $id;
	public $lastUpdate;
	public $notes;
	public $numLists;
	public $numAdded;
	public $numUpdated;
	public $numSkipped;

	public function addNote(string $note) {
		if (empty($this->notes)) {
			$this->notes = "<ol class='cronNotes'>";
		}
		$this->notes = str_replace('</ol>', '', $this->notes);
		$this->notes .= "<li>$note</li>";
		$this->notes .= '</ol>';
	}

	public function addError(string $error) {
		$this->numErrors++;
		$this->addNote($error);
		$this->update();
	}
}