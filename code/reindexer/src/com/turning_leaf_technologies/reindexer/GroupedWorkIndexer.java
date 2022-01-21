package com.turning_leaf_technologies.reindexer;

import com.turning_leaf_technologies.indexing.BaseIndexingSettings;
import com.turning_leaf_technologies.indexing.IndexingProfile;
import com.turning_leaf_technologies.indexing.IndexingUtils;
import com.turning_leaf_technologies.indexing.Scope;
import com.turning_leaf_technologies.logging.BaseLogEntry;
import com.turning_leaf_technologies.marc.MarcUtil;
import com.turning_leaf_technologies.strings.StringUtils;
import com.turning_leaf_technologies.util.MaxSizeHashMap;
import org.apache.solr.client.solrj.impl.BinaryRequestWriter;
import org.apache.solr.client.solrj.impl.ConcurrentUpdateSolrClient;
import org.apache.solr.client.solrj.impl.HttpSolrClient;
import org.apache.solr.client.solrj.response.UpdateResponse;
import org.apache.solr.common.SolrInputDocument;
import org.ini4j.Ini;

import java.io.*;
import java.nio.charset.StandardCharsets;
import java.sql.*;
import java.util.*;
import java.util.Date;
import java.util.zip.CRC32;

import org.apache.logging.log4j.Logger;
import org.marc4j.MarcJsonWriter;
import org.marc4j.MarcWriter;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Record;

public class GroupedWorkIndexer {
	private final String serverName;
	private final BaseLogEntry logEntry;
	private final Logger logger;
	private final Long indexStartTime;
	private int totalRecordsHandled = 0;
	private ConcurrentUpdateSolrClient updateServer;
	private final HashMap<String, MarcRecordProcessor> ilsRecordProcessors = new HashMap<>();
	private final HashMap<String, SideLoadedEContentProcessor> sideLoadProcessors = new HashMap<>();
	private OverDriveProcessor overDriveProcessor;
	private CloudLibraryProcessor cloudLibraryProcessor;
	private Axis360Processor axis360Processor;
	private HooplaProcessor hooplaProcessor;
	private final HashMap<String, HashMap<String, String>> translationMaps = new HashMap<>();
	private final HashMap<String, LexileTitle> lexileInformation = new HashMap<>();

	private PreparedStatement getRatingStmt;
	private PreparedStatement getNovelistStmt;
	private PreparedStatement getDisplayInfoStmt;

	private PreparedStatement getUserReadingHistoryLinkStmt;
	private PreparedStatement getUserRatingLinkStmt;
	private PreparedStatement getUserNotInterestedLinkStmt;

	private final Connection dbConn;

	static int availableAtBoostValue = 50;
	static int ownedByBoostValue = 10;

	private final boolean fullReindex;
	private final boolean clearIndex;
	private long lastReindexTime;
	private Long lastReindexTimeVariableId;
	private boolean okToIndex = true;


	private TreeSet<Scope> scopes ;

	private PreparedStatement getGroupedWorkPrimaryIdentifiers;
	private PreparedStatement getGroupedWorkInfoStmt;
	private PreparedStatement getArBookIdForIsbnStmt;
	private PreparedStatement getArBookInfoStmt;
	private PreparedStatement getScheduledWorksStmt;
	private PreparedStatement getScheduledWorkStmt;
	private PreparedStatement markScheduledWorkProcessedStmt;
	private PreparedStatement addScheduledWorkStmt;

	private PreparedStatement getExistingScopesStmt;
	private PreparedStatement addScopeStmt;
	private PreparedStatement updateScopeStmt;
	private PreparedStatement removeScopeStmt;

	private PreparedStatement marcIlsRecordAsDeletedStmt;
	private PreparedStatement getExistingRecordsForWorkStmt;
	private PreparedStatement addRecordForWorkStmt;
	private PreparedStatement updateRecordForWorkStmt;
	private PreparedStatement getIdForRecordStmt;
	private PreparedStatement removeRecordForWorkStmt;
	private PreparedStatement getExistingVariationsForWorkStmt;
	private PreparedStatement addVariationForWorkStmt;
	private PreparedStatement removeVariationStmt;
	private PreparedStatement getExistingItemsForRecordStmt;
	private PreparedStatement removeItemStmt;
	private PreparedStatement addItemForRecordStmt;
	private PreparedStatement updateItemForRecordStmt;
	private PreparedStatement addItemUrlStmt;
	private PreparedStatement getRecordSourceStmt;
	private PreparedStatement getRecordSourceWithNoSubSourceStmt;
	private PreparedStatement addRecordSourceStmt;
	private PreparedStatement getFormatCategoryStmt;
	private PreparedStatement addFormatCategoryStmt;
	private PreparedStatement getFormatStmt;
	private PreparedStatement addFormatStmt;
	private PreparedStatement getLanguageStmt;
	private PreparedStatement addLanguageStmt;
	private PreparedStatement getEditionStmt;
	private PreparedStatement addEditionStmt;
	private PreparedStatement getPublisherStmt;
	private PreparedStatement addPublisherStmt;
	private PreparedStatement getPublicationDateStmt;
	private PreparedStatement addPublicationDateStmt;
	private PreparedStatement getPhysicalDescriptionStmt;
	private PreparedStatement addPhysicalDescriptionStmt;
	private PreparedStatement getEContentSourceStmt;
	private PreparedStatement addEContentSourceStmt;
	private PreparedStatement getShelfLocationStmt;
	private PreparedStatement addShelfLocationStmt;
	private PreparedStatement getCallNumberStmt;
	private PreparedStatement addCallNumberStmt;
	private PreparedStatement getStatusStmt;
	private PreparedStatement addStatusStmt;
	private PreparedStatement getLocationCodeStmt;
	private PreparedStatement addLocationCodeStmt;
	private PreparedStatement getSubLocationCodeStmt;
	private PreparedStatement addSubLocationCodeStmt;

	private PreparedStatement getExistingRecordInfoForIdentifierStmt;
	private PreparedStatement getRecordForIdentifierStmt;
	private PreparedStatement addRecordToDBStmt;
	private PreparedStatement updateRecordInDBStmt;
	private final CRC32 checksumCalculator = new CRC32();

	private boolean removeRedundantHooplaRecords = false;

	private boolean storeRecordDetailsInSolr = false;
	private boolean storeRecordDetailsInDatabase = true;

	private boolean hideUnknownLiteraryForm;
	private boolean hideNotCodedLiteraryForm;

	private String treatUnknownAudienceAs = "Unknown";
	private boolean treatUnknownAudienceAsUnknown = false;
	private boolean treatUnknownAudienceAsGeneral = false;
	private boolean treatUnknownAudienceAsAdult = false;
	private String treatUnknownLanguageAs = "English";

	public GroupedWorkIndexer(String serverName, Connection dbConn, Ini configIni, boolean fullReindex, boolean clearIndex, BaseLogEntry logEntry, Logger logger) {
		indexStartTime = new Date().getTime() / 1000;
		this.serverName = serverName;
		this.logEntry = logEntry;
		this.logger = logger;
		this.dbConn = dbConn;
		this.fullReindex = fullReindex;
		this.clearIndex = clearIndex;

		String solrPort = configIni.get("Reindex", "solrPort");

		//Load the last Index time
		try{
			PreparedStatement loadLastGroupingTime = dbConn.prepareStatement("SELECT * from variables WHERE name = 'last_reindex_time'");
			ResultSet lastGroupingTimeRS = loadLastGroupingTime.executeQuery();
			if (lastGroupingTimeRS.next()){
				lastReindexTime = lastGroupingTimeRS.getLong("value");
				lastReindexTimeVariableId = lastGroupingTimeRS.getLong("id");
			}
			lastGroupingTimeRS.close();
			loadLastGroupingTime.close();
		} catch (Exception e){
			logEntry.incErrors("Could not load last index time from variables table ", e);
		}

		//Check to see if we should store record details in Solr
		try{
			PreparedStatement systemVariablesStmt = dbConn.prepareStatement("SELECT storeRecordDetailsInSolr, storeRecordDetailsInDatabase from system_variables");
			ResultSet systemVariablesRS = systemVariablesStmt.executeQuery();
			if (systemVariablesRS.next()){
				this.storeRecordDetailsInSolr = systemVariablesRS.getBoolean("storeRecordDetailsInSolr");
				this.storeRecordDetailsInDatabase = systemVariablesRS.getBoolean("storeRecordDetailsInDatabase");
			}
			systemVariablesRS.close();
			systemVariablesStmt.close();
		} catch (Exception e){
			logEntry.incErrors("Could not load last index time from variables table ", e);
		}

		//Load a few statements we will need later
		try{
			getGroupedWorkPrimaryIdentifiers = dbConn.prepareStatement("SELECT * FROM grouped_work_primary_identifiers where grouped_work_id = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			//deleteGroupedWorkStmt = dbConn.prepareStatement("DELETE from grouped_work where id = ?");
			getGroupedWorkInfoStmt = dbConn.prepareStatement("SELECT id, grouping_category from grouped_work where permanent_id = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			getArBookIdForIsbnStmt = dbConn.prepareStatement("SELECT arBookId from accelerated_reading_isbn where isbn = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			getArBookInfoStmt = dbConn.prepareStatement("SELECT * from accelerated_reading_titles where arBookId = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			getScheduledWorksStmt = dbConn.prepareStatement("SELECT * FROM grouped_work_scheduled_index where processed = 0 and indexAfter <= ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			getScheduledWorkStmt = dbConn.prepareStatement("SELECT * FROM grouped_work_scheduled_index where processed = 0 and permanent_id = ? and indexAfter = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			markScheduledWorkProcessedStmt = dbConn.prepareStatement("UPDATE grouped_work_scheduled_index set processed = 1 where id = ?");
			addScheduledWorkStmt = dbConn.prepareStatement("INSERT INTO grouped_work_scheduled_index (permanent_id, indexAfter) VALUES (?, ?)");

			marcIlsRecordAsDeletedStmt = dbConn.prepareStatement("UPDATE ils_records set deleted = 1, dateDeleted = ? where source = ? and ilsId = ?");
			getExistingScopesStmt = dbConn.prepareStatement("SELECT * from scope", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			addScopeStmt = dbConn.prepareStatement("INSERT INTO scope (name, isLibraryScope, isLocationScope) VALUES (?, ?, ?)", Statement.RETURN_GENERATED_KEYS);
			updateScopeStmt = dbConn.prepareStatement("UPDATE scope set isLibraryScope = ?, isLocationScope = ? WHERE id = ?");
			removeScopeStmt = dbConn.prepareStatement("DELETE FROM scope where id = ?");
			getExistingRecordsForWorkStmt = dbConn.prepareStatement("SELECT id, sourceId, recordIdentifier, groupedWorkId, editionId, publisherId, publicationDateId, physicalDescriptionId, formatId, formatCategoryId, languageId from grouped_work_records where groupedWorkId = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			addRecordForWorkStmt = dbConn.prepareStatement("INSERT INTO grouped_work_records (groupedWorkId, sourceId, recordIdentifier, editionId, publisherId, publicationDateId, physicalDescriptionId, formatId, formatCategoryId, languageId) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) " +
					"ON DUPLICATE KEY UPDATE groupedWorkId = VALUES(groupedWorkId), editionId = VALUES(editionId), publisherId = VALUES(publisherId), publicationDateId = VALUES(publicationDateId), physicalDescriptionId = VALUES(physicalDescriptionId), formatId = VALUES(formatId), formatCategoryId = VALUES(formatCategoryId), languageId = VALUES(languageId)", PreparedStatement.RETURN_GENERATED_KEYS);
			updateRecordForWorkStmt = dbConn.prepareStatement("UPDATE grouped_work_records SET groupedWorkId = ?, editionId = ?, publisherId = ?, publicationDateId = ?, physicalDescriptionId = ?, formatId = ?, formatCategoryId = ?, languageId = ? where id = ?");
			removeRecordForWorkStmt = dbConn.prepareStatement("DELETE FROM grouped_work_records where id = ?");
			getIdForRecordStmt = dbConn.prepareStatement("SELECT id from grouped_work_records where sourceId = ? and recordIdentifier = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			getExistingVariationsForWorkStmt = dbConn.prepareStatement("SELECT * from grouped_work_variation where groupedWorkId = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			addVariationForWorkStmt = dbConn.prepareStatement("INSERT INTO grouped_work_variation (groupedWorkId, primaryLanguageId, eContentSourceId, formatId, formatCategoryId) VALUES (?, ?, ?, ?, ?)", PreparedStatement.RETURN_GENERATED_KEYS);
			removeVariationStmt = dbConn.prepareStatement("DELETE FROM grouped_work_variation WHERE id = ?");
			getExistingItemsForRecordStmt = dbConn.prepareStatement("SELECT * from grouped_work_record_items WHERE groupedWorkRecordId = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			addItemForRecordStmt = dbConn.prepareStatement("INSERT INTO grouped_work_record_items (groupedWorkRecordId, groupedWorkVariationId, itemId, shelfLocationId, callNumberId, sortableCallNumberId, numCopies, isOrderItem, statusId, dateAdded, locationCodeId, subLocationCodeId, lastCheckInDate, groupedStatusId, available, holdable, inLibraryUseOnly, locationOwnedScopes, libraryOwnedScopes, recordIncludedScopes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", PreparedStatement.RETURN_GENERATED_KEYS);
			updateItemForRecordStmt = dbConn.prepareStatement("UPDATE grouped_work_record_items set groupedWorkVariationId = ?, shelfLocationId = ?, callNumberId = ?, sortableCallNumberId = ?, numCopies = ?, isOrderItem = ?, statusId = ?, dateAdded = ?, " +
					"locationCodeId = ?, subLocationCodeId = ?, lastCheckInDate = ?, groupedStatusId = ?, available = ?, holdable = ?, inLibraryUseOnly = ?, locationOwnedScopes = ?, libraryOwnedScopes = ?, recordIncludedScopes = ? WHERE id = ?");
			removeItemStmt = dbConn.prepareStatement("DELETE FROM grouped_work_record_items WHERE id = ?");
			addItemUrlStmt = dbConn.prepareStatement("INSERT INTO grouped_work_record_item_url (groupedWorkItemId, scopeId, url) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE url = VALUES(url) ");
			getRecordSourceStmt = dbConn.prepareStatement("SELECT id from indexed_record_source where source = ? and subSource = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			getRecordSourceWithNoSubSourceStmt = dbConn.prepareStatement("SELECT id from indexed_record_source where source = ? and subSource IS NULL", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			addRecordSourceStmt = dbConn.prepareStatement("INSERT INTO indexed_record_source (source, subSource) VALUES (?, ?)", Statement.RETURN_GENERATED_KEYS);
			getFormatCategoryStmt = dbConn.prepareStatement("SELECT id from indexed_format_category where formatCategory = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			addFormatCategoryStmt = dbConn.prepareStatement("INSERT INTO indexed_format_category (formatCategory) VALUES (?)", Statement.RETURN_GENERATED_KEYS);
			getFormatStmt = dbConn.prepareStatement("SELECT id from indexed_format where format = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			addFormatStmt = dbConn.prepareStatement("INSERT INTO indexed_format (format) VALUES (?)", Statement.RETURN_GENERATED_KEYS);
			getLanguageStmt = dbConn.prepareStatement("SELECT id from indexed_language where language = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			addLanguageStmt = dbConn.prepareStatement("INSERT INTO indexed_language (language) VALUES (?)", Statement.RETURN_GENERATED_KEYS);
			getEditionStmt = dbConn.prepareStatement("SELECT id from indexed_edition where edition = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			addEditionStmt = dbConn.prepareStatement("INSERT INTO indexed_edition (edition) VALUES (?)", Statement.RETURN_GENERATED_KEYS);
			getPublisherStmt = dbConn.prepareStatement("SELECT id from indexed_publisher where publisher = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			addPublisherStmt = dbConn.prepareStatement("INSERT INTO indexed_publisher (publisher) VALUES (?)", Statement.RETURN_GENERATED_KEYS);
			getPublicationDateStmt = dbConn.prepareStatement("SELECT id from indexed_publicationDate where publicationDate = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			addPublicationDateStmt = dbConn.prepareStatement("INSERT INTO indexed_publicationDate (publicationDate) VALUES (?)", Statement.RETURN_GENERATED_KEYS);
			getPhysicalDescriptionStmt = dbConn.prepareStatement("SELECT id from indexed_physicalDescription where physicalDescription = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			addPhysicalDescriptionStmt = dbConn.prepareStatement("INSERT INTO indexed_physicalDescription (physicalDescription) VALUES (?)", Statement.RETURN_GENERATED_KEYS);
			getEContentSourceStmt = dbConn.prepareStatement("SELECT id from indexed_eContentSource where eContentSource = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			addEContentSourceStmt = dbConn.prepareStatement("INSERT INTO indexed_eContentSource (eContentSource) VALUES (?)", Statement.RETURN_GENERATED_KEYS);
			getShelfLocationStmt = dbConn.prepareStatement("SELECT id from indexed_shelfLocation where shelfLocation = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			addShelfLocationStmt = dbConn.prepareStatement("INSERT INTO indexed_shelfLocation (shelfLocation) VALUES (?)", Statement.RETURN_GENERATED_KEYS);
			getCallNumberStmt = dbConn.prepareStatement("SELECT id from indexed_callNumber where callNumber = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			addCallNumberStmt = dbConn.prepareStatement("INSERT INTO indexed_callNumber (callNumber) VALUES (?)", Statement.RETURN_GENERATED_KEYS);
			getStatusStmt = dbConn.prepareStatement("SELECT id from indexed_status where status = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			addStatusStmt = dbConn.prepareStatement("INSERT INTO indexed_status (status) VALUES (?)", Statement.RETURN_GENERATED_KEYS);
			getLocationCodeStmt = dbConn.prepareStatement("SELECT id from indexed_locationCode where locationCode = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			addLocationCodeStmt = dbConn.prepareStatement("INSERT INTO indexed_locationCode (locationCode) VALUES (?)", Statement.RETURN_GENERATED_KEYS);
			getSubLocationCodeStmt = dbConn.prepareStatement("SELECT id from indexed_subLocationCode where subLocationCode = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			addSubLocationCodeStmt = dbConn.prepareStatement("INSERT INTO indexed_subLocationCode (subLocationCode) VALUES (?)", Statement.RETURN_GENERATED_KEYS);

			getExistingRecordInfoForIdentifierStmt = dbConn.prepareStatement("SELECT id, checksum, deleted, UNCOMPRESSED_LENGTH(sourceData) as sourceDataLength FROM ils_records where ilsId = ? and source = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			getRecordForIdentifierStmt = dbConn.prepareStatement("SELECT UNCOMPRESS(sourceData) as sourceData FROM ils_records where ilsId = ? and source = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			addRecordToDBStmt = dbConn.prepareStatement("INSERT INTO ils_records set ilsId = ?, source = ?, checksum = ?, dateFirstDetected = ?, deleted = 0, suppressedNoMarcAvailable = 0, sourceData = COMPRESS(?), lastModified = ?", PreparedStatement.RETURN_GENERATED_KEYS);
			updateRecordInDBStmt = dbConn.prepareStatement("UPDATE ils_records set checksum = ?, sourceData = COMPRESS(?), lastModified = ?, deleted = 0, suppressedNoMarcAvailable = 0 WHERE id = ?", PreparedStatement.RETURN_GENERATED_KEYS);
		} catch (Exception e){
			logEntry.incErrors("Could not load statements to get identifiers ", e);
			this.okToIndex = false;
			return;
		}

		//Check hoopla settings to see if we need to remove redundant records
		try{
			PreparedStatement getHooplaSettingsStmt = dbConn.prepareStatement("SELECT excludeTitlesWithCopiesFromOtherVendors from hoopla_settings");
			ResultSet getHooplaSettingsRS = getHooplaSettingsStmt.executeQuery();
			if (getHooplaSettingsRS.next()) {
				removeRedundantHooplaRecords = getHooplaSettingsRS.getBoolean("excludeTitlesWithCopiesFromOtherVendors");
			}
		}catch (Exception e){
			logEntry.incErrors("Error loading Hoopla Settings", e);
		}

		//Initialize the updateServer and solr server
		logEntry.addNote("Setting up update server and solr server");

		ConcurrentUpdateSolrClient.Builder solrBuilder = new ConcurrentUpdateSolrClient.Builder("http://localhost:" + solrPort + "/solr/grouped_works");
		solrBuilder.withThreadCount(1);
		solrBuilder.withQueueSize(25);
		updateServer = solrBuilder.build();
		updateServer.setRequestWriter(new BinaryRequestWriter());

		try {
			scopes = IndexingUtils.loadScopes(dbConn, logger);
			if (scopes == null){
				logEntry.incErrors("Error loading scopes, scopes were null");
				this.okToIndex = false;
				return;
			}else{
				logger.info("Loaded " + scopes.size() + " scopes");
			}

			HashMap<String, ExistingScopeInfo> existingScopes = this.getExistingScopes();
			for (Scope scope : scopes){
				String scopeKey = scope.getScopeName();
				ExistingScopeInfo scopeInfo = existingScopes.get(scopeKey);
				if (scopeInfo != null){
					scope.setId(scopeInfo.id);
					if (scopeInfo.isLocationScope != scope.isLocationScope() || scopeInfo.isLibraryScope != scope.isLibraryScope()){
						this.updateScope(scope);
					}
					existingScopes.remove(scopeKey);
				}else {
					Long scopeId = this.saveScope(scope);
					scope.setId(scopeId);
				}
			}
			for (ExistingScopeInfo scope : existingScopes.values()){
				this.removeScope(scope.id);
			}
		}catch (Exception e) {
			logEntry.incErrors("Error loading scopes", e);
			this.okToIndex = false;
			return;
		}

		//Initialize processors based on our indexing profiles and the primary identifiers for the records.
		try {
			PreparedStatement uniqueIdentifiersStmt = dbConn.prepareStatement("SELECT DISTINCT type FROM grouped_work_primary_identifiers");
			PreparedStatement getIndexingProfile = dbConn.prepareStatement("SELECT * from indexing_profiles where name = ?");
			PreparedStatement getSideLoadSettings = dbConn.prepareStatement("SELECT * from sideloads where name = ?");

			ResultSet uniqueIdentifiersRS = uniqueIdentifiersStmt.executeQuery();

			while (uniqueIdentifiersRS.next()){
				String curType = uniqueIdentifiersRS.getString("type");
				getIndexingProfile.setString(1, curType);
				ResultSet indexingProfileRS = getIndexingProfile.executeQuery();
				if (indexingProfileRS.next()){
					String ilsIndexingClassString = indexingProfileRS.getString("indexingClass");
					switch (ilsIndexingClassString) {
						case "ArlingtonKoha":
							ilsRecordProcessors.put(curType, new ArlingtonKohaRecordProcessor(this, curType, dbConn, indexingProfileRS, logger, fullReindex));
							break;
						case "CarlX":
							ilsRecordProcessors.put(curType, new CarlXRecordProcessor(this, curType, dbConn, indexingProfileRS, logger, fullReindex));
							break;
						case "III":
							ilsRecordProcessors.put(curType, new IIIRecordProcessor(this, curType, dbConn, indexingProfileRS, logger, fullReindex));
							break;
						case "SideLoadedEContent":
							ilsRecordProcessors.put(curType, new SideLoadedEContentProcessor(this, curType, dbConn, indexingProfileRS, logger, fullReindex));
							break;
						case "Koha":
							ilsRecordProcessors.put(curType, new KohaRecordProcessor(this, curType, dbConn, indexingProfileRS, logger, fullReindex));
							break;
						case "Symphony":
							ilsRecordProcessors.put(curType, new SymphonyRecordProcessor(this, curType, dbConn, indexingProfileRS, logger, fullReindex));
							break;
						case "Polaris":
							ilsRecordProcessors.put(curType, new PolarisRecordProcessor(this, curType, dbConn, indexingProfileRS, logger, fullReindex));
							break;
						case "Evergreen":
							ilsRecordProcessors.put(curType, new EvergreenRecordProcessor(this, curType, dbConn, indexingProfileRS, logger, fullReindex));
							break;
						case "Folio":
							ilsRecordProcessors.put(curType, new FolioRecordProcessor(this, curType, dbConn, indexingProfileRS, logger, fullReindex));
							break;
						default:
							logEntry.incErrors("Unknown indexing class " + ilsIndexingClassString);
							break;
					}
					if (ilsRecordProcessors.containsKey(curType)){
						this.treatUnknownAudienceAs = indexingProfileRS.getString("treatUnknownAudienceAs");
						if (this.treatUnknownAudienceAs.equals("Unknown")){
							treatUnknownAudienceAsUnknown = true;
						}else if (this.treatUnknownAudienceAs.equals("Adult")){
							treatUnknownAudienceAsAdult = true;
						}else if (this.treatUnknownAudienceAs.equals("General")){
							treatUnknownAudienceAsGeneral = true;
						}
						this.treatUnknownLanguageAs = indexingProfileRS.getString("treatUnknownLanguageAs");
					}
				}else if (!curType.equals("cloud_library")  && !curType.equals("hoopla") && !curType.equals("overdrive") && !curType.equals("axis360")) {
					getSideLoadSettings.setString(1, curType);
					ResultSet getSideLoadSettingsRS = getSideLoadSettings.executeQuery();
					if (getSideLoadSettingsRS.next()){
						String sideLoadIndexingClassString = getSideLoadSettingsRS.getString("indexingClass");
						if ("SideLoadedEContent".equals(sideLoadIndexingClassString) || "SideLoadedEContentProcessor".equals(sideLoadIndexingClassString)) {
							sideLoadProcessors.put(curType, new SideLoadedEContentProcessor(this, curType, dbConn, getSideLoadSettingsRS, logger, fullReindex));
						} else {
							logEntry.incErrors("Unknown side load processing class " + sideLoadIndexingClassString);
							getSideLoadSettings.close();
							getIndexingProfile.close();
							okToIndex = false;
							return;
						}
					}else{
						logEntry.addNote("Could not find indexing profile or side load settings for type " + curType);
					}
					getSideLoadSettingsRS.close();
				}
				indexingProfileRS.close();
			}
			uniqueIdentifiersRS.close();
			uniqueIdentifiersStmt.close();
			getIndexingProfile.close();
			getSideLoadSettings.close();

		}catch (Exception e){
			logEntry.incErrors("Error loading record processors for ILS records", e);
		}
		overDriveProcessor = new OverDriveProcessor(this, dbConn, logger);

		cloudLibraryProcessor = new CloudLibraryProcessor(this, "cloud_library", dbConn, logger);

		hooplaProcessor = new HooplaProcessor(this, dbConn, logger);

		axis360Processor = new Axis360Processor(this, dbConn, logger);

		//Check to see if we want to display Unknown and Not Coded Literary Forms.  This is done by looking
		//at the indexing profiles since that is the least confusing place to put the settings.
		for (MarcRecordProcessor recordProcessor : ilsRecordProcessors.values()){
			if (recordProcessor instanceof IlsRecordProcessor){
				if (((IlsRecordProcessor) recordProcessor).isHideNotCodedLiteraryForm()){
					this.hideNotCodedLiteraryForm = true;
				}
				if (((IlsRecordProcessor) recordProcessor).isHideUnknownLiteraryForm()){
					this.hideUnknownLiteraryForm = true;
				}
			}
		}

		//Load translation maps
		loadSystemTranslationMaps();

		//Setup prepared statements to load local enrichment
		try {
			//No need to filter for ratings greater than 0 because the user has to rate from 1-5
			getRatingStmt = dbConn.prepareStatement("SELECT AVG(rating) as averageRating, groupedRecordPermanentId from user_work_review where groupedRecordPermanentId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			getNovelistStmt = dbConn.prepareStatement("SELECT * from novelist_data where groupedRecordPermanentId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			getDisplayInfoStmt = dbConn.prepareStatement("SELECT * from grouped_work_display_info where permanent_id = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			getUserReadingHistoryLinkStmt = dbConn.prepareStatement("SELECT DISTINCT userId from user_reading_history_work where groupedWorkPermanentId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			getUserRatingLinkStmt = dbConn.prepareStatement("SELECT DISTINCT userId from user_work_review where groupedRecordPermanentId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			getUserNotInterestedLinkStmt = dbConn.prepareStatement("SELECT DISTINCT userId from user_not_interested where groupedRecordPermanentId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
		} catch (SQLException e) {
			logEntry.incErrors("Could not prepare statements to load local enrichment", e);
		}

		String lexileExportPath = configIni.get("Reindex", "lexileExportPath");
		loadLexileData(lexileExportPath);

		if (clearIndex){
			clearIndex();
		}
	}

	public void close(){
		updateServer = null;
		ilsRecordProcessors.clear();
		sideLoadProcessors.clear();
		overDriveProcessor = null;
		cloudLibraryProcessor = null;
		axis360Processor = null;
		hooplaProcessor = null;
		translationMaps.clear();
		lexileInformation.clear();
		scopes.clear();
		try {
			getRatingStmt.close();
			getNovelistStmt.close();
			getDisplayInfoStmt.close();
			getGroupedWorkPrimaryIdentifiers.close();
		} catch (Exception e) {
			logEntry.incErrors("Error closing prepared statements in grouped work indexer", e);
		}
	}

	boolean isOkToIndex(){
		return okToIndex;
	}

	TreeSet<String> overDriveRecordsSkipped = new TreeSet<>();

	private void loadLexileData(String lexileExportPath) {
		String[] lexileFields = new String[0];
		int curLine = 0;
		try{
			File lexileData = new File(lexileExportPath);
			BufferedReader lexileReader = new BufferedReader(new FileReader(lexileData));
			//Skip over the header
			lexileReader.readLine();
			String lexileLine = lexileReader.readLine();
			curLine++;
			while (lexileLine != null){
				lexileFields = lexileLine.split("\\t");
				LexileTitle titleInfo = new LexileTitle();
				if (lexileFields.length >= 11){
					titleInfo.setTitle(lexileFields[0]);
					titleInfo.setAuthor(lexileFields[1]);
					String isbn = lexileFields[3];
					titleInfo.setLexileCode(lexileFields[4]);
					titleInfo.setLexileScore(lexileFields[5]);
					titleInfo.setSeries(lexileFields[10]);
					if (lexileFields.length >= 12) {
						titleInfo.setAwards(lexileFields[11]);
					}
					if (lexileFields.length >= 13) {
						titleInfo.setDescription(lexileFields[12]);
					}
					lexileInformation.put(isbn, titleInfo);
				}
				lexileLine = lexileReader.readLine();
				curLine++;
			}
			logger.info("Read " + lexileInformation.size() + " lines of lexile data");
		}catch (FileNotFoundException fne){
			//This is normal
			//logEntry.addNote("Error loading lexile data, the file was not found at " + lexileExportPath);
		}catch (Exception e){
			logEntry.incErrors("Error loading lexile data on " + curLine +  Arrays.toString(lexileFields), e);
		}
	}

	private void clearIndex() {
		//Check to see if we should clear the existing index
		logger.info("Clearing existing marc records from index");
		try {
			updateServer.deleteByQuery("recordtype:grouped_work");
			//3-19-2019 Don't commit so the index does not get cleared during run (but will clear at the end).
		} catch (HttpSolrClient.RemoteSolrException rse) {
			logEntry.incErrors("Solr is not running properly, try restarting", rse);
			System.exit(-1);
		} catch (Exception e) {
			logEntry.incErrors("Error deleting from index", e);
		}
	}

	public synchronized void deleteRecord(String permanentId) {
		logger.info("Clearing existing work " + permanentId + " from index");
		try {
			updateServer.deleteById(permanentId);
			//With this commit, we get errors in the log "Previous SolrRequestInfo was not closed!"
			//Allow auto commit functionality to handle this
			totalRecordsHandled++;
			if (totalRecordsHandled % 25 == 0) {
				updateServer.commit(false, false, true);
			}

			//Delete the work from the database?
			//TODO: Should we do this or leave a record if it was linked to lists, reading history, etc?
			//TODO: Add a deleted flag since overdrive will return titles that can no longer be accessed?
			//TODO: If we restore deleting the grouped work we should clean up enrichment, reading history, etc
			//We would avoid continually deleting and re-adding?
			//MDN: leave the grouped work to deal with OverDrive records.  The grouped work will still be active, but
			//it won't be in search results.
			//deleteGroupedWorkStmt.setLong(1, groupedWorkId);
			//deleteGroupedWorkStmt.executeUpdate();

		} catch (Exception e) {
			logEntry.incErrors("Error deleting work from index", e);
		}
	}

	public void finishIndexingFromExtract(BaseLogEntry logEntry){
		try {
			processScheduledWorks(logEntry, true);

			updateServer.commit(false, false, true);
			logEntry.addNote("Shutting down the update server");
			updateServer.blockUntilFinished();
			updateServer.close();
		}catch (Exception e) {
			logEntry.incErrors("Error finishing extract ", e);
		}
	}

	public void commitChanges(){
		try {
			updateServer.commit(false, false, true);
		}catch (Exception e) {
			logEntry.incErrors("Error finishing extract ", e);
		}
	}

	public void processScheduledWorks(BaseLogEntry logEntry, boolean doLogging) {
		//Check to see what records still need to be indexed based on a timed index
		if (doLogging) {
			logEntry.addNote("Checking for additional works that need to be indexed");
		}

		try {
			int numWorksProcessed = 0;
			getScheduledWorksStmt.setLong(1, new Date().getTime() / 1000);
			ResultSet scheduledWorksRS = getScheduledWorksStmt.executeQuery();
			while (scheduledWorksRS.next()) {
				long scheduleId = scheduledWorksRS.getLong("id");
				String workToProcess = scheduledWorksRS.getString("permanent_id");

				//reindex the actual work
				this.processGroupedWork(workToProcess);

				markScheduledWorkProcessedStmt.setLong(1, scheduleId);
				markScheduledWorkProcessedStmt.executeUpdate();
				numWorksProcessed++;
			}
			scheduledWorksRS.close();
			if (numWorksProcessed > 0){
				if (doLogging) {
					logEntry.addNote("Processed " + numWorksProcessed + " works that were scheduled for indexing");
				}
				updateServer.commit(false, false, true);
			}
		}catch (Exception e){
			logEntry.addNote("Error updating scheduled works " + e);
		}
	}

	void finishIndexing(){
		logEntry.addNote("Finishing indexing");
		if (fullReindex) {
			try {
				logEntry.addNote("Calling final commit");
				updateServer.commit(true, true, false);
			} catch (Exception e) {
				logEntry.incErrors("Error calling final commit", e);
			}
		}else {
			try {
				logEntry.addNote("Doing a soft commit to make sure changes are saved");
				updateServer.commit(false, false, true);
				logEntry.addNote("Shutting down the update server");
				updateServer.blockUntilFinished();
				updateServer.close();
			} catch (Exception e) {
				logEntry.incErrors("Error shutting down update server", e);
			}
		}

		updateLastReindexTime();
	}

	private void updateLastReindexTime() {
		//Update the last grouping time in the variables table.  This needs to be the time the index started to catch anything that changes during the index
		try{
			if (lastReindexTimeVariableId != null){
				PreparedStatement updateVariableStmt  = dbConn.prepareStatement("UPDATE variables set value = ? WHERE id = ?");
				updateVariableStmt.setLong(1, indexStartTime);
				updateVariableStmt.setLong(2, lastReindexTimeVariableId);
				updateVariableStmt.executeUpdate();
				updateVariableStmt.close();
			} else{
				PreparedStatement insertVariableStmt = dbConn.prepareStatement("INSERT INTO variables (`name`, `value`) VALUES ('last_reindex_time', ?)");
				insertVariableStmt.setString(1, Long.toString(indexStartTime));
				insertVariableStmt.executeUpdate();
				insertVariableStmt.close();
			}
		}catch (Exception e){
			logEntry.incErrors("Error setting last grouping time", e);
		}
	}

	void processGroupedWorks() {
		long numWorksProcessed = 0L;
		try {
			PreparedStatement getAllGroupedWorks;
			PreparedStatement getNumWorksToIndex;
			PreparedStatement setLastUpdatedTime = dbConn.prepareStatement("UPDATE grouped_work set date_updated = ? where id = ?");
			if (fullReindex){
				getAllGroupedWorks = dbConn.prepareStatement("SELECT * FROM grouped_work", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
				getNumWorksToIndex = dbConn.prepareStatement("SELECT count(id) FROM grouped_work", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			}else{
				//Load all grouped works that have changed since the last time the index ran
				getAllGroupedWorks = dbConn.prepareStatement("SELECT * FROM grouped_work WHERE date_updated IS NULL OR date_updated >= ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
				getAllGroupedWorks.setLong(1, lastReindexTime);
				getNumWorksToIndex = dbConn.prepareStatement("SELECT count(id) FROM grouped_work WHERE date_updated IS NULL OR date_updated >= ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
				getNumWorksToIndex.setLong(1, lastReindexTime);
			}

			//Get the number of works we will be processing
			ResultSet numWorksToIndexRS = getNumWorksToIndex.executeQuery();
			numWorksToIndexRS.next();
			long numWorksToIndex = numWorksToIndexRS.getLong(1);
			numWorksToIndexRS.close();
			logEntry.addNote("Starting to process " + numWorksToIndex + " grouped works");

			ResultSet groupedWorks = getAllGroupedWorks.executeQuery();
			while (groupedWorks.next()){
				long id = groupedWorks.getLong("id");
				String permanentId = groupedWorks.getString("permanent_id");
				String grouping_category = groupedWorks.getString("grouping_category");
				Long lastUpdated = groupedWorks.getLong("date_updated");
				if (groupedWorks.wasNull()){
					lastUpdated = null;
				}
				processGroupedWork(id, permanentId, grouping_category);

				numWorksProcessed++;
				if (logEntry instanceof NightlyIndexLogEntry){
					((NightlyIndexLogEntry) logEntry).incNumWorksProcessed();
				}
				if (!this.clearIndex && (numWorksProcessed % 5000 == 0)){
					//Testing shows that regular commits do seem to improve performance.
					//However, we can't do it too often or we get errors with too many searchers warming.
					//This is happening now with the auto commit settings in solrconfig.xml
					if (numWorksProcessed % 10000 == 0) {
						try {
							logger.info("Doing a regular commit during full indexing");
							updateServer.commit(false, false, true);
						} catch (Exception e) {
							logger.warn("Error committing changes", e);
						}
					}
					//Change to a debug statement to avoid filling up the notes.
					logger.debug("Processed " + numWorksProcessed + " grouped works processed.");
				}
				if (lastUpdated == null){
					setLastUpdatedTime.setLong(1, indexStartTime - 1); //Set just before the index started so we don't index multiple times
					setLastUpdatedTime.setLong(2, id);
					setLastUpdatedTime.executeUpdate();
				}
			}
			groupedWorks.close();
			setLastUpdatedTime.close();

		} catch (SQLException e) {
			logEntry.incErrors("Unexpected SQL error", e);
		}
		logger.info("Finished processing grouped works.  Processed a total of " + numWorksProcessed + " grouped works");
	}

	public synchronized void processGroupedWork(String permanentId) {
		try{
			getGroupedWorkInfoStmt.setString(1, permanentId);
			ResultSet getGroupedWorkInfoRS = getGroupedWorkInfoStmt.executeQuery();
			if (getGroupedWorkInfoRS.next()) {
				long id = getGroupedWorkInfoRS.getLong("id");
				String grouping_category = getGroupedWorkInfoRS.getString("grouping_category");
				processGroupedWork(id, permanentId, grouping_category);
			}
			getGroupedWorkInfoRS.close();
			totalRecordsHandled++;
			if (totalRecordsHandled % 25 == 0) {
				updateServer.commit(false, false, true);
			}
		} catch (Exception e) {
			logEntry.incErrors("Error indexing grouped work " + permanentId + " by id", e);
		}

	}

	synchronized void processGroupedWork(Long id, String permanentId, String grouping_category) throws SQLException {
		//Create a solr record for the grouped work
		GroupedWorkSolr groupedWork = new GroupedWorkSolr(this, logger);
		groupedWork.setId(permanentId);
		groupedWork.setGroupingCategory(grouping_category);

		getGroupedWorkPrimaryIdentifiers.setLong(1, id);
		ResultSet groupedWorkPrimaryIdentifiers = getGroupedWorkPrimaryIdentifiers.executeQuery();
		int numPrimaryIdentifiers = 0;
		while (groupedWorkPrimaryIdentifiers.next()){
			String type = groupedWorkPrimaryIdentifiers.getString("type");
			String identifier = groupedWorkPrimaryIdentifiers.getString("identifier");

			//Make a copy of the grouped work so we can revert if we don't add any records
			GroupedWorkSolr originalWork;
			try {
				originalWork = groupedWork.clone();
			}catch (CloneNotSupportedException cne){
				logEntry.incErrors("Could not clone grouped work", cne);
				return;
			}
			//Figure out how many records we had originally
			int numRecords = groupedWork.getNumRecords();
			logger.debug("Processing " + type + ":" + identifier + " work currently has " + numRecords + " records");

			//This does the bulk of the work building fields for the solr document
			updateGroupedWorkForPrimaryIdentifier(groupedWork, type, identifier);

			//If we didn't add any records to the work (because they are all suppressed) revert to the original
			if (groupedWork.getNumRecords() == numRecords){
				//No change in the number of records, revert to the previous
				logger.debug("Record " + type + ":" + identifier + " did not contribute any records to work " + permanentId + ", reverting to previous state " + groupedWork.getNumRecords());
				groupedWork = originalWork;
			}else{
				logger.debug("Record " + identifier + " added to work " + permanentId);
				numPrimaryIdentifiers++;
			}
		}
		groupedWorkPrimaryIdentifiers.close();

		if (numPrimaryIdentifiers > 0) {
			//Strip out any hoopla records that have the same format as another econtent record with apis
			if (removeRedundantHooplaRecords) {
				groupedWork.removeRedundantHooplaRecords();
			}

			//Load local enrichment for the work
			loadLocalEnrichment(groupedWork);
			//Load links for how users have interacted with the work
			loadUserLinkages(groupedWork);
			//Load lexile data for the work
			loadLexileDataForWork(groupedWork);
			//Load accelerated reader data for the work
			loadAcceleratedDataForWork(groupedWork);
			//Load Novelist data
			loadNovelistInfo(groupedWork);
			//Load Display Info
			loadDisplayInfo(groupedWork);

			//Write the record to Solr.
			try {
				if (this.isStoreRecordDetailsInDatabase()) {
					groupedWork.saveRecordsToDatabase(id);
				}
				SolrInputDocument inputDocument = groupedWork.getSolrDocument(logEntry);
				UpdateResponse response = updateServer.add(inputDocument);
				if (response.getException() != null){
					logEntry.incErrors("Error adding Solr record for " + groupedWork.getId() + " response: " + response);
				}
				//logger.debug("Updated solr \r\n" + inputDocument.toString());
				//Check to see if we need to automatically reindex this record in the future.
				HashSet<Long> autoReindexTimes = groupedWork.getAutoReindexTimes();
				if (autoReindexTimes.size() > 0){
					for (Long autoReindexTime : autoReindexTimes) {
						getScheduledWorkStmt.setString(1, groupedWork.getId());
						getScheduledWorkStmt.setLong(2, autoReindexTime);
						ResultSet getScheduledWorkRS = getScheduledWorkStmt.executeQuery();
						if (!getScheduledWorkRS.next()) {
							try {
								addScheduledWorkStmt.setString(1, groupedWork.getId());
								addScheduledWorkStmt.setLong(2, autoReindexTime);
								addScheduledWorkStmt.executeUpdate();
							} catch (SQLException sqe) {
								logEntry.incErrors("Error adding scheduled reindex time", sqe);
							}
						}
						getScheduledWorkRS.close();
					}
				}

			} catch (Exception e) {
				logEntry.incErrors("Error adding grouped work to solr " + groupedWork.getId(), e);
			}
		}else{
			//Log that this record did not have primary identifiers after
			logger.debug("Grouped work " + permanentId + " did not have any primary identifiers for it, suppressing");
			if (!this.clearIndex){
				this.deleteRecord(permanentId);
			}

		}

	}

	private void loadLexileDataForWork(GroupedWorkSolr groupedWork) {
		for(String isbn : groupedWork.getIsbns()){
			if (lexileInformation.containsKey(isbn)){
				LexileTitle lexileTitle = lexileInformation.get(isbn);
				String lexileCode = lexileTitle.getLexileCode();
				if (lexileCode.length() > 0){
					groupedWork.setLexileCode(this.translateSystemValue("lexile_code", lexileCode, groupedWork.getId()));
				}
				groupedWork.setLexileScore(lexileTitle.getLexileScore());
				groupedWork.addAwards(lexileTitle.getAwards());
				if (lexileTitle.getSeries().length() > 0){
					groupedWork.addSeries(lexileTitle.getSeries());
				}
				break;
			}
		}
	}

	private void loadAcceleratedDataForWork(GroupedWorkSolr groupedWork){
		try {
			for (String isbn : groupedWork.getIsbns()){
				getArBookIdForIsbnStmt.setString(1, isbn);
				ResultSet arBookIdRS = getArBookIdForIsbnStmt.executeQuery();
				if (arBookIdRS.next()){
					String arBookId = arBookIdRS.getString("arBookId");
					getArBookInfoStmt.setString(1, arBookId);
					ResultSet arBookInfoRS = getArBookInfoStmt.executeQuery();
					if (arBookInfoRS.next()){
						String bookLevel = arBookInfoRS.getString("bookLevel");
						if (bookLevel.length() > 0){
							groupedWork.setAcceleratedReaderReadingLevel(bookLevel);
						}
						groupedWork.setAcceleratedReaderPointValue(arBookInfoRS.getString("arPoints"));
						groupedWork.setAcceleratedReaderInterestLevel(arBookInfoRS.getString("interestLevel"));
						break;
					}
					arBookInfoRS.close();
				}
				arBookIdRS.close();
			}
		} catch (SQLException e) {
			logEntry.incErrors("Error loading accelerated reader information", e);
		}
	}

	private void loadLocalEnrichment(GroupedWorkSolr groupedWork) {
		//Load rating
		try{
			getRatingStmt.setString(1, groupedWork.getId());
			ResultSet ratingsRS = getRatingStmt.executeQuery();
			if (ratingsRS.next()){
				float averageRating = ratingsRS.getFloat("averageRating");
				if (!ratingsRS.wasNull()){
					groupedWork.setRating(averageRating);
				}
			}
			ratingsRS.close();
		}catch (Exception e){
			logEntry.incErrors("Unable to load local enrichment", e);
		}
	}

	private void loadUserLinkages(GroupedWorkSolr groupedWork) {
		try {
			loadReadingHistoryLinksForUsers(groupedWork);
			loadRatingLinksForUsers(groupedWork);
			loadNotInterestedLinksForUsers(groupedWork);
		}catch (Exception e){
			logEntry.incErrors("Unable to load user linkages", e);
		}
	}

	private void loadNotInterestedLinksForUsers(GroupedWorkSolr groupedWork) throws SQLException {
		//Add users who are not interested in the title
		getUserNotInterestedLinkStmt.setString(1, groupedWork.getId());
		ResultSet userNotInterestedRS = getUserNotInterestedLinkStmt.executeQuery();
		while (userNotInterestedRS.next()) {
			groupedWork.addNotInterestedLink(userNotInterestedRS.getLong("userId"));
		}
		userNotInterestedRS.close();
	}

	private void loadRatingLinksForUsers(GroupedWorkSolr groupedWork) throws SQLException {
		//Add users who rated the title
		getUserRatingLinkStmt.setString(1, groupedWork.getId());
		ResultSet userRatingRS = getUserRatingLinkStmt.executeQuery();
		while (userRatingRS.next()){
			groupedWork.addRatingLink(userRatingRS.getLong("userId"));
		}
		userRatingRS.close();
	}

	private void loadReadingHistoryLinksForUsers (GroupedWorkSolr groupedWork) throws SQLException {
		//Add users with the work in their reading history
		getUserReadingHistoryLinkStmt.setString(1, groupedWork.getId());
		ResultSet userReadingHistoryRS = getUserReadingHistoryLinkStmt.executeQuery();
		while (userReadingHistoryRS.next()){
			groupedWork.addReadingHistoryLink(userReadingHistoryRS.getLong("userId"));
		}
		userReadingHistoryRS.close();
	}

	private void loadNovelistInfo(GroupedWorkSolr groupedWork){
		try{
			getNovelistStmt.setString(1, groupedWork.getId());
			ResultSet novelistRS = getNovelistStmt.executeQuery();
			if (novelistRS.next()){
				String series = novelistRS.getString("seriesTitle");
				if (!novelistRS.wasNull()){
					//Don't clear since there are valid cases when they are different
					//groupedWork.clearSeriesData();
					//groupedWork.addSeries(series);
					String volume = novelistRS.getString("volume");
					if (novelistRS.wasNull()){
						volume = "";
					}
					groupedWork.addSeriesWithVolume(series, volume);
				}
			}
			novelistRS.close();
		}catch (Exception e){
			logEntry.incErrors("Unable to load novelist data", e);
		}
	}

	private void loadDisplayInfo(GroupedWorkSolr groupedWork) {
		try {
			getDisplayInfoStmt.setString(1, groupedWork.getId());
			ResultSet displayInfoRS = getDisplayInfoStmt.executeQuery();
			if (displayInfoRS.next()) {
				String title = displayInfoRS.getString("title");
				if (title.length() > 0){
					groupedWork.setTitle(title, "", title, StringUtils.makeValueSortable(title), "", "", true);
					groupedWork.clearSubTitle();
				}
				String author = displayInfoRS.getString("author");
				if (author.length() > 0){
					groupedWork.setAuthorDisplay(author);
				}
				String seriesName = displayInfoRS.getString("seriesName");
				String seriesDisplayOrder = displayInfoRS.getString("seriesDisplayOrder");
				if (seriesName.length() > 0) {
					groupedWork.clearSeries();
					groupedWork.addSeries(seriesName);
					if (seriesDisplayOrder.length() > 0) {
						groupedWork.addSeriesWithVolume(seriesName, seriesDisplayOrder);
					}
				}
			}
			displayInfoRS.close();
		}catch (Exception e){
			logEntry.incErrors("Unable to load display info", e);
		}
	}

	private void updateGroupedWorkForPrimaryIdentifier(GroupedWorkSolr groupedWork, String type, String identifier)  {
		groupedWork.addAlternateId(identifier);
		type = type.toLowerCase();
		switch (type) {
			case "overdrive":
				overDriveProcessor.processRecord(groupedWork, identifier, logEntry);
				break;
			case "hoopla":
				hooplaProcessor.processRecord(groupedWork, identifier, logEntry);
				break;
			case "cloud_library":
				cloudLibraryProcessor.processRecord(groupedWork, identifier, logEntry);
				break;
			case "axis360":
				axis360Processor.processRecord(groupedWork, identifier, logEntry);
				break;
			default:
				if (ilsRecordProcessors.containsKey(type)) {
					ilsRecordProcessors.get(type).processRecord(groupedWork, identifier, logEntry);
				}else if (sideLoadProcessors.containsKey(type)){
					sideLoadProcessors.get(type).processRecord(groupedWork, identifier, logEntry);
				}else{
					//This happens if a side load processor is deleted and all the related record don't get cleaned up.
					logger.debug("Could not find a record processor for type " + type);
				}
				break;
		}
	}

	/**
	 * System translation maps are used for things that are not customizable (or that shouldn't be customized)
	 * by library.  For example, translations of language codes, or things where MARC standards define the values.
	 *
	 * We can also load translation maps that are specific to an indexing profile.  That is done within
	 * the record processor itself.
	 */
	private void loadSystemTranslationMaps(){
		//Load all translationMaps, first from default, then from the site specific configuration
		File defaultTranslationMapDirectory = new File("../../sites/default/translation_maps");
		File[] defaultTranslationMapFiles = defaultTranslationMapDirectory.listFiles((dir, name) -> name.endsWith("properties"));

		File serverTranslationMapDirectory = new File("../../sites/" + serverName + "/translation_maps");
		File[] serverTranslationMapFiles = serverTranslationMapDirectory.listFiles((dir, name) -> name.endsWith("properties"));

		if (defaultTranslationMapFiles != null) {
			for (File curFile : defaultTranslationMapFiles) {
				String mapName = curFile.getName().replace(".properties", "");
				mapName = mapName.replace("_map", "");
				translationMaps.put(mapName, loadSystemTranslationMap(curFile));
			}
			if (serverTranslationMapFiles != null) {
				for (File curFile : serverTranslationMapFiles) {
					String mapName = curFile.getName().replace(".properties", "");
					mapName = mapName.replace("_map", "");
					translationMaps.put(mapName, loadSystemTranslationMap(curFile));
				}
			}
		}
	}

	private HashMap<String, String> loadSystemTranslationMap(File translationMapFile) {
		Properties props = new Properties();
		try {
			props.load(new FileReader(translationMapFile));
		} catch (IOException e) {
			logEntry.incErrors("Could not read translation map, " + translationMapFile.getAbsolutePath(), e);
		}
		HashMap<String, String> translationMap = new HashMap<>();
		for (Object keyObj : props.keySet()){
			String key = (String)keyObj;
			translationMap.put(key.toLowerCase(), props.getProperty(key));
		}
		return translationMap;
	}

	boolean hasSystemTranslation(String mapName, String value) {
		return translationMaps.containsKey(mapName) && translationMaps.get(mapName).containsKey(value);
	}
	private final HashSet<String> unableToTranslateWarnings = new HashSet<>();
	private final HashSet<String> missingTranslationMaps = new HashSet<>();
	String translateSystemValue(String mapName, String value, String identifier){
		if (value == null){
			return null;
		}
		HashMap<String, String> translationMap = translationMaps.get(mapName);
		String translatedValue;
		if (translationMap == null){
			if (!missingTranslationMaps.contains(mapName)) {
				missingTranslationMaps.add(mapName);
				logEntry.incErrors("Unable to find system translation map for " + mapName);
			}
			translatedValue = value;
		}else{
			String lowerCaseValue = value.toLowerCase();
			if (translationMap.containsKey(lowerCaseValue)){
				translatedValue = translationMap.get(lowerCaseValue);
			}else{
				if (translationMap.containsKey("*")){
					translatedValue = translationMap.get("*");
				}else{
					String concatenatedValue = mapName + ":" + value;
					if (!unableToTranslateWarnings.contains(concatenatedValue)){
						if (fullReindex) {
							logger.warn("Could not translate '" + concatenatedValue + "' sample record " + identifier);
						}
						unableToTranslateWarnings.add(concatenatedValue);
					}
					translatedValue = value;
				}
			}
		}
		if (translatedValue != null){
			translatedValue = translatedValue.trim();
			if (translatedValue.length() == 0){
				translatedValue = null;
			}
		}
		return translatedValue;
	}

	LinkedHashSet<String> translateSystemCollection(String mapName, Set<String> values, String identifier) {
		LinkedHashSet<String> translatedCollection = new LinkedHashSet<>();
		for (String value : values){
				String translatedValue = translateSystemValue(mapName, value, identifier);
				if (translatedValue != null) {
						translatedCollection.add(translatedValue);
					}
			}
		return  translatedCollection;
	}

	TreeSet<Scope> getScopes() {
		return this.scopes;
	}

	public HashMap<String, SavedRecordInfo> getExistingRecordsForGroupedWork(long groupedWorkId)
	{
		HashMap<String, SavedRecordInfo> existingRecords = new HashMap<>();
		try {
			getExistingRecordsForWorkStmt.setLong(1, groupedWorkId);
			ResultSet getExistingRecordsForWorkRS = getExistingRecordsForWorkStmt.executeQuery();
			while (getExistingRecordsForWorkRS.next()){
				String key = getExistingRecordsForWorkRS.getString("sourceId") + ":" + getExistingRecordsForWorkRS.getString("recordIdentifier");
				existingRecords.put(key, new SavedRecordInfo(getExistingRecordsForWorkRS));
			}
		} catch (SQLException e) {
			logEntry.incErrors("Error loading existing records for grouped works", e);
		}

		return existingRecords;
	}

	public long saveGroupedWorkRecord(long groupedWorkId, RecordInfo recordInfo, SavedRecordInfo existingRecord) {
		long recordId = -1;
		try {
			if (existingRecord == null) {
				addRecordForWorkStmt.setLong(1, groupedWorkId);
				long sourceId = getSourceId(recordInfo.getSource(), recordInfo.getSubSource());
				addRecordForWorkStmt.setLong(2, sourceId);
				addRecordForWorkStmt.setString(3, recordInfo.getRecordIdentifier());
				addRecordForWorkStmt.setLong(4, getEditionId(recordInfo.getEdition()));
				addRecordForWorkStmt.setLong(5, getPublisherId(recordInfo.getPublisher()));
				addRecordForWorkStmt.setLong(6, getPublicationDateId(recordInfo.getPublicationDate()));
				addRecordForWorkStmt.setLong(7, getPhysicalDescriptionId(recordInfo.getPhysicalDescription()));
				addRecordForWorkStmt.setLong(8, getFormatId(recordInfo.getPrimaryFormat()));
				addRecordForWorkStmt.setLong(9, getFormatCategoryId(recordInfo.getPrimaryFormatCategory()));
				addRecordForWorkStmt.setLong(10, getLanguageId(recordInfo.getPrimaryLanguage()));
				addRecordForWorkStmt.executeUpdate();
				ResultSet addRecordForWorkRS = addRecordForWorkStmt.getGeneratedKeys();
				if (addRecordForWorkRS.next()) {
					recordId = addRecordForWorkRS.getLong(1);
				} else {
					getIdForRecordStmt.setLong(1, sourceId);
					getIdForRecordStmt.setString(2, recordInfo.getRecordIdentifier());
					ResultSet getIdForRecordRS = getIdForRecordStmt.executeQuery();
					if (getIdForRecordRS.next()) {
						recordId = getIdForRecordRS.getLong("id");
					}
				}
			}else{
				recordId = existingRecord.id;
				//Check to see if we have any changes
				boolean hasChanges = false;
				long editionId = getEditionId(recordInfo.getEdition());
				long publisherId = getPublisherId(recordInfo.getPublisher());
				long publicationDateId = getPublicationDateId(recordInfo.getPublicationDate());
				long physicalDescriptionId = getPhysicalDescriptionId(recordInfo.getPhysicalDescription());
				long formatId = getFormatId(recordInfo.getPrimaryFormat());
				long formatCategoryId = getFormatCategoryId(recordInfo.getPrimaryFormatCategory());
				long languageId = getLanguageId(recordInfo.getPrimaryLanguage());
				if (groupedWorkId != existingRecord.groupedWorkId) { hasChanges = true; }
				if (editionId != existingRecord.editionId) { hasChanges = true; }
				if (publisherId != existingRecord.publisherId) { hasChanges = true; }
				if (publicationDateId != existingRecord.publicationDateId) { hasChanges = true; }
				if (physicalDescriptionId != existingRecord.physicalDescriptionId) { hasChanges = true; }
				if (formatId != existingRecord.formatId) { hasChanges = true; }
				if (formatCategoryId != existingRecord.formatCategoryId) { hasChanges = true; }
				if (languageId != existingRecord.languageId) { hasChanges = true; }
				if (hasChanges){
					updateRecordForWorkStmt.setLong(1, groupedWorkId);
					updateRecordForWorkStmt.setLong(2, editionId);
					updateRecordForWorkStmt.setLong(3, publisherId);
					updateRecordForWorkStmt.setLong(4, publicationDateId);
					updateRecordForWorkStmt.setLong(5, physicalDescriptionId);
					updateRecordForWorkStmt.setLong(6, formatId);
					updateRecordForWorkStmt.setLong(7, formatCategoryId);
					updateRecordForWorkStmt.setLong(8, languageId);
					updateRecordForWorkStmt.setLong(9, existingRecord.id);
					updateRecordForWorkStmt.executeUpdate();
				}
			}
		} catch (SQLException e) {
			logEntry.incErrors("Error saving grouped work record", e);
		}

		return recordId;
	}

	private final HashMap<String, Long> sourceIds = new HashMap<>();
	long getSourceId(String source, String subSource) {
		String key = source + ":" + (subSource == null ? "" : subSource);
		Long sourceId = sourceIds.get(key);
		if (sourceId == null){
			try {
				ResultSet getRecordSourceRS;
				if (subSource == null) {
					getRecordSourceWithNoSubSourceStmt.setString(1, source);
					getRecordSourceRS = getRecordSourceWithNoSubSourceStmt.executeQuery();
				}else{
					getRecordSourceStmt.setString(1, source);
					getRecordSourceStmt.setString(2, subSource);
					getRecordSourceRS = getRecordSourceStmt.executeQuery();
				}
				if (getRecordSourceRS.next()){
					sourceId = getRecordSourceRS.getLong("id");
				}else {
					addRecordSourceStmt.setString(1, source);
					addRecordSourceStmt.setString(2, subSource);
					addRecordSourceStmt.executeUpdate();
					ResultSet addRecordSourceRS = addRecordSourceStmt.getGeneratedKeys();
					if (addRecordSourceRS.next()) {
						sourceId = addRecordSourceRS.getLong(1);
					} else {
						logEntry.incErrors("Could not add source");
						sourceId = -1L;
					}
				}
			} catch (SQLException e) {
				logEntry.incErrors("Error getting source id", e);
				sourceId = -1L;
			}
			sourceIds.put(key, sourceId);
		}
		return sourceId;
	}

	private final HashMap<String, Long> formatCategoryIds = new HashMap<>();
	private long getFormatCategoryId(String formatCategory) {
		if (formatCategory == null){
			return -1;
		}
		Long id = formatCategoryIds.get(formatCategory);
		if (id == null){
			try {
				getFormatCategoryStmt.setString(1, formatCategory);
				ResultSet getFormatCategoryRS = getFormatCategoryStmt.executeQuery();
				if (getFormatCategoryRS.next()){
					id = getFormatCategoryRS.getLong("id");
				}else {
					addFormatCategoryStmt.setString(1, formatCategory);
					addFormatCategoryStmt.executeUpdate();
					ResultSet addFormatCategoryRS = addFormatCategoryStmt.getGeneratedKeys();
					if (addFormatCategoryRS.next()) {
						id = addFormatCategoryRS.getLong(1);
					} else {
						logEntry.incErrors("Could not add format category");
						id = -1L;
					}
				}
			} catch (SQLException e) {
				logEntry.incErrors("Error getting format category id", e);
				id = -1L;
			}
			formatCategoryIds.put(formatCategory, id);
		}
		return id;
	}

	private final HashMap<String, Long> formatIds = new HashMap<>();
	private long getFormatId(String format) {
		if (format == null){
			return -1;
		}
		Long id = formatIds.get(format);
		if (id == null){
			try {
				getFormatStmt.setString(1, format);
				ResultSet getFormatRS = getFormatStmt.executeQuery();
				if (getFormatRS.next()){
					id = getFormatRS.getLong("id");
				}else {
					addFormatStmt.setString(1, format);
					addFormatStmt.executeUpdate();
					ResultSet addFormatRS = addFormatStmt.getGeneratedKeys();
					if (addFormatRS.next()) {
						id = addFormatRS.getLong(1);
					} else {
						logEntry.incErrors("Could not add format");
						id = -1L;
					}
				}
			} catch (SQLException e) {
				logEntry.incErrors("Error getting format id", e);
				id = -1L;
			}
			formatIds.put(format, id);
		}
		return id;
	}

	private final HashMap<String, Long> languageIds = new HashMap<>();
	private long getLanguageId(String language) {
		if (language == null){
			return -1;
		}
		Long id = languageIds.get(language);
		if (id == null){
			try {
				getLanguageStmt.setString(1, language);
				ResultSet getLanguageRS = getLanguageStmt.executeQuery();
				if (getLanguageRS.next()){
					id = getLanguageRS.getLong("id");
				}else {
					addLanguageStmt.setString(1, language);
					addLanguageStmt.executeUpdate();
					ResultSet addLanguageRS = addLanguageStmt.getGeneratedKeys();
					if (addLanguageRS.next()) {
						id = addLanguageRS.getLong(1);
					} else {
						logEntry.incErrors("Could not add language");
						id = -1L;
					}
				}
			} catch (SQLException e) {
				logEntry.incErrors("Error getting language id", e);
				id = -1L;
			}
			languageIds.put(language, id);
		}
		return id;
	}

	private final MaxSizeHashMap<String, Long> editionIds = new MaxSizeHashMap<>(1000);
	private long getEditionId(String edition) {
		if (edition == null){
			return -1;
		}
		if (edition.length() > 255) {
			edition = edition.substring(0, 255);
		}
		Long id = editionIds.get(edition);
		if (id == null){
			try {
				getEditionStmt.setString(1, edition);
				ResultSet getEditionRS = getEditionStmt.executeQuery();
				if (getEditionRS.next()){
					id = getEditionRS.getLong("id");
				}else {
					addEditionStmt.setString(1, edition);
					addEditionStmt.executeUpdate();
					ResultSet addEditionRS = addEditionStmt.getGeneratedKeys();
					if (addEditionRS.next()) {
						id = addEditionRS.getLong(1);
					} else {
						logEntry.incErrors("Could not add edition");
						id = -1L;
					}
				}
			} catch (SQLException e) {
				logEntry.incErrors("Error getting edition id for edition (" + edition.length() + "): " + edition, e);
				id = -1L;
			}
			editionIds.put(edition, id);
		}
		return id;
	}

	private final MaxSizeHashMap<String, Long> publisherIds = new MaxSizeHashMap<>(1000);
	private long getPublisherId(String publisher) {
		if (publisher == null){
			return -1;
		}
		if (publisher.length() > 500) {
			logEntry.incErrors("Publisher was more than 500 characters (" + publisher.length() + ") " + publisher);
			publisher = publisher.substring(0, 500);
		}
		Long id = publisherIds.get(publisher);
		if (id == null){
			try {
				getPublisherStmt.setString(1, publisher);
				ResultSet getPublisherRS = getPublisherStmt.executeQuery();
				if (getPublisherRS.next()){
					id = getPublisherRS.getLong("id");
				}else {
					addPublisherStmt.setString(1, publisher);
					addPublisherStmt.executeUpdate();
					ResultSet addPublisherRS = addPublisherStmt.getGeneratedKeys();
					if (addPublisherRS.next()) {
						id = addPublisherRS.getLong(1);
					} else {
						logEntry.incErrors("Could not add publisher");
						id = -1L;
					}
				}
			} catch (SQLException e) {
				logEntry.incErrors("Error getting publisher id (" + publisher.length() + ") " + publisher, e);
				id = -1L;
			}
			publisherIds.put(publisher, id);
		}
		return id;
	}

	private final MaxSizeHashMap<String, Long> publicationDateIds = new MaxSizeHashMap<>(1000);
	private long getPublicationDateId(String publicationDate) {
		if (publicationDate == null){
			return -1;
		}
		Long id = publicationDateIds.get(publicationDate);
		if (id == null){
			try {
				getPublicationDateStmt.setString(1, publicationDate);
				ResultSet getPublicationDateRS = getPublicationDateStmt.executeQuery();
				if (getPublicationDateRS.next()){
					id = getPublicationDateRS.getLong("id");
				}else {
					addPublicationDateStmt.setString(1, publicationDate);
					addPublicationDateStmt.executeUpdate();
					ResultSet addPublicationDateRS = addPublicationDateStmt.getGeneratedKeys();
					if (addPublicationDateRS.next()) {
						id = addPublicationDateRS.getLong(1);
					} else {
						logEntry.incErrors("Could not add publicationDate");
						id = -1L;
					}
				}
			} catch (SQLException e) {
				logEntry.incErrors("Error getting publicationDate id", e);
				id = -1L;
			}
			publicationDateIds.put(publicationDate, id);
		}
		return id;
	}

	private final MaxSizeHashMap<String, Long> physicalDescriptionIds = new MaxSizeHashMap<>(1000);
	private long getPhysicalDescriptionId(String physicalDescription) {
		if (physicalDescription == null){
			return -1;
		}
		if (physicalDescription.length() > 1000) {
			physicalDescription = physicalDescription.substring(0, 1000);
		}
		Long id = physicalDescriptionIds.get(physicalDescription);
		if (id == null){
			try {
				getPhysicalDescriptionStmt.setString(1, physicalDescription);
				ResultSet getPhysicalDescriptionRS = getPhysicalDescriptionStmt.executeQuery();
				if (getPhysicalDescriptionRS.next()){
					id = getPhysicalDescriptionRS.getLong("id");
				}else {
					addPhysicalDescriptionStmt.setString(1, physicalDescription);
					addPhysicalDescriptionStmt.executeUpdate();
					ResultSet addPhysicalDescriptionRS = addPhysicalDescriptionStmt.getGeneratedKeys();
					if (addPhysicalDescriptionRS.next()) {
						id = addPhysicalDescriptionRS.getLong(1);
					} else {
						logEntry.incErrors("Could not add physicalDescription");
						id = -1L;
					}
				}
			} catch (SQLException e) {
				logEntry.incErrors("Error getting physicalDescription id (" + physicalDescription.length() + "):" + physicalDescription, e);
				id = -1L;
			}
			physicalDescriptionIds.put(physicalDescription, id);
		}
		return id;
	}

	private final HashMap<String, Long> eContentSourceIds = new HashMap<>();
	private long getEContentSourceId(String eContentSource) {
		if (eContentSource == null){
			return -1;
		}
		Long id = eContentSourceIds.get(eContentSource);
		if (id == null){
			try {
				getEContentSourceStmt.setString(1, eContentSource);
				ResultSet getEContentSourceRS = getEContentSourceStmt.executeQuery();
				if (getEContentSourceRS.next()){
					id = getEContentSourceRS.getLong("id");
				}else {
					addEContentSourceStmt.setString(1, eContentSource);
					addEContentSourceStmt.executeUpdate();
					ResultSet addEContentSourceRS = addEContentSourceStmt.getGeneratedKeys();
					if (addEContentSourceRS.next()) {
						id = addEContentSourceRS.getLong(1);
					} else {
						logEntry.incErrors("Could not add eContentSource");
						id = -1L;
					}
				}
			} catch (SQLException e) {
				logEntry.incErrors("Error getting eContentSource id", e);
				id = -1L;
			}
			eContentSourceIds.put(eContentSource, id);
		}
		return id;
	}

	private final HashMap<String, Long> shelfLocationIds = new HashMap<>();
	private long getShelfLocationId(String shelfLocation) {
		if (shelfLocation == null){
			return -1;
		}
		Long id = shelfLocationIds.get(shelfLocation);
		if (id == null){
			try {
				getShelfLocationStmt.setString(1, shelfLocation);
				ResultSet getShelfLocationRS = getShelfLocationStmt.executeQuery();
				if (getShelfLocationRS.next()){
					id = getShelfLocationRS.getLong("id");
				}else {
					addShelfLocationStmt.setString(1, shelfLocation);
					addShelfLocationStmt.executeUpdate();
					ResultSet addShelfLocationRS = addShelfLocationStmt.getGeneratedKeys();
					if (addShelfLocationRS.next()) {
						id = addShelfLocationRS.getLong(1);
					} else {
						logEntry.incErrors("Could not add shelfLocation");
						id = -1L;
					}
				}
			} catch (SQLException e) {
				logEntry.incErrors("Error getting shelfLocation id", e);
				id = -1L;
			}
			shelfLocationIds.put(shelfLocation, id);
		}
		return id;
	}

	private final MaxSizeHashMap<String, Long> callNumberIds = new MaxSizeHashMap<>(1000);
	private long getCallNumberId(String callNumber) {
		if (callNumber == null){
			return -1;
		}
		Long id = callNumberIds.get(callNumber);
		if (id == null){
			try {
				getCallNumberStmt.setString(1, callNumber);
				ResultSet getCallNumberRS = getCallNumberStmt.executeQuery();
				if (getCallNumberRS.next()){
					id = getCallNumberRS.getLong("id");
				}else {
					addCallNumberStmt.setString(1, callNumber);
					addCallNumberStmt.executeUpdate();
					ResultSet addCallNumberRS = addCallNumberStmt.getGeneratedKeys();
					if (addCallNumberRS.next()) {
						id = addCallNumberRS.getLong(1);
					} else {
						logEntry.incErrors("Could not add callNumber");
						id = -1L;
					}
				}
			} catch (SQLException e) {
				logEntry.incErrors("Error getting callNumber id", e);
				id = -1L;
			}
			callNumberIds.put(callNumber, id);
		}
		return id;
	}

	private final HashMap<String, Long> statusIds = new HashMap<>();
	private long getStatusId(String status) {
		if (status == null){
			return -1;
		}
		Long id = statusIds.get(status);
		if (id == null){
			try {
				getStatusStmt.setString(1, status);
				ResultSet getStatusRS = getStatusStmt.executeQuery();
				if (getStatusRS.next()){
					id = getStatusRS.getLong("id");
				}else {
					addStatusStmt.setString(1, status);
					addStatusStmt.executeUpdate();
					ResultSet addStatusRS = addStatusStmt.getGeneratedKeys();
					if (addStatusRS.next()) {
						id = addStatusRS.getLong(1);
					} else {
						logEntry.incErrors("Could not add status");
						id = -1L;
					}
				}
			} catch (SQLException e) {
				logEntry.incErrors("Error getting status id", e);
				id = -1L;
			}
			statusIds.put(status, id);
		}
		return id;
	}

	private final HashMap<String, Long> locationCodeIds = new HashMap<>();
	private long getLocationCodeId(String locationCode) {
		if (locationCode == null){
			return -1;
		}
		Long id = locationCodeIds.get(locationCode);
		if (id == null){
			try {
				getLocationCodeStmt.setString(1, locationCode);
				ResultSet getLocationCodeRS = getLocationCodeStmt.executeQuery();
				if (getLocationCodeRS.next()){
					id = getLocationCodeRS.getLong("id");
				}else {
					addLocationCodeStmt.setString(1, locationCode);
					addLocationCodeStmt.executeUpdate();
					ResultSet addLocationCodeRS = addLocationCodeStmt.getGeneratedKeys();
					if (addLocationCodeRS.next()) {
						id = addLocationCodeRS.getLong(1);
					} else {
						logEntry.incErrors("Could not add locationCode");
						id = -1L;
					}
				}
			} catch (SQLException e) {
				logEntry.incErrors("Error getting locationCode id", e);
				id = -1L;
			}
			locationCodeIds.put(locationCode, id);
		}
		return id;
	}

	private final HashMap<String, Long> subLocationCodeIds = new HashMap<>();
	private long getSubLocationCodeId(String subLocationCode) {
		if (subLocationCode == null){
			return -1;
		}
		Long id = subLocationCodeIds.get(subLocationCode);
		if (id == null){
			try {
				getSubLocationCodeStmt.setString(1, subLocationCode);
				ResultSet getSubLocationCodeRS = getSubLocationCodeStmt.executeQuery();
				if (getSubLocationCodeRS.next()){
					id = getSubLocationCodeRS.getLong("id");
				}else {
					addSubLocationCodeStmt.setString(1, subLocationCode);
					addSubLocationCodeStmt.executeUpdate();
					ResultSet addSubLocationCodeRS = addSubLocationCodeStmt.getGeneratedKeys();
					if (addSubLocationCodeRS.next()) {
						id = addSubLocationCodeRS.getLong(1);
					} else {
						logEntry.incErrors("Could not add subLocationCode");
						id = -1L;
					}
				}
			} catch (SQLException e) {
				logEntry.incErrors("Error getting subLocationCode id", e);
				id = -1L;
			}
			subLocationCodeIds.put(subLocationCode, id);
		}
		return id;
	}

	void removeGroupedWorkRecord(long recordId) {
		try {
			removeRecordForWorkStmt.setLong(1, recordId);
			removeRecordForWorkStmt.executeUpdate();
		}catch (SQLException e){
			logEntry.incErrors("Could not delete record", e);
		}
	}

	HashMap<VariationInfo, Long> getExistingVariationsForGroupedWork(long groupedWorkId) {
		HashMap<VariationInfo, Long> existingVariations = new HashMap<>();
		try {
			getExistingVariationsForWorkStmt.setLong(1, groupedWorkId);
			ResultSet getExistingVariationsForWorkRS = getExistingVariationsForWorkStmt.executeQuery();
			while (getExistingVariationsForWorkRS.next()){
				VariationInfo variation = new VariationInfo();
				variation.id = getExistingVariationsForWorkRS.getLong("id");
				variation.primaryLanguageId = getExistingVariationsForWorkRS.getLong("primaryLanguageId");
				variation.eContentSourceId = getExistingVariationsForWorkRS.getLong("eContentSourceId");
				variation.formatId = getExistingVariationsForWorkRS.getLong("formatId");
				variation.formatCategoryId = getExistingVariationsForWorkRS.getLong("formatCategoryId");
				existingVariations.put(variation, variation.id);
			}
		}catch (SQLException e){
			logEntry.incErrors("Could not get existing variations for grouped work", e);
		}
		return existingVariations;
	}

	long saveGroupedWorkVariation(HashMap<VariationInfo, Long> existingVariations, long groupedWorkId, RecordInfo recordInfo, ItemInfo itemInfo) {
		VariationInfo curVariationInfo = new VariationInfo();
		curVariationInfo.primaryLanguageId = getLanguageId(recordInfo.getPrimaryLanguage());
		curVariationInfo.eContentSourceId = this.getEContentSourceId(itemInfo.geteContentSource());
		String format = itemInfo.getFormat() == null ? recordInfo.getPrimaryFormat() : itemInfo.getFormat();
		curVariationInfo.formatId = this.getFormatId(format);
		String formatCategory = itemInfo.getFormatCategory() == null ? recordInfo.getPrimaryFormatCategory() : itemInfo.getFormatCategory();
		curVariationInfo.formatCategoryId = this.getFormatCategoryId(formatCategory);
		Long existingId = existingVariations.get(curVariationInfo);
		if (existingId != null){
			return existingId;
		}else{
			//Add it to the database and return the id
			try {
				addVariationForWorkStmt.setLong(1, groupedWorkId);
				addVariationForWorkStmt.setLong(2, curVariationInfo.primaryLanguageId);
				addVariationForWorkStmt.setLong(3, curVariationInfo.eContentSourceId);
				addVariationForWorkStmt.setLong(4, curVariationInfo.formatId);
				addVariationForWorkStmt.setLong(5, curVariationInfo.formatCategoryId);
				addVariationForWorkStmt.executeUpdate();
				ResultSet addVariationForWorkRS = addVariationForWorkStmt.getGeneratedKeys();
				if (addVariationForWorkRS.next()){
					curVariationInfo.id = addVariationForWorkRS.getLong(1);
					existingVariations.put(curVariationInfo, curVariationInfo.id);
					return curVariationInfo.id;
				}
			} catch (SQLException e) {
				logEntry.incErrors("Error saving grouped work variation", e);
			}
		}
		return -1L;
	}

	void removeGroupedWorkVariation(Long existingVariationId) {
		try {
			removeVariationStmt.setLong(1, existingVariationId);
			removeVariationStmt.executeUpdate();
		}catch (SQLException e) {
			logEntry.incErrors("Error removing grouped work variation", e);
		}

	}

	HashMap<String, SavedItemInfo> getExistingItemsForRecord(long recordId) {
		HashMap<String, SavedItemInfo> existingItems = new HashMap<>();
		try{
			getExistingItemsForRecordStmt.setLong(1, recordId);
			ResultSet getExistingItemsForRecordRS = getExistingItemsForRecordStmt.executeQuery();
			while (getExistingItemsForRecordRS.next()){
				existingItems.put(getExistingItemsForRecordRS.getString("itemId"), new SavedItemInfo(getExistingItemsForRecordRS));
			}
		}catch (SQLException e){
			logEntry.incErrors("Error loading existing items for record", e);
		}
		return existingItems;
	}

	void removeRecordItem(Long itemId) {
		try {
			removeItemStmt.setLong(1, itemId);
			removeItemStmt.executeUpdate();
		}catch (SQLException e) {
			logEntry.incErrors("Error removing item for record", e);
		}
	}

	long saveItemForRecord(long recordId, long variationId, ItemInfo itemInfo, HashMap<String, SavedItemInfo> existingItems) {
		SavedItemInfo savedItem = existingItems.get(itemInfo.getItemIdentifier());
		long itemId = -1;
		if (savedItem != null){
			itemId = savedItem.id;
		}
		try {
			long shelfLocationId = this.getShelfLocationId(itemInfo.getDetailedLocation());
			long callNumberId = this.getCallNumberId(itemInfo.getCallNumber());
			long sortableCallNumberId;
			if (StringUtils.compareStrings(itemInfo.getCallNumber(), itemInfo.getSortableCallNumber())){
				sortableCallNumberId = callNumberId;
			}else{
				sortableCallNumberId = this.getCallNumberId(itemInfo.getSortableCallNumber());
			}
			long statusId = this.getStatusId(itemInfo.getDetailedStatus());
			long locationCodeId = this.getLocationCodeId(itemInfo.getLocationCode());
			long subLocationId = this.getSubLocationCodeId(itemInfo.getSubLocationCode());
			long groupedStatusId = this.getStatusId(itemInfo.getGroupedStatus());
			if (savedItem == null) {
				addItemForRecordStmt.setLong(1, recordId);
				addItemForRecordStmt.setLong(2, variationId);
				addItemForRecordStmt.setString(3, itemInfo.getItemIdentifier());
				addItemForRecordStmt.setLong(4, shelfLocationId);
				addItemForRecordStmt.setLong(5, callNumberId);
				addItemForRecordStmt.setLong(6, sortableCallNumberId);
				addItemForRecordStmt.setLong(7, itemInfo.getNumCopies());
				addItemForRecordStmt.setBoolean(8, itemInfo.isOrderItem());
				addItemForRecordStmt.setLong(9, statusId);
				if (itemInfo.getDateAdded() == null) {
					addItemForRecordStmt.setNull(10, Types.BIGINT);
				} else {
					addItemForRecordStmt.setLong(10, itemInfo.getDateAdded().getTime() / 1000);
				}
				addItemForRecordStmt.setLong(11, locationCodeId);
				addItemForRecordStmt.setLong(12, subLocationId);
				if (itemInfo.getLastCheckinDate() == null) {
					addItemForRecordStmt.setNull(13, Types.INTEGER);
				} else {
					addItemForRecordStmt.setLong(13, itemInfo.getLastCheckinDate().getTime() / 1000);
				}
				addItemForRecordStmt.setLong(14, groupedStatusId);
				addItemForRecordStmt.setBoolean(15, itemInfo.isAvailable());
				addItemForRecordStmt.setBoolean(16, itemInfo.isHoldable());
				addItemForRecordStmt.setBoolean(17, itemInfo.isInLibraryUseOnly());
				addItemForRecordStmt.setString(18, itemInfo.getLocationOwnedScopes());
				addItemForRecordStmt.setString(19, itemInfo.getLibraryOwnedScopes());
				addItemForRecordStmt.setString(20, itemInfo.getRecordsIncludedScopes());
				addItemForRecordStmt.executeUpdate();
				ResultSet addItemForWorkRS = addItemForRecordStmt.getGeneratedKeys();
				if (addItemForWorkRS.next()) {
					itemId = addItemForWorkRS.getLong(1);
				}
				SavedItemInfo savedItemInfo = new SavedItemInfo(itemId, recordId, variationId, itemInfo.getItemIdentifier(), shelfLocationId, callNumberId, sortableCallNumberId, itemInfo.getNumCopies(),
						itemInfo.isOrderItem(), statusId, itemInfo.getDateAdded(), locationCodeId, subLocationId, itemInfo.getLastCheckinDate(), groupedStatusId, itemInfo.isAvailable(),
						itemInfo.isHoldable(), itemInfo.isInLibraryUseOnly(), itemInfo.getLocationOwnedScopes(), itemInfo.getLibraryOwnedScopes(), itemInfo.getRecordsIncludedScopes());

				existingItems.put(itemInfo.getItemIdentifier(), savedItemInfo);
			}else if (savedItem.hasChanged(recordId, variationId, itemInfo.getItemIdentifier(), shelfLocationId, callNumberId, sortableCallNumberId, itemInfo.getNumCopies(),
					itemInfo.isOrderItem(), statusId, itemInfo.getDateAdded(), locationCodeId, subLocationId, itemInfo.getLastCheckinDate(), groupedStatusId, itemInfo.isAvailable(),
					itemInfo.isHoldable(), itemInfo.isInLibraryUseOnly(), itemInfo.getLocationOwnedScopes(), itemInfo.getLibraryOwnedScopes(), itemInfo.getRecordsIncludedScopes())){
				updateItemForRecordStmt.setLong(1, variationId);
				updateItemForRecordStmt.setLong(2, shelfLocationId);
				updateItemForRecordStmt.setLong(3, callNumberId);
				updateItemForRecordStmt.setLong(4, sortableCallNumberId);
				updateItemForRecordStmt.setLong(5, itemInfo.getNumCopies());
				updateItemForRecordStmt.setBoolean(6, itemInfo.isOrderItem());
				updateItemForRecordStmt.setLong(7, statusId);
				if (itemInfo.getDateAdded() == null) {
					updateItemForRecordStmt.setNull(8, Types.BIGINT);
				} else {
					updateItemForRecordStmt.setLong(8, itemInfo.getDateAdded().getTime() / 1000);
				}
				updateItemForRecordStmt.setLong(9, locationCodeId);
				updateItemForRecordStmt.setLong(10, subLocationId);
				if (itemInfo.getLastCheckinDate() == null) {
					updateItemForRecordStmt.setNull(11, Types.INTEGER);
				} else {
					updateItemForRecordStmt.setLong(11, itemInfo.getLastCheckinDate().getTime() / 1000);
				}
				updateItemForRecordStmt.setLong(12, groupedStatusId);
				updateItemForRecordStmt.setBoolean(13, itemInfo.isAvailable());
				updateItemForRecordStmt.setBoolean(14, itemInfo.isHoldable());
				updateItemForRecordStmt.setBoolean(15, itemInfo.isInLibraryUseOnly());
				updateItemForRecordStmt.setString(16, itemInfo.getLocationOwnedScopes());
				updateItemForRecordStmt.setString(17, itemInfo.getLibraryOwnedScopes());
				updateItemForRecordStmt.setString(18, itemInfo.getRecordsIncludedScopes());
				updateItemForRecordStmt.setLong(19, itemId);
				updateItemForRecordStmt.executeUpdate();
			}

			if (itemInfo.geteContentUrl() != null){
				addItemUrlStmt.setLong(1, itemId);
				addItemUrlStmt.setLong(2, -1);
				addItemUrlStmt.setString(3, itemInfo.geteContentUrl());
				addItemUrlStmt.executeUpdate();

				//Check to see if we need to save local urls
				for (ScopingInfo scopingInfo : itemInfo.getScopingInfo().values()) {
					String localUrl = scopingInfo.getLocalUrl();
					if (localUrl != null && localUrl.length() > 0 && !localUrl.equals(itemInfo.geteContentUrl())) {
						addItemUrlStmt.setLong(1, itemId);
						addItemUrlStmt.setLong(2, scopingInfo.getScope().getId());
						addItemUrlStmt.setString(3, localUrl);
						addItemUrlStmt.executeUpdate();
					}
				}
			}

		} catch (SQLException e) {
			logEntry.incErrors("Error saving grouped work item", e);
		}

		return itemId;
	}

	void removeScope(Long scopeId) {
		try {
			removeScopeStmt.setLong(1, scopeId);
			removeScopeStmt.executeUpdate();
		}catch (SQLException e) {
			logEntry.incErrors("Error removing scope", e);
		}
	}

	void updateScope(Scope scope) {
		try {
			updateScopeStmt.setBoolean(1, scope.isLibraryScope());
			updateScopeStmt.setBoolean(2, scope.isLocationScope());
			updateScopeStmt.setLong(3, scope.getId());
			updateScopeStmt.executeUpdate();
		} catch (SQLException e) {
			logEntry.incErrors("Error updating scope", e);
		}
	}

	Long saveScope(Scope scope) {
		long scopeId = -1;
		try {
			addScopeStmt.setString(1, scope.getScopeName());
			addScopeStmt.setBoolean(2, scope.isLibraryScope());
			addScopeStmt.setBoolean(3, scope.isLocationScope());
			addScopeStmt.executeUpdate();
			ResultSet addScopeRS = addScopeStmt.getGeneratedKeys();
			if (addScopeRS.next()){
				scopeId = addScopeRS.getLong(1);
			}
		} catch (SQLException e) {
			logEntry.incErrors("Error saving scope", e);
		}

		return scopeId;
	}

	HashMap<String, ExistingScopeInfo> getExistingScopes() {
		HashMap<String, ExistingScopeInfo> existingScopes = new HashMap<>();
		try {
			ResultSet getExistingScopesRS = getExistingScopesStmt.executeQuery();
			while (getExistingScopesRS.next()){
				ExistingScopeInfo scopeInfo = new ExistingScopeInfo();
				scopeInfo.id = getExistingScopesRS.getLong("id");
				scopeInfo.scopeName = getExistingScopesRS.getString("name");
				scopeInfo.isLibraryScope = getExistingScopesRS.getBoolean("isLibraryScope");
				scopeInfo.isLocationScope = getExistingScopesRS.getBoolean("isLocationScope");
				existingScopes.put(scopeInfo.scopeName, scopeInfo);
			}
		} catch (SQLException e) {
			logEntry.incErrors("Error loading existing scopes", e);
		}

		return existingScopes;
	}

	public int disabledAutoCommitCounter = 0;
	void disableAutoCommit(){

		disabledAutoCommitCounter++;
		if (disabledAutoCommitCounter == 1) {
			try {
				dbConn.setAutoCommit(false);
			} catch (SQLException throwables) {
				logEntry.incErrors("Error disabling auto commit", throwables);
			}
		}
	}

	void enableAutoCommit() {
		disabledAutoCommitCounter--;
		if (disabledAutoCommitCounter == 0){
			try{
				dbConn.setAutoCommit(true);
			} catch (SQLException throwables) {
				logEntry.incErrors("Error enabling auto commit", throwables);
			}
		}
	}

	public boolean isStoreRecordDetailsInSolr(){
		return storeRecordDetailsInSolr;
	}

	public boolean isStoreRecordDetailsInDatabase() {
		return storeRecordDetailsInDatabase;
	}

	public void markIlsRecordAsDeleted(String name, String existingIdentifier) {
		try {
			marcIlsRecordAsDeletedStmt.setLong(1, new Date().getTime() / 1000);
			marcIlsRecordAsDeletedStmt.setString(2, name);
			marcIlsRecordAsDeletedStmt.setString(3, existingIdentifier);
			marcIlsRecordAsDeletedStmt.executeUpdate();
		}catch (Exception e) {
			logEntry.incErrors("Could not mark ils record as deleted", e);
		}
	}

	public BaseLogEntry getLogEntry() {
		return logEntry;
	}

	public boolean isHideUnknownLiteraryForm() {
		return hideUnknownLiteraryForm;
	}

	public boolean isHideNotCodedLiteraryForm() {
		return hideNotCodedLiteraryForm;
	}

	public String getTreatUnknownAudienceAs() {
		return treatUnknownAudienceAs;
	}

	public String getTreatUnknownLanguageAs() {
		return treatUnknownLanguageAs;
	}

	public boolean isTreatUnknownAudienceAsUnknown() {
		return treatUnknownAudienceAsUnknown;
	}

	public boolean isTreatUnknownAudienceAsGeneral() {
		return treatUnknownAudienceAsGeneral;
	}

	public boolean isTreatUnknownAudienceAsAdult() {
		return treatUnknownAudienceAsAdult;
	}

	public enum MarcStatus {
		UNCHANGED, CHANGED, NEW
	}

	public MarcStatus appendItemsToExistingRecord(IndexingProfile indexingSettings, Record recordWithAdditionalItems, String recordNumber) {
		MarcStatus marcRecordStatus = MarcStatus.UNCHANGED;
		//Copy the record to the individual marc path
		if (recordNumber != null) {
			Record mergedRecord = loadMarcRecordFromDatabase(indexingSettings.getName(), recordNumber, logEntry);

			List<DataField> additionalItems = recordWithAdditionalItems.getDataFields(indexingSettings.getItemTag());
			for (DataField additionalItem : additionalItems) {
				mergedRecord.addVariableField(additionalItem);
			}

			marcRecordStatus = MarcStatus.CHANGED;
			saveMarcRecordToDatabase(indexingSettings, recordNumber, mergedRecord);
		} else {
			logEntry.incErrors("Error did not find record number for MARC record");
		}
		return marcRecordStatus;
	}

	/**
	 *
	 * @param indexingProfile - the indexing profile for the record
	 * @param ilsId - The id of the record to save
	 * @param marcRecord - The contents of the marc record
	 * @return int 0 if the marc has not changed, 1 if the marc is new, and 2 if the marc has changes
	 */
	public synchronized MarcStatus saveMarcRecordToDatabase(BaseIndexingSettings indexingProfile, String ilsId, Record marcRecord) {
		ByteArrayOutputStream outputStream = new ByteArrayOutputStream();
		MarcWriter writer = new MarcJsonWriter(outputStream);
		writer.write(marcRecord);
		checksumCalculator.reset();
		byte[] marcAsBytes = outputStream.toByteArray();

		checksumCalculator.update(marcAsBytes);
		MarcStatus returnValue = MarcStatus.UNCHANGED;
		boolean foundExisting = false;
		try {
			//check to see if we need to make an update
			getExistingRecordInfoForIdentifierStmt.setString(1, ilsId);
			getExistingRecordInfoForIdentifierStmt.setString(2, indexingProfile.getName());
			ResultSet getExistingRecordInfoForIdentifierRS = getExistingRecordInfoForIdentifierStmt.executeQuery();
			if (getExistingRecordInfoForIdentifierRS.next()){
				foundExisting = true;
				long existingChecksum = getExistingRecordInfoForIdentifierRS.getLong("checksum");
				long uncompressedLength = getExistingRecordInfoForIdentifierRS.getLong("sourceDataLength");
				boolean deleted = getExistingRecordInfoForIdentifierRS.getBoolean("deleted");
				//String marcAsString = new String(marcAsBytes);
				if (deleted || (marcAsBytes.length != uncompressedLength) || (existingChecksum != checksumCalculator.getValue())){
					long curTime = new Date().getTime() / 1000;
					updateRecordInDBStmt.setLong(1, checksumCalculator.getValue());
					updateRecordInDBStmt.setBlob(2, new ByteArrayInputStream(marcAsBytes));
					updateRecordInDBStmt.setLong(3, curTime);
					updateRecordInDBStmt.setLong(4, getExistingRecordInfoForIdentifierRS.getLong("id"));
					updateRecordInDBStmt.executeUpdate();
					returnValue = MarcStatus.CHANGED;
				}
			}else{
				File marcFile = indexingProfile.getFileForIlsRecord(ilsId);
				long lastModified;
				if (marcFile.exists()) {
					lastModified = marcFile.lastModified() / 1000;
				}else{
					lastModified = new Date().getTime() / 1000;
				}

				addRecordToDBStmt.setString(1, ilsId);
				addRecordToDBStmt.setString(2, indexingProfile.getName());
				addRecordToDBStmt.setLong(3, checksumCalculator.getValue());
				addRecordToDBStmt.setLong(4, lastModified);
				addRecordToDBStmt.setBlob(5, new ByteArrayInputStream(marcAsBytes));
				addRecordToDBStmt.setLong(6, lastModified);
				addRecordToDBStmt.executeUpdate();
				returnValue = MarcStatus.NEW;
				if (marcFile.exists() && !marcFile.delete()) {
					logEntry.incErrors("Could not delete individual marc " + marcFile.getAbsolutePath());
				}
			}

		}catch (Exception e){
			logEntry.incErrors("Error saving MARC record to database for " + ilsId + " found existing? " + foundExisting, e);
		}
		marcRecordCache.put(indexingProfile.getName() + ilsId, marcRecord);

		return returnValue;
	}

	//Create a small cache to hold recently used marc records to avoid time reloading them.
	public MaxSizeHashMap<String, Record> marcRecordCache = new MaxSizeHashMap<>(100);
	public Record loadMarcRecordFromDatabase(String source, String identifier, BaseLogEntry logEntry) {
		String key = source + identifier;
		Record marcRecord = marcRecordCache.get(key);
		if (marcRecord == null) {
			try {
				getRecordForIdentifierStmt.setString(1, identifier);
				getRecordForIdentifierStmt.setString(2, source);
				ResultSet getRecordForIdentifierRS = getRecordForIdentifierStmt.executeQuery();
				if (getRecordForIdentifierRS.next()) {
					byte[] marcData = getRecordForIdentifierRS.getBytes("sourceData");
					if (marcData != null && marcData.length > 0) {
						String marcRecordRaw = new String(marcData, StandardCharsets.UTF_8);
						marcRecord = MarcUtil.readJsonFormattedRecord(identifier, marcRecordRaw, logEntry);
						marcRecordCache.put(key, marcRecord);
					}
				}
				getRecordForIdentifierRS.close();
			} catch (Exception e) {
				logEntry.incErrors("Error loading MARC record from database", e);
			}
		}
		return marcRecord;

	}
}
