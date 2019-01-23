<?php
class Pagination
{
	const initialPageDefault =1;
	const numItemsPerPageDefault = 30;
	
	private $page;
	private $numItemsPerPage;
	private $offset;
	
	public function __construct()
	{
		//Loading the defaults values
		$this->page = self::initialPageDefault;
		$this->numItemsPerPage = self::numItemsPerPageDefault;
		$this->setOffset();
	}
	
	public function setPagination($page = 1, $numItemsPerPage = 30)
	{
		if ($page < 1) $page = self::initialPageDefault;
		if ($numItemsPerPage < 1) $numItemsPerPage = self::numItemsPerPageDefault;
		$this->page = $page;
		$this->numItemsPerPage = $numItemsPerPage;
		$this->setOffset();
	}
	
	public function getLimitSQL()
	{
		return $this->getOffset().",".$this->getNumItemsPerPage;
	}
	
	//gettets
	public function getOffset()
	{
		return $this->offset;
	}
	
	public function getNumItemsPerPage()
	{
		return $this->numItemsPerPage;
	}
	
	private function setOffset()
	{
		switch ($this->page)
		{
			case 1:
				$this->offset = 0;
				break;
			default:
				$this->offset = ($this->page-1) * $this->numItemsPerPage;
				break;
		}
	}
}
?>