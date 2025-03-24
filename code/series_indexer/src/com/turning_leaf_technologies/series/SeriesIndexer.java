package com.turning_leaf_technologies.series;

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
import java.util.TreeSet;

class SeriesIndexer {
	private Connection dbConn;
	private final Logger logger;
	private ConcurrentUpdateHttp2SolrClient updateServer;
	private Http2SolrClient groupedWorkServer;
	private TreeSet<Scope> scopes;

	SeriesIndexer(Ini configIni, Connection dbConn, Logger logger){
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
		try {
			updateServer = new ConcurrentUpdateHttp2SolrClient.Builder("http://" + solrHost + ":" + solrPort + "/solr/series", http2Client)
				.withThreadCount(1)
				.withQueueSize(25)
				.build();
		}catch (OutOfMemoryError e) {
			logger.error("Unable to create solr client, out of memory", e);
			System.exit(-7);
		}
		//Get the search version from system variables
		int searchVersion = 1;
		try {
			PreparedStatement searchVersionStmt = dbConn.prepareStatement("SELECT searchVersion from system_variables");
			ResultSet searchVersionRS = searchVersionStmt.executeQuery();
			if (searchVersionRS.next()){
				searchVersion = searchVersionRS.getInt("searchVersion");
			}
			searchVersionRS.close();
		}catch (Exception e){
			logger.error("Error loading search version", e);
		}
		Http2SolrClient.Builder groupedWorkHttpBuilder;
		if (searchVersion == 1) {
			groupedWorkHttpBuilder = new Http2SolrClient.Builder("http://localhost:" + solrPort + "/solr/grouped_works");
		}else{
			groupedWorkHttpBuilder = new Http2SolrClient.Builder("http://localhost:" + solrPort + "/solr/grouped_works_v2");
		}
		groupedWorkServer = groupedWorkHttpBuilder.build();

		scopes = IndexingUtils.loadScopes(dbConn, logger);
	}

	void close() {
		this.dbConn = null;

		try {
			groupedWorkServer.close();
			groupedWorkServer = null;
		}catch (Exception e) {
			logger.error("Error closing grouped work server ", e);
			System.exit(-5);
		}

		try {
			updateServer.close();
			updateServer = null;
		}catch (Exception e) {
			logger.error("Error closing update server ", e);
			System.exit(-5);
		}

		scopes = null;
	}

	Long processSeries(boolean fullReindex, long lastReindexTime, SeriesLogEntry logEntry) {
		long numSeriesProcessed = 0L;
		long numSeriesIndexed = 0;
		try{
			PreparedStatement seriesStmt;
			PreparedStatement numSeriesStmt;
			if (fullReindex) {
				//Delete all series from the index
				updateServer.deleteByQuery("recordtype:series");
				//Get a list of all series
				numSeriesStmt = dbConn.prepareStatement("select count(id) as numSeries from series WHERE isIndexed = 1;");
				seriesStmt = dbConn.prepareStatement("SELECT * FROM series WHERE isIndexed = 1;");
			} else {
				numSeriesStmt = dbConn.prepareStatement("select count(id) as numSeries from series WHERE dateUpdated >= ?;");
				numSeriesStmt.setLong(1, lastReindexTime);
				seriesStmt = dbConn.prepareStatement("SELECT * FROM series WHERE dateUpdated >= ?;");
				seriesStmt.setLong(1, lastReindexTime);
			}

			PreparedStatement getSeriesMembersStmt = dbConn.prepareStatement("SELECT * FROM series_member WHERE seriesId = ? AND excluded = 0");

			ResultSet allSeriesRS = seriesStmt.executeQuery();
			ResultSet numSeriesRS = numSeriesStmt.executeQuery();
			if (numSeriesRS.next()){
				logEntry.setNumSeries(numSeriesRS.getInt("numSeries"));
			}

			while (allSeriesRS.next()){
				if (updateSolrForSeries(fullReindex, updateServer, getSeriesMembersStmt, allSeriesRS, lastReindexTime, logEntry)){
					numSeriesIndexed++;
				}
				if (numSeriesIndexed % 500 == 0) {
					if (!fullReindex) {
						updateServer.commit(false, false, true);
					}
					logEntry.saveResults();
				}
				numSeriesProcessed++;
			}
			if (numSeriesProcessed > 0){
				allSeriesRS.close();
				logEntry.addNote("Calling final commit");
				logEntry.saveResults();
				updateServer.commit(false, false, true);
			}

		} catch (IOException e) {
			logEntry.incErrors("Error processing series quitting", e);
			System.exit(-8);
		}catch (Exception e){
			logger.error("Error processing series", e);
		}
		logger.debug("Indexed series: processed " + numSeriesProcessed + " indexed " + numSeriesIndexed);
		return numSeriesProcessed;
	}

	private boolean updateSolrForSeries(boolean fullReindex, ConcurrentUpdateHttp2SolrClient updateServer, PreparedStatement getTitlesForSeriesStmt, ResultSet allSeriesRS, long lastReindexTime, SeriesLogEntry logEntry) throws SQLException, SolrServerException, IOException {
		SeriesSolr seriesSolr = new SeriesSolr(this);
		long seriesId = allSeriesRS.getLong("id");
		int deleted = allSeriesRS.getInt("deleted");
		int isIndexed = allSeriesRS.getInt("isIndexed");
		boolean indexed = false;
		if (!fullReindex && (deleted == 1 || isIndexed == 0)) {
			updateServer.deleteByQuery("id:" + seriesId);
			logEntry.incDeleted();
		} else {
			logger.info("Processing series " + seriesId + " " + allSeriesRS.getString("displayName"));
			seriesSolr.setId(seriesId);
			seriesSolr.setTitle(allSeriesRS.getString("displayName"));
			seriesSolr.setDescription(allSeriesRS.getString("description"));
			String audience = allSeriesRS.getString("audience");
			if (audience.charAt(0) == '[') {
				String[] audiences = audience.substring(1, audience.length() - 1).split(",");
				seriesSolr.setAudiences(audiences);
			}else{
				seriesSolr.setAudience(audience);
			}

			long created = allSeriesRS.getLong("created");
			long dateUpdated = allSeriesRS.getLong("dateUpdated");
			seriesSolr.setCreated(created);
			seriesSolr.setDateUpdated(dateUpdated);
			try {
				//Get information about all series titles
				getTitlesForSeriesStmt.setLong(1, seriesId);
				ResultSet allTitlesRS = getTitlesForSeriesStmt.executeQuery();
				while (allTitlesRS.next()) {
					String groupedWorkPermanentId = allTitlesRS.getString("groupedWorkPermanentId");
					if (!allTitlesRS.wasNull()) {
						if (!groupedWorkPermanentId.isEmpty()) {
							SolrQuery query = new SolrQuery();
							query.setQuery("id:" + groupedWorkPermanentId);
							query.setFields("title_display", "author_display", "language", "subject", "literary_form", "format", "format_category", "econtent_source");

							try {
								QueryResponse response = groupedWorkServer.query(query);
								SolrDocumentList results = response.getResults();
								//Should only ever get one response
								if (!results.isEmpty()) {
									SolrDocument curWork = results.get(0);
									seriesSolr.addListTitle("grouped_work", groupedWorkPermanentId, curWork.getFieldValue("title_display"), curWork.getFieldValue("author_display"), curWork);
								}
							} catch (Exception e) {
								logger.error("Error loading information about title " + groupedWorkPermanentId);
							}
						}
					}
				}
				allTitlesRS.close();
				// Index in the solr catalog
				SolrInputDocument document = seriesSolr.getSolrDocument();
				if (document != null) {
					updateServer.add(document);
					if (created > lastReindexTime) {
						logEntry.incAdded();
					} else {
						logEntry.incUpdated();
					}
					indexed = true;
				} else {
					updateServer.deleteByQuery("id:" + seriesId);
					logEntry.incSkipped();
				}
			} catch (Exception e) {
				updateServer.deleteByQuery("id:" + seriesId);
				logEntry.addNote("Could not get title information for " + seriesId + " - " + e);
				logEntry.incSkipped();
			}

		}
		return indexed;
	}
	TreeSet<Scope> getScopes() {
		return this.scopes;
	}
}
