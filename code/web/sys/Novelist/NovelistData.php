<?php
/**
 * Description goes here
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 12/2/13
 * Time: 11:33 AM
 */

class NovelistData extends DataObject{
	public $id;
	public $groupedRecordPermanentId;
	public $lastUpdate;
	public $groupedRecordHasISBN;
	public $hasNovelistData;
	public $primaryISBN;

	//Series Data
	public $seriesTitle;
    public $seriesNote;
    public $volume;

    /** @var array */
	private $_seriesTitles = [];
    /** @var int */
	private $_seriesDefaultIndex;
	/** @var int */
	private $_seriesCount;
    /** @var int */
	private $_seriesCountOwned;

	/** @var int */
    private $_similarTitleCountOwned;
    /** @var array */
    private $_similarTitles;

    /** @var array */
    private $_authors;

    /** @var array */
    private $_similarSeries;

	public $__table = 'novelist_data';
	public function getNumericColumnNames()
    {
        return ['hasNovelistData'];
    }

    public function getSeriesTitles(){
	    return $this->_seriesTitles;
    }

    public function setSeriesTitles($seriesTitles){
	    $this->_seriesTitles = $seriesTitles;
    }

    public function getSeriesDefaultIndex()
    {
        return $this->_seriesDefaultIndex;
    }

    public function setSeriesDefaultIndex($seriesDefaultIndex)
    {
        $this->_seriesDefaultIndex = $seriesDefaultIndex;
    }

    /**
     * @return int
     */
    public function getSeriesCount()
    {
        return $this->_seriesCount;
    }

    /**
     * @param int $seriesCount
     */
    public function setSeriesCount($seriesCount)
    {
        $this->_seriesCount = $seriesCount;
    }

    /**
     * @return int
     */
    public function getSeriesCountOwned()
    {
        return $this->_seriesCountOwned;
    }

    /**
     * @param int $seriesCountOwned
     */
    public function setSeriesCountOwned($seriesCountOwned)
    {
        $this->_seriesCountOwned = $seriesCountOwned;
    }

    /**
     * @return int
     */
    public function getSimilarTitleCountOwned()
    {
        return $this->_similarTitleCountOwned;
    }

    /**
     * @param int $similarTitleCountOwned
     */
    public function setSimilarTitleCountOwned($similarTitleCountOwned)
    {
        $this->_similarTitleCountOwned = $similarTitleCountOwned;
    }

    /**
     * @return int
     */
    public function getSimilarTitleCount()
    {
        return count($this->_similarTitles);
    }

    /**
     * @return array
     */
    public function getSimilarTitles()
    {
        return $this->_similarTitles;
    }

    /**
     * @param array $similarTitles
     */
    public function setSimilarTitles($similarTitles)
    {
        $this->_similarTitles = $similarTitles;
    }

    /**
     * @return int
     */
    public function getAuthorCount()
    {
        return count($this->_authors);
    }

    /**
     * @return array
     */
    public function getAuthors()
    {
        return $this->_authors;
    }

    /**
     * @param array $authors
     */
    public function setAuthors($authors)
    {
        $this->_authors = $authors;
    }

    /**
     * @return int
     */
    public function getSimilarSeriesCount()
    {
        return count($this->_similarSeries);
    }

    /**
     * @return array
     */
    public function getSimilarSeries()
    {
        return $this->_similarSeries;
    }

    /**
     * @param array $similarSeries
     */
    public function setSimilarSeries($similarSeries)
    {
        $this->_similarSeries = $similarSeries;
    }
} 