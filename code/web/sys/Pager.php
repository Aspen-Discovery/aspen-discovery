<?php

/**
 * Pager Class
 *
 * Creates pagination links for search results and other locations
 *
 */
class Pager
{
	private $options;
	private $_currentPage;
	private $_totalPages;
	private $_baseUrl;

	/**
	 * Constructor
	 *
	 * Initialize the PEAR pager object.
	 *
	 * @param array $options The Pager options to override.
	 * @access  public
	 */
	public function __construct($options = array())
	{
		// Set default Pager options:
		$finalOptions = array(
			'perPage' => 20,
			'urlVar' => 'page',
			'totalItems' => 0,
			'canChangeRecordsPerPage' => false,
			'canJumpToPage' => false
		);

		// Override defaults with user-provided values:
		foreach ($options as $optionName => $optionValue) {
			$finalOptions[$optionName] = $optionValue;
		}
		$this->options = $finalOptions;

		if (isset($this->options)) {
			$urlVar = $this->options['urlVar'];
		}
		$this->_currentPage = (isset($_REQUEST[$urlVar]) && is_numeric($_REQUEST[$urlVar])) ? $_REQUEST[$urlVar] : 1;
		$this->_totalPages = ceil($this->options['totalItems'] / $this->options['perPage']);

		$this->_baseUrl = $this->curPageURL();
		//remove the page parameter
		$this->_baseUrl = preg_replace("/\?{$this->options['urlVar']}=\d+&/", '?', $this->_baseUrl);
		$this->_baseUrl = preg_replace("/{$this->options['urlVar']}=\d+&|[?&]{$this->options['urlVar']}=\d+/", '', $this->_baseUrl);
	}

	private function curPageURL()
	{
		$pageURL = 'http';
		if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {
			$pageURL .= "s";
		}
		$pageURL .= "://";
		if ($_SERVER["SERVER_PORT"] != "80") {
			$pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
		} else {
			$pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
		}
		return $pageURL;
	}


	/**
	 * Generate the pager HTML using the options passed to the constructor.
	 *
	 * @access  public
	 * @return  array
	 */
	public function getLinks()
	{
		$links = array();
		if ($this->_totalPages > 1) {
			//$links['all'] = 'Pagination goes here.  On page ' . $this->getCurrentPage() . ' of ' . $this->getTotalPages();
			$linksText = '<nav aria-label="Page navigation">';
			if ($this->options['canJumpToPage']){
				$linksText .= '<div class="form-group">';
				$linksText .= '<label for="page" class="control-label">'. translate(['text' => 'Go to page', 'isPublicFacing'=>true]) . '&nbsp;</label>';
				$linksText .= '<input type="text" min="1" max="' . $this->_totalPages . '" id="page" name="page" size="2" class="input input-sm">';
				$linksText .= '<button id="goToPageSubmit" name="goToPageSubmit" class="input-sm" onclick="return AspenDiscovery.changePage();">' . translate(['text' => 'Go', 'isPublicFacing'=>true]) . '</button>';
				$linksText .= '</div>';
			}
			$linksText .= '<div class="pagination btn-group btn-group-sm justify-content-end">';
			if ($this->getCurrentPage() != 1) {
				$linksText .= $this->renderLink(1) . '[1]</a>';
				$linksText .= $this->renderLink($this->_currentPage - 1) . '&laquo; ' . translate(['text' => 'Previous', 'isPublicFacing'=>true]) . '</a>';
			}

			//Print links to pages before and after the current
			$firstPageToPrint = $this->_currentPage - 2;
			$lastPageToPrint = $this->_currentPage + 2;
			if ($firstPageToPrint < 1) {
				$lastPageToPrint -= ($firstPageToPrint - 1);
				$firstPageToPrint = 1;
			}
			if ($lastPageToPrint > $this->_totalPages) {
				$firstPageToPrint -= $lastPageToPrint - $this->_totalPages;
				$lastPageToPrint = $this->_totalPages;
			}
			if ($firstPageToPrint < 1) {
				$firstPageToPrint = 1;
			}
			if ($lastPageToPrint > $this->_totalPages) {
				$lastPageToPrint = $this->_totalPages;
			}
			for ($i = $firstPageToPrint; $i <= $lastPageToPrint; $i++) {
				$active = ($this->_currentPage == $i) ? ' active' : '';
				$linksText .= $this->renderLink($i, $active) . "$i</a>";
			}
			if ($this->_currentPage != $this->_totalPages) {
				$linksText .= $this->renderLink($this->_currentPage + 1) . translate(['text' => 'Next', 'isPublicFacing'=>true]) . ' &raquo;</a>';
				$linksText .= $this->renderLink($this->getTotalPages()) . '[' . $this->getTotalPages() . ']</a>';
			}
			$linksText .= '</div>';
			if ($this->options['canChangeRecordsPerPage']){
				$linksText .= '<div class="form-group">';
				$linksText .= '<select id="pageSize" name="pageSize" class="pageSize form-control input-sm" onchange="AspenDiscovery.changePageSize()">';
				$linksText .= '<option value="25" ' . ($this->options['perPage'] == 25 ? 'selected="selected"' : '') . '>25</option>';
				if ($this->options['totalItems'] > 25) {
					$linksText .= '<option value="50" ' . ($this->options['perPage'] == 50 ? 'selected="selected"' : '') . '>50</option>';
					if ($this->options['totalItems'] > 50) {
						$linksText .= '<option value="75" ' . ($this->options['perPage'] == 75 ? 'selected="selected"' : '') . '>75</option>';
						if ($this->options['totalItems'] > 75) {
							$linksText .= '<option value="100" ' . ($this->options['perPage'] == 100 ? 'selected="selected"' : '') . '>100</option>';
							if ($this->options['totalItems'] > 100) {
								$linksText .= '<option value="250" ' . ($this->options['perPage'] == 250 ? 'selected="selected"' : '') . '>250</option>';
							}
						}
					}
				}
				$linksText .= '</select>';
				$linksText .= '<label for="pageSize" class="control-label">'. translate(['text' => 'Per Page', 'isPublicFacing'=>true]) . '&nbsp;</label></div>';
			}
			$linksText .= '</nav>';
			$links['all'] = $linksText;
		} else {
			$links['all'] = null;
		}
		return $links;
	}

	public function renderLink($pageNumber, $active = false)
	{
		if (empty($this->options['linkRenderingFunction'])) {
			return '<a class="page-link btn btn-default btn-sm' . ($active ? ' active' : '') . '" href="' . $this->getPageUrl($pageNumber) . '" alt="Page ' . $pageNumber . '">';
		} else {
			$object = $this->options['linkRenderingObject'];
			$function = $this->options['linkRenderingFunction'];
			return $object->$function($pageNumber, $this->options);
		}
	}

	public function isLastPage()
	{
		$currentPage = $this->_currentPage;
		$totalPages = $this->_totalPages;
		return $currentPage == $totalPages;
	}

	public function getNumRecordsOnPage()
	{
		if (!$this->isLastPage()) {
			return $this->getItemsPerPage();
		}
		return $this->getTotalItems() - ($this->getItemsPerPage() * ($this->getCurrentPage() - 1));
	}

	public function getCurrentPage()
	{
		return $this->_currentPage;
	}

	public function getTotalPages()
	{
		return $this->_totalPages;
	}

	public function getTotalItems()
	{
		return $this->options['totalItems'];
	}

	public function getItemsPerPage()
	{
		return $this->options['perPage'];
	}

	public function getPageUrl($page)
	{
		if (strpos($this->_baseUrl, '?') > 0) {
			$url = $this->_baseUrl . '&' . $this->options['urlVar'] . '=' . $page;
		} else {
			$url = $this->_baseUrl . '?' . $this->options['urlVar'] . '=' . $page;
		}

		return $url;
	}

}
