<?php

require_once ROOT_DIR . '/sys/WikipediaParser.php';

/**
 * Author_Wikipedia
 *
 * Provides a convenient interface for fetching an author's Wikipedia bio,
 * trying multiple name variants and returning structured information.
 */
class Author_Wikipedia {

	/**
	 * Fetches a Wikipedia biography blurb for the given author,
	 * trying multiple name variations for best match.
	 *
	 * @param string $author The author name to look up (e.g., "Jane Austen").
	 * @param string $lang ISO 639-1 language code (default "en").
	 * @return array|null Info array ['name', 'description', 'image', 'altimage'] or null if not found.
	 */
	public function getWikipedia(string $author, string $lang = 'en'): ?array {
		$lang = in_array($lang, ['ub', 'pi'], true) ? 'en' : $lang;
		$parser = new WikipediaParser($lang);
		$candidates = [$author];

		if (str_contains($author, ',')) {
			$parts = array_map('trim', explode(',', $author, 2));
			$candidates[] = "$parts[1] $parts[0]";
		}

		$candidates[] = str_replace('.', '', $author);
		$candidates = array_unique($candidates);

		foreach ($candidates as $name) {
			$title = rawurlencode(str_replace(' ', '_', trim($name, '"')));
			$url = "https://{$lang}.wikipedia.org/api/rest_v1/page/html/{$title}";

			if ($info = $parser->getWikipediaPage($url, $name, $lang)) {
				return $info;
			}
		}

		return null;
	}
}