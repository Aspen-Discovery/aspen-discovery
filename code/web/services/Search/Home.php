<?php
/**
 *
 * Copyright (C) Villanova University 2007.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

require_once ROOT_DIR . '/Action.php';

class Search_Home extends Action {

	function launch()
	{
		global $interface;
		global $configArray;
		global $library;
		global $locationSingleton;
		global $timer;

		// Include Search Engine Class
		require_once ROOT_DIR . '/sys/' . $configArray['Index']['engine'] . '.php';
		$timer->logTime('Include search engine');

		$interface->assign('showBreadcrumbs', 0);

		// Load browse categories
		require_once ROOT_DIR . '/sys/Browse/BrowseCategory.php';
		/** @var BrowseCategory[] $browseCategories */
		$browseCategories = array();

		// Get Location's Browse Categories if Location is set
		$activeLocation = $locationSingleton->getActiveLocation();
		if ($activeLocation != null && $activeLocation->browseCategories){
			$browseCategories = $this->getBrowseCategories($activeLocation->browseCategories);
		}

		// Get Library's Browse Categories if none were set for Location
		if (isset($library) && empty($browseCategories) && $library->browseCategories){
			$browseCategories = $this->getBrowseCategories($library->browseCategories);
		}

		// Get Browse Categories for default Library if none are set for the Library and Location
		if (empty($browseCategories)) {
			$defaultLibrary = new Library();
			$defaultLibrary->isDefault = true;
			if ($defaultLibrary->find(true)) {
				$browseCategories = $this->getBrowseCategories($defaultLibrary->browseCategories);
			}
		}

		// Get All Browse Categories if Location & Library had none set
		if (empty($browseCategories)){
			$browseCategories = $this->getBrowseCategories();
		}

		$interface->assign('browseCategories', $browseCategories);

		//Set a Default Browse Mode
		if (count($browseCategories) > 0){
			require_once ROOT_DIR . '/services/Browse/AJAX.php';
			$browseAJAX = new Browse_AJAX();
			$browseAJAX->setBrowseMode(); // set default browse mode in the case that the user hasn't chosen one.

			// browse results no longer needed. there is an embedded ajax call in home.tpl. plb 5-4-2015
////			$browseResults = $browseAJAX->getBrowseCategoryInfo(reset($browseCategories)->textId);
////			$interface->assign('browseResults', $browseResults);
		}
		if (!$interface->get_template_vars('browseMode')) {
			$interface->assign('browseMode', 'covers'); // fail safe: if no browseMode is set at all, go with covers
		}

		$this->display('home.tpl', 'Catalog Home');
	}


	/**
	 * @param LocationBrowseCategory|LibraryBrowseCategory|null $localBrowseCategories
	 * @return BrowseCategory[]
	 */
	public function getBrowseCategories($localBrowseCategories=null) {
		global $interface,
						$user;

		$browseCategories = array();
		$specifiedCategory = isset($_REQUEST['browseCategory']);
		$specifiedSubCategory = $specifiedCategory && isset($_REQUEST['subCategory']); // make a specified main browse category required
		if ($localBrowseCategories) {
			$first = key($localBrowseCategories); // get key of first category
			foreach ($localBrowseCategories as $index => $localBrowseCategory) {
				$browseCategory         = new BrowseCategory();
				$browseCategory->textId = $localBrowseCategory->browseCategoryTextId;
				if (($browseCategory->textId == 'system_recommended_for_you' && $user && $user->hasRatings()) || $browseCategory->find(true)) {
					// Only Show the Recommended for You browse category if the user is logged in and has rated titles
					if ($browseCategory->textId == 'system_recommended_for_you') {
						$browseCategory->label = translate('Recommended For You');
					}
					$browseCategories[] = clone($browseCategory);
					if (
						($specifiedCategory && $_REQUEST['browseCategory'] == $browseCategory->textId) // A category has been selected through URL parameter
						|| (!$specifiedCategory && $index == $first) // Or default to selecting the first browse category
					) {
						$selectedBrowseCategory = clone($browseCategory); //TODO needed?
						$interface->assign('selectedBrowseCategory', $selectedBrowseCategory);
						if ($specifiedSubCategory) {
							$selectedBrowseCategory->getSubCategories();

							$validSubCategory = false;
							$subCategories = array();
							/** @var SubBrowseCategories $subCategory */
							foreach ($selectedBrowseCategory->subBrowseCategories as $subCategory) {
								// Get Needed Info about sub-category
								/** @var BrowseCategory $temp */
								$temp = new BrowseCategory();
								$temp->get($subCategory->subCategoryId);
								if ($temp) {
									if ($temp->textId == $_REQUEST['subCategory']) $validSubCategory = true;
									$subCategories[] = array('label' => $temp->label, 'textId' => $temp->textId);
								}
							}
							if ($validSubCategory) {
								$interface->assign('subCategoryTextId', $_REQUEST['subCategory']);
								$interface->assign('subCategories', $subCategories);
							}
						}
					}
				}
			}
		} else { // get All BrowseCategories
			$browseCategory = new BrowseCategory();
			$browseCategory->orderBy('numTitlesClickedOn');
			$browseCategory->limit(0, 20);
			$browseCategory->find();
			while($browseCategory->fetch()){
				//Do not use the browse category if it is a subcategory of any other category
				$subCategoryInfo = new SubBrowseCategories();
				$subCategoryInfo->subCategoryId = $browseCategory->id;
				$subCategoryInfo->find();
				if ($subCategoryInfo->N > 0){
					continue;
				}

//				$browseCategory->getSubCategories(); // add subcategory information to the object
				$browseCategories[] = clone($browseCategory);
				if ($specifiedCategory && $_REQUEST['browseCategory'] == $browseCategory->textId) {
					$selectedBrowseCategory = clone($browseCategory);
					$interface->assign('selectedBrowseCategory', $selectedBrowseCategory);
					if ($specifiedSubCategory) {
						$selectedBrowseCategory->getSubCategories();

						$validSubCategory = false;
						$subCategories = array();
						/** @var SubBrowseCategories $subCategory */
						foreach ($selectedBrowseCategory->subBrowseCategories as $subCategory) {
							// Get Needed Info about sub-category
							/** @var BrowseCategory $temp */
							$temp = new BrowseCategory();
							$temp->get($subCategory->subCategoryId);
							if ($temp) {
								if ($temp->textId == $_REQUEST['subCategory']) $validSubCategory = true;
								$subCategories[] = array('label' => $temp->label, 'textId' => $temp->textId);
							}
						}
						if ($validSubCategory) {
							$interface->assign('subCategoryTextId', $_REQUEST['subCategory']);
							$interface->assign('subCategories', $subCategories);
						}
					}
				}
			}
		}
		return $browseCategories;
	}

}