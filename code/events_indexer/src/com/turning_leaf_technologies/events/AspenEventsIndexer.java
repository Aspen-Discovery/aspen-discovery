package com.turning_leaf_technologies.events;

import com.turning_leaf_technologies.config.ConfigUtil;
import org.apache.logging.log4j.Logger;
import org.apache.solr.client.solrj.SolrServerException;
import org.apache.solr.client.solrj.impl.BaseHttpSolrClient;
import org.apache.solr.client.solrj.impl.ConcurrentUpdateHttp2SolrClient;
import org.apache.solr.common.SolrInputDocument;
import org.ini4j.Ini;

import java.io.IOException;
import java.sql.*;
import java.text.SimpleDateFormat;
import java.time.Instant;
import java.util.*;
import java.util.Date;
import java.util.zip.CRC32;

import static java.util.Calendar.DAY_OF_YEAR;

public class AspenEventsIndexer {
	private final long settingsId;
	private final int numberOfDaysToIndex;
	private final boolean runFullUpdate;
	private final long lastUpdateOfAllEvents;
	private final long lastUpdateOfChangedEvents;
	private final Connection aspenConn;
	private final EventsIndexerLogEntry logEntry;
	private final HashMap<Long, AspenEvent> eventInstances = new HashMap<>();
	private final static CRC32 checksumCalculator = new CRC32();
	private final String coverPath;

	private final List<String> idsToDelete = new ArrayList<>();

	private final ConcurrentUpdateHttp2SolrClient solrUpdateServer;

	AspenEventsIndexer(long settingsId, int numberOfDaysToIndex, boolean runFullUpdate, long lastUpdateOfAllEvents, long lastUpdateOfChangedEvents, ConcurrentUpdateHttp2SolrClient solrUpdateServer, Connection aspenConn, Logger logger, String serverName) {
		this.settingsId = settingsId;
		this.aspenConn = aspenConn;
		this.solrUpdateServer = solrUpdateServer;
		this.numberOfDaysToIndex = numberOfDaysToIndex;
		this.runFullUpdate = runFullUpdate;
		this.lastUpdateOfAllEvents = lastUpdateOfAllEvents;
		this.lastUpdateOfChangedEvents = lastUpdateOfChangedEvents;

		logEntry = new EventsIndexerLogEntry("Aspen Events", aspenConn, logger);

		Ini configIni = ConfigUtil.loadConfigFile("config.ini", serverName, logger);
		coverPath = configIni.get("Site","coverPath");

		loadEvents();
	}

	private final SimpleDateFormat dateFormat = new SimpleDateFormat("yyyy-MM-dd");
	private final SimpleDateFormat eventDayFormatter = new SimpleDateFormat("yyyy-MM-dd");
	private final SimpleDateFormat eventWeekFormatter = new SimpleDateFormat("yyyy-ww");
	private final SimpleDateFormat eventMonthFormatter = new SimpleDateFormat("yyyy-MM");
	private final SimpleDateFormat eventYearFormatter = new SimpleDateFormat("yyyy");
	private void loadEvents() {
		try {
			// Calculate date for numberOfDaysToIndex into the future to add to where statement
			GregorianCalendar lastDateToIndex = new GregorianCalendar();
			lastDateToIndex.setTime(new Date());
			lastDateToIndex.add(DAY_OF_YEAR, this.numberOfDaysToIndex);

			// Get total number of events for log
			PreparedStatement eventCountStmt = aspenConn.prepareStatement("SELECT COUNT(*) FROM event_instance WHERE deleted = 0;");
			ResultSet eventCountRS = eventCountStmt.executeQuery();
			if (eventCountRS.next()) {
				logEntry.incNumEvents(eventCountRS.getInt("COUNT(*)"));
			}

			PreparedStatement eventsStmt;
			PreparedStatement deleteEventsStmt;
			if (runFullUpdate) {
				// Get event instance and event info
				eventsStmt = aspenConn.prepareStatement("SELECT ei.*, e.title, e.description, e.eventTypeId, e.locationId, l.displayName, sl.name AS sublocationName, sl2.name AS sublocationOverride, e.sublocationId, e.cover, e.private FROM event_instance AS ei LEFT JOIN event as e ON e.id = ei.eventID LEFT JOIN location AS l ON e.locationId = l.locationId LEFT JOIN sublocation AS sl on e.sublocationId = sl.id LEFT JOIN sublocation AS sl2 ON ei.sublocationId = sl2.id WHERE ei.date < ? AND ei.deleted = 0;");
			} else {
				eventsStmt = aspenConn.prepareStatement("SELECT ei.*, e.title, e.description, e.eventTypeId, e.locationId, l.displayName, sl.name AS sublocationName, sl2.name AS sublocationOverride, e.sublocationId, e.cover, e.private FROM event_instance AS ei LEFT JOIN event as e ON e.id = ei.eventID LEFT JOIN location AS l ON e.locationId = l.locationId LEFT JOIN sublocation AS sl on e.sublocationId = sl.id LEFT JOIN sublocation AS sl2 ON ei.sublocationId = sl2.id WHERE ei.date < ? AND (e.dateUpdated > ? OR ei.dateUpdated > ?) AND ei.deleted = 0;");
				deleteEventsStmt = aspenConn.prepareStatement("SELECT id FROM event_instance WHERE deleted = 1 AND dateUpdated > ?;");
				eventsStmt.setLong(2, lastUpdateOfChangedEvents);
				eventsStmt.setLong(3, lastUpdateOfChangedEvents);
				deleteEventsStmt.setLong(1, lastUpdateOfChangedEvents);
				ResultSet deleteEventsRS = deleteEventsStmt.executeQuery();
				while (deleteEventsRS.next()) {
					idsToDelete.add("aspenEvent_" + settingsId + "_" + deleteEventsRS.getString("id"));
				}
			}
			eventsStmt.setString(1, dateFormat.format(lastDateToIndex.getTime()));
			// Get libraries for this event type
			PreparedStatement librariesStmt = aspenConn.prepareStatement("SELECT etl.libraryId, l.subdomain FROM event_type_library AS etl LEFT JOIN library as l ON etl.libraryId = l.libraryId WHERE eventTypeId = ?");
			// Get custom fields
			PreparedStatement eventFieldStmt = aspenConn.prepareStatement("SELECT ef.name, ef.allowableValues, ef.type, ef.facetName, eef.value from event_event_field AS eef LEFT JOIN event_field AS ef ON ef.id = eef.eventFieldId WHERE eef.eventId = ?;");


			ResultSet existingEventsRS = eventsStmt.executeQuery();


			while (existingEventsRS.next()) {
				AspenEvent event = new AspenEvent(existingEventsRS);
				librariesStmt.clearParameters();
				librariesStmt.setLong(1, event.getEventType());
				ResultSet librariesFieldsRS = librariesStmt.executeQuery();
				while (librariesFieldsRS.next()) {
					event.addLibrary(librariesFieldsRS.getString("subdomain").toLowerCase());
				}
				eventFieldStmt.clearParameters();
				eventFieldStmt.setLong(1, event.getParentEventId());
				ResultSet eventFieldsRS = eventFieldStmt.executeQuery();
				while (eventFieldsRS.next()) {
					String[] allowableValues = eventFieldsRS.getString("allowableValues").split("\n");
					if (allowableValues[0].isEmpty()) {
						allowableValues = new String[0];
					}
					event.addField(eventFieldsRS.getString("name"), eventFieldsRS.getString("value"), allowableValues, eventFieldsRS.getInt("type"), eventFieldsRS.getInt("facetName"));
				}
				eventInstances.put(event.getId(), event);
			}
		} catch (SQLException e) {
			logEntry.incErrors("Error loading event instances for Aspen Events ", e);
		}
	}


	void indexEvents() {

		// Delete everything and start fresh for full index
		if (runFullUpdate) {
			try {
				solrUpdateServer.deleteByQuery("type:event_aspenEvent AND source:" + this.settingsId);
			} catch (BaseHttpSolrClient.RemoteSolrException rse) {
				logEntry.incErrors("Solr is not running properly, try restarting " + rse);
				System.exit(-1);
			} catch (Exception e) {
				logEntry.incErrors("Error deleting from index ", e);
			}
		} else if (!idsToDelete.isEmpty()) {
			try {
				for (String id : idsToDelete) {
					solrUpdateServer.deleteByQuery("type:event_aspenEvent AND id:" + id);
					logEntry.incDeleted();
				}
			} catch (Exception e) {
				logEntry.incErrors("Error deleting event by id ", e);
			}
		}

		for (AspenEvent eventInfo : eventInstances.values()) {
			//Add the event to solr
			try {
				SolrInputDocument solrDocument = new SolrInputDocument();
				solrDocument.addField("id", "aspenEvent_" + settingsId + "_" + eventInfo.getId());
				solrDocument.addField("identifier", eventInfo.getId());
				solrDocument.addField("type", "event_aspenEvent");
				solrDocument.addField("source", settingsId);

				int boost = 1;
				solrDocument.addField("last_indexed", new Date());
				solrDocument.addField("last_change", null);
				//Make sure the start date exists
				Date startDate = eventInfo.getStartDateTime(logEntry);

				solrDocument.addField("start_date", startDate);
				if (startDate == null) {
					continue;
				}

				solrDocument.addField("start_date_sort", startDate.getTime() / 1000);
				Date endDate = eventInfo.getEndDateTime(logEntry);
				solrDocument.addField("end_date", endDate);

				HashSet<String> eventDays = new HashSet<>();
				HashSet<String> eventWeeks = new HashSet<>();
				HashSet<String> eventMonths = new HashSet<>();
				HashSet<String> eventYears = new HashSet<>();
				Date tmpDate = (Date)startDate.clone();

				if (tmpDate.equals(endDate) || tmpDate.after(endDate)){
					eventDays.add(eventDayFormatter.format(tmpDate));
					eventWeeks.add(eventWeekFormatter.format(tmpDate));
					eventMonths.add(eventMonthFormatter.format(tmpDate));
					eventYears.add(eventYearFormatter.format(tmpDate));
				}else {
					while (tmpDate.before(endDate)) {
						eventDays.add(eventDayFormatter.format(tmpDate));
						eventWeeks.add(eventWeekFormatter.format(tmpDate));
						eventMonths.add(eventMonthFormatter.format(tmpDate));
						eventYears.add(eventYearFormatter.format(tmpDate));
						tmpDate.setTime(tmpDate.getTime() + 24 * 60 * 60 * 1000);
					}
				}
				//Boost based on start date, we will give preference to anything in the next 30 days
				Date today = new Date();
				if (startDate.before(today) || startDate.equals(today)){
					boost += 30;
				}else{
					long daysInFuture = (startDate.getTime() - today.getTime()) / (1000 * 60 * 60 * 24);
					if (daysInFuture > 30){
						daysInFuture = 30;
					}
					boost += (int) (30 - daysInFuture);
				}
				solrDocument.addField("event_day", eventDays);
				solrDocument.addField("event_week", eventWeeks);
				solrDocument.addField("event_month", eventMonths);
				solrDocument.addField("event_year", eventYears);
				solrDocument.addField("title", eventInfo.getName());

				// Locations
				solrDocument.addField("branch", eventInfo.getLocationName());
				// Also get sublocation
				if (!eventInfo.getSublocationName().isEmpty()) {
					solrDocument.addField("room", eventInfo.getSublocationName());
				}

				solrDocument.addField("reservation_state", eventInfo.getStatus());
				solrDocument.addField("private", eventInfo.getNonPublic());
				if (eventInfo.getNonPublic().equals("private")) {
					solrDocument.addField("private", "private_" + eventInfo.getLocationName());
				}

				// Extra fields
				ArrayList<AspenEvent.EventField> extraFields = eventInfo.getFields();
				for (AspenEvent.EventField field : extraFields) {
					solrDocument.addField(field.getSolrFieldName(), field.getValue()); // Add as a dynamic field
					if (field.getType() == 2) { // Handle checkbox/boolean facets
						solrDocument.addField(field.getFacetName(), field.getValue().equals("1") ? "Yes" : "No");
					} else if (!field.getFacetName().isEmpty()) {
						solrDocument.addField(field.getFacetName(), field.getValue());
					}
				}
				if (eventInfo.getCover() != null && !eventInfo.getCover().isBlank() ) {
					solrDocument.addField("image_url", eventInfo.getCoverUrl(coverPath));
				}

				solrDocument.addField("description", eventInfo.getDescription());

				// Libraries scopes
				solrDocument.addField("library_scopes", eventInfo.getLibraries());

				solrDocument.addField("boost", boost);
				solrUpdateServer.add(solrDocument);

				logEntry.incUpdated(); // Need to add a way to distinguish between added/updated
			} catch (SolrServerException | IOException e) {
				logEntry.incErrors("Error adding event to solr ", e);
			}
		}

		if (!logEntry.hasErrors()) {
			//Update the last time we ran the update in settings
			PreparedStatement updateExtractTime;
			try {
				if (runFullUpdate) {
					updateExtractTime = aspenConn.prepareStatement("UPDATE events_indexing_settings set runFullUpdate = 0, lastUpdateOfAllEvents = ? WHERE id = ?");
				} else {
					updateExtractTime = aspenConn.prepareStatement("UPDATE events_indexing_settings set lastUpdateOfChangedEvents = ? WHERE id = ?");
				}
				updateExtractTime.setLong(1, Instant.now().getEpochSecond());
				updateExtractTime.setLong(2, this.settingsId);
				updateExtractTime.executeUpdate();
			} catch (SQLException e) {
				logEntry.incErrors("Error updating last updated time ", e);
			}
		} else {
			logEntry.addNote("Not setting last index update time since there were problems indexing events");
		}

		try {
			solrUpdateServer.commit(false, false, true);
		} catch (Exception e) {
			logEntry.incErrors("Error in final commit while finishing extract, shutting down", e);
			logEntry.setFinished();
			logEntry.saveResults();
			System.exit(-3);
		}

		logEntry.setFinished();

	}


}
