<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../bootstrap_aspen.php';
global $configArray;
global $serverName;
global $interface;

require_once ROOT_DIR . '/sys/Module.php';
$module = new Module();
$module->enabled = true;
$module->find();
$addGroupedWorks = false;
while ($module->fetch()) {
	//See if we need to create a sitemap for it
	if ($module->indexName == 'grouped_works') {
		$addGroupedWorks = true;
	}
}

$library = new Library();
$library->find();
while ($library->fetch()){
	if ($addGroupedWorks && $library->generateSitemap) {
		$subdomain = $library->subdomain;
		global $solrScope;
		$solrScope = $subdomain;

		if (empty($library->baseUrl)){
			$baseUrl = $configArray['Site']['url'];
		}else{
			$baseUrl = $library->baseUrl;
		}

		//Do a quick search to see how many results we have
		/** @var SearchObject_GroupedWorkSearcher $searchObject */
		$searchObject = SearchObjectFactory::initSearchObject();
		$searchObject->init($searchSource);
		$searchObject->setFieldsToReturn('id');
		$searchObject->setLimit(1);
		$result = $searchObject->processSearch();
		//Technically google will take 50k, but they don't like it if you have that many.
		$recordsPerSitemap = 40000;
		if (!$result instanceof AspenError && empty($result['error'])) {
			$numResults = $searchObject->getResultTotal();
			$lastPage = (int)ceil($numResults / 100);
			$searchObject->setLimit(100);

			$numSitemaps = (int)ceil($numResults / $recordsPerSitemap);

			//Now do searches in batch and create the sitemap files
			for ($curSitemap = 1; $curSitemap <= $numSitemaps; $curSitemap++) {
				set_time_limit(300);
				$curSitemapName = 'grouped_work_site_map_' . $subdomain . '_' . str_pad($curSitemap, 3, "0", STR_PAD_LEFT) . '.txt';
				//Store sitemaps in the sitemaps directory
				$sitemapFhnd = fopen(ROOT_DIR . '/sitemaps/' . $curSitemapName, 'w');

				$sitemapStartIndex = ($curSitemap - 1) * $recordsPerSitemap;
				$sitemapStartPage = $sitemapStartIndex / 100;
				$sitemapEndIndex = $curSitemap * $recordsPerSitemap;
				$sitemapEndPage = $sitemapEndIndex / 100;

				for ($curPage = $sitemapStartPage; $curPage < $sitemapEndPage; $curPage++) {
					$searchObject->setPage($curPage);
					$result = $searchObject->processSearch();
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
		}
		gc_collect_cycles();
		$searchObject = null;
	}
}
