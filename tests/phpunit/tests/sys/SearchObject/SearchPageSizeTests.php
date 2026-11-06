<?php

use PHPUnit\Framework\TestCase;

require_once ROOT_DIR . '/sys/SearchObject/BaseSearcher.php';

class PageSizeTestSearcher extends SearchObject_BaseSearcher {
	public function applyRequestedPageSize() : void {
		$this->initPageSize();
	}

	public function getEngineName() {
		return 'PageSizeTest';
	}

	public function getSearchIndexes() {
		return [];
	}

	public function getAdvancedSearchIndexes() {
		return [];
	}

	function getSearchesFile() {
		return '';
	}

	public function processSearch(bool $returnIndexErrors = false, bool $recommendations = false, bool $preventQueryModification = false) : AspenError|stdClass|SimpleXMLElement|array|null {
		return null;
	}

	public function getIndexError() {
		return null;
	}

	public function buildRSS($result = null) {
		return '';
	}

	public function buildExcel($result = null) {
		return '';
	}

	public function getResultRecordSet() {
		return [];
	}

	function getSearchName() {
		return 'pageSizeTest';
	}

	public function getDefaultIndex() {
		return '';
	}

	function loadValidFields() {
	}

	function loadDynamicFields() {
	}
}

class SearchPageSizeTests extends TestCase {
	protected function tearDown() : void {
		unset($_REQUEST['pageSize']);
		parent::tearDown();
	}

	public function testAllowedPageSizeIsApplied() : void {
		$_REQUEST['pageSize'] = '50';
		$searcher = new PageSizeTestSearcher();
		$searcher->applyRequestedPageSize();
		$this->assertEquals(50, $searcher->getLimit());
		$this->assertTrue($searcher->hasRequestedPageSize());
	}

	public function testSearchUrlKeepsTheSelectedPageSize() : void {
		$_REQUEST['pageSize'] = '100';
		$searcher = new PageSizeTestSearcher();
		$searcher->applyRequestedPageSize();
		$this->assertStringContainsString('pageSize=100', $searcher->renderSearchUrl());
	}

	public function testDefaultPageSizeIsLeftOutWhenNoSizeIsRequested() : void {
		$searcher = new PageSizeTestSearcher();
		$pageSizeList = $searcher->getPageSizeList();
		$this->assertStringNotContainsString('pageSize=', $pageSizeList[20]['url']);
		$this->assertStringContainsString('pageSize=50', $pageSizeList[50]['url']);
		$this->assertTrue($pageSizeList[20]['selected']);
	}

	public function testDefaultPageSizeIsKeptWhenASizeIsRequested() : void {
		$_REQUEST['pageSize'] = '50';
		$searcher = new PageSizeTestSearcher();
		$searcher->applyRequestedPageSize();
		$pageSizeList = $searcher->getPageSizeList();
		$this->assertStringContainsString('pageSize=20', $pageSizeList[20]['url']);
		$this->assertStringContainsString('pageSize=100', $pageSizeList[100]['url']);
		$this->assertTrue($pageSizeList[50]['selected']);
	}

	public function testRssUrlLeavesOutTheRequestedPageSize() : void {
		$_REQUEST['pageSize'] = '50';
		$searcher = new PageSizeTestSearcher();
		$searcher->applyRequestedPageSize();
		$this->assertStringNotContainsString('pageSize=', $searcher->getRSSUrl());
		$this->assertStringContainsString('pageSize=50', $searcher->renderSearchUrl());
	}

	public function testLimitSetWithoutARequestIsLeftOutOfSearchUrls() : void {
		$searcher = new PageSizeTestSearcher();
		$searcher->setLimit(50);
		$this->assertFalse($searcher->hasRequestedPageSize());
		$this->assertStringNotContainsString('pageSize=', $searcher->renderSearchUrl());
	}

	public function testUnknownPageSizeIsIgnored() : void {
		$_REQUEST['pageSize'] = '33';
		$searcher = new PageSizeTestSearcher();
		$searcher->applyRequestedPageSize();
		$this->assertEquals(SearchObject_BaseSearcher::DEFAULT_PAGE_SIZE, $searcher->getLimit());
		$this->assertFalse($searcher->hasRequestedPageSize());
	}
}
