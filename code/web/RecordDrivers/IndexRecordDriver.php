<?php

require_once ROOT_DIR . '/RecordDrivers/RecordInterface.php';

/**
 * Index Record Driver
 *
 * This base class contains utility functions for records that appear in a Solr Index
 *  - Grouped Works
 *  - People
 *  - Lists
 */
abstract class IndexRecordDriver extends RecordInterface
{
	protected $fields;
	protected $index = false;

	/**
	 * These Solr fields should NEVER be used for snippets.  (We exclude author
	 * and title because they are already covered by displayed fields; we exclude
	 * spelling because it contains lots of fields jammed together and may cause
	 * incorrect output; we exclude ID because random numbers are not helpful).
	 *
	 * @var    array
	 * @access protected
	 */
	protected $forbiddenSnippetFields = array(
		'author', 'author-letter', 'auth_author2', 'title', 'title_short', 'title_full',
		'title_auth', 'subtitle_display', 'title_display', 'spelling', 'id',
		'fulltext_unstemmed', 'econtentText_unstemmed',
		'spellingShingle', 'collection', 'title_proper',
		'display_description'
	);

	/**
	 * These are captions corresponding with Solr fields for use when displaying
	 * snippets.
	 *
	 * @var    array
	 * @access protected
	 */
	protected $snippetCaptions = array(
		'display_description' => 'Description'
	);

	/**
	 * Should we highlight fields in search results?
	 *
	 * @var    bool
	 * @access protected
	 */
	protected $highlight = false;

	/**
	 * Should we include snippets in search results?
	 *
	 * @var    bool
	 * @access protected
	 */
	protected $snippet = false;

	/**
	 * Constructor.  We build the object using all the data retrieved
	 * from the (Solr) index.  Since we have to
	 * make a search call to find out which record driver to construct,
	 * we will already have this data available, so we might as well
	 * just pass it into the constructor.
	 *
	 * @param array|string $recordData Data to construct the driver from
	 * @access  public
	 */
	public function __construct($recordData)
	{
		$this->fields = $recordData;

		global $configArray;
		// Load highlighting/snippet preferences:
		$searchSettings = getExtraConfigArray('groupedWorksSearches');
		$this->highlight = $configArray['Index']['enableHighlighting'];
		$this->snippet = $configArray['Index']['enableSnippets'];
		$this->snippetCaptions = isset($searchSettings['Snippet_Captions']) && is_array($searchSettings['Snippet_Captions']) ? $searchSettings['Snippet_Captions'] : array();
	}

	public function getExplain()
	{
		if (isset($this->fields['explain'])) {
			return nl2br(str_replace(' ', '&nbsp;', $this->fields['explain']));
		}
		return null;
	}

	/**
	 * @return string[]
	 */
	public function getFormat()
	{
		if (isset($this->fields['format'])) {
			if (is_array($this->fields['format'])) {
				return $this->fields['format'];
			} else {
				return array($this->fields['format']);
			}
		} else {
			return array("Unknown");
		}
	}

	/**
	 * Get an array of all the formats associated with the record.
	 *
	 * @access  public
	 * @return  array
	 */
	public function getFormats()
	{
		return isset($this->fields['format']) ? $this->fields['format'] : array();
	}

	/**
	 * Get an array of all the format categories associated with the record.
	 *
	 * @return  array
	 */
	public function getFormatCategory()
	{
		return isset($this->fields['format_category']) ? $this->fields['format_category'] : array();
	}

	/**
	 * Pick one line from the highlighted text (if any) to use as a snippet.
	 *
	 * @return mixed False if no snippet found, otherwise associative array
	 * with 'snippet' and 'caption' keys.
	 * @access protected
	 */
	protected function getHighlightedSnippets()
	{
		$snippets = array();
		// Only process snippets if the setting is enabled:
		if ($this->snippet && isset($this->fields['_highlighting'])) {
			if (is_array($this->fields['_highlighting'])) {
				foreach ($this->fields['_highlighting'] as $key => $value) {
					if (!in_array($key, $this->forbiddenSnippetFields)) {
						$snippets[] = array(
							'snippet' => $value[0],
							'caption' => $this->getSnippetCaption($key)
						);
					}
				}
			}
			return $snippets;
		}

		// If we got this far, no snippet was found:
		return false;
	}

	public function getId()
	{
		if (isset($this->fields['id'])) {
			return $this->fields['id'];
		}
		return null;
	}

	public function getLanguage()
	{
		if (isset($this->fields['language'])) {
			return $this->fields['language'];
		} else {
			return "Implement this when not backed by Solr data";
		}
	}

	/**
	 * Assign necessary Smarty variables and return a template name to
	 * load in order to display a summary of the item suitable for use in
	 * user's favorites list.
	 *
	 * @access  public
	 * @param int $listId ID of list containing desired tags/notes (or
	 *                              null to show tags/notes from all user's lists).
	 * @param bool $allowEdit Should we display edit controls?
	 * @return  string              Name of Smarty template file to display.
	 */
	public abstract function getListEntry($listId = null, $allowEdit = true);

	public function getMoreDetailsOptions()
	{
		return $this->getBaseMoreDetailsOptions(false);
	}

	/**
	 * Get the main author of the record.
	 *
	 * @access  protected
	 * @return  string
	 */
	public function getPrimaryAuthor()
	{
		return isset($this->fields['author_display']) ? $this->fields['author_display'] : '';
	}

	public function getPrimaryFormat()
	{
		$formats = $this->getFormats();
		return reset($formats);
	}

	/**
	 * Get the publishers of the record.
	 *
	 * @access  protected
	 * @return  array
	 */
	protected function getPublishers()
	{
		return isset($this->fields['publisher']) ?
			$this->fields['publisher'] : array();
	}

	public function getScore()
	{
		if (isset($this->fields['score'])) {
			return $this->fields['score'];
		}
		return null;
	}

	public abstract function getSearchResult($view = 'list');

	/**
	 * Given a Solr field name, return an appropriate caption.
	 *
	 * @param string $field Solr field name
	 *
	 * @return mixed        Caption if found, false if none available.
	 * @access protected
	 */
	protected function getSnippetCaption($field)
	{
		if (isset($this->snippetCaptions[$field])) {
			return $this->snippetCaptions[$field];
		} else {
			if (preg_match('/callnumber/', $field)) {
				return 'Call Number';
			} else {
				return ucwords(str_replace('_', ' ', $field));
			}

		}
	}

	/**
	 * Get the full title of the record.
	 *
	 * @return  string
	 */
	public function getTitle()
	{
		$title = isset($this->fields['title']) ? $this->fields['title'] : (isset($this->fields['title_display']) ? $this->fields['title_display'] : '');
		if (strpos($title, '|') > 0) {
			$title = substr($title, 0, strpos($title, '|'));
		}
		return trim($title);
	}

	public function getSortableTitle(){
		$title = $this->getTitle();
		$title = preg_replace('/^(?:(an|a|the|el|la)\s)/i', '', $title);
		return $title;
	}

	/**
	 * Return the unique identifier of this record within the Solr index;
	 * useful for retrieving additional information (like tags and user
	 * comments) from the external MySQL database.
	 *
	 * @access  public
	 * @return  string              Unique identifier.
	 */
	public function getUniqueID()
	{
		return $this->fields['id'];
	}

	/**
	 * Assign necessary Smarty variables and return a template name
	 * to load in order to display the requested citation format.
	 * For legal values, see getCitationFormats().  Returns null if
	 * format is not supported.
	 *
	 * @param string $format Citation format to display.
	 * @access  public
	 * @return  string              Name of Smarty template file to display.
	 */
	public function getCitation($format)
	{
		require_once ROOT_DIR . '/sys/CitationBuilder.php';

		// Build author list:
		$authors = array();
		$primary = $this->getPrimaryAuthor();
		if (!empty($primary)) {
			$authors[] = $primary;
		}

		// Collect all details for citation builder:
		$publishers = $this->getPublishers();
		//$pubPlaces = $this->getPlacesOfPublication();
		$details = array(
			'authors' => $authors,
			'title' => $this->getTitle(),
			'subtitle' => '',
			//'pubPlace' => count($pubPlaces) > 0 ? $pubPlaces[0] : null,
			'pubName' => count($publishers) > 0 ? $publishers[0] : null,
			'pubDate' => null,
			'edition' => null,
			'format' => $this->getFormats()
		);

		// Build the citation:
		$citation = new CitationBuilder($details);
		switch ($format) {
			case 'APA':
				return $citation->getAPA();
			case 'AMA':
				return $citation->getAMA();
			case 'ChicagoAuthDate':
				return $citation->getChicagoAuthDate();
			case 'ChicagoHumanities':
				return $citation->getChicagoHumanities();
			case 'MLA':
				return $citation->getMLA();
		}
		return '';
	}

	public function getBrowseResult()
	{
		global $interface;
		$id = $this->getUniqueID();
		$interface->assign('summId', $id);

		$url = $this->getLinkUrl();

		$interface->assign('summUrl', $url);
		$interface->assign('summTitle', $this->getTitle());
		$interface->assign('summAuthor', $this->getPrimaryAuthor());

		$interface->assign('bookCoverUrl', $this->getBookcoverUrl('small'));
		$interface->assign('bookCoverUrlMedium', $this->getBookcoverUrl('medium'));

		return 'RecordDrivers/Index/browse_result.tpl';
	}

	/**
	 * Assign necessary Smarty variables and return a template name to
	 * load in order to display a summary of the item suitable for use in
	 * search results.
	 *
	 * @access  public
	 * @param string $view The current view.
	 *
	 * @return  string              Name of Smarty template file to display.
	 */
	public function getCombinedResult($view = 'list')
	{
		if ($view == 'covers') { // Displaying Results as bookcover tiles
			return $this->getBrowseResult();
		}

		// Displaying results as the default list
		global $configArray;
		global $interface;
		global $timer;
		global $memoryWatcher;

		$interface->assign('displayingSearchResults', true);

		$id = $this->getUniqueID();
		$timer->logTime("Starting to load search result for grouped work $id");
		$interface->assign('summId', $id);

		$interface->assign('summUrl', $this->getLinkUrl());
		$interface->assign('summTitle', $this->getTitle());
		$interface->assign('summAuthor', $this->getPrimaryAuthor());
		$memoryWatcher->logMemory("Finished assignment of main data");

		//Description
		$interface->assign('summDescription', $this->getDescription());

		$interface->assign('bookCoverUrl', $this->getBookcoverUrl('small'));
		$interface->assign('bookCoverUrlMedium', $this->getBookcoverUrl('medium'));

		$interface->assign('recordDriver', $this);

		return 'RecordDrivers/Index/combinedResult.tpl';
	}

	public function getFields(){
		return $this->fields;
	}
}
