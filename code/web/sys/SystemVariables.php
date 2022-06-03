<?php


class SystemVariables extends DataObject
{
	public $__table = 'system_variables';
	public $id;
	public $errorEmail;
	public $ticketEmail;
	public $searchErrorEmail;
	public $loadCoversFrom020z;
	public $currencyCode;
	public $runNightlyFullIndex;
	public $allowableHtmlTags;
	public $allowHtmlInMarkdownFields;
	public $useHtmlEditorRatherThanMarkdown;
	public $storeRecordDetailsInSolr;
	public $storeRecordDetailsInDatabase;
	public $indexVersion;
	public $searchVersion;
	public $greenhouseUrl;
	public $libraryToUseForPayments;
	public $solrConnectTimeout;
	public $solrQueryTimeout;
	public $catalogStatus;
	public $offlineMessage;

	static function getObjectStructure() : array {
		return [
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id'),
			'greenhouseUrl' => array('property' => 'greenhouseUrl', 'type' => 'url', 'label' => 'Greenhouse URL', 'description' => 'URL of the Greenhouse to store shared content', 'maxLength' => 128),
			'errorEmail' => array('property' => 'errorEmail', 'type' => 'text', 'label' => 'Error Email Address', 'description' => 'Email Address to send errors to', 'maxLength' => 128),
			'ticketEmail' => array('property' => 'ticketEmail', 'type' => 'text', 'label' => 'Ticket Email Address', 'description' => 'Email Address to send tickets from administrators to', 'maxLength' => 128),
			'searchErrorEmail' => array('property' => 'searchErrorEmail', 'type' => 'text', 'label' => 'Search Error Email Address', 'description' => 'Email Address to send errors to', 'maxLength' => 128),
			'currencyCode' => array('property' => 'currencyCode', 'type' => 'enum', 'values' => ['USD' => 'USD', 'CAD' => 'CAD', 'EUR' => 'EUR', 'GBP' => 'GBP'], 'label' => 'Currency Code', 'description' => 'Currency code to use when formatting money', 'required' => true, 'default' => 'USD' ),
			'runNightlyFullIndex' => array('property' => 'runNightlyFullIndex', 'type' => 'checkbox', 'label' => 'Run full index tonight', 'description' => 'Whether or not a full index should be run in the middle of the night', 'default' => false),
			'regroupAllRecordsDuringNightlyIndex' => array('property' => 'regroupAllRecordsDuringNightlyIndex', 'type' => 'checkbox', 'label' => 'Regroup all records during nightly index', 'description' => 'Whether or not all records should be regrouped during the nightly index', 'default' => false),
			'storeRecordDetailsInSolr' => array('property' => 'storeRecordDetailsInSolr', 'type' => 'checkbox', 'label' => 'Store Record Details In Solr', 'description' => 'Whether or not a record details should be stored in solr (for backwards compatibility with 21.07)', 'default' => false),
			'storeRecordDetailsInDatabase' => array('property' => 'storeRecordDetailsInDatabase', 'type' => 'checkbox', 'label' => 'Store Record Details in Database', 'description' => 'Whether or not a record details should be stored in the database', 'default' => true),
			'indexVersion' => array('property' => 'indexVersion', 'type' => 'enum', 'values' => [1=>'Version 1 (No edition information)', 2=>'Version 2 (Edition information)'], 'label' => 'Grouped Work Indexing Version', 'description' => 'The Solr Core Version to index with.  In 22.06 and above this should be version 2 in most cases.', 'required' => true, 'default' => 2 ),
			'searchVersion' => array('property' => 'searchVersion', 'type' => 'enum', 'values' => [1=>'Version 1 (No edition information)', 2=>'Version 2 (Edition information)'], 'label' => 'Grouped Work Search Version', 'description' => 'The Solr Core Version to search with.  In 22.06 and above this should be version 2 in most cases.', 'required' => true, 'default' => 2 ),
			'loadCoversFrom020z' => array('property' => 'loadCoversFrom020z', 'type' => 'checkbox', 'label' => 'Load covers from cancelled & invalid ISBNs (020$z)', 'description' => 'Whether or not covers can be loaded from the 020z', 'default' => false),
			'allowableHtmlTags' => array('property' => 'allowableHtmlTags', 'type' => 'text', 'label' => 'Allowable HTML Tags (blank to allow all, separate tags with pipes)', 'description' => 'HTML Tags to allow in HTML and Markdown fields', 'maxLength' => 512, 'default'=>'p|em|i|strong|b|span|style|a|table|ul|ol|li|h1|h2|h3|h4|h5|h6|pre|code|hr|table|tbody|tr|th|td|caption|img|br|div|span', 'hideInLists'=>true),
			'allowHtmlInMarkdownFields' => array('property' => 'allowHtmlInMarkdownFields', 'type' => 'checkbox', 'label' => 'Allow HTML in Markdown fields', 'description' => 'Whether or administrators can add HTML to a Markdown field, if disabled, all tags will be stripped', 'default' => true),
			'useHtmlEditorRatherThanMarkdown' => array('property' => 'useHtmlEditorRatherThanMarkdown', 'type' => 'checkbox', 'label' => 'Use HTML Editor rather than Markdown', 'description' => 'Changes all Markdown fields to HTML fields', 'default' => true),
			'libraryToUseForPayments' => ['property'=>'libraryToUseForPayments','type'=>'enum','values'=>[0=>'Patron Home Library', 1=>'Active Catalog'], 'label'=>'Library to use for fine payments', 'description'=>'What library settings should be used when making fine payments', 'default'=>0],
			'solrConnectTimeout' => ['property' => 'solrConnectTimeout', 'type' => 'integer', 'label' => 'Solr Connect Timeout in seconds', 'required'=> true, 'default' => 2, 'min'=>1],
			'solrQueryTimeout' => ['property' => 'solrQueryTimeout', 'type' => 'integer', 'label' => 'Solr Query Timeout in seconds', 'required'=> true, 'default' => 10, 'min'=>1],
			'catalogStatus' => ['property' => 'catalogStatus', 'type' => 'enum', 'values' => [0=>'Catalog Online', 1=> 'Catalog Offline, no login allowed', 2 => 'Catalog Offline, login allowed with eContent active'], 'label' => 'Catalog Online/Offline', 'description' => 'Allows Aspen to be placed in offline mode for use during migrations and upgrade processes', 'default'=>0],
			'offlineMessage' => ['property' => 'offlineMessage', 'type' => 'html', 'label' => 'Offline Message', 'description' => 'A message to be displayed while Aspen is offline.', 'default'=>'The catalog is down for maintenance, please check back later.', 'hideInLists' => true],
		];
	}

	public static function forceNightlyIndex()
	{
		$variables = new SystemVariables();
		if ($variables->find(true)){
			if ($variables->runNightlyFullIndex == 0) {
				$variables->runNightlyFullIndex = 1;
				$variables->update();
			}
		}
	}

	/** @var null|SystemVariables */
	protected static $_systemVariables = null;

	/**
	 * @return SystemVariables|false
	 */
	public static function getSystemVariables()
	{
		if (SystemVariables::$_systemVariables == null){
			SystemVariables::$_systemVariables = new SystemVariables();
			if (!SystemVariables::$_systemVariables->find(true)){
				SystemVariables::$_systemVariables = false;
			}
		}
		return SystemVariables::$_systemVariables;
	}
}