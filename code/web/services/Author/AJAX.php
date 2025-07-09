<?php

require_once ROOT_DIR . '/JSON_Action.php';

class Author_AJAX extends JSON_Action {
	/** @noinspection PhpUnused */
	function getEnrichmentInfo(): array {
		global $interface;
		global $memoryWatcher;

		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		$id = $_REQUEST['workId'];
		$recordDriver = new GroupedWorkDriver($id);

		$enrichmentResult = [];
		$enrichmentData = $recordDriver->loadEnrichment();
		$memoryWatcher->logMemory('Loaded Enrichment information from NoveList');

		/** @var NovelistData $novelistData */
		if (isset($enrichmentData['novelist'])) {
			$novelistData = $enrichmentData['novelist'];

			if ($novelistData->getAuthorCount()) {
				$interface->assign('similarAuthors', $novelistData->getAuthors());
				$enrichmentResult['similarAuthorsNovelist'] = $interface->fetch('GroupedWork/similarAuthorsNovelistSidebar.tpl');
			}
			$memoryWatcher->logMemory('Loaded Similar authors from NoveList');
		}

		return $enrichmentResult;
	}

	/** @noinspection PhpUnused */
	function getWikipediaData(): array {
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
			$errorType = '';
			if ($authorEnrichment->find(true)) {
				if ($authorEnrichment->hideWikipedia) {
					$doLookup = false;
					$errorType = 'lookup_disabled';
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
				if (!$authorInfo || isset($_REQUEST['reload'])) {
					require_once ROOT_DIR . '/services/Author/Wikipedia.php';
					$wikipediaParser = new Author_Wikipedia();
					$authorInfo = $wikipediaParser->getWikipedia($authorName, $wiki_lang);
					$memCache->set("wikipedia_article_{$authorName}_{$wiki_lang}", $authorInfo, $configArray['Caching']['wikipedia_article']);
				}

				$returnVal['searchedName'] = $authorName;
				$returnVal['article'] = $authorInfo;
				if ($authorInfo) {
					$returnVal['success'] = true;
					$interface->assign('info', $authorInfo);
					$returnVal['formatted_article'] = $interface->fetch('Author/wikipedia_article.tpl');
				} else {
					$returnVal['success'] = false;
					$errorType = 'not_found';
				}
			} else {
				$returnVal['searchedName'] = $authorName;
				$returnVal['success'] = false;
			}

			if (!$returnVal['success'] && IPAddress::showDebuggingInformation() && !empty($errorType)) {
				$returnVal['debugMessage'] = 'Wikipedia search for "' . $authorName . '" returned no result (' . $errorType . '). ' .
					'Consider using Wikipedia Integration (Author Enrichment) to correct the Wikipedia search or to prevent Wikipedia searching for this author.';
			}

		} else {
			$returnVal['success'] = false;
		}
		return $returnVal;
	}

	function getBreadcrumbs(): array {
		return [];
	}
}