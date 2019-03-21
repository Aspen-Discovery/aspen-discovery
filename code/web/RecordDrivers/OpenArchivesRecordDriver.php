<?php

require_once 'IndexRecordDriver.php';
class OpenArchivesRecordDriver extends IndexRecordDriver
{
    public function getListEntry($user, $listId = null, $allowEdit = true)
    {
        // TODO: Implement getListEntry() method.
    }

    public function getSearchResult($view = 'list')
    {
        global $interface;

        $openArchiveUrl = $this->getUniqueID();
        $interface->assign('bookCoverUrl', $this->getBookcoverUrl('small'));
        $interface->assign('openArchiveUrl', $openArchiveUrl);
        $interface->assign('title', $this->getTitle());
        if (isset($this->fields['description'])){
            $interface->assign('description', $this->getDescription());
        }else{
            $interface->assign('description', '');
        }
        $interface->assign('type', $this->fields['type']);
        $interface->assign('source', isset($this->fields['source']) ? $this->fields['source'] : '');
        $interface->assign('publisher', isset($this->fields['publisher']) ? $this->fields['publisher'] : '');
        if (array_key_exists('date', $this->fields)) {
            $interface->assign('date', $this->fields['date']);
        } else {
            $interface->assign('date', null);
        }

        return 'RecordDrivers/OpenArchives/result.tpl';
    }

    public function getBookcoverUrl($size = 'small')
    {
        //The thumbnail is not saved in the metadata.  To get the URL we need to fetch the page
        //and then get the thumbnail from the og:image element
        //TODO: Add caching for the cover image
        $url = $this->fields['identifier'];
        $pageContents = file_get_contents($url);
        $matches = [];
        $bookcoverUrl = '';
        if (preg_match('~<meta property="og:image" content="(.*?)" />~', $pageContents, $matches)){
            $bookcoverUrl = $matches[1];
        }

        return $bookcoverUrl;
    }

    public function getModule()
    {
        return 'OpenArchives';
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
        // TODO: Implement getRecordActions() method.
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
        return $this->fields['identifier'];
    }

    public function getLinkUrl($absolutePath = false) {
        return $this->fields['identifier'];
    }
}