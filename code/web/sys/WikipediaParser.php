<?php

require_once ROOT_DIR . '/sys/CurlWrapper.php';

/**
 * WikipediaParser
 *
 * Parses the HTML returned by the REST API endpoint. Rather than the
 * heavy wikitext logic, the browser-ready markup is in the payload.
 * API Documentation: https://en.wikipedia.org/api/rest_v1/#
 */
class WikipediaParser {
	private string $lang;

	public function __construct($lang) {
		$this->lang = (!empty($lang) && !in_array($lang, ['pi', 'ub'])) ? $lang : 'en';
	}

	/**
	 * Parses the lead section of a Wikipedia HTML page and extracts the description and infobox image.
	 *
	 * @param string $html Raw HTML from the Wikipedia REST endpoint.
	 * @param string $title Original page title (for alt text and fallback).
	 * @return array|null Canonical info: ['name', 'description', 'image', 'altimage'] or null if not found.
	 */
	private function parseWikipedia(string $html, string $title): ?array {
		$dom = new DOMDocument();
		// Silence warnings triggered by malformed HTML fragments.
		@$dom->loadHTML($html, LIBXML_NOWARNING | LIBXML_NOERROR | LIBXML_COMPACT);
		$xpath = new DOMXPath($dom);
		if ($description = $this->disambiguate($xpath, $dom)) {
			return [
				'name' => $title,
				'description' => $description,
				'image' => null,
				'altimage' => null,
			];
		}

		// Grab the lead (i.e., main introductory content).
		$leadSection = $xpath->query('//section[@data-mw-section-id="0"]')->item(0);
		if (!$leadSection) {
			return null;
		}

		foreach ($xpath->query('.//*[contains(@class,"nowraplinks") or contains(@class,"ext-phonos")]', $leadSection) as $n) {
			$n->parentNode->removeChild($n);
		}

		$this->rewriteInternalLinks($leadSection, $xpath);

		$leadParas = [];
		foreach ($xpath->query('.//p', $leadSection) as $p) {
			$text = trim($p->textContent);
			// Skip known empty markers.
			if ($p->hasAttribute('class') &&
				str_contains($p->getAttribute('class'), 'mw-empty-elt')) {
				continue;
			}
			// Skip stubs and nav headings (<40 chars or no period).
			if (mb_strlen($text) < 40 || !str_contains($text, '.')) {
				continue;
			}
			// Strip citation superscripts (e.g., "[1]").
			foreach ($xpath->query('.//sup[contains(@class,"reference")]', $p) as $sup) {
				$sup->parentNode->removeChild($sup);
			}
			$leadParas[] = $dom->saveHTML($p);

			// Break after two paragraphs because the leads could be several paragraphs and clutter the display.
			if (count($leadParas) === 2) {
				break;
			}
		}

		if (!$leadParas) {
			return null;
		}

		$description = implode("\n", $leadParas);

		$imgNode = $xpath->query('//body//figure[contains(@class,"infobox")]//img | //body//table[contains(@class,"infobox")]//img')->item(0);
		$image = null;
		$altImage = null;
		if ($imgNode instanceof DOMElement && $imgNode->hasAttribute('src')) {
			$src = $imgNode->getAttribute('src');
			$image = (str_starts_with($src, '//') ? 'https:' : '') . $src;
			$altImage = $imgNode->getAttribute('alt') ?: $title;
		}

		return [
			'name' => $title,
			'description' => $description,
			'image' => $image,
			'altimage'=> $altImage,
		];
	}

	/**
	 * Fetches a Wikipedia page by URL, handles errors, and parses its content.
	 *
	 * @param string $pageUrl HTTPS URL of the Wikipedia REST endpoint.
	 * @param string $title Original title requested (used for fallback).
	 * @return array|null Canonical info array or null on error/failure.
	 */
	public function getWikipediaPage(string $pageUrl, string $title): ?array {
		$curlWrapper = new CurlWrapper();
		$html = $curlWrapper->curlGetPage($pageUrl);

		if ($curlWrapper->getResponseCode() >= 400) {
			global $logger;
			$logger->log(sprintf('Wikipedia fetch failed (%d) for %s.', $curlWrapper->getResponseCode(), $pageUrl), Logger::LOG_DEBUG);
			return null;
		}

		return $this->parseWikipedia($html, $title);
	}

	/**
	 * Rewrites or unwraps internal Wikipedia links inside a DOM node.
	 * - Converts internal article links to local catalog searches.
	 * - Unwraps (removes) non-article or special Wikipedia links, preserving their content.
	 *
	 * @param DOMNode $section The node within which to rewrite links.
	 * @param DOMXPath $xp The XPath object for running queries.
	 * @return void
	 */
	private function rewriteInternalLinks(DOMNode $section, DOMXPath $xp): void
	{
		foreach ($xp->query('.//a', $section) as $a) {
			$href = $a->getAttribute('href');

			if (!str_contains($href, ':')) {
				$term = rawurlencode('"' . trim($a->textContent) . '"');
				$a->setAttribute('href', "/Search/Results?lookfor=$term&type=Keyword");
				$a->removeAttribute('rel');
				$a->removeAttribute('title');
				$a->removeAttribute('class');
				continue;
			}
			// Unwraps the anchor while leaving its inner nodes,
			// and move the child out and drop the empty <a>.
			// This is to keep the <img> element, even without its link.
			while ($a->firstChild) {
				$a->parentNode->insertBefore($a->firstChild, $a);
			}
			$a->parentNode->removeChild($a);
		}
	}

	/**
	 * Return a HTML description for Wikipedia disambiguation pages.
	 *
	 * If the DOM represents a "may refer to" page, gather the lead
	 * sentence and every list-item link and turn each into an external
	 * anchor that opens in a new tab.
	 * - Example output: "Jane Smith may refer to: <a …>Jane Smith (diver)</a>; ..."
	 *
	 * @param DOMXPath $xp XPath helper bound to the same DOM.
	 * @param DOMDocument $dom The loaded page’s DOM.
	 * @return string|null HTML fragment or null when the page is not a disambiguation or contains no options.
	 */
	private function disambiguate(DOMXPath $xp, DOMDocument $dom): ?string
	{
		// Discard if no <meta property="mw:PageProp/disambiguation">.
		if (!$xp->query('//meta[@property="mw:PageProp/disambiguation"]')->length) {
			return null;
		}

		$lead = $xp->query('//section[@data-mw-section-id="0"]/p')->item(0);
		if (!$lead) {
			return null;
		}
		$intro = trim($dom->saveHTML($lead));

		// Build the option list.
		$options = [];
		foreach ($xp->query('//section[contains(@id,"mw")]//li[.//a]') as $li) {
			$a = $xp->query('.//a', $li)->item(0);
			/** @var DOMElement $a */
			$href = $a->getAttribute('href');
			$label = trim($a->textContent);
			$absUrl = 'https://' . $this->lang . '.wikipedia.org/wiki' . substr($href, 1);
			$context = trim($li->textContent);
			$context = preg_replace('/^' . preg_quote($label, '/') . '\s*/', '', $context);

			$options[] = sprintf(
				'<a href="%s" rel="external" target="_blank">%s</a>%s',
				htmlspecialchars($absUrl, ENT_QUOTES, 'UTF-8'),
				htmlspecialchars($label,  ENT_QUOTES, 'UTF-8'),
				$context ? ' ' . htmlspecialchars($context, ENT_QUOTES, 'UTF-8') : ''
			);
		}

		if (!$options) {
			return null;
		}

		return $intro . ' ' . implode('; ', $options) . '.';
	}

}