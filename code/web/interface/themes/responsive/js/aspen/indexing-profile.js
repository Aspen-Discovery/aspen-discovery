AspenDiscovery.IndexingClass = (function () {
	return {

		indexingClassSelect: function (id) {
			//Hide all
			$(".form-group").each(function () {
				$(this).hide();
			});

			//Show Class Select
			$("#propertyRowid").show();
			$("#propertyRowindexingClass").show();
			$(".btn-group").parent().show();


			//Config per Class
			var ilsOptions = {
				//Common for all classes
				commonFields: ['propertyRowid', 'propertyRowname', 'propertyRowmarcPath', 'propertyRowfilenamesToInclude',
					'propertyRowmarcEncoding', 'propertyRowindividualMarcPath', 'propertyRownumCharsToCreateFolderFrom',
					'propertyRowcreateFolderFromLeadingCharacters', 'propertyRowgroupingClass', 'propertyRowrecordDriver',
					'propertyRowcatalogDriver', 'propertyRowrecordUrlComponent', 'propertyRowprocessRecordLinking',
					'propertyRowrecordNumberTag', 'propertyRowrecordNumberSubfield', 'propertyRowrecordNumberPrefix',
					'propertyRowcustomMarcFieldsToIndexAsKeyword', 'propertyRowtreatUnknownLanguageAs',
					'propertyRowtreatUndeterminedLanguageAs', 'propertyRowsuppressRecordsWithUrlsMatching',
					'propertyRowdetermineAudienceBy', 'propertyRowaudienceSubfield', 'propertyRowtreatUnknownAudienceAs',
					'propertyRowdetermineLiteraryFormBy', 'propertyRowliteraryFormSubfield', 'propertyRowhideUnknownLiteraryForm',
					'propertyRowhideNotCodedLiteraryForm', 'propertyRowitemSection', 'propertyRowsuppressItemlessBibs',
					'propertyRowitemTag', 'propertyRowitemRecordNumber', 'propertyRowuseItemBasedCallNumbers',
					'propertyRowcallNumberPrestamp', 'propertyRowcallNumberPrestamp2', 'propertyRowcallNumber', 'propertyRowcallNumberCutter', 'propertyRowcallNumberPoststamp',
					'propertyRowlocation', 'propertyRowincludeLocationNameInDetailedLocation', 'propertyRownonHoldableLocations',
					'propertyRowlocationsToSuppress', 'propertyRowsubLocation', 'propertyRowshelvingLocation', 'propertyRowcollection',
					'propertyRowcollectionsToSuppress', 'propertyRowitemUrl', 'propertyRowitemUrlDescription', 'propertyRowvolume', 'propertyRowbarcode',
					'propertyRowstatus', 'propertyRownonHoldableStatuses', 'propertyRowstatusesToSuppress',
					'propertyRowtreatLibraryUseOnlyGroupedStatusesAsAvailable', 'propertyRowtotalCheckouts', 'propertyRowlastYearCheckouts',
					'propertyRowyearToDateCheckouts', 'propertyRowtotalRenewals', 'propertyRowiType', 'propertyRownonHoldableITypes',
					'propertyRowiTypesToSuppress', 'propertyRowdueDate', 'propertyRowdueDateFormat', 'propertyRowdateCreated',
					'propertyRowdateCreatedFormat', 'propertyRowlastCheckinDate', 'propertyRowlastCheckinFormat', 'propertyRowformat',
					'propertyRoweContentDescriptor', 'propertyRowdoAutomaticEcontentSuppression', 'propertyRownoteSubfield',
					'propertyRowformatMappingSection', 'propertyRowformatSource', 'propertyRowfallbackFormatField',
					'propertyRowspecifiedFormat', 'propertyRowspecifiedFormatCategory', 'propertyRowspecifiedFormatBoost',
					'propertyRowcheckRecordForLargePrint', 'propertyRowformatMap', 'propertyRowstatusMappingSection',
					'propertyRowstatusMap', 'propertyRoworderTag', 'propertyRoworderStatus', 'propertyRoworderLocationSingle',
					'propertyRoworderLocation', 'propertyRoworderCopies', 'propertyRoworderCode3', 'propertyRowregroupAllRecords',
					'propertyRowrunFullUpdate', 'propertyRowlastUpdateOfChangedRecords', 'propertyRowlastUpdateOfAllRecords',
					'propertyRowlastChangeProcessed', 'propertyRowfullMarcExportRecordIdThreshold', 'propertyRowlastUpdateFromMarcExport',
					'propertyRowtranslationMaps', 'FloatingSave', 'propertyRowindex856Links', 'propertyRowincludePersonalAndCorporateNamesInTopics',
					'propertyRowcustomFacetSection', 'propertyRowcustomFacet1SourceField', 'propertyRowcustomFacet1ValuesToInclude', 'propertyRowcustomFacet1ValuesToExclude',
					'propertyRowcustomFacet2SourceField', 'propertyRowcustomFacet2ValuesToInclude', 'propertyRowcustomFacet2ValuesToExclude',
					'propertyRowcustomFacet3SourceField', 'propertyRowcustomFacet3ValuesToInclude', 'propertyRowcustomFacet3ValuesToExclude'
				],
				//Specific per class
				Koha: ['propertyRowlastUpdateOfAuthorities'],
				Evolve: [],
				ArlingtonKoha: ['propertyRowlastUpdateOfAuthorities'],
				CarlX: [],
				NashvilleCarlX: [],
				Folio: [],
				III: ['propertyRowbCode3sToSuppress', 'propertyRowiCode2', 'propertyRowuseICode2Suppression', 'propertyRowiCode2sToSuppress', 'propertyRoworderSection', 'propertyRowsierraSection', 'propertyRoworderRecordsStatusesToInclude', 'propertyRowhideOrderRecordsForBibsWithPhysicalItems', 'propertyRoworderRecordsToSuppressByDate', 'propertyRowsierraFieldMappings', 'propertyRowcheckSierraMatTypeForFormat'],
				Symphony: ['propertyRowlastVolumeExportTimestamp'],
				Polaris: [],
				Evergreen: ['propertyRowevergreenOrgUnitSchema', 'propertyRowevergreenSection', 'propertyRownumRetriesForBibLookups', 'propertyRownumMillisecondsToPauseAfterBibLookups']
			};

			//Show rows for selected class
			var selectedIndexingClass = $("#indexingClassSelect").val();
			var selectedIndexingClassText = $("#indexingClassSelect option:selected").text();

			if (selectedIndexingClass !== '...' && selectedIndexingClass !== '') {
				var iterator = ilsOptions[selectedIndexingClass];
				iterator = $.merge(ilsOptions['commonFields'], iterator);
				iterator.forEach(function (value) {
					$("#" + value).show();
				});
			}
		}
	}
}(AspenDiscovery.IndexingClass || {}));
