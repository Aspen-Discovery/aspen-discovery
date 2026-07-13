<?php
use PHPUnit\Framework\TestCase;

class FinalizationTests extends TestCase {
	public function test_stoppingSolr() {
		global $configArray;
		$solrHost = $configArray['Index']['solrHost'] ?? 'localhost';
		$solrIsRemote = !in_array($solrHost, ['localhost', '127.0.0.1']);
		if ($solrIsRemote) {
			$this->markTestSkipped("Solr runs remotely on $solrHost and cannot be stopped locally");
		}
		require_once __DIR__ . '/../../../code/web/sys/SolrUtils.php';
		SolrUtils::stopSolr();
		sleep(15);

		$solrSearcher = SearchObjectFactory::initSearchObject('GroupedWork');
		$pingResult = $solrSearcher->ping(true);
		$this->assertFalse($pingResult);
	}
}