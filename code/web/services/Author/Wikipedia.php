<?php
/**
 * Loads author data from Wikipedia and cleans it for display in the user interface
 *
 * @category VuFind-Plus
 * @author Mark Noble <mark@marmot.org>
 * Date: 7/23/13
 * Time: 8:51 AM
 */

require_once ROOT_DIR . '/sys/WikipediaParser.php';
class Author_Wikipedia {


	/**
	 * getWikipedia
	 *
	 * This method is responsible for connecting to Wikipedia via the REST API
	 * and pulling the content for the relevant author.
	 *
	 * @param   string  $author The author to load data for
	 * @param   string  $lang   The language code of the language to use
	 * @return  string|null
	 * @access  public
	 * @author  Andrew Nagy <andrew.nagy@villanova.edu>
	 */
	public function getWikipedia($author, $lang = 'en')
	{
		$wikipediaParser = new WikipediaParser($lang);

		$author = trim(str_replace('"','',$author));
		$url = "http://{$lang}.wikipedia.org/w/api.php" .
				'?action=query&prop=revisions&rvprop=content&format=json' .
				'&titles=' . urlencode($author);

		$result = $wikipediaParser->getWikipediaPage($url);
		if ($result == null){
			//Try reversing the name
			if (strpos($author, ',') > 0){
				$authorParts = explode(',', $author, 2);
				$author = trim($authorParts[1] . ' ' . $authorParts[0]);
				$url = "http://{$lang}.wikipedia.org/w/api.php" .
					'?action=query&prop=revisions&rvprop=content&format=json' .
					'&titles=' . urlencode($author);

				$result = $wikipediaParser->getWikipediaPage($url);
			}
			if ($result == null){
				//Try one last time with no periods
				$author = str_replace('.','', $author);
				$url = "http://{$lang}.wikipedia.org/w/api.php" .
					'?action=query&prop=revisions&rvprop=content&format=json' .
					'&titles=' . urlencode($author);

				$result = $wikipediaParser->getWikipediaPage($url);
			}
		}
		return $result;
	}

}