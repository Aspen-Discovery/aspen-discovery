<?php

class WikipediaParser {
	private $lang;

	public function __construct($lang) {
		if ($lang) {
			if ($lang == 'pi' || $lang == 'ub') {
				$lang = 'en';
			}
			$this->lang = $lang;
		} else {
			$this->lang = 'en';
		}
	}

	/**
	 * parseWikipedia
	 *
	 * This method is responsible for parsing the output from the Wikipedia
	 * REST API.
	 *
	 * @author  Rushikesh Katikar <rushikesh.katikar@gmail.com>
	 * @param string $lang
	 * @param array $body JSON formatted data to be parsed
	 * @return  array|AspenError
	 * @access  private
	 */
	public function parseWikipedia($body, $lang = 'en') {
		// Check if data exists or not
		if (!isset($body['query']['pages']) || isset($body['query']['pages']['-1'])) {
			return new AspenError('No page found');
		}

		// Get the default page
		$body = array_shift($body['query']['pages']);
		$info['name'] = $body['title'];

		// Get the latest revision
		$body = array_shift($body['revisions']);
		// Check for redirection
		$as_lines = explode("\n", $body['*']);
		if (stristr($as_lines[0], '#REDIRECT')) {
			preg_match('/\[\[(.*)\]\]/', $as_lines[0], $matches);
			$url = "http://{$lang}.wikipedia.org/w/api.php" . '?action=query&prop=revisions&rvprop=content&format=json' . '&titles=' . urlencode($matches[1]);
			return $this->getWikipediaPage($url, $lang);
		}

		/**
		 * **************
		 *
		 *   Infobox
		 *
		 */ // We are looking for the infobox inside "{{...}}"
		//   It may contain nested blocks too, thus the recursion
		preg_match_all('/\{([^{}]++|(?R))*\}/s', $body['*'], $matches);
		$firstInfoBox = null;
		foreach ($matches[1] as $m) {
			// If this is the Infobox
			if (substr($m, 0, 8) == "{Infobox" || substr($m, 0, 9) == "{ infobox") {
				// Keep the string for later, we need the body block that follows it
				$infoboxStr = "{" . $m . "}";
				if ($firstInfoBox == null) {
					$firstInfoBox = $infoboxStr;
				}
				// Get rid of the last pair of braces and split
				$infobox = explode("\n|", substr($m, 1, -1));
				// Look through every row of the infobox
				foreach ($infobox as $row) {
					$data = explode("=", $row);
					$key = trim(array_shift($data));
					$value = trim(join("=", $data));

					// At the moment we only want stuff related to the image.
					switch (strtolower($key)) {
						case "img":
						case "image":
						case "image:":
						case "image_name":
							$imageName = str_replace(' ', '_', $value);
							break;
						case "caption":
						case "img_capt":
						case "image_caption":
							$image_caption = $value;
							break;
						default:         /* Nothing else... yet */ break;
					}
				}
			}
		}

		/**
		 * **************
		 *
		 *   Image
		 *
		 */ // If we didn't successfully extract an image from the infobox, let's see if we
		// can find one in the body -- we'll just take the first match:
		if (!isset($imageName)) {
			$pattern = '/(\x5b\x5b)Image:([^\x5d]*)(\x5d\x5d)/U';
			preg_match_all($pattern, $body['*'], $matches);
			if (isset($matches[2][0])) {
				$parts = explode('|', $matches[2][0]);
				$imageName = str_replace(' ', '_', $parts[0]);
				if (count($parts) > 1) {
					$image_caption = strip_tags(preg_replace('/({{).*(}})/U', '', $parts[count($parts) - 1]));
				}
			}
		}

		// Given an image name found above, look up the associated URL:
		if (isset($imageName)) {
			$imageUrl = $this->getWikipediaImageURL($imageName);
		}

		/**
		 * **************
		 *
		 *   Body
		 *
		 */
		if (isset($firstInfoBox)) {
			// Start of the infobox
			$start = strpos($body['*'], $firstInfoBox);
			// + the length of the infobox
			$offset = strlen($firstInfoBox);
			// Every after the infobox
			$body = substr($body['*'], $start + $offset);
		} else {
			// No infobox -- use whole thing:
			$body = $body['*'];
		}
		// Find the first heading
		$end = strpos($body, "==");
		// Now cull our content back to everything before the first heading
		$body = trim(substr($body, 0, $end));

		// Remove unwanted image/file links
		// Nested brackets make this annoying: We can't add 'File' or 'Image' as mandatory
		//    because the recursion fails, or as optional because then normal links get hit.
		//    ... unless there's a better pattern? TODO
		// eg. [[File:Johann Sebastian Bach.jpg|thumb|Bach in a 1748 portrait by [[Elias Gottlob Haussmann|Haussmann]]]]
		$open = "\\[";
		$close = "\\]";
		$content = "(?>[^\\[\\]]+)";  // Anything but [ or ]
		$recursive_match = "($content|(?R))*"; // We can either find content or recursive brackets
		preg_match_all("/" . $open . $recursive_match . $close . "/Us", $body, $new_matches);
		// Loop through every match (link) we found
		if (is_array($new_matches)) {
			foreach ($new_matches as $nm) {
				// Might be an array of arrays
				if (is_array($nm)) {
					foreach ($nm as $n) {
						// If it's a file link get rid of it
						if (strtolower(substr($n, 0, 7)) == "[[file:" || strtolower(substr($n, 0, 8)) == "[[image:") {
							$body = str_replace($n, "", $body);
						}
					}
					// Or just a normal array
				} else {
					// If it's a file link get rid of it
					if (isset($n)) {
						if (strtolower(substr($n, 0, 7)) == "[[file:" || strtolower(substr($n, 0, 8)) == "[[image:") {
							$body = str_replace($nm, "", $body);
						}
					}
				}
			}
		}

		// Initialize arrays of processing instructions
		$pattern = [];
		$replacement = [];

		//Strip out taxobox
		$pattern[] = '/{{Taxobox.*}}\n\n/Us';
		$replacement[] = "";

		//Strip out embedded Infoboxes
		$pattern[] = '/{{Infobox.*}}\n\n/Us';
		$replacement[] = "";

		//Strip out anything like {{!}}
		$pattern[] = '/{{!}}/Us';
		$replacement[] = "";

		// Convert wikipedia links
		$pattern[] = '/(\x5b\x5b)([^\x5d|]*)(\x5d\x5d)/Us';
		$replacement[] = '<a href="' . '/Search/Results?lookfor=%22$2%22&amp;type=Keyword">$2</a>';
		$pattern[] = '/(\x5b\x5b)([^\x5d]*)\x7c([^\x5d]*)(\x5d\x5d)/Us';
		$replacement[] = '<a href="' . '/Search/Results?lookfor=%22$2%22&amp;type=Keyword">$3</a>';

		// Fix pronunciation guides
		$pattern[] = '/({{)pron-en\|([^}]*)(}})/Us';
		$replacement[] = translate([
				'text' => "pronounced",
				'isPublicFacing' => true,
			]) . " /$2/";

		// Removes citations
		$pattern[] = '/({{)[^}]*(}})/Us';
		$replacement[] = "";
		//  <ref ... > ... </ref> OR <ref> ... </ref>
		$pattern[] = '/<ref[^\/]*>.*<\/ref>/Us';
		$replacement[] = "";
		//    <ref ... />
		$pattern[] = '/<ref.*\/>/Us';
		$replacement[] = "";

		// Removes comments followed by carriage returns to avoid excess whitespace
		$pattern[] = '/<!--.*-->\n*/Us';
		$replacement[] = '';

		// Formatting
		$pattern[] = "/'''([^']*)'''/Us";
		$replacement[] = '<strong>$1</strong>';

		// Trim leading newlines (which can result from leftovers after stripping
		// other items above).  We want this to be greedy.
		$pattern[] = '/^\n*/s';
		$replacement[] = '';

		// Convert multiple newlines into two breaks
		// We DO want this to be greedy
		$pattern[] = "/\n{2,}/s";
		$replacement[] = '<br/><br/>';

		$body = preg_replace($pattern, $replacement, $body);

		//Clean up spaces within hrefs
		$body = preg_replace_callback('/href="(.*?)"/si', [
			$this,
			'fix_whitespace',
		], $body);

		$body = str_replace('<br>', '<br/>', $body);

		if (isset($imageUrl) && $imageUrl != false) {
			$info['image'] = $imageUrl;
			if (isset($image_caption)) {
				$info['altimage'] = $image_caption;
			}
		}
		$info['description'] = $body;

		return $info;
	}

	function fix_whitespace($matches) {
		// as usual: $matches[0] is the complete match
		// $matches[1] the match for the first sub pattern
		// enclosed in '(...)' and so on
		return str_replace(' ', '+', $matches[0]);
	}

	/**
	 * getWikipediaImageURL
	 *
	 * This method is responsible for obtaining an image URL based on a name.
	 *
	 * @param string $imageName The image name to look up
	 * @return  mixed               URL on success, false on failure
	 * @access  private
	 */
	private function getWikipediaImageURL($imageName) {
		$url = "http://{$this->lang}.wikipedia.org/w/api.php" . '?prop=imageinfo&action=query&iiprop=url&iiurlwidth=150&format=json' . '&titles=Image:' . $imageName;

		$response = file_get_contents($url);

		if ($response) {

			if ($imageinfo = json_decode($response, true)) {
				if (isset($imageinfo['query']['pages']['-1']['imageinfo'][0]['url'])) {
					$imageUrl = $imageinfo['query']['pages']['-1']['imageinfo'][0]['url'];
				}

				// Hack for wikipedia api, just in case we couldn't find it
				//   above look for a http url inside the response.
				if (!isset($imageUrl)) {
					preg_match('/\"http:\/\/(.*)\"/', $response, $matches);
					if (isset($matches[1])) {
						$imageUrl = 'http://' . substr($matches[1], 0, strpos($matches[1], '"'));
					}
				}
			}
		}

		return isset($imageUrl) ? $imageUrl : false;
	}

	/**
	 * @param string $pageUrl
	 * @param string $lang
	 * @return array|AspenError|null
	 */
	public function getWikipediaPage($pageUrl, $lang) {
		if (filter_var($pageUrl, FILTER_VALIDATE_URL)) {
			$result = file_get_contents($pageUrl);
			$jsonResult = json_decode($result, true);
			$info = $this->parseWikipedia($jsonResult, $lang);
			if (!($info instanceof AspenError)) {
				return $info;
			}
		}
		return null;

	}
}