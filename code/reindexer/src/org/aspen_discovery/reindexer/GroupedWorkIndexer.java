package org.aspen_discovery.reindexer;

import org.apache.commons.lang.math.NumberUtils;
import org.aspen_discovery.grouping.*;
import com.turning_leaf_technologies.indexing.*;
import com.turning_leaf_technologies.logging.BaseIndexingLogEntry;
import com.turning_leaf_technologies.marc.MarcUtil;
import com.turning_leaf_technologies.strings.AspenStringUtils;
import com.turning_leaf_technologies.util.MaxSizeHashMap;
import org.apache.solr.client.solrj.SolrServerException;
import org.apache.solr.client.solrj.impl.ConcurrentUpdateHttp2SolrClient;
import org.apache.solr.client.solrj.impl.BaseHttpSolrClient;
import org.apache.solr.client.solrj.impl.Http2SolrClient;
import org.apache.solr.client.solrj.response.UpdateResponse;
import org.apache.solr.common.SolrInputDocument;
import org.ini4j.Ini;

import java.io.*;
import java.nio.charset.StandardCharsets;
import java.sql.*;
import java.text.Normalizer;
import java.util.*;
import java.util.Date;
import java.util.zip.CRC32;

import org.apache.logging.log4j.Logger;
import org.marc4j.MarcJsonWriter;
import org.marc4j.MarcWriter;
import org.marc4j.marc.DataField;
import org.marc4j.marc.MarcFactory;
import org.marc4j.marc.Record;

public class GroupedWorkIndexer {
	private final String serverName;
	private final BaseIndexingLogEntry logEntry;
	private final Logger logger;
	private final Long indexStartTime;
	private int deletionCommitInterval = 1000;
	private boolean waitAfterDeleteCommit = false;
	private int totalRecordsHandled = 0;
	private ConcurrentUpdateHttp2SolrClient updateServer;
	private RecordGroupingProcessor recordGroupingProcessor;
	private final HashMap<String, MarcRecordProcessor> ilsRecordProcessors = new HashMap<>();
	private final HashMap<String, SideLoadedEContentProcessor> sideLoadProcessors = new HashMap<>();
	private final HashMap<String, MarcRecordGrouper> ilsRecordGroupers = new HashMap<>();
	private final HashMap<String, SideLoadedRecordGrouper> sideLoadRecordGroupers = new HashMap<>();
	private OverDriveProcessor overDriveProcessor;
	private OverDriveRecordGrouper overDriveRecordGrouper;
	private CloudLibraryProcessor cloudLibraryProcessor;
	private Axis360Processor axis360Processor;
	private HooplaProcessor hooplaProcessor;
	private PalaceProjectProcessor palaceProjectProcessor;
	private final HashMap<String, HashMap<String, String>> translationMaps = new HashMap<>();
	private final HashMap<String, LexileTitle> lexileInformation = new HashMap<>();
	protected static final HashSet<String> hideSubjects = new HashSet<>();
	protected static final HashSet<String> hideSeries = new HashSet<>();

	private PreparedStatement getRatingStmt;
	private PreparedStatement getNovelistStmt;
	private PreparedStatement getDisplayInfoStmt;

	private PreparedStatement getUserReadingHistoryLinkStmt;
	private PreparedStatement getUserRatingLinkStmt;
	private PreparedStatement getUserNotInterestedLinkStmt;

	private PreparedStatement forceReindexOfRecordStmt;

	private final Connection dbConn;

	static int availableAtBoostValue = 50;
	static int ownedByBoostValue = 10;

	private final boolean fullReindex;
	private final boolean clearIndex;
	private boolean regroupAllRecords;
	private boolean processEmptyGroupedWorks;
	private long lastReindexTime;
	private Long lastReindexTimeVariableId;
	private boolean okToIndex = true;

	private TreeSet<Scope> scopes ;

	private PreparedStatement getGroupedWorkPrimaryIdentifiers;
	private PreparedStatement getGroupedWorkInfoStmt;
	private PreparedStatement getArBookIdForIsbnStmt;
	private PreparedStatement getArBookInfoStmt;
	private PreparedStatement getNumScheduledWorksStmt;
	private PreparedStatement getScheduledWorksStmt;
	private PreparedStatement getScheduledWorkStmt;
	private PreparedStatement markScheduledWorkProcessedStmt;
	private PreparedStatement addScheduledWorkStmt;

	private PreparedStatement getExistingScopesStmt;
	private PreparedStatement addScopeStmt;
	private PreparedStatement updateScopeStmt;
	private PreparedStatement removeScopeStmt;

	private PreparedStatement markIlsRecordAsDeletedStmt;
	private PreparedStatement markIlsRecordAsRestoredStmt;
	private PreparedStatement getExistingRecordsForWorkStmt;
	private PreparedStatement addRecordForWorkStmt;
	private PreparedStatement updateRecordForWorkStmt;
	private PreparedStatement getIdForRecordStmt;
	private PreparedStatement removeRecordForWorkStmt;
	private PreparedStatement getExistingVariationsForWorkStmt;
	private PreparedStatement addVariationForWorkStmt;
	private PreparedStatement getIdForVariationStmt;
	private PreparedStatement removeVariationStmt;
	private PreparedStatement getExistingItemsForRecordStmt;
	private PreparedStatement removeItemStmt;
	private PreparedStatement addItemForRecordStmt;
	private PreparedStatement updateItemForRecordStmt;
	private PreparedStatement getIdForItemStmt;
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
	private PreparedStatement getPlaceOfPublicationStmt;
	private PreparedStatement addPlaceOfPublicationStmt;
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
	private PreparedStatement getHideSubjectsStmt;
	private PreparedStatement getHideSeriesStmt;
	private PreparedStatement getDebugInfoStmt;
	private PreparedStatement updateDebuggingInfoStmt;

	private PreparedStatement removeItemsForWorkStmt;
	private PreparedStatement removeVariationsForWorkStmt;
	private PreparedStatement removeRecordsForWorkStmt;

	private boolean seriesModuleEnabled = false;
	private PreparedStatement getSeriesStmt;
	private PreparedStatement getSeriesMemberStmt;
	private PreparedStatement addSeriesMemberStmt;
	private PreparedStatement deleteSeriesMemberStmt;
	private PreparedStatement deleteSeriesStmt;
	private PreparedStatement addSeriesStmt;
	private PreparedStatement setSeriesDateUpdated;
	private PreparedStatement updateSeriesAuthor;

	private final CRC32 checksumCalculator = new CRC32();

	private boolean storeRecordDetailsInSolr = false;
	private boolean storeRecordDetailsInDatabase = true;

	private boolean hideUnknownLiteraryForm;
	private boolean hideNotCodedLiteraryForm;

	private String treatUnknownAudienceAs = "Unknown";
	private boolean treatUnknownAudienceAsUnknown = false;
	private String treatUnknownLanguageAs = "English";
	private int indexVersion;
	private int searchVersion;
	private boolean enableNovelistSeriesIntegration;

	public GroupedWorkIndexer(String serverName, Connection dbConn, Ini configIni, boolean fullReindex, boolean clearIndex, BaseIndexingLogEntry logEntry, Logger logger) {
		this(serverName, dbConn, configIni, fullReindex, clearIndex, false, logEntry, logger);
	}

	public GroupedWorkIndexer(String serverName, Connection dbConn, Ini configIni, boolean fullReindex, boolean clearIndex, boolean regroupAllRecords, BaseIndexingLogEntry logEntry, Logger logger) {
		indexStartTime = new Date().getTime() / 1000;
		this.serverName = serverName;
		this.logEntry = logEntry;
		this.logger = logger;
		this.dbConn = dbConn;
		this.fullReindex = fullReindex;
		this.clearIndex = clearIndex;
		this.regroupAllRecords = regroupAllRecords;

		String solrPort = configIni.get("Index", "solrPort");
		if (solrPort == null || solrPort.isEmpty()) {
			solrPort = configIni.get("Reindex", "solrPort");
			if (solrPort == null || solrPort.isEmpty()) {
				solrPort = "8080";
			}
		}
		String solrHost = configIni.get("Index", "solrHost");
		if (solrHost == null || solrHost.isEmpty()) {
			solrHost = configIni.get("Reindex", "solrHost");
			if (solrHost == null || solrHost.isEmpty()) {
				solrHost = "localhost";
			}
		}

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
			PreparedStatement systemVariablesStmt = dbConn.prepareStatement("SELECT storeRecordDetailsInSolr, storeRecordDetailsInDatabase, indexVersion, searchVersion, processEmptyGroupedWorks, enableNovelistSeriesIntegration, deletionCommitInterval, waitAfterDeleteCommit from system_variables");
			ResultSet systemVariablesRS = systemVariablesStmt.executeQuery();
			if (systemVariablesRS.next()){
				this.storeRecordDetailsInSolr = systemVariablesRS.getBoolean("storeRecordDetailsInSolr");
				this.storeRecordDetailsInDatabase = systemVariablesRS.getBoolean("storeRecordDetailsInDatabase");
				this.indexVersion = systemVariablesRS.getInt("indexVersion");
				this.searchVersion = systemVariablesRS.getInt("searchVersion");
				this.enableNovelistSeriesIntegration = systemVariablesRS.getBoolean("enableNovelistSeriesIntegration");
				this.deletionCommitInterval = systemVariablesRS.getInt("deletionCommitInterval");
				this.waitAfterDeleteCommit = systemVariablesRS.getBoolean("waitAfterDeleteCommit");
				if (fullReindex) {
					this.processEmptyGroupedWorks = systemVariablesRS.getBoolean("processEmptyGroupedWorks");
				}
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
			getNumScheduledWorksStmt = dbConn.prepareStatement("SELECT COUNT(DISTINCT permanent_id) as numScheduledWorks FROM grouped_work_scheduled_index where processed = 0 and indexAfter <= ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			getScheduledWorksStmt = dbConn.prepareStatement("SELECT id, permanent_id FROM grouped_work_scheduled_index where processed = 0 and indexAfter <= ? ORDER BY indexAfter ASC LIMIT 0, 1", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			getScheduledWorkStmt = dbConn.prepareStatement("SELECT * FROM grouped_work_scheduled_index where processed = 0 and permanent_id = ? and indexAfter = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			markScheduledWorkProcessedStmt = dbConn.prepareStatement("UPDATE grouped_work_scheduled_index set processed = 1 where permanent_id = ? and indexAfter <= ?");
			addScheduledWorkStmt = dbConn.prepareStatement("INSERT INTO grouped_work_scheduled_index (permanent_id, indexAfter) VALUES (?, ?)");

			forceReindexOfRecordStmt = dbConn.prepareStatement("INSERT INTO record_identifiers_to_reload (type, identifier, processed) VALUES (?, ?, 0)");

			markIlsRecordAsDeletedStmt = dbConn.prepareStatement("UPDATE ils_records set deleted = 1, dateDeleted = ? where source = ? and ilsId = ?");
			markIlsRecordAsRestoredStmt = dbConn.prepareStatement("UPDATE ils_records set deleted = 0, dateDeleted = null where source = ? and ilsId = ?");
			getExistingScopesStmt = dbConn.prepareStatement("SELECT * from scope", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			addScopeStmt = dbConn.prepareStatement("INSERT INTO scope (name, isLibraryScope, isLocationScope) VALUES (?, ?, ?)", Statement.RETURN_GENERATED_KEYS);
			updateScopeStmt = dbConn.prepareStatement("UPDATE scope set isLibraryScope = ?, isLocationScope = ? WHERE id = ?");
			removeScopeStmt = dbConn.prepareStatement("DELETE FROM scope where id = ?");
			getExistingRecordsForWorkStmt = dbConn.prepareStatement("SELECT id, sourceId, recordIdentifier, groupedWorkId, editionId, publisherId, publicationDateId, placeOfPublicationId, physicalDescriptionId, formatId, formatCategoryId, languageId, isClosedCaptioned, hasParentRecord, hasChildRecord from grouped_work_records where groupedWorkId = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			addRecordForWorkStmt = dbConn.prepareStatement("INSERT INTO grouped_work_records (groupedWorkId, sourceId, recordIdentifier, editionId, publisherId, publicationDateId, placeOfPublicationId, physicalDescriptionId, formatId, formatCategoryId, languageId, isClosedCaptioned, hasParentRecord, hasChildRecord) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) " +
					"ON DUPLICATE KEY UPDATE groupedWorkId = VALUES(groupedWorkId), editionId = VALUES(editionId), publisherId = VALUES(publisherId), publicationDateId = VALUES(publicationDateId), placeOfPublicationId = VALUES(placeOfPublicationId), physicalDescriptionId = VALUES(physicalDescriptionId), formatId = VALUES(formatId), formatCategoryId = VALUES(formatCategoryId), languageId = VALUES(languageId), isClosedCaptioned = VALUES(isClosedCaptioned), hasParentRecord = VALUES(hasParentRecord), hasChildRecord = VALUES(hasChildRecord)", PreparedStatement.RETURN_GENERATED_KEYS);
			updateRecordForWorkStmt = dbConn.prepareStatement("UPDATE grouped_work_records SET groupedWorkId = ?, editionId = ?, publisherId = ?, publicationDateId = ?, placeOfPublicationId = ?, physicalDescriptionId = ?, formatId = ?, formatCategoryId = ?, languageId = ?, isClosedCaptioned = ?, hasParentRecord = ?, hasChildRecord = ? where id = ?");
			removeRecordForWorkStmt = dbConn.prepareStatement("DELETE FROM grouped_work_records where id = ?");
			getIdForRecordStmt = dbConn.prepareStatement("SELECT id from grouped_work_records where sourceId = ? and recordIdentifier = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			getExistingVariationsForWorkStmt = dbConn.prepareStatement("SELECT * from grouped_work_variation where groupedWorkId = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			//The entire row is meant to be unique, but we want to get the id back, so we update the language to itself on duplicate
			addVariationForWorkStmt = dbConn.prepareStatement("INSERT INTO grouped_work_variation (groupedWorkId, primaryLanguageId, eContentSourceId, formatId, formatCategoryId) VALUES (?, ?, ?, ?, ?)" +
					" ON DUPLICATE KEY " +
					"UPDATE primaryLanguageId = VALUES(primaryLanguageId)", PreparedStatement.RETURN_GENERATED_KEYS);
			getIdForVariationStmt = dbConn.prepareStatement("SELECT id from grouped_work_variation where groupedWorkId = ? AND primaryLanguageId = ? AND eContentSourceId = ? AND formatId = ? AND formatCategoryId = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			removeVariationStmt = dbConn.prepareStatement("DELETE FROM grouped_work_variation WHERE id = ?");
			getExistingItemsForRecordStmt = dbConn.prepareStatement("SELECT * from grouped_work_record_items WHERE groupedWorkRecordId = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			addItemForRecordStmt = dbConn.prepareStatement("INSERT INTO grouped_work_record_items (groupedWorkRecordId, groupedWorkVariationId, itemId, shelfLocationId, callNumberId, sortableCallNumberId, numCopies, isOrderItem, statusId, dateAdded, locationCodeId, subLocationCodeId, lastCheckInDate, groupedStatusId, available, holdable, inLibraryUseOnly, locationOwnedScopes, libraryOwnedScopes, recordIncludedScopes, isVirtual) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)" +
					" ON DUPLICATE KEY " +
					"UPDATE shelfLocationId = VALUES(shelfLocationId), callNumberId = VALUES(callNumberId), sortableCallNumberId = VALUES(sortableCallNumberId), numCopies = VALUES(numCopies), isOrderItem = VALUES(isOrderItem), statusId = VALUES(statusId), locationCodeId = VALUES(locationCodeId), subLocationCodeId = VALUES(subLocationCodeId), lastCheckInDate = VALUES(lastCheckInDate), groupedStatusId = VALUES(groupedStatusId), available = VALUES(available), inLibraryUseOnly = VALUES(inLibraryUseOnly), holdable = VALUES(holdable), locationOwnedScopes = VALUES(locationOwnedScopes), libraryOwnedScopes = VALUES(libraryOwnedScopes), recordIncludedScopes = VALUES(recordIncludedScopes), isVirtual = VALUES(isVirtual)", PreparedStatement.RETURN_GENERATED_KEYS);
			updateItemForRecordStmt = dbConn.prepareStatement("UPDATE grouped_work_record_items set groupedWorkVariationId = ?, shelfLocationId = ?, callNumberId = ?, sortableCallNumberId = ?, numCopies = ?, isOrderItem = ?, statusId = ?, dateAdded = ?, " +
					"locationCodeId = ?, subLocationCodeId = ?, lastCheckInDate = ?, groupedStatusId = ?, available = ?, holdable = ?, inLibraryUseOnly = ?, locationOwnedScopes = ?, libraryOwnedScopes = ?, recordIncludedScopes = ?, isVirtual = ? WHERE id = ?");
			getIdForItemStmt = dbConn.prepareStatement("SELECT id from grouped_work_record_items where groupedWorkRecordId = ? and groupedWorkVariationId = ? and itemId = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
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
			getPublicationDateStmt = dbConn.prepareStatement("SELECT id from indexed_publication_date where publicationDate = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			addPublicationDateStmt = dbConn.prepareStatement("INSERT INTO indexed_publication_date (publicationDate) VALUES (?)", Statement.RETURN_GENERATED_KEYS);
			getPlaceOfPublicationStmt = dbConn.prepareStatement("SELECT id from indexed_place_of_publication where placeOfPublication = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			addPlaceOfPublicationStmt = dbConn.prepareStatement("INSERT INTO indexed_place_of_publication (placeOfPublication) VALUES (?)", Statement.RETURN_GENERATED_KEYS);
			getPhysicalDescriptionStmt = dbConn.prepareStatement("SELECT id from indexed_physical_description where physicalDescription = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			addPhysicalDescriptionStmt = dbConn.prepareStatement("INSERT INTO indexed_physical_description (physicalDescription) VALUES (?)", Statement.RETURN_GENERATED_KEYS);
			getEContentSourceStmt = dbConn.prepareStatement("SELECT id from indexed_econtent_source where eContentSource = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			addEContentSourceStmt = dbConn.prepareStatement("INSERT INTO indexed_econtent_source (eContentSource) VALUES (?)", Statement.RETURN_GENERATED_KEYS);
			getShelfLocationStmt = dbConn.prepareStatement("SELECT id from indexed_shelf_location where shelfLocation = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			addShelfLocationStmt = dbConn.prepareStatement("INSERT INTO indexed_shelf_location (shelfLocation) VALUES (?)", Statement.RETURN_GENERATED_KEYS);
			getCallNumberStmt = dbConn.prepareStatement("SELECT id from indexed_call_number where callNumber = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			addCallNumberStmt = dbConn.prepareStatement("INSERT INTO indexed_call_number (callNumber) VALUES (?)", Statement.RETURN_GENERATED_KEYS);
			getStatusStmt = dbConn.prepareStatement("SELECT id from indexed_status where status = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			addStatusStmt = dbConn.prepareStatement("INSERT INTO indexed_status (status) VALUES (?)", Statement.RETURN_GENERATED_KEYS);
			getLocationCodeStmt = dbConn.prepareStatement("SELECT id from indexed_location_code where locationCode = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			addLocationCodeStmt = dbConn.prepareStatement("INSERT INTO indexed_location_code (locationCode) VALUES (?)", Statement.RETURN_GENERATED_KEYS);
			getSubLocationCodeStmt = dbConn.prepareStatement("SELECT id from indexed_sub_location_code where subLocationCode = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			addSubLocationCodeStmt = dbConn.prepareStatement("INSERT INTO indexed_sub_location_code (subLocationCode) VALUES (?)", Statement.RETURN_GENERATED_KEYS);

			getExistingRecordInfoForIdentifierStmt = dbConn.prepareStatement("SELECT id, checksum, deleted, UNCOMPRESSED_LENGTH(sourceData) as sourceDataLength FROM ils_records where ilsId = ? and source = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			getRecordForIdentifierStmt = dbConn.prepareStatement("SELECT UNCOMPRESS(sourceData) as sourceData FROM ils_records where ilsId = ? and source = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			addRecordToDBStmt = dbConn.prepareStatement("INSERT INTO ils_records set ilsId = ?, source = ?, checksum = ?, dateFirstDetected = ?, deleted = 0, suppressedNoMarcAvailable = 0, sourceData = COMPRESS(?), lastModified = ?", PreparedStatement.RETURN_GENERATED_KEYS);
			updateRecordInDBStmt = dbConn.prepareStatement("UPDATE ils_records set checksum = ?, sourceData = COMPRESS(?), lastModified = ?, deleted = 0, suppressedNoMarcAvailable = 0 WHERE id = ?", PreparedStatement.RETURN_GENERATED_KEYS);
			getHideSubjectsStmt = dbConn.prepareStatement("SELECT subjectNormalized from hide_subject_facets", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			getHideSeriesStmt = dbConn.prepareStatement("SELECT seriesNormalized from hide_series", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			getDebugInfoStmt = dbConn.prepareStatement("SELECT id from grouped_work_debug_info where permanent_id = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			updateDebuggingInfoStmt = dbConn.prepareStatement("UPDATE grouped_work_debug_info set processed = 1, debugInfo = ?, debugTime = ? where id =?");

			removeItemsForWorkStmt = dbConn.prepareStatement("DELETE grouped_work_record_items FROM grouped_work_records LEFT JOIN grouped_work_record_items ON ( grouped_work_record_items.groupedWorkRecordId = grouped_work_records.id ) WHERE groupedWorkId = ?");
			removeVariationsForWorkStmt = dbConn.prepareStatement("DELETE FROM grouped_work_variation where groupedWorkId = ?");
			removeRecordsForWorkStmt = dbConn.prepareStatement("DELETE FROM grouped_work_records where groupedWorkId = ?");

			getSeriesMemberStmt = dbConn.prepareStatement("SELECT s.groupedWorkSeriesTitle, sm.seriesId FROM series_member AS sm LEFT JOIN series AS s ON sm.seriesId = s.id WHERE groupedWorkPermanentId = ?");
			getSeriesStmt = dbConn.prepareStatement("SELECT * FROM series WHERE groupedWorkSeriesTitle = ?");
			addSeriesStmt = dbConn.prepareStatement("INSERT INTO series (displayName, audience, created, dateUpdated, author, groupedWorkSeriesTitle) VALUES (?, ?, ?, ?, ?, ?)", PreparedStatement.RETURN_GENERATED_KEYS);
			addSeriesMemberStmt = dbConn.prepareStatement("INSERT INTO series_member (seriesId, isPlaceholder, groupedWorkPermanentId, volume, pubDate, displayName, author, description, weight) VALUES (?, 0, ?, ?, ?, ?, ?, ?, ?)");
			deleteSeriesMemberStmt = dbConn.prepareStatement("DELETE FROM series_member WHERE seriesId = ? AND groupedWorkPermanentId = ? AND userAdded = 0;");
			deleteSeriesStmt = dbConn.prepareStatement("DELETE FROM series WHERE id in (SELECT seriesId FROM series_member WHERE seriesId = ? HAVING COUNT(groupedWorkPermanentId) = 1);");
			updateSeriesAuthor = dbConn.prepareStatement("UPDATE series SET author = ? WHERE id = ?;");
			setSeriesDateUpdated = dbConn.prepareStatement("UPDATE series SET dateUpdated = ? WHERE id = ?;");
		} catch (Exception e){
			logEntry.incErrors("Could not load statements to get identifiers ", e);
			this.okToIndex = false;
			return;
		}

		// Check if series module is enabled
		try {
			PreparedStatement seriesModuleEnabledStmt = dbConn.prepareStatement("SELECT enabled FROM modules WHERE name = 'series'");
			ResultSet enabledRS = seriesModuleEnabledStmt.executeQuery();
			if (enabledRS.next()) {
				seriesModuleEnabled = enabledRS.getBoolean("enabled");
			}
			enabledRS.close();
		} catch (Exception e) {
			logEntry.incErrors("Could not check if series module enabled ", e);
		}

		//Initialize the updateServer and solr server
		logEntry.addNote("Setting up update server and solr server");

		String solrUrl;
		if (indexVersion == 1) {
			solrUrl = "http://" + solrHost + ":" + solrPort + "/solr/grouped_works";
		}else{
			solrUrl = "http://" + solrHost + ":" + solrPort + "/solr/grouped_works_v2";
		}
		Http2SolrClient http2Client = new Http2SolrClient.Builder().build();
		try {
			updateServer = new ConcurrentUpdateHttp2SolrClient.Builder(solrUrl, http2Client)
					.withThreadCount(1)
					.withQueueSize(25)
					.build();
		}catch (OutOfMemoryError e) {
			logger.error("Unable to create solr client, out of memory", e);
			System.exit(-7);
		}

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
			//Don't load these based on the records in the database, base it on the actual indexing profiles defined (otherwise it will not index things the first time)
			//PreparedStatement uniqueIdentifiersStmt = dbConn.prepareStatement("SELECT DISTINCT type FROM grouped_work_primary_identifiers");
			PreparedStatement getIndexingProfilesStmt = dbConn.prepareStatement("SELECT * from indexing_profiles");

			ResultSet indexingProfilesRS = getIndexingProfilesStmt.executeQuery();

			while (indexingProfilesRS.next()){
				String ilsIndexingClassString = indexingProfilesRS.getString("indexingClass");
				String curType = indexingProfilesRS.getString("name");
				IndexingProfile indexingProfile = new IndexingProfile(serverName, indexingProfilesRS, dbConn, logEntry);
				switch (ilsIndexingClassString) {
					case "ArlingtonKoha":
						ilsRecordProcessors.put(curType, new ArlingtonKohaRecordProcessor(serverName, this, curType, dbConn, indexingProfilesRS, logger, fullReindex));
						break;
					case "CarlX":
						ilsRecordProcessors.put(curType, new CarlXRecordProcessor(serverName, this, curType, dbConn, indexingProfilesRS, logger, fullReindex));
						break;
					case "NashvilleCarlX":
						ilsRecordProcessors.put(curType, new NashvilleCarlXRecordProcessor(serverName, this, curType, dbConn, indexingProfilesRS, logger, fullReindex));
						break;
					case "III":
						ilsRecordProcessors.put(curType, new IIIRecordProcessor(serverName, this, curType, dbConn, indexingProfilesRS, logger, fullReindex));
						break;
					case "SideLoadedEContent":
						ilsRecordProcessors.put(curType, new SideLoadedEContentProcessor(serverName, this, curType, dbConn, indexingProfilesRS, logger, fullReindex));
						break;
					case "Koha":
						ilsRecordProcessors.put(curType, new KohaRecordProcessor(serverName, this, curType, dbConn, indexingProfilesRS, logger, fullReindex));
						break;
					case "Symphony":
						ilsRecordProcessors.put(curType, new SymphonyRecordProcessor(serverName, this, curType, dbConn, indexingProfilesRS, logger, fullReindex));
						break;
					case "Polaris":
						ilsRecordProcessors.put(curType, new PolarisRecordProcessor(serverName, this, curType, dbConn, indexingProfilesRS, logger, fullReindex));
						break;
					case "Evergreen":
						ilsRecordProcessors.put(curType, new EvergreenRecordProcessor(serverName, this, curType, dbConn, indexingProfilesRS, logger, fullReindex));
						break;
					case "Evolve":
						ilsRecordProcessors.put(curType, new EvolveRecordProcessor(serverName, this, curType, dbConn, indexingProfilesRS, logger, fullReindex));
						break;
					case "Folio":
						ilsRecordProcessors.put(curType, new FolioRecordProcessor(serverName, this, curType, dbConn, indexingProfilesRS, logger, fullReindex));
						break;
					default:
						logEntry.incErrors("Unknown indexing class " + ilsIndexingClassString);
						continue;
				}
				ilsRecordGroupers.put(curType, new MarcRecordGrouper(serverName, dbConn, indexingProfile, logEntry, logger));

				//Load how to treat unknown audiences for the entire indexer, this will be based on the last indexing profile encountered (at least for now since we only have one no big deal)
				this.treatUnknownAudienceAs = indexingProfilesRS.getString("treatUnknownAudienceAs");
				if ("Unknown".equals(this.treatUnknownAudienceAs)) {
					treatUnknownAudienceAsUnknown = true;
				}
				this.treatUnknownLanguageAs = indexingProfilesRS.getString("treatUnknownLanguageAs");
			}
			indexingProfilesRS.close();
			getIndexingProfilesStmt.close();

			PreparedStatement getSideLoadSettingsStmt = dbConn.prepareStatement("SELECT * from sideloads");
			ResultSet getSideLoadSettingsRS = getSideLoadSettingsStmt.executeQuery();

			while (getSideLoadSettingsRS.next()) {
				String curType = getSideLoadSettingsRS.getString("name").toLowerCase();
				String sideLoadIndexingClassString = getSideLoadSettingsRS.getString("indexingClass");
				if ("SideLoadedEContent".equals(sideLoadIndexingClassString) || "SideLoadedEContentProcessor".equals(sideLoadIndexingClassString)) {
					SideLoadedEContentProcessor sideloadProcessor = new SideLoadedEContentProcessor(serverName, this, curType, dbConn, getSideLoadSettingsRS, logger, fullReindex);
					sideLoadProcessors.put(curType, sideloadProcessor);
					sideLoadRecordGroupers.put(curType, new SideLoadedRecordGrouper(serverName, dbConn, sideloadProcessor.getSettings(), logEntry, logger));
				} else {
					logEntry.incErrors("Unknown side load processing class " + sideLoadIndexingClassString);
					okToIndex = false;
					return;
				}
			}
			getSideLoadSettingsStmt.close();
			getSideLoadSettingsRS.close();

		}catch (Exception e){
			logEntry.incErrors("Error loading record processors for ILS records", e);
		}
		overDriveProcessor = new OverDriveProcessor(this, dbConn, logger);
		overDriveRecordGrouper = new OverDriveRecordGrouper(dbConn, serverName, logEntry, logger);

		cloudLibraryProcessor = new CloudLibraryProcessor(this, dbConn, logger);

		hooplaProcessor = new HooplaProcessor(this, dbConn, logger);

		axis360Processor = new Axis360Processor(this, dbConn, logger);

		palaceProjectProcessor = new PalaceProjectProcessor(this, dbConn, logger);

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

		//Load subject facets to hide
		loadHideSubjects();

		//Load series to hide
		loadHideSeries();

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
		palaceProjectProcessor = null;
		translationMaps.clear();
		lexileInformation.clear();
		hideSubjects.clear();
		hideSeries.clear();
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

	public boolean isOkToIndex(){
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
			lexileReader.close();
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
		} catch (BaseHttpSolrClient.RemoteSolrException rse) {
			logEntry.incErrors("Solr is not running properly, try restarting", rse);
			System.exit(-1);
		} catch (Exception e) {
			logEntry.incErrors("Error deleting from index", e);
		}
	}

	public synchronized void deleteRecord(String permanentId, Long groupedWorkId) {
		logger.info("Clearing existing work " + permanentId + " from index");
		//noinspection CommentedOutCode
		try {
			if (permanentId.length() < 40) {
				//Delete both the original id (if less than 40 characters)
				updateServer.deleteById(permanentId);
			}

			if (permanentId.length() >= 37 && permanentId.length() < 40) {
				//Also try padding with spaces if less than 40 characters
				StringBuilder permanentIdBuilder = new StringBuilder(permanentId);
				while (permanentIdBuilder.length() < 40) {
					permanentIdBuilder.append(" ");
				}
				permanentId = permanentIdBuilder.toString();
			}
			updateServer.deleteById(permanentId);
			//With this commit, we get errors in the log "Previous SolrRequestInfo was not closed!"
			//Allow auto commit functionality to handle this
			totalRecordsHandled++;
			if (totalRecordsHandled % this.deletionCommitInterval == 0) {
				if (this.waitAfterDeleteCommit) {
					this.commitChangesWithWait();
				}else{
					this.commitChanges();
				}
			}

			/*
			Delete the work from the database?
			TODO: Should we do this or leave a record if it was linked to lists, reading history, etc?
			TODO: Add a deleted flag since overdrive will return titles that can no longer be accessed?
			TODO: If we restore deleting the grouped work we should clean up enrichment, reading history, etc
			We would avoid continually deleting and re-adding?
			MDN: leave the grouped work to deal with OverDrive records.  The grouped work will still be active, but
			it won't be in search results.*/
			/*
			deleteGroupedWorkStmt.setLong(1, groupedWorkId);
			deleteGroupedWorkStmt.executeUpdate();
			*/

			//Delete all grouped_work items, records, and variations, but leave the grouped work
			removeItemsForWorkStmt.setLong(1, groupedWorkId);
			removeItemsForWorkStmt.executeUpdate();

			removeVariationsForWorkStmt.setLong(1, groupedWorkId);
			removeVariationsForWorkStmt.executeUpdate();

			removeRecordsForWorkStmt.setLong(1, groupedWorkId);
			removeRecordsForWorkStmt.executeUpdate();

		}catch (SolrServerException sse) {
			logEntry.incErrors("Solr Exception deleting work from index, quitting to be safe", sse);
			logEntry.setFinished();
			logEntry.saveResults();
			System.exit(-6);
		} catch (Exception e) {
			logEntry.incErrors("Error deleting work from index", e);
		}
	}

	public void finishIndexingFromExtract(BaseIndexingLogEntry logEntry){
		processScheduledWorks(logEntry, true, 100);

		try {
			updateServer.commit(false, false, true);
		}catch (Exception e) {
			logEntry.incErrors("Error in final commit while finishing extract, shutting down", e);
			logEntry.setFinished();
			logEntry.saveResults();
			System.exit(-3);
		}
		try {
			logEntry.addNote("Shutting down the update server");
			updateServer.blockUntilFinished();
		}catch (Exception e) {
			logEntry.incErrors("Error blocking until finished while finishing extract, shutting down", e);
			logEntry.setFinished();
			logEntry.saveResults();
			System.exit(-4);
		}
		try {
			updateServer.close();
		}catch (Exception e) {
			logEntry.incErrors("Error closing update server ", e);
			logEntry.setFinished();
			logEntry.saveResults();
			System.exit(-5);
		}
	}

	public void commitChanges(){
		try {
			updateServer.commit(false, false, true);
		}catch (Exception e) {
			logEntry.incErrors("Error committing changes ", e);
		}
	}

	public void commitChangesWithWait(){
		try {
			updateServer.commit(false, false, true);
		}catch (Exception e) {
			logEntry.incErrors("Error committing changes ", e);
		}
	}

	/**
	 * This is called from all the indexers, so we would like to prevent scheduled works from being processed multiple times.
	 * Rather than getting a list of all the scheduled works, we will process up to max works to process by getting the oldest record
	 * continually, marking it as processed and then processing another.  The exception to this is during the full index
	 * when we will process everything to ensure that records that have been regrouped will get processed during the full index.
	 *
	 * @param logEntry the log entry where results are written to
	 * @param doLogging if logging should be done
	 * @param maxWorksToProcess the maximum number of works that should be processed this run.
	 */
	public void processScheduledWorks(BaseIndexingLogEntry logEntry, boolean doLogging, int maxWorksToProcess) {
		//Check to see what records still need to be indexed based on a timed index
		if (doLogging) {
			logEntry.addNote("Checking for additional works that need to be indexed");
		}

		try {
			int numWorksProcessed = 0;
			long startTime = new Date().getTime() / 1000;

			getNumScheduledWorksStmt.setLong(1, startTime);
			ResultSet numScheduledWorksRS = getNumScheduledWorksStmt.executeQuery();
			int numScheduledWorks = 0;
			if (numScheduledWorksRS.next()) {
				numScheduledWorks = numScheduledWorksRS.getInt("numScheduledWorks");
				if (numScheduledWorks > 0) {
					logEntry.addNote("There are " + numScheduledWorks + " scheduled works to be indexed");
					logEntry.saveResults();
				}else{
					return;
				}
			}

			if (maxWorksToProcess == -1){
				maxWorksToProcess = numScheduledWorks;
			}else{
				maxWorksToProcess = Math.min(numScheduledWorks, maxWorksToProcess);
			}

			while (numWorksProcessed < maxWorksToProcess) {
				getScheduledWorksStmt.setLong(1, startTime);
				ResultSet scheduledWorksRS = getScheduledWorksStmt.executeQuery();
				if (scheduledWorksRS.next()) {
					String workToProcess = scheduledWorksRS.getString("permanent_id");

					markScheduledWorkProcessedStmt.setString(1, workToProcess);
					markScheduledWorkProcessedStmt.setLong(2, new Date().getTime() / 1000);
					markScheduledWorkProcessedStmt.executeUpdate();

					//reindex the actual work
					try {
						this.processGroupedWork(workToProcess, true);
					}catch (Exception e){
						logEntry.incErrors("Error processing scheduled work " + workToProcess, e);
					}

					numWorksProcessed++;
					scheduledWorksRS.close();
				}else{
					scheduledWorksRS.close();
					break;
				}
				if (numWorksProcessed % 10000 == 0) {
					this.commitChanges();
				}
			}
			if (numWorksProcessed > 0){
				if (doLogging) {
					logEntry.addNote("Processed " + numWorksProcessed + " works that were scheduled for indexing");
				}
				this.commitChanges();
			}
		}catch (Exception e){
			logEntry.addNote("Error updating scheduled works " + e);
		}
	}

	void finishIndexing(){
		this.processScheduledWorks(logEntry, true, -1);
		logEntry.addNote("Finishing indexing");
		if (fullReindex) {
			try {
				logEntry.addNote("Calling final commit");
				updateServer.commit(false, false, true);
			} catch (Exception e) {
				logEntry.incErrors("Error calling final commit", e);
			}
			if (indexVersion == 2 && searchVersion == 1){
				//Update the search version to version 2
				try {
					logEntry.addNote("Updating search version to version 2");
					dbConn.prepareStatement("UPDATE system_variables set searchVersion = 2").executeUpdate();
				} catch (Exception e) {
					logEntry.incErrors("Error updating search version", e);
				}
			}
			if (regroupAllRecords){
				try {
					logEntry.addNote("Turning off regroupAllRecords");
					dbConn.prepareStatement("UPDATE system_variables set regroupAllRecordsDuringNightlyIndex = 0").executeUpdate();
				} catch (Exception e) {
					logEntry.incErrors("Error turning off regroupAllRecords", e);
				}
			}
			if (processEmptyGroupedWorks){
				try {
					logEntry.addNote("Turning off processEmptyGroupedWorks");
					dbConn.prepareStatement("UPDATE system_variables set processEmptyGroupedWorks = 0").executeUpdate();
				} catch (Exception e) {
					logEntry.incErrors("Error turning off processEmptyGroupedWorks", e);
				}
			}

			updateLastReindexTime();
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
				getAllGroupedWorks = dbConn.prepareStatement("SELECT grouped_work.id, permanent_id, grouping_category, date_updated FROM grouped_work INNER JOIN grouped_work_records on grouped_work.id = groupedWorkId GROUP BY permanent_id;", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
				getNumWorksToIndex = dbConn.prepareStatement("SELECT COUNT(DISTINCT permanent_id) as numWorksWithRecords FROM grouped_work INNER JOIN grouped_work_records on grouped_work.id = groupedWorkId;", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
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
					//However, we can't do it too often, or we get errors with too many searchers warming.
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
					setLastUpdatedTime.setLong(1, indexStartTime - 1); //Set just before the index started, so we don't index multiple times
					setLastUpdatedTime.setLong(2, id);
					setLastUpdatedTime.executeUpdate();
				}
			}
			groupedWorks.close();
			setLastUpdatedTime.close();

			if (logEntry instanceof NightlyIndexLogEntry){
				logEntry.addNote("Used Author authorities a total of " + getRecordGroupingProcessor().getNumAuthoritiesUsed() + " times");
			}

			if (processEmptyGroupedWorks) {
				processEmptyGroupedWorks();
			}

		} catch (SQLException e) {
			logEntry.incErrors("Unexpected SQL error", e);
		}
		logger.info("Finished processing grouped works.  Processed a total of " + numWorksProcessed + " grouped works");
	}

	protected void processEmptyGroupedWorks() throws SQLException {
		PreparedStatement getEmptyGroupedWorksStmt = dbConn.prepareStatement("SELECT grouped_work.id as grouped_work_id, permanent_id, grouping_category, count(grouped_work_records.id) as numRecords FROM grouped_work LEFT JOIN grouped_work_records on grouped_work.id = groupedWorkId where length(permanent_id) > 36 GROUP BY permanent_id having numRecords = 0;", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
		PreparedStatement getPrimaryIdentifiersForGroupedWorkStmt = dbConn.prepareStatement("SELECT count(*) as numIdentifiers from grouped_work_primary_identifiers where grouped_work_id = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
		logEntry.addNote("Starting to process grouped works with no records attached to them.");

		ResultSet emptyGroupedWorksRS = getEmptyGroupedWorksStmt.executeQuery();
		int numDeleted = 0;
		boolean localRegroupAll = this.regroupAllRecords;
		setRegroupAllRecords(true);
		logEntry.addNote("Preparing to process empty grouped works");
		logEntry.saveResults();

		int numProcessed = 0;
		while (emptyGroupedWorksRS.next()) {
			long groupedWorkId = emptyGroupedWorksRS.getLong("grouped_work_id");
			String permanentId = emptyGroupedWorksRS.getString("permanent_id");

			getPrimaryIdentifiersForGroupedWorkStmt.setLong(1, groupedWorkId);
			ResultSet numPrimaryIdentifiersRS = getPrimaryIdentifiersForGroupedWorkStmt.executeQuery();
			boolean hasIdentifiersAttached = false;
			if (numPrimaryIdentifiersRS.next()) {
				if (numPrimaryIdentifiersRS.getLong("numIdentifiers") > 0) {
					hasIdentifiersAttached = true;
				}
			}

			if (hasIdentifiersAttached) {
				processGroupedWork(groupedWorkId, permanentId, emptyGroupedWorksRS.getString("grouping_category"));
			}else {
				deleteRecord(permanentId, groupedWorkId);
				numDeleted++;
				if (numDeleted % 10000 == 0) {
					try {
						updateServer.commit(false, false, true);
					} catch (Exception e) {
						logger.warn("Error committing changes", e);
					}
				}
			}
			numProcessed++;
			if (numProcessed % 1000 == 0) {
				logEntry.addNote("Processed " + numProcessed);
			}
		}
		setRegroupAllRecords(localRegroupAll);
		logEntry.addNote("Finished processing empty grouped works, processed " + numProcessed + ".");
		logEntry.saveResults();
	}

	public synchronized void processGroupedWork(String permanentId) {
		processGroupedWork(permanentId, true);
	}

	public synchronized void processGroupedWork(String permanentId, boolean allowRegrouping) {
		try{
			getGroupedWorkInfoStmt.setString(1, permanentId);
			ResultSet getGroupedWorkInfoRS = getGroupedWorkInfoStmt.executeQuery();
			if (getGroupedWorkInfoRS.next()) {
				long id = getGroupedWorkInfoRS.getLong("id");
				String grouping_category = getGroupedWorkInfoRS.getString("grouping_category");
				processGroupedWork(id, permanentId, grouping_category, allowRegrouping);
			}
			getGroupedWorkInfoRS.close();
			totalRecordsHandled++;
			if (totalRecordsHandled % 1000 == 0) {
				updateServer.commit(false, false, true);
			}
		} catch (Exception e) {
			logEntry.incErrors("Error indexing grouped work " + permanentId + " by id", e);
		}

	}

	synchronized void processGroupedWork(Long id, String permanentId, String grouping_category) throws SQLException {
		processGroupedWork(id, permanentId, grouping_category, true);
	}

	synchronized void processGroupedWork(Long id, String permanentId, String grouping_category, boolean allowRegrouping) throws SQLException {
		//Create a solr record for the grouped work
		AbstractGroupedWorkSolr groupedWork;
		if (indexVersion == 2) {
			groupedWork = new GroupedWorkSolr2(this, logger);
		}else{
			groupedWork = new GroupedWorkSolr(this, logger);
		}

		//Check to see if we should enable debugging while indexing
		getDebugInfoStmt.setString(1, permanentId);
		ResultSet getDebugInfoRS = getDebugInfoStmt.executeQuery();
		if (getDebugInfoRS.next()) {
			groupedWork.setDebugEnabled(true);
			groupedWork.setDebugId(getDebugInfoRS.getLong("id"));
		}
		getDebugInfoRS.close();

		groupedWork.setId(permanentId);
		groupedWork.setGroupingCategory(grouping_category);

		getGroupedWorkPrimaryIdentifiers.setLong(1, id);
		ResultSet groupedWorkPrimaryIdentifiersRS = getGroupedWorkPrimaryIdentifiers.executeQuery();
		ArrayList<RecordIdentifier> recordIdentifiers = new ArrayList<>();
		while (groupedWorkPrimaryIdentifiersRS.next()){
			String type = groupedWorkPrimaryIdentifiersRS.getString("type");
			String identifier = groupedWorkPrimaryIdentifiersRS.getString("identifier");
			recordIdentifiers.add(new RecordIdentifier(type, identifier));
		}
		groupedWorkPrimaryIdentifiersRS.close();
		int numPrimaryIdentifiers = 0;
		HashSet<String> regroupedIdsToProcess = new HashSet<>();
		HashSet<RecordIdentifier> regroupedIdentifiers = new HashSet<>();

		if ((regroupAllRecords && allowRegrouping) || permanentId.endsWith("|||") || permanentId.contains(" ")){
			if (groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Starting to regroup all records");}
			for (RecordIdentifier recordIdentifier : recordIdentifiers) {
				String type = recordIdentifier.getType();
				String identifier = recordIdentifier.getIdentifier();

				//Get the proper record grouper
				String newId = permanentId;
				if (ilsRecordGroupers.containsKey(type)) {
					MarcRecordGrouper ilsGrouper = ilsRecordGroupers.get(type);
					org.marc4j.marc.Record record = loadMarcRecordFromDatabase(type, identifier, logEntry);
					if (record == null) {
						RemoveRecordFromWorkResult result = getRecordGroupingProcessor().removeRecordFromGroupedWork(type, identifier);
						if (result.reindexWork) {
							regroupedIdsToProcess.add(result.permanentId);
						} else if (result.deleteWork) {
							//Delete the work from solr and the database
							deleteRecord(result.permanentId, result.groupedWorkId);
						}
						regroupedIdentifiers.add(recordIdentifier);
					} else {
						newId = ilsGrouper.processMarcRecord(record, false, permanentId, this);
					}
				} else if (sideLoadRecordGroupers.containsKey(type)) {
					SideLoadedRecordGrouper sideLoadGrouper = sideLoadRecordGroupers.get(type);
					org.marc4j.marc.Record record = loadMarcRecordFromDatabase(type, identifier, logEntry);
					if (record == null) {
						RemoveRecordFromWorkResult result = getRecordGroupingProcessor().removeRecordFromGroupedWork(type, identifier);
						if (result.reindexWork) {
							regroupedIdsToProcess.add(result.permanentId);
						} else if (result.deleteWork) {
							//Delete the work from solr and the database
							deleteRecord(result.permanentId, result.groupedWorkId);
						}
						regroupedIdentifiers.add(recordIdentifier);
					} else {
						newId = sideLoadGrouper.processMarcRecord(record, false, permanentId, this);
					}
				} else if (type.equals("overdrive")) {
					newId = overDriveRecordGrouper.processOverDriveRecord(identifier);
				} else if (type.equals("axis360")) {
					newId = getRecordGroupingProcessor().groupAxis360Record(identifier);
				} else if (type.equals("cloud_library")) {
					org.marc4j.marc.Record cloudLibraryRecord = loadMarcRecordFromDatabase("cloud_library", identifier, logEntry);
					if (cloudLibraryRecord == null) {
						RemoveRecordFromWorkResult result = getRecordGroupingProcessor().removeRecordFromGroupedWork(type, identifier);
						if (result.reindexWork) {
							regroupedIdsToProcess.add(result.permanentId);
						} else if (result.deleteWork) {
							//Delete the work from solr and the database
							deleteRecord(result.permanentId, result.groupedWorkId);
						}
					} else {
						newId = getRecordGroupingProcessor().groupCloudLibraryRecord(identifier, cloudLibraryRecord);
					}
				} else if (type.equals("hoopla")) {
					newId = getRecordGroupingProcessor().groupHooplaRecord(identifier);
				} else if (type.equals("palace_project")) {
					newId = getRecordGroupingProcessor().groupPalaceProjectRecord(identifier);
				}
				if (newId == null) {
					//The record is not valid, skip it.
					RemoveRecordFromWorkResult result = getRecordGroupingProcessor().removeRecordFromGroupedWork(type, identifier);
					if (result.reindexWork) {
						regroupedIdsToProcess.add(result.permanentId);
					} else if (result.deleteWork) {
						//Delete the work from solr and the database
						deleteRecord(result.permanentId, result.groupedWorkId);
					}
					regroupedIdentifiers.add(recordIdentifier);
				} else if (!newId.equals(permanentId)) {
					//The work will be marked as updated and therefore re-indexed at the end
					//Or just index it now?
					regroupedIdsToProcess.add(newId);
					regroupedIdentifiers.add(recordIdentifier);
				}
			}
		}

		recordIdentifiers.removeAll(regroupedIdentifiers);

		for (RecordIdentifier recordIdentifier : recordIdentifiers){
			String type = recordIdentifier.getType();
			String identifier = recordIdentifier.getIdentifier();
			if (groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Processing record " + type + ":" + identifier, 1);}

			//Make a copy of the grouped work, so we can revert if we don't add any records
			AbstractGroupedWorkSolr originalWork;
			try {
				originalWork = groupedWork.clone();
			}catch (CloneNotSupportedException cne){
				logEntry.incErrors("Could not clone grouped work", cne);
				return;
			}
			//Figure out how many records we had originally
			int numRecords = groupedWork.getNumRecords();

			//This does the bulk of the work building fields for the solr document
			updateGroupedWorkForPrimaryIdentifier(groupedWork, type, identifier);

			//If we didn't add any records to the work (because they are all suppressed) revert to the original
			if (groupedWork.getNumRecords() == numRecords){
				//No change in the number of records, revert to the previous
				if (groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Record " + type + ":" + identifier + " did not contribute any records to work " + permanentId + ", reverting to previous state", 1);}
				groupedWork = originalWork;
			}else{
				numPrimaryIdentifiers++;
			}
		}

		if (numPrimaryIdentifiers > 0) {
			//Strip out any hoopla records that have the same format as another econtent record with apis
			groupedWork.removeRedundantHooplaRecords();

			//Load local enrichment for the work
			loadLocalEnrichment(groupedWork);
			//Load links for how users have interacted with the work
			loadUserLinkages(groupedWork);
			//Load lexile data for the work
			loadLexileDataForWork(groupedWork);
			//Load accelerated reader data for the work
			loadAcceleratedDataForWork(groupedWork);
			//Update Series index data
			updateSeriesDataForWork(groupedWork);
			// else use Novelist the old way
			//Load Novelist data
			loadNovelistInfo(groupedWork);
			//Load Display Info
			loadDisplayInfo(groupedWork);

			//Write the record to Solr.
			try {
				if (this.isStoreRecordDetailsInDatabase()) {
					groupedWork.saveRecordsToDatabase(id);
				}

				//Check to see if the grouped work has parent records and if so, skip it.
				SolrInputDocument inputDocument = groupedWork.getSolrDocument(logEntry);
				if (inputDocument == null) {
					logEntry.incErrors("Solr Input document was null for " + groupedWork.getId());
				} else {
					UpdateResponse response = updateServer.add(inputDocument);
					if (response == null) {
						logEntry.incErrors("Error adding Solr record for " + groupedWork.getId() + ", the response was null");
					} else if (response.getException() != null) {
						logEntry.incErrors("Error adding Solr record for " + groupedWork.getId() + " response: " + response);
					}

					//Check to see if we need to automatically reindex this record in the future.
					//Reindexing in the future is done if the time to reshelve is set to ensure that we reindex when that time expires.
					try {
						HashSet<Long> autoReindexTimes = groupedWork.getAutoReindexTimes();
						if (!autoReindexTimes.isEmpty()) {
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
						logEntry.incErrors("Error setting auto reindex times", e);
					}
				}
			} catch (IOException e) {
				logEntry.incErrors("Error adding grouped work to solr " + groupedWork.getId() + " quitting", e);
				System.exit(-8);
			} catch (Exception e) {
				logEntry.incErrors("Error adding grouped work to solr " + groupedWork.getId(), e);
			}
		}else{
			//Log that this record did not have primary identifiers after
			if (groupedWork.isDebugEnabled()) {groupedWork.addDebugMessage("Grouped work " + permanentId + " did not have any primary identifiers for it, suppressing", 1);}
			if (!this.clearIndex){
				this.deleteRecord(permanentId, id);
			}
		}

		try {
			//mark that the work has been processed, so we don't reprocess it later
			markScheduledWorkProcessedStmt.setString(1, permanentId);
			markScheduledWorkProcessedStmt.setLong(2, new Date().getTime() / 1000);
			markScheduledWorkProcessedStmt.executeUpdate();
		}catch (SQLException e){
			logEntry.incErrors("Error marking that the record has been processed.", e);
		}

		for (String regroupedId : regroupedIdsToProcess){
			if (!regroupedId.equals(permanentId)){
				processGroupedWork(regroupedId, false);
			}
		}

		if (groupedWork.isDebugEnabled()) {
			updateDebuggingInfoStmt.setString(1, groupedWork.getDebuggingInfo());
			updateDebuggingInfoStmt.setLong(2, new Date().getTime() / 1000);
			updateDebuggingInfoStmt.setLong(3, groupedWork.getDebugId());
			updateDebuggingInfoStmt.executeUpdate();
		}
	}

	private void loadLexileDataForWork(AbstractGroupedWorkSolr groupedWork) {
		for(String isbn : groupedWork.getIsbns()){
			if (lexileInformation.containsKey(isbn)){
				LexileTitle lexileTitle = lexileInformation.get(isbn);
				String lexileCode = lexileTitle.getLexileCode();
				if (!lexileCode.isEmpty()){
					groupedWork.setLexileCode(this.translateSystemValue("lexile_code", lexileCode, groupedWork.getId()));
				}
				groupedWork.setLexileScore(lexileTitle.getLexileScore());
				groupedWork.addAwards(lexileTitle.getAwards());
				if (!lexileTitle.getSeries().isEmpty()){
					groupedWork.addSeries(lexileTitle.getSeries());
				}
				break;
			}
		}
	}

	private void loadAcceleratedDataForWork(AbstractGroupedWorkSolr groupedWork){
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
						if (!bookLevel.isEmpty()){
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

	private void loadLocalEnrichment(AbstractGroupedWorkSolr groupedWork) {
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

	private void loadUserLinkages(AbstractGroupedWorkSolr groupedWork) {
		try {
			loadReadingHistoryLinksForUsers(groupedWork);
			loadRatingLinksForUsers(groupedWork);
			loadNotInterestedLinksForUsers(groupedWork);
		}catch (Exception e){
			logEntry.incErrors("Unable to load user linkages", e);
		}
	}

	private void loadNotInterestedLinksForUsers(AbstractGroupedWorkSolr groupedWork) throws SQLException {
		//Add users who are not interested in the title
		getUserNotInterestedLinkStmt.setString(1, groupedWork.getId());
		ResultSet userNotInterestedRS = getUserNotInterestedLinkStmt.executeQuery();
		while (userNotInterestedRS.next()) {
			groupedWork.addNotInterestedLink(userNotInterestedRS.getLong("userId"));
		}
		userNotInterestedRS.close();
	}

	private void loadRatingLinksForUsers(AbstractGroupedWorkSolr groupedWork) throws SQLException {
		//Add users who rated the title
		getUserRatingLinkStmt.setString(1, groupedWork.getId());
		ResultSet userRatingRS = getUserRatingLinkStmt.executeQuery();
		while (userRatingRS.next()){
			groupedWork.addRatingLink(userRatingRS.getLong("userId"));
		}
		userRatingRS.close();
	}

	private void loadReadingHistoryLinksForUsers (AbstractGroupedWorkSolr groupedWork) throws SQLException {
		//Add users with the work in their reading history
		getUserReadingHistoryLinkStmt.setString(1, groupedWork.getId());
		ResultSet userReadingHistoryRS = getUserReadingHistoryLinkStmt.executeQuery();
		while (userReadingHistoryRS.next()){
			groupedWork.addReadingHistoryLink(userReadingHistoryRS.getLong("userId"));
		}
		userReadingHistoryRS.close();
	}

	private void loadNovelistInfo(AbstractGroupedWorkSolr groupedWork){
		if (enableNovelistSeriesIntegration) {
			try {
				getNovelistStmt.setString(1, groupedWork.getId());
				ResultSet novelistRS = getNovelistStmt.executeQuery();
				if (novelistRS.next()) {
					String series = novelistRS.getString("seriesTitle");
					if (!novelistRS.wasNull()) {
						//Don't clear since there are valid cases when they are different
						String volume = novelistRS.getString("volume");
						if (novelistRS.wasNull()) {
							volume = "";
						}
						groupedWork.addSeriesWithVolume(series, volume);
					}
				}
				novelistRS.close();
			} catch (Exception e) {
				logEntry.incErrors("Unable to load novelist data", e);
			}
		}
	}

	private void updateSeriesDataForWork(AbstractGroupedWorkSolr groupedWork) {
		try {
			if (seriesModuleEnabled) {
				getSeriesMemberStmt.setString(1, groupedWork.getId());
				ResultSet seriesMemberRS = getSeriesMemberStmt.executeQuery();
				HashMap<String, Integer> seriesInDb = new HashMap<>();
				while (seriesMemberRS.next()) {
					// Strip diacritics and switch to lowercase for comparing series names to minimize duplicates
					String seriesTitle = seriesMemberRS.getString("groupedWorkSeriesTitle");
					if (seriesTitle == null) {
						//If the grouped work series title is null, this is a manually created series, so we should skip it.
						continue;
					}
					String normalizedSeriesName = Normalizer.normalize(seriesTitle.toLowerCase(), Normalizer.Form.NFKD).replaceAll("\\p{M}", "");
					seriesInDb.put(normalizedSeriesName, seriesMemberRS.getInt("seriesId"));
				}
				for (String seriesNameWithVolume : groupedWork.seriesWithVolume.keySet()) {
					String[] series = seriesNameWithVolume.split("\\|");
					String normalizedSeriesName = Normalizer.normalize(series[0], Normalizer.Form.NFKD).replaceAll("\\p{M}", "");
					if (!seriesInDb.containsKey(normalizedSeriesName)) { // Skip if this work is already in the series
						// Check if series exists
						getSeriesStmt.setString(1, series[0]);
						ResultSet seriesRS = getSeriesStmt.executeQuery();
						long timeNow = new Date().getTime() / 1000;
						long seriesId;
						if (seriesRS.next()) { // Should only be one match
							seriesId = seriesRS.getLong("id");
							// Check to see if we need to add additional authors
							String authors = seriesRS.getString("author");
							String newAuthor = groupedWork.getPrimaryAuthor();
							if (newAuthor != null) {
								updateSeriesAuthor.setLong(2, seriesId);
								if (authors == null) {
									updateSeriesAuthor.setString(1, newAuthor);
									updateSeriesAuthor.executeUpdate();
								} else if (!authors.equals("Various") && !authors.contains(newAuthor)) {
									// If we already have three authors, change author to Various
									if (authors.split("\\|").length == 3) {
										authors = "Various";
									} else {
										authors = authors + "|" + newAuthor;
									}
									updateSeriesAuthor.setString(1, authors);
									updateSeriesAuthor.executeUpdate();
								}
							}
							addSeriesMemberStmt.setLong(1, seriesId);
						} else {
							// Add the series first
							String[] displayTitle = groupedWork.seriesWithVolume.get(seriesNameWithVolume).split("\\|");
							addSeriesStmt.setString(1, displayTitle[0]); //displayTitle (user can edit)
							addSeriesStmt.setString(6, displayTitle[0]); //groupedWorkSeriesTitle (to match on)
							addSeriesStmt.setString(2, groupedWork.getTargetAudiencesAsString());
							addSeriesStmt.setLong(3, timeNow);
							addSeriesStmt.setLong(4, timeNow);
							addSeriesStmt.setString(5, groupedWork.getPrimaryAuthor());
							addSeriesStmt.executeUpdate();
							ResultSet generatedKeys = addSeriesStmt.getGeneratedKeys();
							if (generatedKeys.next()) {
								seriesId = generatedKeys.getLong(1);
								addSeriesMemberStmt.setLong(1, seriesId);
							} else {
								seriesId = -1;
							}
							generatedKeys.close();
						}
						addSeriesMemberStmt.setString(2, groupedWork.getId());
						if (series.length == 2) {
							addSeriesMemberStmt.setString(3, series[1]); // Add volume
							addSeriesMemberStmt.setLong(8, NumberUtils.toLong(series[1])); // Add volume as weight if it's an integer - 0 otherwise
						} else {
							addSeriesMemberStmt.setString(3, "");
							addSeriesMemberStmt.setLong(8, 0);
						}
						addSeriesMemberStmt.setLong(4, groupedWork.earliestPublicationDate != null ? groupedWork.earliestPublicationDate : 0);
						addSeriesMemberStmt.setString(5, groupedWork.displayTitle);
						addSeriesMemberStmt.setString(6, groupedWork.getPrimaryAuthor());
						addSeriesMemberStmt.setString(7, groupedWork.displayDescription);
						addSeriesMemberStmt.executeUpdate();
						if (seriesId >= 0) {
							setSeriesDateUpdated.setLong(1, timeNow);
							setSeriesDateUpdated.setLong(2, seriesId);
							setSeriesDateUpdated.executeUpdate();
						}
						seriesRS.close();

					} else {
						seriesInDb.remove(normalizedSeriesName); // Remove since we have accounted for it
					}
				}
				if (!seriesInDb.isEmpty()) {
					// Remove entries from series_member table when this work is no longer part of the series
					for (int seriesId : seriesInDb.values()) {
						deleteSeriesMemberStmt.setInt(1, seriesId);
						deleteSeriesMemberStmt.setString(2, groupedWork.id);
						int result = deleteSeriesMemberStmt.executeUpdate();
						// Also delete the series if it no longer has any members
						if (result != 0) {
							deleteSeriesStmt.setInt(1, seriesId); // Deletes if it only had 1 member (this work)
							deleteSeriesStmt.executeUpdate();
						}
					}
				}
				seriesMemberRS.close();
			}
		} catch (Exception e) {
			logEntry.incErrors("Unable to update series data for grouped work " + groupedWork.getId(), e);
		}
	}

	private void loadDisplayInfo(AbstractGroupedWorkSolr groupedWork) {
		try {
			getDisplayInfoStmt.setString(1, groupedWork.getId());
			ResultSet displayInfoRS = getDisplayInfoStmt.executeQuery();
			if (displayInfoRS.next()) {
				String title = displayInfoRS.getString("title");
				if (!title.isEmpty()){
					groupedWork.setTitle(title, "", title, AspenStringUtils.makeValueSortable(title), "", true);
					groupedWork.clearSubTitle();
				}
				String author = displayInfoRS.getString("author");
				if (!author.isEmpty()){
					//Force a format category of Books since we want to preserve this
					groupedWork.setAuthorDisplay(author, "Books");
				}
				String seriesName = displayInfoRS.getString("seriesName");
				String seriesDisplayOrder = displayInfoRS.getString("seriesDisplayOrder");
				if (!seriesName.isEmpty()) {
					groupedWork.clearSeries();
					groupedWork.addSeries(seriesName);
					if (!seriesDisplayOrder.isEmpty()) {
						groupedWork.addSeriesWithVolume(seriesName, seriesDisplayOrder);
					}
				}
			}
			displayInfoRS.close();
		}catch (Exception e){
			logEntry.incErrors("Unable to load display info", e);
		}
	}

	private void updateGroupedWorkForPrimaryIdentifier(AbstractGroupedWorkSolr groupedWork, String type, String identifier)  {
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
			case "palace_project":
				palaceProjectProcessor.processRecord(groupedWork, identifier, logEntry);
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
			FileReader translationMapReader = new FileReader(translationMapFile);
			props.load(translationMapReader);
			translationMapReader.close();
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
			if (translatedValue.isEmpty()){
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

	private void loadHideSubjects() {
		try {
			ResultSet hideSubjectsRS = getHideSubjectsStmt.executeQuery();
			while (hideSubjectsRS.next()) {
				hideSubjects.add(hideSubjectsRS.getString("subjectNormalized"));
			}
		} catch (SQLException e) {
			logEntry.incErrors("Error loading subjects to hide: ", e);
		}
	}

	private void loadHideSeries() {
		try {
			ResultSet hideSeriesRS = getHideSeriesStmt.executeQuery();
			while (hideSeriesRS.next()) {
				hideSeries.add(hideSeriesRS.getString("seriesNormalized").toLowerCase());
			}
		} catch (SQLException e) {
			logEntry.incErrors("Error loading series to hide: ", e);
		}
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
				String key = getExistingRecordsForWorkRS.getString("sourceId") + ":" + getExistingRecordsForWorkRS.getString("recordIdentifier"); // + ":" + getExistingRecordsForWorkRS.getLong("formatId");
				existingRecords.put(key, new SavedRecordInfo(getExistingRecordsForWorkRS));
			}
		} catch (SQLException e) {
			logEntry.incErrors("Error loading existing records for grouped works", e);
		}

		return existingRecords;
	}

	public synchronized long saveGroupedWorkRecord(long groupedWorkId, RecordInfo recordInfo, SavedRecordInfo existingRecord) {
		long recordId = -1;
		try {
			if (existingRecord == null) {
				addRecordForWorkStmt.setLong(1, groupedWorkId);
				long sourceId = getSourceId(recordInfo.getSource(), recordInfo.getSubSource(), 1);
				addRecordForWorkStmt.setLong(2, sourceId);
				addRecordForWorkStmt.setString(3, recordInfo.getRecordIdentifier());
				addRecordForWorkStmt.setLong(4, getEditionId(recordInfo.getEdition(), 1));
				addRecordForWorkStmt.setLong(5, getPublisherId(recordInfo.getPublisher(), recordInfo.getRecordIdentifier(), 1));
				addRecordForWorkStmt.setLong(6, getPublicationDateId(recordInfo.getPublicationDate(), 1));
				addRecordForWorkStmt.setLong(7, getPlaceOfPublicationId(recordInfo.getPlaceOfPublication(), 1));
				addRecordForWorkStmt.setLong(8, getPhysicalDescriptionId(recordInfo.getPhysicalDescription(), 1));
				addRecordForWorkStmt.setLong(9, getFormatId(recordInfo.getPrimaryFormat(), 1));
				addRecordForWorkStmt.setLong(10, getFormatCategoryId(recordInfo.getPrimaryFormatCategory(), 1));
				addRecordForWorkStmt.setLong(11, getLanguageId(recordInfo.getPrimaryLanguage(), 1));
				addRecordForWorkStmt.setBoolean(12, recordInfo.isClosedCaptioned());
				addRecordForWorkStmt.setBoolean(13, recordInfo.hasParentRecord());
				addRecordForWorkStmt.setBoolean(14, recordInfo.hasChildRecord());
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
				long editionId = getEditionId(recordInfo.getEdition(), 1);
				long publisherId = getPublisherId(recordInfo.getPublisher(), recordInfo.getRecordIdentifier(), 1);
				long publicationDateId = getPublicationDateId(recordInfo.getPublicationDate(), 1);
				long physicalDescriptionId = getPhysicalDescriptionId(recordInfo.getPhysicalDescription(), 1);
				long placeOfPublicationId = getPlaceOfPublicationId(recordInfo.getPlaceOfPublication(), 1);
				long formatId = getFormatId(recordInfo.getPrimaryFormat(), 1);
				long formatCategoryId = getFormatCategoryId(recordInfo.getPrimaryFormatCategory(), 1);
				long languageId = getLanguageId(recordInfo.getPrimaryLanguage(), 1);
				boolean isClosedCaptioned = recordInfo.isClosedCaptioned();
				boolean hasParentRecord = recordInfo.hasParentRecord();
				boolean hasChildRecord = recordInfo.hasChildRecord();
				if (groupedWorkId != existingRecord.groupedWorkId) { hasChanges = true; }
				if (editionId != existingRecord.editionId) { hasChanges = true; }
				if (publisherId != existingRecord.publisherId) { hasChanges = true; }
				if (publicationDateId != existingRecord.publicationDateId) { hasChanges = true; }
				if (placeOfPublicationId != existingRecord.placeOfPublicationId) {hasChanges = true; }
				if (physicalDescriptionId != existingRecord.physicalDescriptionId) { hasChanges = true; }
				if (formatId != existingRecord.formatId) { hasChanges = true; }
				if (formatCategoryId != existingRecord.formatCategoryId) { hasChanges = true; }
				if (languageId != existingRecord.languageId) { hasChanges = true; }
				if (isClosedCaptioned != existingRecord.isClosedCaptioned) { hasChanges = true; }
				if (hasParentRecord != existingRecord.hasParentRecord) { hasChanges = true; }
				if (hasChildRecord != existingRecord.hasChildRecord) { hasChanges = true; }
				if (hasChanges){
					updateRecordForWorkStmt.setLong(1, groupedWorkId);
					updateRecordForWorkStmt.setLong(2, editionId);
					updateRecordForWorkStmt.setLong(3, publisherId);
					updateRecordForWorkStmt.setLong(4, publicationDateId);
					updateRecordForWorkStmt.setLong(5, placeOfPublicationId);
					updateRecordForWorkStmt.setLong(6, physicalDescriptionId);
					updateRecordForWorkStmt.setLong(7, formatId);
					updateRecordForWorkStmt.setLong(8, formatCategoryId);
					updateRecordForWorkStmt.setLong(9, languageId);
					updateRecordForWorkStmt.setBoolean(10, isClosedCaptioned);
					updateRecordForWorkStmt.setBoolean(11, hasParentRecord);
					updateRecordForWorkStmt.setBoolean(12, hasChildRecord);
					updateRecordForWorkStmt.setLong(13, existingRecord.id);
					updateRecordForWorkStmt.executeUpdate();
				}
			}
		} catch (SQLException e) {
			logEntry.incErrors("Error saving grouped work record", e);
		}

		return recordId;
	}

	private final HashMap<String, Long> sourceIds = new HashMap<>();
	long getSourceId(String source, String subSource, int numTries) {
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
				//Another thread already created it, call it again
				if (numTries == 1) {
					return getSourceId(source, subSource, numTries + 1);
				}else {
					logEntry.incErrors("Error getting source id", e);
					sourceId = -1L;
				}
			}
			sourceIds.put(key, sourceId);
		}
		return sourceId;
	}

	private final HashMap<String, Long> formatCategoryIds = new HashMap<>();
	private long getFormatCategoryId(String formatCategory, int numTries) {
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
				//Another thread already created it, call it again
				if (numTries == 1) {
					return getFormatCategoryId(formatCategory, numTries + 1);
				}else {
					logEntry.incErrors("Error getting format category id", e);
					id = -1L;
				}
			}
			formatCategoryIds.put(formatCategory, id);
		}
		return id;
	}

	private final HashMap<String, Long> formatIds = new HashMap<>();
	protected long getFormatId(String format, int numTries) {
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
				//Another thread already created it, call it again
				if (numTries == 1) {
					return getFormatId(format, numTries + 1);
				}else {
					logEntry.incErrors("Error getting format id", e);
					id = -1L;
				}
			}
			formatIds.put(format, id);
		}
		return id;
	}

	private final HashMap<String, Long> languageIds = new HashMap<>();
	private long getLanguageId(String language, int numTries) {
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
				//Another thread already created it, call it again
				if (numTries == 1) {
					return getLanguageId(language, numTries + 1);
				}else {
					logEntry.incErrors("Error getting language id", e);
					id = -1L;
				}
			}
			languageIds.put(language, id);
		}
		return id;
	}

	private final MaxSizeHashMap<String, Long> editionIds = new MaxSizeHashMap<>(1000);
	private long getEditionId(String edition, int numTries) {
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
				//Another thread already created it, call it again
				if (numTries == 1) {
					return getEditionId(edition, numTries + 1);
				}else {
					logEntry.incErrors("Error getting edition id for edition (" + edition.length() + "): " + edition, e);
					id = -1L;
				}
			}
			editionIds.put(edition, id);
		}
		return id;
	}

	private final MaxSizeHashMap<String, Long> publisherIds = new MaxSizeHashMap<>(1000);
	private long getPublisherId(String publisher, String recordIdentifier, int numTries) {
		if (publisher == null){
			return -1;
		}
		if (publisher.length() > 500) {
			logEntry.incInvalidRecords("Publisher for record " + recordIdentifier + " was more than 500 characters (" + publisher.length() + ") " + publisher);
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
				//Another thread already created it, call it again
				if (numTries == 1) {
					return getPublisherId(publisher, recordIdentifier, numTries + 1);
				}else {
					logEntry.incErrors("Error getting publisher id (" + publisher.length() + ") " + publisher, e);
					id = -1L;
				}
			}
			publisherIds.put(publisher, id);
		}
		return id;
	}

	private final MaxSizeHashMap<String, Long> publicationDateIds = new MaxSizeHashMap<>(1000);
	private long getPublicationDateId(String publicationDate, int numTries) {
		if (publicationDate == null){
			return -1;
		}
		if (publicationDate.length() > 250) {
			publicationDate = publicationDate.substring(0, 250);
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
				//Another thread already created it, call it again
				if (numTries == 1) {
					return getPublicationDateId(publicationDate, numTries + 1);
				}else {
					logEntry.incErrors("Error getting publicationDate id", e);
					id = -1L;
				}
			}
			publicationDateIds.put(publicationDate, id);
		}
		return id;
	}


	private final MaxSizeHashMap<String, Long>placeOfPublicationIds = new MaxSizeHashMap<>(1000);
	private long getPlaceOfPublicationId(String placeOfPublication, int numTries) {
		if(placeOfPublication == null) {
			return -1;
		}
		if (placeOfPublication.length() > 500) {
			placeOfPublication = placeOfPublication.substring(0, 500);
		}
		Long id = placeOfPublicationIds.get(placeOfPublication);
		if (id == null) {
			try {
				getPlaceOfPublicationStmt.setString(1, placeOfPublication);
				ResultSet getPlaceOfPublicationRS = getPlaceOfPublicationStmt.executeQuery();
				if(getPlaceOfPublicationRS.next()) {
					id = getPlaceOfPublicationRS.getLong("id");
				} else {
					addPlaceOfPublicationStmt.setString(1, placeOfPublication);
					addPlaceOfPublicationStmt.executeUpdate();
					ResultSet addPlaceOfPublicationRS = addPlaceOfPublicationStmt.getGeneratedKeys();
					if(addPlaceOfPublicationRS.next()) {
						id = addPlaceOfPublicationRS.getLong(1);
					} else {
						logEntry.incErrors("Could not add placeOfPublication");
						id = -1L;
					}
				}
			} catch (SQLException e) {
				//Another thread already created it, call it again
				if (numTries == 1) {
					return getPlaceOfPublicationId(placeOfPublication, numTries + 1);
				}else {
					logEntry.incErrors("Error getting place of publication (" + placeOfPublication.length() + "):" + placeOfPublication, e);
					id = -1L;
				}
			}
			placeOfPublicationIds.put(placeOfPublication, id);
		}
		return id;
	}


	private final MaxSizeHashMap<String, Long> physicalDescriptionIds = new MaxSizeHashMap<>(1000);
	private long getPhysicalDescriptionId(String physicalDescription, int numTries) {
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
				//Another thread already created it, call it again
				if (numTries == 1) {
					return getPhysicalDescriptionId(physicalDescription, numTries + 1);
				}else {
					logEntry.incErrors("Error getting physicalDescription id (" + physicalDescription.length() + "):" + physicalDescription, e);
					id = -1L;
				}
			}
			physicalDescriptionIds.put(physicalDescription, id);
		}
		return id;
	}

	private final HashMap<String, Long> eContentSourceIds = new HashMap<>();
	private long getEContentSourceId(String eContentSource, int numTries) {
		if (eContentSource == null){
			return -1;
		}
		Long id = eContentSourceIds.get(eContentSource);
		if (id == null){
			try {
				getEContentSourceStmt.setString(1, eContentSource);
				ResultSet getEContentSourceRS = getEContentSourceStmt.executeQuery();
				if (getEContentSourceRS.next()) {
					id = getEContentSourceRS.getLong("id");
				} else {
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
			} catch (SQLIntegrityConstraintViolationException cve) {
				//Another thread already created it, call it again
				if (numTries == 1) {
					return getEContentSourceId(eContentSource, numTries + 1);
				}else{
					logEntry.incErrors("Error getting eContentSource id", cve);
					id = -1L;
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
	private long getShelfLocationId(String shelfLocation, int numTries) {
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
				//Another thread already created it, call it again
				if (numTries == 1) {
					return getShelfLocationId(shelfLocation, numTries + 1);
				}else {
					logEntry.incErrors("Error getting shelfLocation id", e);
					id = -1L;
				}
			}
			shelfLocationIds.put(shelfLocation, id);
		}
		return id;
	}

	private long lastRecordId = -1;
	private final HashMap<String, Long> callNumberIds = new HashMap<>();
	private long getCallNumberId(long recordId, String callNumber, int numTries) {
		if (callNumber == null){
			return -1;
		}
		if (lastRecordId != recordId) {
			callNumberIds.clear();
		}
		lastRecordId = recordId;
		if (callNumber.length() > 255){
			callNumber = callNumber.substring(0, 255);
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
				//Another thread already created it, call it again
				if (numTries == 1) {
					return getCallNumberId(recordId, callNumber, numTries + 1);
				}else {
					logEntry.incErrors("Error getting callNumber id", e);
					id = -1L;
				}
			}
			callNumberIds.put(callNumber, id);
		}else{
			logger.debug("Found cached call number");
		}
		return id;
	}

	private final HashMap<String, Long> statusIds = new HashMap<>();
	private long getStatusId(String status, int numTries) {
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
				//Another thread already created it, call it again
				if (numTries == 1) {
					return getStatusId(status, numTries + 1);
				}else {
					logEntry.incErrors("Error getting status id", e);
					id = -1L;
				}
			}
			statusIds.put(status, id);
		}
		return id;
	}

	private final HashMap<String, Long> locationCodeIds = new HashMap<>();
	private long getLocationCodeId(String locationCode, int numTries) {
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
				//Another thread already created it, call it again
				if (numTries == 1) {
					return getLocationCodeId(locationCode, numTries + 1);
				}else {
					logEntry.incErrors("Error getting locationCode id", e);
					id = -1L;
				}
			}
			locationCodeIds.put(locationCode, id);
		}
		return id;
	}

	private final HashMap<String, Long> subLocationCodeIds = new HashMap<>();
	private long getSubLocationCodeId(String subLocationCode, int numTries) {
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
				//Another thread already created it, call it again
				if (numTries == 1) {
					return getSubLocationCodeId(subLocationCode, numTries + 1);
				}else {
					logEntry.incErrors("Error getting subLocationCode id", e);
					id = -1L;
				}
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
		curVariationInfo.primaryLanguageId = getLanguageId(recordInfo.getPrimaryLanguage(), 1);
		curVariationInfo.eContentSourceId = this.getEContentSourceId(itemInfo.geteContentSource(), 1);
		String format = itemInfo.getFormat() == null ? recordInfo.getPrimaryFormat() : itemInfo.getFormat();
		curVariationInfo.formatId = this.getFormatId(format, 1);
		String formatCategory = itemInfo.getFormatCategory() == null ? recordInfo.getPrimaryFormatCategory() : itemInfo.getFormatCategory();
		curVariationInfo.formatCategoryId = this.getFormatCategoryId(formatCategory, 1);
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
				}else{
					getIdForVariationStmt.setLong(1, groupedWorkId);
					getIdForVariationStmt.setLong(2, curVariationInfo.primaryLanguageId);
					getIdForVariationStmt.setLong(3, curVariationInfo.eContentSourceId);
					getIdForVariationStmt.setLong(4, curVariationInfo.formatId);
					getIdForVariationStmt.setLong(5, curVariationInfo.formatCategoryId);
					ResultSet getIdForVariationRS = getIdForVariationStmt.executeQuery();
					if (getIdForVariationRS.next()) {
						curVariationInfo.id = getIdForVariationRS.getLong("id");
						existingVariations.put(curVariationInfo, curVariationInfo.id);
						return curVariationInfo.id;
					}
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
				existingItems.put(getExistingItemsForRecordRS.getString("itemId").toLowerCase(), new SavedItemInfo(getExistingItemsForRecordRS));
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
		SavedItemInfo savedItem = existingItems.get(itemInfo.getItemIdentifier().toLowerCase());
		long itemId = -1;
		if (savedItem != null){
			itemId = savedItem.id;
		}
		try {
			long shelfLocationId = this.getShelfLocationId(itemInfo.getDetailedLocation(), 1);
			long callNumberId = this.getCallNumberId(recordId, itemInfo.getCallNumber(), 1);
			long sortableCallNumberId;
			if (AspenStringUtils.compareStrings(itemInfo.getCallNumber(), itemInfo.getSortableCallNumber())){
				sortableCallNumberId = callNumberId;
			}else{
				sortableCallNumberId = this.getCallNumberId(recordId, itemInfo.getSortableCallNumber(), 1);
			}
			long statusId = this.getStatusId(itemInfo.getDetailedStatus(), 1);
			long locationCodeId = this.getLocationCodeId(itemInfo.getLocationCode(), 1);
			long subLocationId = this.getSubLocationCodeId(itemInfo.getSubLocationCode(), 1);
			long groupedStatusId = this.getStatusId(itemInfo.getGroupedStatus(), 1);
			boolean errorsSavingItem = false;
			if (savedItem == null) {
				try {
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
					addItemForRecordStmt.setBoolean(21, itemInfo.isVirtual());
					addItemForRecordStmt.executeUpdate();
					ResultSet addItemForWorkRS = addItemForRecordStmt.getGeneratedKeys();
					if (addItemForWorkRS.next()) {
						itemId = addItemForWorkRS.getLong(1);
					} else {
						getIdForItemStmt.setLong(1, recordId);
						getIdForItemStmt.setLong(2, variationId);
						getIdForItemStmt.setString(3, itemInfo.getItemIdentifier());
						ResultSet getIdForItemRS = getIdForItemStmt.executeQuery();
						if (getIdForItemRS.next()) {
							recordId = getIdForItemRS.getLong("id");
						}
					}
					SavedItemInfo savedItemInfo = new SavedItemInfo(itemId, recordId, variationId, itemInfo.getItemIdentifier(), shelfLocationId, callNumberId, sortableCallNumberId, itemInfo.getNumCopies(),
							itemInfo.isOrderItem(), statusId, itemInfo.getDateAdded(), locationCodeId, subLocationId, itemInfo.getLastCheckinDate(), groupedStatusId, itemInfo.isAvailable(),
							itemInfo.isHoldable(), itemInfo.isInLibraryUseOnly(), itemInfo.getLocationOwnedScopes(), itemInfo.getLibraryOwnedScopes(), itemInfo.getRecordsIncludedScopes());

					existingItems.put(itemInfo.getItemIdentifier().toLowerCase(), savedItemInfo);
				}catch (SQLException e){
					logEntry.incErrors("Error adding item " + itemId + " for record " + recordId, e);
					errorsSavingItem = true;
				}
			}else if (savedItem.hasChanged(recordId, variationId, itemInfo.getItemIdentifier(), shelfLocationId, callNumberId, sortableCallNumberId, itemInfo.getNumCopies(),
					itemInfo.isOrderItem(), statusId, itemInfo.getDateAdded(), locationCodeId, subLocationId, itemInfo.getLastCheckinDate(), groupedStatusId, itemInfo.isAvailable(),
					itemInfo.isHoldable(), itemInfo.isInLibraryUseOnly(), itemInfo.getLocationOwnedScopes(), itemInfo.getLibraryOwnedScopes(), itemInfo.getRecordsIncludedScopes())){
				try {
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
					updateItemForRecordStmt.setBoolean(19, itemInfo.isVirtual());
					updateItemForRecordStmt.setLong(20, itemId);
					updateItemForRecordStmt.executeUpdate();
				}catch (SQLException e){
					logEntry.incErrors("Error updating item " + itemId + " record " + recordId, e);
					errorsSavingItem = true;
				}
			}

			if (itemInfo.geteContentUrl() != null && ! errorsSavingItem){
				try {
					addItemUrlStmt.setLong(1, itemId);
					addItemUrlStmt.setLong(2, -1);
					addItemUrlStmt.setString(3, itemInfo.geteContentUrl());
					addItemUrlStmt.executeUpdate();

					//Check to see if we need to save local urls
					for (ScopingInfo scopingInfo : itemInfo.getScopingInfo().values()) {
						String localUrl = scopingInfo.getLocalUrl();
						if (localUrl != null && !localUrl.isEmpty() && !localUrl.equals(itemInfo.geteContentUrl())) {
							addItemUrlStmt.setLong(1, itemId);
							addItemUrlStmt.setLong(2, scopingInfo.getScope().getId());
							addItemUrlStmt.setString(3, localUrl);
							addItemUrlStmt.executeUpdate();
						}
					}
				}catch (SQLException e){
					logEntry.incErrors("Error adding url for item " + itemId, e);
				}
			}

		} catch (Exception e) {
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

	public boolean isStoreRecordDetailsInSolr(){
		return storeRecordDetailsInSolr;
	}

	public boolean isStoreRecordDetailsInDatabase() {
		return storeRecordDetailsInDatabase;
	}

	public void markIlsRecordAsDeleted(String name, String existingIdentifier) {
		try {
			markIlsRecordAsDeletedStmt.setLong(1, new Date().getTime() / 1000);
			markIlsRecordAsDeletedStmt.setString(2, name);
			markIlsRecordAsDeletedStmt.setString(3, existingIdentifier);
			markIlsRecordAsDeletedStmt.executeUpdate();
		}catch (Exception e) {
			logEntry.incErrors("Could not mark ils record as deleted", e);
		}
	}

	public int markIlsRecordAsRestored(String name, String existingIdentifier){
		try {
			markIlsRecordAsRestoredStmt.setString(1, name);
			markIlsRecordAsRestoredStmt.setString(2, existingIdentifier);
			return markIlsRecordAsRestoredStmt.executeUpdate();
		}catch (Exception e) {
			logEntry.incErrors("Could not mark ils record as deleted", e);
			return 0;
		}
	}

	public BaseIndexingLogEntry getLogEntry() {
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

	@SuppressWarnings("BooleanMethodIsAlwaysInverted")
	public boolean isTreatUnknownAudienceAsUnknown() {
		return treatUnknownAudienceAsUnknown;
	}

	public void setRegroupAllRecords(boolean regroupAllRecords) {
		this.regroupAllRecords = regroupAllRecords;
	}

	public enum MarcStatus {
		UNCHANGED, CHANGED, NEW
	}

	public AppendItemsToRecordResult appendItemsToExistingRecord(IndexingProfile indexingSettings, org.marc4j.marc.Record recordWithAdditionalItems, String recordNumber, MarcFactory marcFactory, String marcIndex) {
		MarcStatus marcRecordStatus = MarcStatus.UNCHANGED;
		//Copy the record to the individual marc path
		Record mergedRecord = recordWithAdditionalItems;
		if (recordNumber != null) {
			mergedRecord = loadMarcRecordFromDatabase(indexingSettings.getName(), recordNumber, logEntry);

			List<DataField> additionalItems = recordWithAdditionalItems.getDataFields(indexingSettings.getItemTagInt());
			for (DataField additionalItem : additionalItems) {
				mergedRecord.addVariableField(additionalItem);
			}

			List<DataField> additional852s = recordWithAdditionalItems.getDataFields(852);
			for (DataField additionalItem : additional852s) {
				if (marcFactory != null && additionalItem.getSubfield('6') == null){
					additionalItem.addSubfield(MarcFactory.newInstance().newSubfield('6', marcIndex));
				}
				mergedRecord.addVariableField(additionalItem);
			}

			List<DataField> additional853s = recordWithAdditionalItems.getDataFields(853);
			for (DataField additionalItem : additional853s) {
				if (marcFactory != null && additionalItem.getSubfield('6') == null){
					additionalItem.addSubfield(MarcFactory.newInstance().newSubfield('6', marcIndex));
				}
				mergedRecord.addVariableField(additionalItem);
			}

			List<DataField> additional856s = recordWithAdditionalItems.getDataFields(856);
			for (DataField additionalItem : additional856s) {
				if (marcFactory != null && additionalItem.getSubfield('6') == null){
					additionalItem.addSubfield(MarcFactory.newInstance().newSubfield('6', marcIndex));
				}
				mergedRecord.addVariableField(additionalItem);
			}

			List<DataField> additional866s = recordWithAdditionalItems.getDataFields(866);
			for (DataField additionalItem : additional866s) {
				if (marcFactory != null && additionalItem.getSubfield('6') == null){
					additionalItem.addSubfield(MarcFactory.newInstance().newSubfield('6', marcIndex));
				}
				mergedRecord.addVariableField(additionalItem);
			}

			marcRecordStatus = MarcStatus.CHANGED;
			saveMarcRecordToDatabase(indexingSettings, recordNumber, mergedRecord);
		} else {
			logEntry.incErrors("Error did not find record number for MARC record");
		}
		return new AppendItemsToRecordResult(marcRecordStatus, mergedRecord);
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
				long lastModified = new Date().getTime() / 1000;

				addRecordToDBStmt.setString(1, ilsId);
				addRecordToDBStmt.setString(2, indexingProfile.getName());
				addRecordToDBStmt.setLong(3, checksumCalculator.getValue());
				addRecordToDBStmt.setLong(4, lastModified);
				addRecordToDBStmt.setBlob(5, new ByteArrayInputStream(marcAsBytes));
				addRecordToDBStmt.setLong(6, lastModified);
				addRecordToDBStmt.executeUpdate();
				returnValue = MarcStatus.NEW;
			}

		}catch (Exception e){
			logEntry.incErrors("Error saving MARC record to database for " + ilsId + " found existing? " + foundExisting, e);
		}
		marcRecordCache.put(indexingProfile.getName() + ilsId, marcRecord);

		return returnValue;
	}

	//Create a small cache to hold recently used marc records to avoid time reloading them.
	public MaxSizeHashMap<String, Record> marcRecordCache = new MaxSizeHashMap<>(100);
	public Record loadMarcRecordFromDatabase(String source, String identifier, BaseIndexingLogEntry logEntry) {
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
				logEntry.incErrors("Error loading MARC record " + source + " " + identifier + " from database", e);
			}
		}
		return marcRecord;

	}

	private RecordGroupingProcessor getRecordGroupingProcessor() {
		if (recordGroupingProcessor == null) {
			recordGroupingProcessor = new RecordGroupingProcessor(dbConn, serverName, logEntry, logger);
		}
		return recordGroupingProcessor;
	}

	public void forceRecordReindex(String source, String identifier) {
		try {
			forceReindexOfRecordStmt.setString(1, source);
			forceReindexOfRecordStmt.setString(2, identifier);
			forceReindexOfRecordStmt.executeUpdate();
		}catch (Exception e) {
			logEntry.incErrors("Error forcing record reindex", e);
		}
	}
}
