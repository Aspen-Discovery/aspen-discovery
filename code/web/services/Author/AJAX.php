<?php

class Author_AJAX {
	function launch() {
		$method = (isset($_GET['method']) && !is_array($_GET['method'])) ? $_GET['method'] : '';
		if (method_exists($this, $method)) {
			//JSON Encoded data
			header('Content-type: application/json');
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			echo $this->$method();
		} else {
			echo json_encode(['error' => 'invalid_method']);
		}
	}

	/** @noinspection PhpUnused */
	function getEnrichmentInfo() {
		global $interface;
		global $memoryWatcher;

		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		$id = $_REQUEST['workId'];
		$recordDriver = new GroupedWorkDriver($id);

		$enrichmentResult = [];
		$enrichmentData = $recordDriver->loadEnrichment();
		$memoryWatcher->logMemory('Loaded Enrichment information from Novelist');

		/** @var NovelistData $novelistData */
		if (isset($enrichmentData['novelist'])) {
			$novelistData = $enrichmentData['novelist'];

			if ($novelistData->getAuthorCount()) {
				$interface->assign('similarAuthors', $novelistData->getAuthors());
				$enrichmentResult['similarAuthorsNovelist'] = $interface->fetch('GroupedWork/similarAuthorsNovelistSidebar.tpl');
			}
			$memoryWatcher->logMemory('Loaded Similar authors from Novelist');
		}

		return json_encode($enrichmentResult);
	}

	/** @noinspection PhpUnused */
	function getWikipediaData() {
		global $configArray;
		global $library;
		global $interface;
		global $memCache;
		$returnVal = [];
		if ($library->showWikipediaContent == 1) {
			// Only use first two characters of language string; Wikipedia
			// uses language domains but doesn't break them up into regional
			// variations like pt-br or en-gb.
			$authorName = $_REQUEST['articleName'];
			if (is_array($authorName)) {
				$authorName = reset($authorName);
			}
			$authorName = trim($authorName);

			//Check to see if we have an override
			require_once ROOT_DIR . '/sys/LocalEnrichment/AuthorEnrichment.php';
			$authorEnrichment = new AuthorEnrichment();
			$authorEnrichment->authorName = $authorName;
			$doLookup = true;
			if ($authorEnrichment->find(true)) {
				if ($authorEnrichment->hideWikipedia) {
					$doLookup = false;
				} else {
					require_once ROOT_DIR . '/sys/WikipediaParser.php';
					$wikipediaUrl = $authorEnrichment->wikipediaUrl;
					$authorName = str_replace('https://en.wikipedia.org/wiki/', '', $wikipediaUrl);
					$authorName = urldecode($authorName);
				}
			}
			if ($doLookup) {
				global $activeLanguage;
				$wiki_lang = substr($activeLanguage->code, 0, 2);
				$interface->assign('wiki_lang', $wiki_lang);
				$authorInfo = $memCache->get("wikipedia_article_{$authorName}_{$wiki_lang}");
				if ($authorInfo == false || isset($_REQUEST['reload'])) {
					require_once ROOT_DIR . '/services/Author/Wikipedia.php';
					$wikipediaParser = new Author_Wikipedia();
					$authorInfo = $wikipediaParser->getWikipedia($authorName, $wiki_lang);
					$memCache->set("wikipedia_article_{$authorName}_{$wiki_lang}", $authorInfo, $configArray['Caching']['wikipedia_article']);
				}
				$returnVal['success'] = true;
				$returnVal['article'] = $authorInfo;
				$interface->assign('info', $authorInfo);
				$returnVal['formatted_article'] = $interface->fetch('Author/wikipedia_article.tpl');
			} else {
				$returnVal['success'] = false;
			}
		} else {
			$returnVal['success'] = false;
		}
		return json_encode($returnVal);
	}

	function getBreadcrumbs(): array {
		return [];
	}
}