<?php

class NovelistData extends DataObject
{
	public $id;
	public $groupedRecordPermanentId;
	public $groupedRecordHasISBN;
	public $hasNovelistData;
	public $primaryISBN;

	//Series Data
	public $seriesTitle;
	public $seriesNote;
	public $volume;

	public $lastUpdate;
	public $jsonResponse;
	private $_jsonData = null;

	/** @var array */
	protected $_seriesTitles = [];
	/** @var int */
	protected $_seriesDefaultIndex;
	/** @var int */
	protected $_seriesCount;
	/** @var int */
	protected $_seriesCountOwned;

	/** @var int */
	protected $_similarTitleCountOwned;
	/** @var array */
	protected $_similarTitles;

	/** @var array */
	protected $_authors;

	/** @var array */
	protected $_similarSeries;

	public $__table = 'novelist_data';

	public function getNumericColumnNames(): array
	{
		return ['hasNovelistData', 'groupedRecordHasISBN'];
	}

	public function getCompressedColumnNames() : array
	{
		return ['jsonResponse'];
	}

	public function getSeriesTitles()
	{
		return $this->_seriesTitles;
	}

	public function setSeriesTitles($seriesTitles)
	{
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
		return $this->_similarTitles == null ? 0 : count($this->_similarTitles);
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
		return $this->_authors == null ? 0 : count($this->_authors);
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
		return $this->_similarSeries == null ? 0 : count($this->_similarSeries);
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

	public function getJsonData()
	{
		if (empty($this->_jsonData) && $this->jsonResponse != null) {
			$this->_jsonData = json_decode($this->jsonResponse);
		}
		return $this->_jsonData;
	}

	public function setSeriesNote($seriesNote)
	{
		if (strlen($seriesNote) > 255) {
			require_once ROOT_DIR . '/sys/Utils/StringUtils.php';
			$this->seriesNote = StringUtils::truncate($seriesNote, 255);
		} else {
			$this->seriesNote = $seriesNote;
		}
	}
} 