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

ini_set('memory_limit', '4G');
$library = new Library();
$library->find();
while ($library->fetch()) {
	if ($addGroupedWorks && $library->generateSitemap) {
		$subdomain = $library->subdomain;
		global $solrScope;
		$solrScope = preg_replace('/[^a-zA-Z0-9_]/', '', $subdomain);


		if (empty($library->baseUrl)) {
			$baseUrl = $configArray['Site']['url'];
		} else {
			$baseUrl = $library->baseUrl;
		}
		echo(date('H:i:s') . " Creating sitemaps for $library->displayName ($library->subdomain)\r\n");
		ob_flush();

		//Technically google will take 50k, but they don't like it if you have that many.
		$recordsPerSitemap = 40000;

		//Export all Grouped Works
		require_once ROOT_DIR . '/sys/Grouping/Scope.php';
		require_once ROOT_DIR . '/sys/Grouping/GroupedWorkItem.php';
		$scope = new Grouping_Scope();
		$scope->name = $solrScope;
		$scope->isLibraryScope = 1;
		if ($scope->find(true)) {
			global $aspen_db;
			$scopeId = $scope->id;
			//Get a count of the number of works for the scope
			$numResults = 0;
			$results = $aspen_db->query("select count(distinct(permanent_id)) as numWorks from grouped_work_record_items inner join grouped_work_records on grouped_work_records.id = groupedWorkRecordId inner join grouped_work on groupedWorkId = grouped_work.id where recordIncludedScopes like '%~$scopeId~%' or libraryOwnedScopes like '%~$scopeId~%'", PDO::FETCH_ASSOC);
			if ($results) {
				$result = $results->fetch();
				if ($result) {
					$numResults = $result['numWorks'];
				}
			}
			$results->closeCursor();

			$numSitemaps = (int)ceil($numResults / $recordsPerSitemap);
			echo(date('H:i:s') . "   Found a total of $numResults results in the collection\r\n");

			$curRecord = 1;
			$curSitemap = 1;
			echo(date('H:i:s') . "   Sitemap {$curSitemap} of {$numSitemaps}\r\n");
			$curSitemapName = 'grouped_work_site_map_' . $subdomain . '_' . str_pad($curSitemap, 3, "0", STR_PAD_LEFT) . '.txt';
			//Store sitemaps in the sitemaps directory
			$sitemapFhnd = fopen(ROOT_DIR . '/sitemaps/' . $curSitemapName, 'w');

			$results = $aspen_db->query("select distinct(permanent_id) from grouped_work_record_items inner join grouped_work_records on grouped_work_records.id = groupedWorkRecordId inner join grouped_work on groupedWorkId = grouped_work.id where recordIncludedScopes like '%~$scopeId~%' or libraryOwnedScopes like '%~$scopeId~%'", PDO::FETCH_ASSOC);
			if ($results) {
				while ($result = $results->fetch()) {
					if ($curRecord % $recordsPerSitemap == 0) {
						$curSitemap++;
						fclose($sitemapFhnd);
						echo(date('H:i:s') . "   Sitemap {$curSitemap} of {$numSitemaps}\r\n");
						$curSitemapName = 'grouped_work_site_map_' . $subdomain . '_' . str_pad($curSitemap, 3, "0", STR_PAD_LEFT) . '.txt';
						//Store sitemaps in the sitemaps directory
						$sitemapFhnd = fopen(ROOT_DIR . '/sitemaps/' . $curSitemapName, 'w');
					}
					$curRecord++;
					$permanent_id = $result['permanent_id'];
					$url = $baseUrl . '/GroupedWork/' . $permanent_id . '/Home';
					fwrite($sitemapFhnd, $url . "\r\n");
				}
			}
			$results->closeCursor();
			fclose($sitemapFhnd);
		}

		//TODO: Export Web Builder Pages and Resources


	}
}

