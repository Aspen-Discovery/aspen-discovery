package com.turning_leaf_technologies.reindexer;

import com.turning_leaf_technologies.indexing.IndexingUtils;
import com.turning_leaf_technologies.indexing.Scope;
import org.apache.logging.log4j.Logger;
import org.apache.solr.client.solrj.SolrQuery;
import org.apache.solr.client.solrj.SolrServerException;
import org.apache.solr.client.solrj.impl.ConcurrentUpdateHttp2SolrClient;
import org.apache.solr.client.solrj.impl.Http2SolrClient;
import org.apache.solr.client.solrj.response.QueryResponse;
import org.apache.solr.common.SolrDocument;
import org.apache.solr.common.SolrDocumentList;
import org.apache.solr.common.SolrInputDocument;
import org.ini4j.Ini;

import java.io.IOException;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.HashMap;
import java.util.TreeSet;

class CourseReservesIndexer {
	private final Connection dbConn;
	private final Logger logger;
	private ConcurrentUpdateHttp2SolrClient updateServer;
	private Http2SolrClient groupedWorkServer;
	private final TreeSet<Scope> scopes;
	private final HashMap<String, String> libraryTranslations = new HashMap<>();

	CourseReservesIndexer(Ini configIni, Connection dbConn, Logger logger){
		this.dbConn = dbConn;
		this.logger = logger;

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

		Http2SolrClient http2Client = new Http2SolrClient.Builder().build();
		updateServer = new ConcurrentUpdateHttp2SolrClient.Builder("http://" + solrHost + ":" + solrPort + "/solr/course_reserves", http2Client)
				.withThreadCount(2)
				.withQueueSize(25)
				.build();

		groupedWorkServer = new Http2SolrClient.Builder("http://" + solrHost + ":" + solrPort + "/solr/grouped_works_v2").build();

		scopes = IndexingUtils.loadScopes(dbConn, logger);
	}

	void close() {
		groupedWorkServer.close();
		groupedWorkServer = null;

		updateServer.close();
		updateServer = null;
	}

	public long processCourseReserves(boolean fullReindex, long lastReindexTime, CourseReservesIndexingLogEntry logEntry) {
		long numCourseReservesProcessed = 0L;
		long numCourseReservesIndexed = 0;
		try{
			PreparedStatement courseReservesStmt;
			PreparedStatement numCourseReservesStmt;
			if (fullReindex){
				//Delete all lists from the index
				updateServer.deleteByQuery("recordtype:course_reserve");
				//Get a list of all public lists
				numCourseReservesStmt = dbConn.prepareStatement("select count(id) as numReserves from course_reserve WHERE deleted = 0");
				courseReservesStmt = dbConn.prepareStatement("SELECT id, deleted, created, dateUpdated, courseInstructor, courseNumber, courseTitle, courseLibrary from course_reserve WHERE deleted = 0");
			}else{
				//Get a list of all course reserves that were changed since the last update
				numCourseReservesStmt = dbConn.prepareStatement("select count(id) as numReserves from course_reserve WHERE dateUpdated > 0");
				courseReservesStmt = dbConn.prepareStatement("SELECT id, deleted, created, dateUpdated, courseInstructor, courseNumber, courseTitle, courseLibrary from course_reserve WHERE dateUpdated > ?");
				courseReservesStmt.setLong(1, lastReindexTime);
			}

			PreparedStatement getTitlesForCourseReserveStmt = dbConn.prepareStatement("SELECT source, sourceId from course_reserve_entry WHERE courseReserveId = ?");
			ResultSet allCourseReservesRS = courseReservesStmt.executeQuery();
			ResultSet numCourseReservesRS = numCourseReservesStmt.executeQuery();
			if (numCourseReservesRS.next()){
				logEntry.setNumLists(numCourseReservesRS.getInt("numReserves"));
			}

			while (allCourseReservesRS.next()){
				if (updateSolrForCourseReserve(fullReindex, updateServer, getTitlesForCourseReserveStmt, allCourseReservesRS, lastReindexTime, logEntry)){
					numCourseReservesIndexed++;
				}
				numCourseReservesProcessed++;
			}
			if (numCourseReservesProcessed > 0){
				updateServer.commit(true, true);
			}

		}catch (Exception e){
			logger.error("Error processing public lists", e);
		}
		logger.debug("Indexed lists: processed " + numCourseReservesProcessed + " indexed " + numCourseReservesIndexed);
		return numCourseReservesProcessed;
	}

	private boolean updateSolrForCourseReserve(boolean fullReindex, ConcurrentUpdateHttp2SolrClient updateServer, PreparedStatement getTitlesForCourseReserveStmt, ResultSet allCourseReservesRS, long lastReindexTime, CourseReservesIndexingLogEntry logEntry) throws SQLException, SolrServerException, IOException {
		CourseReserveSolr courseReserveSolr = new CourseReserveSolr(this);
		long courseReserveId = allCourseReservesRS.getLong("id");

		int deleted = allCourseReservesRS.getInt("deleted");
		boolean indexed = false;
		if (!fullReindex && (deleted == 1)){
			updateServer.deleteByQuery("id:" + courseReserveId);
			logEntry.incDeleted();
		}else{
			logger.info("Processing course reserve " + courseReserveId);
			courseReserveSolr.setId(courseReserveId);
			long created = allCourseReservesRS.getLong("created");
			courseReserveSolr.setCreated(created);

			String courseLibrary = allCourseReservesRS.getString("courseLibrary");
			String courseInstructor = allCourseReservesRS.getString("courseInstructor");
			String[] courseInstructors = courseInstructor.split("\\|",0);
			String displayInstructors = String.join(", ", courseInstructors);
			String courseNumber = allCourseReservesRS.getString("courseNumber");
			String courseTitle = allCourseReservesRS.getString("courseTitle");

			String displayName = courseNumber + " " + courseTitle + " - " + displayInstructors;
			courseReserveSolr.setTitle(displayName);
			courseReserveSolr.setCourseNumber(courseNumber);
			courseReserveSolr.setCourseTitle(courseTitle);
			courseReserveSolr.setCourseLibrary(courseLibrary);

			//Translate the library
			String displayLibrary = libraryTranslations.get(courseLibrary);
			if (displayLibrary == null){
				displayLibrary = courseLibrary;
			}
			courseReserveSolr.setDisplayLibrary(displayLibrary);

			// Add Instructors
			for (String instructor : courseInstructors) {
				courseReserveSolr.addInstructor(instructor);
			}

			//Get information about all the titles on reserve
			getTitlesForCourseReserveStmt.setLong(1, courseReserveId);
			ResultSet allTitlesRS = getTitlesForCourseReserveStmt.executeQuery();
			while (allTitlesRS.next()) {
				String source = allTitlesRS.getString("source");
				String sourceId = allTitlesRS.getString("sourceId");
				if (!allTitlesRS.wasNull()){
					if (!sourceId.isEmpty() && source.equals("GroupedWork")) {
						// Skip archive object Ids
						SolrQuery query = new SolrQuery();
						query.setQuery("id:" + sourceId);
						query.setFields("title_display", "author_display");

						try {
							QueryResponse response = groupedWorkServer.query(query);
							SolrDocumentList results = response.getResults();
							//Should only ever get one response
							if (!results.isEmpty()) {
								SolrDocument curWork = results.get(0);
								courseReserveSolr.addTitle(sourceId, curWork.getFieldValue("title_display"), curWork.getFieldValue("author_display"));
							}
						} catch (Exception e) {
							logger.error("Error loading information about title " + sourceId);
						}
					}else{
						logEntry.incErrors("Unhandled source " + source);
					}
					//TODO: Handle other types of objects within a User List
					//people, etc.
				}
			}
			if (courseReserveSolr.getNumTitles() >= 1) {
				// Index in the solr catalog
				SolrInputDocument document = courseReserveSolr.getSolrDocument();
				if (document != null){
					updateServer.add(document);
					if (created > lastReindexTime){
						logEntry.incAdded();
					}else{
						logEntry.incUpdated();
					}
					indexed = true;
				}else{
					updateServer.deleteByQuery("id:" + courseReserveId);
					logEntry.incDeleted();
				}
			} else {
				updateServer.deleteByQuery("id:" + courseReserveId);
				logEntry.incDeleted();
			}
		}

		return indexed;
	}

	TreeSet<Scope> getScopes() {
		return this.scopes;
	}

	public void addLibraryMap(String value, String translation){
		this.libraryTranslations.put(value, translation);
	}
}
