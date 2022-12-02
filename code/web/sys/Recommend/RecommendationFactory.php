<?php

/**
 * RecommendationFactory Class
 *
 * This is a factory class to build recommendation modules for use in searches.
 *
 * @author      Demian Katz <demian.katz@villanova.edu>
 * @access      public
 */
class RecommendationFactory {
	/**
	 * initRecommendation
	 *
	 * This constructs a recommendation module object.
	 *
	 * @access  public
	 * @param string $module The name of the recommendation module to build
	 * @param object $searchObj The SearchObject using the recommendations.
	 * @param string $params Configuration string to send to the constructor
	 * @return  mixed               The $module object on success, false otherwise
	 */
	static function initRecommendation($module, $searchObj, $params) {
		global $configArray;
		$path = "{$configArray['Site']['local']}/sys/Recommend/{$module}.php";
		if (is_readable($path)) {
			require_once $path;
			if (class_exists($module)) {
				$recommend = new $module($searchObj, $params);
				return $recommend;
			}
		}

		return false;
	}
}