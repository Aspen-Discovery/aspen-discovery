<?php
require_once ROOT_DIR . '/sys/BaseLogEntry.php';

/***************************************
 * Simple class to retrieve feed of NYT best sellers.
 * documentation:
 * https://developer.nytimes.com/docs/books-product/1/overview
 *
 * Last Updated: March, 2025
 ***************************************
 */
class NYTApi {

	const BASE_URI = 'https://api.nytimes.com/svc/books/v3/lists/';
	protected string $api_key;

	static string|false|null $allListsInfo = null;

	/**
	 * NYTApi constructor.
	 * @param string $key
	 */
	public function __construct(string $key) {
		$this->api_key = $key;
	}

	protected function build_url($list_name): string
	{
		$url = self::BASE_URI . $list_name;
		// For v3 API, we need to use the format: lists/{date}/{list_name}.json.
		// Special case for the 'names' endpoint, which doesn't need a date.
		if ($list_name == 'names') {
			$url = self::BASE_URI . 'names.json';
		} else {
			// Use "current" for date to get the most recent list.
			$url = self::BASE_URI . 'current/' . $list_name . '.json';
		}
		$url .= '?api-key=' . $this->api_key;
		return $url;
	}

	public function get_list($list_name): bool|string|null
	{
		if ($list_name == 'names' && isset(NYTApi::$allListsInfo)) {
			return NYTApi::$allListsInfo;
		}
		$url = $this->build_url($list_name);
		/*
		// super fast and easy way, but not as many options
		$response = file_get_contents($url);
		*/

		// array of request options
		$curl_opts = [
			// set request url
			CURLOPT_URL => $url,
			// return data
			CURLOPT_RETURNTRANSFER => 1,
			// do not include header in result
			CURLOPT_HEADER => 0,
			// set user agent
			CURLOPT_USERAGENT => 'Aspen Discovery app cURL Request',
		];
		// Get cURL resource
		$curl = curl_init();
		// Set curl options
		curl_setopt_array($curl, $curl_opts);
		// Send the request & save response to $response
		$response = curl_exec($curl);
		// Close request to clear up some resources
		curl_close($curl);
		// NYT recommends sleeping for 12 seconds between API calls to avoid rate limits.
		sleep(13);

		if ($list_name == 'names' && !isset(NYTApi::$allListsInfo)) {
			NYTApi::$allListsInfo = $response;
		}

		// return response
		return $response;
	}

}