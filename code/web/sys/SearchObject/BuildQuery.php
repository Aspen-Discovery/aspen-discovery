<?php

class SummonQuery {
    private $query;
    private $holdings = true;
    private $facets = null;
    private $filters = array();
    private $groupFilters = array();
    private $rangeFilters = array();
    private $openAccessFilter = false;

    private $sort = null;

    // Page number
	protected $pageSize = 25;
	// Result limit
	// protected $limit = 20;
    protected $pageNumber = 1;

    protected $didYouMean = false;
    protected $highlight = false;
    protected $highlightStart = '';
    protected $highlightEnd = '';
    protected $language = 'en';
    protected $expand = false;
    protected $idsToFetch = array();
    protected $maxTopics = 1;



    /**
     * Constructor for Query Class
     * 
     * @param string $query
     * @param array $options - associative array of other options
     */
    public function __construct($query = null, $options = array()) {
        $this->query = $query;

        foreach ($options as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }

         // Define default facets to request if necessary:
         if (is_null($this->facets)) {
             $this->facets = array(
                'IsScholarly,or,1,2',
                'Library,or,1,30',
                'ContentType,or,1,30',
                 'SubjectTerms,or,1,30',
                 'Language,or,1,30'
            );
          }
    }

    /**
     * Turn the options within this object into an array of Summon parameters.
     *
     * @return array
     */
    public function getOptionsArray()
    {
        $options = array(
            's.q' => $this->query,
            's.ps' => $this->pageSize,
            's.pn' => $this->pageNumber,
            's.ho' => $this->holdings ? 'true' : 'false',
            's.dym' => $this->didYouMean ? 'true' : 'false',
            's.l' => $this->language,
            's.ff' => $this->facets,
        );
        if (!empty($this->idsToFetch)) {
            $options['s.fids'] = implode(',', (array)$this->idsToFetch);
        }
        if (!empty($this->filters)) {
            $options['s.fvf'] = $this->filters;
        }
        if ($this->maxTopics !== false) {
            $options['s.rec.topic.max'] = $this->maxTopics;
        }
        if (!empty($this->groupFilters)) {
            $options['s.fvgf'] = $this->groupFilters;
        }
        if (!empty($this->rangeFilters)) {
            $options['s.rf'] = $this->rangeFilters;
        }
        if (!empty($this->sort)) {
            $options['s.sort'] = $this->sort;
        }
        if ($this->expand) {
            $options['s.exp'] = 'true';
        }
        if ($this->openAccessFilter) {
            $options['s.oaf'] = 'true';
        }
        if ($this->highlight) {
            $options['s.hl'] = 'true';
            $options['s.hs'] = $this->highlightStart;
            $options['s.he'] = $this->highlightEnd;
        } else {
            $options['s.hl'] = 'false';
            $options['s.hs'] = $options['s.he'] = '';
        }
        return $options;
    }

    public function addFilter($f) {
        $this->filters[] = $f;
    }

    public function addGroupFilter($f){
        $this->groupFilters[] = $f;
    }

    public function addRangeFilter($f){
        $this->rangeFilters[] = $f;
    }

    //Setting and getting properties
   /**
     * Magic method for getting/setting properties.
     *
     * @param string $method Method being called
     * @param string $params Array of parameters
     *
     * @return mixed
     */
    public function __call($method, $params)
    {
        if (strlen($method) > 4) {
            $action = substr($method, 0, 3);
            $property = strtolower(substr($method, 3, 1)) . substr($method, 4);
            if ($action == 'get' && property_exists($this, $property)) {
                return $this->$property;
            }
            if ($action == 'set' && property_exists($this, $property)) {
                if (isset($params[0])) {
                    $this->$property = $params[0];
                    return;
                }
                throw new ErrorException(
                    $method . ' missing required parameter', 0, E_ERROR
                ); 
            }
        }
        throw new ErrorException(
            'Call to Undefined Method/Class Function', 0, E_ERROR
        ); 
    }

    public static function escapeParam($input)
    {
        // List of characters to escape taken from:
        //      http://api.summon.serialssolutions.com/help/api/search/parameters
        return addcslashes($input, ",:\\()\${}");
    }



}