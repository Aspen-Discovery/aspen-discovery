<?php
# ****************************************************************************************************************************
# * Last Edit: May 31, 2021
# * - Helper function to ensure that we grab the correct path for the library using it
# *
# * 05-31-21: needed to add libraryIdNumber in order to differentiate on Consortia - CZ
# * 05-03-21: needed to add shortname for the json being returned - CZ
# * 04-08-21: Base Version
# ****************************************************************************************************************************

# ****************************************************************************************************************************
# * FUNCTION urlPath
# * PARAM: $location: the location of selected in the pulldown of the app.
# *
# * Helper function that sets the pathing for the app to follow
# ****************************************************************************************************************************
function urlPath($location)
{
	switch ($location) {
		case 'ajax':
			$url = 'https://discover.ajaxlibrary.ca';
			$librarySubdomain = 'main';
			$libraryIdNumber = 2;
			break;
		case 'arcadiaca':
			$url = 'https://discovery.arcadialibrary.org';
			$librarySubdomain = 'arcadia';
			$libraryIdNumber = 4;
			break;
		case 'arlingtonva':
			$url = 'https://libcat.arlingtonva.us';
			$librarySubdomain = 'arlington';
			$libraryIdNumber = 2;
			break;
		case 'benbrook':
			$url = 'https://discovery.benbrooklibrary.org';
			$librarySubdomain = 'benbrook';
			$libraryIdNumber = 4;
			break;
		case 'ckls':
			$url = 'https://pathfinder.catalog.ckls.org';
			$librarySubdomain = 'pathfinder';
			$libraryIdNumber = 2;
			break;
		case 'clic':
			$url = 'https://catalog.aspencat.info';
			$librarySubdomain = 'catalog';
			$libraryIdNumber = 2;
			break;
		case 'crawfordcounty':
			$url = 'https://ccfls.org';
			$librarySubdomain = 'ccfls-aspen';
			$libraryIdNumber = 2;
			break;
		case 'dubuque':
			$url = 'https://catalog.dubcolib.org';
			$librarySubdomain = 'catalog';
			$libraryIdNumber = 2;
			break;
		case 'duchesne':
			$url = 'https://catalog.duchesnecountylibrary.org';
			$librarySubdomain = 'duchesne';
			$libraryIdNumber = 4;
			break;
		case 'flagstaff':
			$url = 'https://catalog.flagstaffpubliclibrary.org';
			$librarySubdomain = 'flagstaff';
			$libraryIdNumber = 2;
			break;
		case 'jacksoncounty':
			$url = 'https://catalog.jcls.org';
			$librarySubdomain = 'catalog';
			$libraryIdNumber = 2;
			break;
		case 'nashville':
			$url = 'https://catalog.library.nashville.org';
			$librarySubdomain = 'catalog';
			$libraryIdNumber = 2;
			break;
		case 'pueblo':
			$url = 'https://catalog.pueblolibrary.org';
			$librarySubdomain = 'catalog';
			$libraryIdNumber = 2;
			break;
		case 'roundrock':
			$url = 'https://discovery.roundrocktexas.gov';
			$librarySubdomain = 'discovery';
			$libraryIdNumber = 2;
			break;
		case 'test':
			$url = 'https://aspen-test.bywatersolutions.com';
			$librarySubdomain = 'm';
			$libraryIdNumber = 3;
			break;
		case 'salinaks':
			$url = 'https://discover.salinapubliclibrary.org';
			$librarySubdomain = 'SPL';
			$libraryIdNumber = 5;
			break;
		case 'santafe':
			$url = 'https://catalog.santafelibrary.org';
			$librarySubdomain = 'santafe';
			$libraryIdNumber = 2;
			break;
		case 'swan':
			$url = 'https://catalogbeta.swanlibraries.net';
			$librarySubdomain = 'SWS';
			$libraryIdNumber = 298;
			break;
		case 'uintah':
			$url = 'https://catalog.uintahlibrary.org';
			$librarySubdomain = 'uintah';
			$libraryIdNumber = 2;
			break;
		case 'vokal':
			$url = 'https://vokal-aspen.bywatersolutions.com';
			$librarySubdomain = 'vokal-aspen';
			$libraryIdNumber = 2;
			break;
		case 'wasatch':
			$url = 'https://catalog.wasatchlibrary.org';
			$librarySubdomain = 'wcl';
			$libraryIdNumber = 6;
			break;
		case 'washoe':
			$url = 'https://catalog.washoecountylibrary.us';
			$librarySubdomain = 'main';
			$libraryIdNumber = 2;
			break;
	}

	return array($url, $librarySubdomain, $libraryIdNumber);
}