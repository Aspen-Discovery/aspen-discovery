<?php
require_once ROOT_DIR . '/sys/BaseLogEntry.php';
require_once ROOT_DIR . '/sys/Enrichment/NewYorkTimesSetting.php';

class NYTUpdateLogEntry extends BaseLogEntry {
	public $__table = 'nyt_update_log';   // table name
	public $id;
	public $notes;
	public $numLists;
	public $numAdded;
	public $numUpdated;
	public $numSkipped;
	private bool $extensiveLoggingEnabled;
	public int $haltRequested;

	/**
	 * NYTUpdateLogEntry constructor.
	 * Checks if extensive logging is enabled in the settings
	 */
	public function __construct()
	{
		// Check if extensive logging is enabled
		$nytSettings = new NewYorkTimesSetting();
		if ($nytSettings->find(true)) {
			$this->extensiveLoggingEnabled = ($nytSettings->enableExtensiveLogging == 1);
		}
	}

	public function addNote(string $note): void
	{
		if (empty($this->notes)) {
			$this->notes = "<ol class='cronNotes'>";
		}
		$this->notes = str_replace('</ol>', '', $this->notes);
		$this->notes .= "<li>$note</li>";
		$this->notes .= '</ol>';
	}

	public function addError(string $error): void
	{
		$this->numErrors++;
		$this->addNote("ERROR: " . $error);
		$this->update();
	}

	/**
	 * Add a note only if extensive logging is enabled.
	 *
	 * @param string $note The note to add.
	 */
	public function addExtensiveNote(string $note): void
	{
		if ($this->extensiveLoggingEnabled) {
			$this->addNote($note);
		}
	}

	/**
	 * Add an error only if extensive logging is enabled.
	 *
	 * @param string $error The error to add.
	 */
	public function addExtensiveError(string $error): void
	{
		if ($this->extensiveLoggingEnabled) {
			$this->addError($error);
		}
	}
}