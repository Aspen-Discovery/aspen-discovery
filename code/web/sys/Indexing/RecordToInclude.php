<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class RecordToInclude extends DataObject {
	public $id;
	public $indexingProfileId;
	public /** @noinspection PhpUnused */
		$markRecordsAsOwned;
	public $location;
	public /** @noinspection PhpUnused */
		$locationsToExclude;
	public $subLocation;
	public /** @noinspection PhpUnused */
		$subLocationsToExclude;
	public /** @noinspection PhpUnused */
		$iType;
	public /** @noinspection PhpUnused */
		$iTypesToExclude;
	public /** @noinspection PhpUnused */
		$audience;
	public /** @noinspection PhpUnused */
		$audiencesToExclude;
	public $format;
	public /** @noinspection PhpUnused */
		$formatsToExclude;
	public /** @noinspection PhpUnused */
		$shelfLocation;
	public /** @noinspection PhpUnused */
		$shelfLocationsToExclude;
	public /** @noinspection PhpUnused */
		$collectionCode;
	public /** @noinspection PhpUnused */
		$collectionCodesToExclude;
	public /** @noinspection PhpUnused */
		$includeHoldableOnly;
	public /** @noinspection PhpUnused */
		$includeItemsOnOrder;
	public /** @noinspection PhpUnused */
		$includeEContent;
	//The next 3 fields allow inclusion or exclusion of records based on a marc tag
	public /** @noinspection PhpUnused */
		$marcTagToMatch;
	public /** @noinspection PhpUnused */
		$marcValueToMatch;
	public /** @noinspection PhpUnused */
		$includeOnlyMatches;
	//The next 2 fields determine how urls are constructed
	public /** @noinspection PhpUnused */
		$urlToMatch;
	public /** @noinspection PhpUnused */
		$urlReplacement;
	public /** @noinspection PhpUnused */
		$includeExcludeMatches;

	public $weight;

	static function getObjectStructure($context = ''): array {
		$indexingProfiles = [];
		require_once ROOT_DIR . '/sys/Indexing/IndexingProfile.php';
		$indexingProfile = new IndexingProfile();
		$indexingProfile->orderBy('name');
		$indexingProfile->find();
		while ($indexingProfile->fetch()) {
			$indexingProfiles[$indexingProfile->id] = $indexingProfile->name;
		}
		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'weight' => [
				'property' => 'weight',
				'type' => 'integer',
				'label' => 'Weight',
				'description' => 'The sort order',
				'default' => 0,
			],
			'indexingProfileId' => [
				'property' => 'indexingProfileId',
				'type' => 'enum',
				'values' => $indexingProfiles,
				'label' => 'Indexing Profile Id',
				'description' => 'The Indexing Profile this map is associated with',
			],
			'markRecordsAsOwned' => [
				'property' => 'markRecordsAsOwned',
				'type' => 'checkbox',
				'label' => 'Owned?',
				'description' => 'Marks items as owned',
				'forcesReindex' => true,
			],
			'location' => [
				'property' => 'location',
				'type' => 'regularExpression',
				'label' => 'Location (Regex)',
				'description' => 'A regular expression for location codes to include',
				'maxLength' => '100',
				'required' => false,
				'forcesReindex' => true,
			],
			'locationsToExclude' => [
				'property' => 'locationsToExclude',
				'type' => 'regularExpression',
				'label' => 'Locations to Exclude (Regex)',
				'description' => 'A regular expression for location codes to exclude',
				'maxLength' => '500',
				'required' => false,
				'forcesReindex' => true,
			],
			'subLocation' => [
				'property' => 'subLocation',
				'type' => 'regularExpression',
				'label' => 'Sub Location (Regex)',
				'description' => 'A regular expression for sublocation codes to include',
				'maxLength' => '100',
				'required' => false,
				'forcesReindex' => true,
			],
			'subLocationsToExclude' => [
				'property' => 'subLocationsToExclude',
				'type' => 'regularExpression',
				'label' => 'Sub Locations to Exclude (Regex)',
				'description' => 'A regular expression for sublocation codes to exclude',
				'maxLength' => '400',
				'required' => false,
				'forcesReindex' => true,
			],
			'iType' => [
				'property' => 'iType',
				'type' => 'regularExpression',
				'label' => 'iType (Regex)',
				'description' => 'A regular expression for item types to include',
				'maxLength' => '100',
				'required' => false,
				'forcesReindex' => true,
			],
			'iTypesToExclude' => [
				'property' => 'iTypesToExclude',
				'type' => 'regularExpression',
				'label' => 'iTypes to Exclude (Regex)',
				'description' => 'A regular expression for item types to exclude',
				'maxLength' => '100',
				'required' => false,
				'forcesReindex' => true,
			],
			'audience' => [
				'property' => 'audience',
				'type' => 'regularExpression',
				'label' => 'Audience (Regex)',
				'description' => 'A regular expression for audiences to include',
				'maxLength' => '100',
				'required' => false,
				'forcesReindex' => true,
			],
			'audiencesToExclude' => [
				'property' => 'audiencesToExclude',
				'type' => 'regularExpression',
				'label' => 'Audiences to Exclude (Regex)',
				'description' => 'A regular expression for audiences to exclude',
				'maxLength' => '100',
				'required' => false,
				'forcesReindex' => true,
			],
			'format' => [
				'property' => 'format',
				'type' => 'regularExpression',
				'label' => 'Format (Regex)',
				'description' => 'A regular expression for formats to include',
				'maxLength' => '100',
				'required' => false,
				'forcesReindex' => true,
			],
			'formatsToExclude' => [
				'property' => 'formatsToExclude',
				'type' => 'regularExpression',
				'label' => 'Formats to Exclude (Regex)',
				'description' => 'A regular expression for formats to exclude',
				'maxLength' => '100',
				'required' => false,
				'forcesReindex' => true,
			],
			'shelfLocation' => [
				'property' => 'shelfLocation',
				'type' => 'regularExpression',
				'label' => 'Shelf Location (Regex)',
				'description' => 'A regular expression for shelf locations to include',
				'maxLength' => '100',
				'required' => false,
				'forcesReindex' => true,
			],
			'shelfLocationsToExclude' => [
				'property' => 'shelfLocationsToExclude',
				'type' => 'regularExpression',
				'label' => 'Shelf Locations to Exclude (Regex)',
				'description' => 'A regular expression for shelf locations to exclude',
				'maxLength' => '500',
				'required' => false,
				'forcesReindex' => true,
			],
			'collectionCode' => [
				'property' => 'collectionCode',
				'type' => 'regularExpression',
				'label' => 'Collection Code (Regex)',
				'description' => 'A regular expression for collection codes to include',
				'maxLength' => '100',
				'required' => false,
				'forcesReindex' => true,
			],
			'collectionCodesToExclude' => [
				'property' => 'collectionCodesToExclude',
				'type' => 'regularExpression',
				'label' => 'Collection Codes to Exclude (Regex)',
				'description' => 'A regular expression for collection codes to exclude',
				'maxLength' => '500',
				'required' => false,
				'forcesReindex' => true,
			],
			'includeHoldableOnly' => [
				'property' => 'includeHoldableOnly',
				'type' => 'checkbox',
				'label' => 'Include Holdable Only',
				'description' => 'Whether or not non-holdable records are included',
				'default' => false,
				'forcesReindex' => true,
			],
			'includeItemsOnOrder' => [
				'property' => 'includeItemsOnOrder',
				'type' => 'checkbox',
				'label' => 'Include Items On Order',
				'description' => 'Whether or not order records are included',
				'default' => 1,
				'forcesReindex' => true,
			],
			'includeEContent' => [
				'property' => 'includeEContent',
				'type' => 'checkbox',
				'label' => 'Include e-content Items',
				'description' => 'Whether or not e-Content should be included',
				'default' => 1,
				'forcesReindex' => true,
			],
			'marcTagToMatch' => [
				'property' => 'marcTagToMatch',
				'type' => 'text',
				'label' => 'Tag To Match',
				'description' => 'MARC tag(s) to match',
				'maxLength' => '100',
				'required' => false,
				'forcesReindex' => true,
			],
			'marcValueToMatch' => [
				'property' => 'marcValueToMatch',
				'type' => 'text',
				'label' => 'Value To Match',
				'description' => 'The value to match within the MARC tag(s) if multiple tags are specified, a match against any tag will count as a match of everything',
				'maxLength' => '100',
				'required' => false,
				'forcesReindex' => true,
			],
			'includeExcludeMatches' => [
				'property' => 'includeExcludeMatches',
				'type' => 'enum',
				'values' => [
					'1' => 'Include Matches',
					'0' => 'Exclude Matches',
				],
				'label' => 'Include Matches?',
				'description' => 'Whether or not matches are included or excluded',
				'default' => 1,
				'forcesReindex' => true,
			],
			'urlToMatch' => [
				'property' => 'urlToMatch',
				'type' => 'text',
				'label' => 'URL To Match',
				'description' => 'URL to match when rewriting urls',
				'maxLength' => '100',
				'required' => false,
				'forcesReindex' => true,
			],
			'urlReplacement' => [
				'property' => 'urlReplacement',
				'type' => 'text',
				'label' => 'URL Replacement',
				'description' => 'The replacement pattern for url rewriting',
				'maxLength' => '100',
				'required' => false,
				'forcesReindex' => true,
			],
		];
	}
}