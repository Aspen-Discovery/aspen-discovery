<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../bootstrap_aspen.php';
global $configArray;
global $serverName;
global $interface;

require_once ROOT_DIR . '/sys/Module.php';
$aspenModule = new Module();
$aspenModule->enabled = true;
$aspenModule->find();
$addGroupedWorks = false;
while ($aspenModule->fetch()) {
	//See if we need to create a sitemap for it
	if ($aspenModule->indexName == 'grouped_works') {
		$addGroupedWorks = true;
	}
}

ini_set('memory_limit','4G');
$library = new Library();
$library->find();
while ($library->fetch()){
	if ($addGroupedWorks && $library->generateSitemap) {
		$subdomain = $library->subdomain;
		global $solrScope;
		$solrScope = preg_replace('/[^a-zA-Z0-9_]/', '', $subdomain);

		if (empty($library->baseUrl)){
			$baseUrl = $configArray['Site']['url'];
		}else{
			$baseUrl = $library->baseUrl;
		}
		echo(date('H:i:s') . " Creating sitemaps for $library->displayName ($library->subdomain)\r\n");
		ob_flush();

		//Do a quick search to see how many results we have
		/** @var SearchObject_GroupedWorkSearcher $searchObject */
		$searchObject = SearchObjectFactory::initSearchObject();
		$searchObject->init($searchSource);
		$searchObject->setFieldsToReturn('id');
		$searchObject->setLimit(1);
		$result = $searchObject->processSearch();
		//Technically google will take 50k, but they don't like it if you have that many.
		$recordsPerSitemap = 40000;
		$solrBatchSize = 5000;
		if (!$result instanceof AspenError && empty($result['error'])) {
			$numResults = $searchObject->getResultTotal();
			$searchObject->setTimeout(60);
			$lastPage = (int)ceil($numResults / $solrBatchSize);
			$searchObject->setLimit($solrBatchSize);
			$searchObject->clearFacets();

			$numSitemaps = (int)ceil($numResults / $recordsPerSitemap);
			echo(date('H:i:s') . "   Found a total of $numResults results in the collection\r\n");

			//Now do searches in batch and create the sitemap files
			for ($curSitemap = 1; $curSitemap <= $numSitemaps; $curSitemap++) {
				echo(date('H:i:s') . "   Sitemap {$curSitemap} of {$numSitemaps}\r\n");
				ob_flush();

				set_time_limit(300);
				$curSitemapName = 'grouped_work_site_map_' . $subdomain . '_' . str_pad($curSitemap, 3, "0", STR_PAD_LEFT) . '.txt';
				//Store sitemaps in the sitemaps directory
				$sitemapFhnd = fopen(ROOT_DIR . '/sitemaps/' . $curSitemapName, 'w');

				$sitemapStartIndex = ($curSitemap - 1) * $recordsPerSitemap;
				$sitemapStartPage = $sitemapStartIndex / $solrBatchSize;
				$sitemapEndIndex = $curSitemap * $recordsPerSitemap;
				$sitemapEndPage = $sitemapEndIndex / $solrBatchSize;

				for ($curPage = $sitemapStartPage; $curPage < $sitemapEndPage; $curPage++) {
					echo(date('H:i:s') . "     Search page {$curPage} of {$sitemapEndPage}\r\n");
					ob_flush();
					$searchObject->setPage($curPage);
					$result = $searchObject->processSearch(true, false, false);
					if (!$result instanceof AspenError && empty($result['error'])) {
						foreach ($result['response']['docs'] as $doc) {
							$url = $baseUrl . '/GroupedWork/' . $doc['id'] . '/Home';
							fwrite($sitemapFhnd, $url . "\r\n");
						}
					}
				}
				fclose($sitemapFhnd);
				gc_collect_cycles();
			}
		}elseif ($result instanceof AspenError){
			echo(date('H:i:s') . "   Result was an error $result\r\n");
		}elseif (!$result['error']){
			echo(date('H:i:s') . "   Result had error {$result['error']}\r\n");
		}else{
			echo(date('H:i:s') . "   No results found\r\n");
		}
		gc_collect_cycles();
		$searchObject = null;
	}
}

