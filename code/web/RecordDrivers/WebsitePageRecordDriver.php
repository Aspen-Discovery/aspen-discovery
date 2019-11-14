<?php

require_once 'IndexRecordDriver.php';
class WebsitePageRecordDriver extends IndexRecordDriver
{
	private $valid;

	public function __construct($recordData){
		if (is_array($recordData)){
			parent::__construct($recordData);
			$this->valid =true;
		}else{
			require_once ROOT_DIR . '/sys/SearchObject/WebsitesSearcher.php';
			$searchObject = new SearchObject_WebsitesSearcher();
			$recordData = $searchObject->getRecord($recordData);
			parent::__construct($recordData);
			$this->valid =true;
		}
	}

	public function isValid(){
		return $this->valid;
	}

    public function getListEntry($user, $listId = null, $allowEdit = true)
    {
        return $this->getSearchResult('list');
    }

    public function getSearchResult($view = 'list')
    {
        global $interface;

        $interface->assign('id', $this->getId());
        $interface->assign('bookCoverUrl', $this->getBookcoverUrl('small'));
        $interface->assign('pageUrl', $this->getLinkUrl());
        $interface->assign('website_name', $this->fields['website_name']);
        $interface->assign('title', $this->getTitle());
        if (isset($this->fields['description'])){
            $interface->assign('description', $this->getDescription());
        }else{
            $interface->assign('description', '');
        }
        $interface->assign('source', isset($this->fields['source']) ? $this->fields['source'] : '');

        require_once ROOT_DIR . '/sys/WebsiteIndexing/WebPageUsage.php';
        $webPageUsage = new WebPageUsage();
	    $webPageUsage->webPageId = $this->getUniqueID();
	    $webPageUsage->year = date('Y');
	    $webPageUsage->month = date('n');
        if ($webPageUsage->find(true)){
	        $webPageUsage->timesViewedInSearch++;
	        $webPageUsage->update();
        }else {
	        $webPageUsage->timesViewedInSearch = 1;
	        $webPageUsage->timesUsed = 0;
	        $webPageUsage->insert();
        }

        return 'RecordDrivers/WebPage/result.tpl';
    }

    public function getBookcoverUrl($size = 'small', $absolutePath = false)
    {
        global $configArray;

        if ($absolutePath){
            $bookCoverUrl = $configArray['Site']['url'];
        }else{
            $bookCoverUrl = '';
        }
        $bookCoverUrl .= "/bookcover.php?id={$this->getUniqueID()}&size={$size}&type=webpage";

        return $bookCoverUrl;


    }

    public function getModule()
    {
        return 'WebPage';
    }

    public function getStaffView()
    {
        // TODO: Implement getStaffView() method.
    }

    public function getDescription()
    {
        return $this->fields['description'];
    }

    public function getItemActions($itemInfo)
    {
        return array();
    }

    public function getRecordActions($isAvailable, $isHoldable, $isBookable, $relatedUrls = null)
    {
        return array();
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

    public function getLinkUrl($absolutePath = false) {
        return $this->fields['source_url'];
    }
}