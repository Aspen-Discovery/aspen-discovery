<?php
use PHPUnit\Framework\TestCase;

class FinalizationTests extends TestCase {
	public function test_stoppingSolr() {
		require_once __DIR__ . '/../../../code/web/sys/SolrUtils.php';
		SolrUtils::stopSolr();
		sleep(15);

		$solrSearcher = SearchObjectFactory::initSearchObject('GroupedWork');
		$pingResult = $solrSearcher->ping(true);
		$this->assertFalse($pingResult);
	}
}