package com.turning_leaf_technologies.oai;

import com.turning_leaf_technologies.config.ConfigUtil;
import com.turning_leaf_technologies.indexing.IndexingUtils;
import com.turning_leaf_technologies.logging.LoggingUtil;
import com.turning_leaf_technologies.net.NetworkUtils;
import com.turning_leaf_technologies.net.WebServiceResponse;
import com.turning_leaf_technologies.strings.AspenStringUtils;
import org.apache.logging.log4j.Logger;
import org.apache.solr.client.solrj.SolrServerException;
import org.apache.solr.client.solrj.impl.BaseHttpSolrClient;
import org.apache.solr.client.solrj.impl.ConcurrentUpdateHttp2SolrClient;
import org.apache.solr.client.solrj.impl.Http2SolrClient;
import org.ini4j.Ini;
import org.w3c.dom.Document;
import org.w3c.dom.Element;
import org.w3c.dom.Node;
import org.w3c.dom.NodeList;
import org.xml.sax.InputSource;

import javax.xml.parsers.DocumentBuilder;
import javax.xml.parsers.DocumentBuilderFactory;
import java.io.*;
import java.net.URLEncoder;
import java.nio.charset.StandardCharsets;
import java.sql.*;
import java.util.*;
import java.util.Date;
import java.util.regex.Pattern;
import java.io.StringReader;

public class OaiIndexerMain {
	private static Logger logger;

	private static Ini configIni;
	private static ConcurrentUpdateHttp2SolrClient updateServer;

	private static Connection aspenConn;

	private static boolean fullReload = false;

	private static PreparedStatement getOpenArchiveCollections;
	private static PreparedStatement addOpenArchivesRecord;
	private static PreparedStatement getExistingRecordForCollection;
	private static PreparedStatement updateLastSeenForRecord;
	private static PreparedStatement updateCollectionAfterIndexing;
	private static PreparedStatement deleteOpenArchivesRecord;

	public static void main(String[] args) {
		String serverName;
		int collectionToProcess = -1;
		if (args.length == 0) {
			serverName = AspenStringUtils.getInputFromCommandLine("Please enter the server name");
			if (serverName.isEmpty()) {
				System.out.println("You must provide the server name as the first argument.");
				System.exit(1);
			}
			String collectionToProcessStr = AspenStringUtils.getInputFromCommandLine("Enter the Collection ID to process (blank to process all)");
			if (!collectionToProcessStr.isEmpty() && AspenStringUtils.isInteger(collectionToProcessStr)) {
				collectionToProcess = Integer.parseInt(collectionToProcessStr);
			}
		} else {
			serverName = args[0];
		}

		if (args.length >= 2 && args[1].equalsIgnoreCase("full")) {
			fullReload = true;
		}

		Date startTime = new Date();
		String processName = "oai_indexer";

		logger = LoggingUtil.setupLogging(serverName, processName);
		logger.info("Starting {}: {}", processName, startTime);

		// Read the base INI file to get information about the server (current directory/cron/config.ini)
		configIni = ConfigUtil.loadConfigFile("config.ini", serverName, logger);

		//Check to see if the indexer is already running and if so quit
		if (IndexingUtils.isIndexerRunning(processName, configIni, serverName, logger)){
			logger.info("Indexer is already running, quitting");
		}else{
			//Connect to the aspen database
			connectToDatabase();

			extractAndIndexOaiData(collectionToProcess);

			disconnectDatabase();
		}

		logger.info("Finished {}", new Date());
		long endTime = new Date().getTime();
		long elapsedTime = endTime - startTime.getTime();
		logger.info("Elapsed Minutes {}", elapsedTime / 60000);

		System.exit(0);
	}

	private static void connectToDatabase() {
		try {
			String databaseConnectionInfo = ConfigUtil.cleanIniValue(configIni.get("Database", "database_aspen_jdbc"));
			aspenConn = DriverManager.getConnection(databaseConnectionInfo);
			getOpenArchiveCollections = aspenConn.prepareStatement("SELECT * FROM open_archives_collection ORDER BY name");
			addOpenArchivesRecord = aspenConn.prepareStatement("INSERT INTO open_archives_record (sourceCollection, permanentUrl, lastSeen) VALUES (?, ?, ?)", PreparedStatement.RETURN_GENERATED_KEYS);
			getExistingRecordForCollection = aspenConn.prepareStatement("SELECT id, lastSeen from open_archives_record where sourceCollection = ? AND permanentUrl = ?");
			updateLastSeenForRecord = aspenConn.prepareStatement("UPDATE open_archives_record set lastSeen = ? WHERE id = ?");
			updateCollectionAfterIndexing = aspenConn.prepareStatement("UPDATE open_archives_collection SET lastFetched = ?, subjects = ? WHERE id = ?");
			deleteOpenArchivesRecord = aspenConn.prepareStatement("DELETE FROM open_archives_record WHERE id = ?");
		} catch (Exception e) {
			logger.error("Error connecting to aspen database", e);
			System.exit(1);
		}
	}

	private static void disconnectDatabase() {
		try {
			aspenConn.close();
		} catch (Exception e) {
			logger.error("Error closing database ", e);
			System.exit(1);
		}
	}

	private static void extractAndIndexOaiData(int collectionToIndex) {
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

		setupSolrClient(solrHost, solrPort);

		if (fullReload) {
			try {
				updateServer.deleteByQuery("*:*");
				//3-19-2019 Don't commit so the index does not get cleared during run (but will clear at the end).
			} catch (BaseHttpSolrClient.RemoteSolrException rse) {
				logger.error("Solr is not running properly, try restarting", rse);
				System.exit(-1);
			} catch (Exception e) {
				logger.error("Error deleting from index", e);
			}
		}

		try {
			PreparedStatement getLibrariesForCollectionStmt = aspenConn.prepareStatement("SELECT library.subdomain From library_open_archives_collection inner join library on library.libraryId = library_open_archives_collection.libraryId where collectionId = ?");
			PreparedStatement getLocationsForCollectionStmt = aspenConn.prepareStatement("SELECT code, subLocation from location_open_archives_collection inner join location on location.locationId = location_open_archives_collection.locationId where collectionId = ?");

			ResultSet collectionsRS = getOpenArchiveCollections.executeQuery();
			while (collectionsRS.next()) {
				if (collectionToIndex == -1 || collectionToIndex == collectionsRS.getInt("id")) {
					String collectionName = collectionsRS.getString("name");
					String fetchFrequency = collectionsRS.getString("fetchFrequency");
					long lastFetched = collectionsRS.getLong("lastFetched");
					boolean needsIndexing = false;
					long currentTime = new Date().getTime() / 1000;
					if (collectionsRS.wasNull() || lastFetched == 0 || fullReload) {
						needsIndexing = true;
					} else {
						//'daily', 'weekly', 'monthly', 'yearly', 'once'
						switch (fetchFrequency) {
							case "hourly": //Legacy, no longer in the interface
							case "daily":
								needsIndexing = lastFetched < (currentTime - 23 * 60 * 60);
								break;
							case "weekly":
								needsIndexing = lastFetched < (currentTime - (7 * 24 * 60 * 60) - (60 * 60));
								break;
							case "monthly":
								needsIndexing = lastFetched < (currentTime - (30 * 24 * 60 * 60) - (60 * 60));
								break;
							case "yearly":
								needsIndexing = lastFetched < (currentTime - (365 * 24 * 60 * 60) - (60 * 60));
								break;
						}
					}
					if (needsIndexing) {
						long collectionId = collectionsRS.getLong("id");
						String baseUrl = collectionsRS.getString("baseUrl");
						String setName = collectionsRS.getString("setName");
						boolean indexAllSets = collectionsRS.getBoolean("indexAllSets");
						String metadataFormat = collectionsRS.getString("metadataFormat");
						long dateFormatting = collectionsRS.getLong("dateFormatting");
						String subjectFilterString = collectionsRS.getString("subjectFilters");
						boolean loadOneMonthAtATime = collectionsRS.getBoolean("loadOneMonthAtATime");
						boolean deleted = collectionsRS.getBoolean("deleted");
						ArrayList<Pattern> subjectFilters = new ArrayList<>();
						if (subjectFilterString != null && !subjectFilterString.isEmpty()) {
							String[] subjectFiltersRaw = subjectFilterString.split("\\s*(\\r\\n|\\n|\\r)\\s*");
							for (String subjectFilter : subjectFiltersRaw) {
								if (!subjectFilter.isEmpty()) {
									subjectFilters.add(Pattern.compile("(\\b|-)" + subjectFilter.toLowerCase() + "(\\b|-)", Pattern.CASE_INSENSITIVE));
								}
							}
						}

						HashSet<String> scopesToInclude = new HashSet<>();

						//Get a list of libraries and locations that the setting applies to
						getLibrariesForCollectionStmt.setLong(1, collectionId);
						ResultSet librariesForCollectionRS = getLibrariesForCollectionStmt.executeQuery();
						while (librariesForCollectionRS.next()) {
							String subdomain = librariesForCollectionRS.getString("subdomain");
							subdomain = subdomain.replaceAll("[^a-zA-Z0-9_]", "");
							scopesToInclude.add(subdomain.toLowerCase());
						}

						getLocationsForCollectionStmt.setLong(1, collectionId);
						ResultSet locationsForCollectionRS = getLocationsForCollectionStmt.executeQuery();
						while (locationsForCollectionRS.next()) {
							String subLocation = locationsForCollectionRS.getString("subLocation");
							if (!locationsForCollectionRS.wasNull() && !subLocation.isEmpty()) {
								scopesToInclude.add(subLocation.replaceAll("[^a-zA-Z0-9_]", "").toLowerCase());
							} else {
								String code = locationsForCollectionRS.getString("code");
								scopesToInclude.add(code.replaceAll("[^a-zA-Z0-9_]", "").toLowerCase());
							}
						}

						extractAndIndexOaiCollection(collectionName, collectionId, metadataFormat, dateFormatting, deleted, subjectFilters, baseUrl, indexAllSets, setName, currentTime, loadOneMonthAtATime, scopesToInclude);
					}
				}
			}
		} catch (SQLException e) {
			logger.error("Error loading collections", e);
		}

		try {
			updateServer.close();
			updateServer = null;
		}catch (Exception e) {
			logger.error("Error closing update server ", e);
			System.exit(-5);
		}
	}

	private static void extractAndIndexOaiCollection(String collectionName, long collectionId, String metadataFormat, long dateFormatting, boolean deleted, ArrayList<Pattern> subjectFilters, String baseUrl, boolean indexAllSets, String setNames, long currentTime, boolean loadOneMonthAtATime, HashSet<String> scopesToInclude) {
		if (!deleted) {
			long startTime = new Date().getTime() / 1000;
			//Get the existing records for the collection
			//Get existing records for the collection
			OpenArchivesExtractLogEntry logEntry = createDbLogEntry(collectionName);

			logEntry.addNote("Starting Open Archives indexing for collection: " + collectionName);
			logEntry.addNote("Progress tracking: Record counters will update every 500 records processed.");
			logEntry.addNote("Date formatting setting: " + (dateFormatting == 1 ? "Convert to Date Format" : "Use original date format"));
			logEntry.addNote("Load one month at a time: " + loadOneMonthAtATime);

			int numRecordsLoaded = 0;
			int numRecordsSkipped = 0;
			// This commit batch size should be fine as the average Solr document for OA is ~21 KB.
			final int commitBatchSize = 500;

			TreeSet<String> allExistingCollectionSubjects = new TreeSet<>();

			ArrayList<String> oaiSets = new ArrayList<>();
			if (indexAllSets) {
				String listSetsUrl = baseUrl + "?verb=ListSets";

				logger.info("Loading sets from {}", listSetsUrl);
				HashMap<String, String> headers = new HashMap<>();
				headers.put("Accept", "text/xml,text/html,application/xhtml+xml,application/xml");
				headers.put("Accept-Encoding", "gzip");
				headers.put("Accept-Language", "en-US");
				headers.put("Pragma", "no-cache");

				WebServiceResponse oaiResponse = NetworkUtils.getURL(listSetsUrl, logger, headers);
				// Only parse XML if the HTTP call succeeded.
				if (!oaiResponse.isSuccess()) {
					logEntry.incErrors("Could not retrieve sets from " + listSetsUrl + " with response code: " + oaiResponse.getResponseCode() + " and response: " + oaiResponse.getMessage());
				} else {
					try {
						Document doc = parseXmlString(oaiResponse.getMessage());

						Element docElement = doc.getDocumentElement();
						NodeList listSets = docElement.getElementsByTagName("ListSets");
						if (listSets.getLength() > 0) {
							Element listSetsElement = (Element) listSets.item(0);
							NodeList allSets = listSetsElement.getElementsByTagName("set");
							for (int i = 0; i < allSets.getLength(); i++) {
								Node curSetNode = allSets.item(i);
								if (curSetNode instanceof Element) {
									NodeList children = curSetNode.getChildNodes();
									for (int j = 0; j < children.getLength(); j++) {
										Node curChild = children.item(j);
										if (curChild instanceof Element && ((Element) curChild).getTagName().equals("setSpec")) {
											oaiSets.add(curChild.getTextContent().trim());
										}
									}
								}
							}
						}
					} catch (Exception e) {
						logEntry.incErrors("Exception loading sets: ", e);
					}
				}
			}else{
				String[] setsArray = setNames.split(",");
				oaiSets.addAll(Arrays.asList(setsArray));
			}

			logEntry.addNote("Found " + oaiSets.size() + " OAI set(s) to process.");

			for (String oaiSet : oaiSets) {
				logger.info("Loading set {}", oaiSet);
				//To improve performance, load records for a month at a time
				GregorianCalendar now = new GregorianCalendar();
				//Protocol was invented in 2002, so we are safe starting in 2000 to cover anything from OA version 1
				for (int year = 2000; year <= now.get(GregorianCalendar.YEAR); year++) {
					for (int month = 1; month <= 12; month++) {
						boolean continueLoading = true;
						String resumptionToken = null;
						while (continueLoading) {
							continueLoading = false;

							String oaiUrl;
							if (resumptionToken != null) {
								oaiUrl = baseUrl + "?verb=ListRecords&resumptionToken=" + URLEncoder.encode(resumptionToken, StandardCharsets.UTF_8);
							} else {
								oaiUrl = baseUrl + "?verb=ListRecords&metadataPrefix=" + metadataFormat;
								if (loadOneMonthAtATime) {
									String startDate = year + "-" + String.format("%02d", month) + "-01";
									String endDate = year + "-" + String.format("%02d", month + 1) + "-01";
									if (month == 12) {
										endDate = (year + 1) + "-01-01";
									}
									oaiUrl += "&from=" + startDate + "&until=" + endDate;
								}
								if (!oaiSet.isEmpty()) {
									oaiUrl += "&set=" + URLEncoder.encode(oaiSet, StandardCharsets.UTF_8);
								}
							}

							for (int j = 0; j < 3; j++) {
								WebServiceResponse oaiResponse = null;
								try {
									logger.info("Loading from {}", oaiUrl);
									HashMap<String, String> headers = new HashMap<>();
									headers.put("Accept", "text/xml,text/html,application/xhtml+xml,application/xml");
									headers.put("Accept-Encoding", "gzip");
									headers.put("Accept-Language", "en-US");
									headers.put("Pragma", "no-cache");
									oaiResponse = NetworkUtils.getURL(oaiUrl, logger, headers);
									if (oaiResponse.isSuccess()) {
										Document doc = parseXmlString(oaiResponse.getMessage());

										Element docElement = doc.getDocumentElement();
										//Normally we get list records, but if we are at the end of the list OAI may return an
										//error rather than ListRecords (even though it gave us a resumption token)
										NodeList listRecords = docElement.getElementsByTagName("ListRecords");
										if (listRecords.getLength() > 0) {
											Element listRecordsElement = (Element) docElement.getElementsByTagName("ListRecords").item(0);
											NodeList allRecords = listRecordsElement.getElementsByTagName("record");
											for (int i = 0; i < allRecords.getLength(); i++) {
												Node curRecordNode = allRecords.item(i);
												if (curRecordNode instanceof Element) {
													logEntry.incNumRecords();
													Element curRecordElement = (Element) curRecordNode;
													if (indexElement(curRecordElement, collectionId, collectionName, subjectFilters, dateFormatting, allExistingCollectionSubjects, logEntry, scopesToInclude, startTime)) {
														numRecordsLoaded++;
														if (numRecordsLoaded % commitBatchSize == 0) {
															try {
																updateServer.commit(false, false, true);
															} catch (SolrServerException | IOException e) {
																logEntry.incErrors("Error posting periodic commit to Solr: ", e);
															}
														}
													} else {
														numRecordsSkipped++;
													}
												}
											}

											//Check to see if there are more records to load and if so continue
											NodeList resumptionTokens = listRecordsElement.getElementsByTagName("resumptionToken");
											if (resumptionTokens.getLength() > 0) {
												Node resumptionTokenNode = resumptionTokens.item(0);
												if (resumptionTokenNode instanceof Element) {
													Element resumptionTokenElement = (Element) resumptionTokenNode;
													resumptionToken = resumptionTokenElement.getTextContent();
													if (!resumptionToken.isEmpty()) {
														continueLoading = true;
													}
												}
											}
										}
										break;
									} else {
										if (j == 2) {
											logEntry.incErrors("Could not retrieve records from " + oaiUrl + " response code " + oaiResponse.getResponseCode());
											logger.error(oaiResponse.getMessage());
										} else {
											Thread.sleep(500);
										}
									}
								} catch (Exception e) {
									logEntry.incErrors("Error parsing OAI data", e);
									if (oaiResponse != null){
										logger.error(oaiResponse.getMessage());
									}
								}
								logEntry.saveResults();
							}
						}
						if (!fullReload) {
							try {
								updateServer.commit(false, false, true);
							} catch (SolrServerException | IOException e) {
								logEntry.incErrors("Error posting documents to Solr", e);
							}
						}
						if (!loadOneMonthAtATime) {
							break;
						}
					}
					if (!loadOneMonthAtATime) {
						break;
					}
				}
			}

			logEntry.addNote("Loaded " + numRecordsLoaded + " records from " + collectionName + ".");
			if (numRecordsSkipped > 0) {
				logEntry.addNote("Skipped " + numRecordsSkipped + " records from " + collectionName + ".");
			}

			//Records to delete
			if (!logEntry.hasErrors()) {
				logEntry.addNote("Starting cleanup phase: Identifying records to delete that are no longer in the OAI source.");
				try {
					PreparedStatement getRecordsToDeleteStmt = aspenConn.prepareStatement("SELECT * from open_archives_record where lastSeen < " + startTime + " AND sourceCollection = " + collectionId, ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
					ResultSet recordsToDeleteRS = getRecordsToDeleteStmt.executeQuery();
					int numDeleted = 0;
					while (recordsToDeleteRS.next()) {
						//Delete from solr
						long idToDelete = recordsToDeleteRS.getLong("id");
						try {
							updateServer.deleteByQuery("id:\"" + idToDelete + "\"");
						} catch (BaseHttpSolrClient.RemoteSolrException rse) {
							logger.error("Solr is not running properly, try restarting", rse);
							System.exit(-1);
						} catch (Exception e) {
							logger.error("Error deleting from index", e);
						}

						//Delete from the database
						deleteOpenArchivesRecord.setLong(1, idToDelete);
						deleteOpenArchivesRecord.executeUpdate();
						logEntry.incDeleted();
						numDeleted++;
					}
					logEntry.addNote("Deleted " + numDeleted + " records from the collection");
					recordsToDeleteRS.close();
				} catch (BaseHttpSolrClient.RemoteSolrException rse) {
					logEntry.incErrors("Solr is not running properly, try restarting", rse);
					System.exit(-1);
				} catch (SQLException e) {
					logEntry.incErrors("Error deleting records that have not been seen since the start of indexing", e);
				}
			}

			//Now that we are done with all changes, commit them.
			logEntry.addNote("Starting final Solr commit to save all changes to the search index.");
			try {
				updateServer.commit(false, false, true);
			} catch (Exception e) {
				logEntry.incErrors("Error in final commit while finishing extract, shutting down", e);
				logEntry.setFinished();
				logEntry.saveResults();
				System.exit(-3);
			}

			//Update that we indexed the collection
			try {
				updateCollectionAfterIndexing.setLong(1, currentTime);
				updateCollectionAfterIndexing.setString(2, String.join("\n", allExistingCollectionSubjects));
				updateCollectionAfterIndexing.setLong(3, collectionId);
				updateCollectionAfterIndexing.executeUpdate();
			} catch (SQLException e) {
				logEntry.incErrors("Error updating the last fetch time for collection", e);
			}

			logEntry.setFinished();
		}else{
			//Clean up to be sure the index is cleared of old data
			try {
				//Use the ID rather than name in case the name changes.
				updateServer.deleteByQuery("collection_id:\"" + collectionId + "\"");
				updateServer.commit(false, false, true);
				//3-19-2019 Don't commit so the index does not get cleared during run (but will clear at the end).
			} catch (BaseHttpSolrClient.RemoteSolrException rse) {
				logger.error("Solr is not running properly, try restarting", rse);
				System.exit(-1);
			} catch (Exception e) {
				logger.error("Error deleting from index", e);
			}
		}
	}

	private static OpenArchivesExtractLogEntry createDbLogEntry(String collectionName) {
		Date startTime = new Date();
		//Remove log entries older than 45 days
		long earliestLogToKeep = (startTime.getTime() / 1000) - (60 * 60 * 24 * 45);
		try {
			int numDeletions = aspenConn.prepareStatement("DELETE from open_archives_export_log WHERE startTime < " + earliestLogToKeep).executeUpdate();
			logger.info("Deleted {} old log entries", numDeletions);
		} catch (SQLException e) {
			logger.error("Error deleting old log entries", e);
		}

		//Start a log entry
		return new OpenArchivesExtractLogEntry(collectionName, aspenConn, logger);
	}

	private static void setupSolrClient(String solrHost, String solrPort) {
		Http2SolrClient http2Client = new Http2SolrClient.Builder().build();
		try {
			updateServer = new ConcurrentUpdateHttp2SolrClient.Builder("http://" + solrHost + ":" + solrPort + "/solr/open_archives", http2Client)
					.withThreadCount(1)
					.withQueueSize(25)
					.build();
		}catch (OutOfMemoryError e) {
			logger.error("Unable to create solr client, out of memory", e);
			System.exit(-7);
		}
	}

	private static boolean indexElement(Element curRecordElement, Long collectionId, String collectionName, ArrayList<Pattern> subjectFilters, long dateFormatting, Set<String> collectionSubjects, OpenArchivesExtractLogEntry logEntry, HashSet<String> scopesToInclude, Long startTime) {
		OAISolrRecord solrRecord = new OAISolrRecord();
		solrRecord.setCollectionId(collectionId);
		solrRecord.setCollectionName(collectionName);
		solrRecord.setScopesToInclude(scopesToInclude);
		logger.debug("Indexing element");
		NodeList children = curRecordElement.getChildNodes();
		for (int i = 0; i < children.getLength(); i++) {
			Node curChild = children.item(i);
			if (curChild instanceof Element && ((Element) curChild).getTagName().equals("header")) {
				Element headerElement = (Element) curChild;
				if (headerElement.hasAttribute("status")) {
					if (headerElement.getAttribute("status").equalsIgnoreCase("deleted")) {
						//This record is deleted, no sense evaluating further. Don't mark it as skipped
						//If it is newly deleted, that will show in the stats if it was previously indexed.
						return false;
					}
				}
			} else if (curChild instanceof Element && ((Element) curChild).getTagName().equals("metadata")) {
				Element metadataElement = (Element) curChild;
				NodeList metadataChildren = metadataElement.getChildNodes();
				for (int metaDataChildCtr = 0; metaDataChildCtr < metadataChildren.getLength(); metaDataChildCtr++) {
					Node curMetadataChild = metadataChildren.item(metaDataChildCtr);
					if (curMetadataChild instanceof Element && (((Element) curMetadataChild).getTagName().equals("oai_dc:dc") || ((Element) curMetadataChild).getTagName().equals("mods:mods"))) {
						Element curMetadataChildElement = (Element) curMetadataChild;

						NodeList metadataFields = curMetadataChildElement.getChildNodes();
						for (int j = 0; j < metadataFields.getLength(); j++) {
							Node curNode = metadataFields.item(j);
							if (curNode instanceof Element) {
								Element metadataFieldElement = (Element) curNode;
								String metadataTag = metadataFieldElement.getTagName();
								String textContent = metadataFieldElement.getTextContent().trim();
								switch (metadataTag) {
									case "mods:titleInfo":
									case "dc:title":
										solrRecord.setTitle(textContent);
										break;
									case "dc:identifier":
									case "mods:identifier":
										String textContentLower = textContent.toLowerCase();
										if (textContentLower.startsWith("http") && !textContentLower.endsWith(".jpg") && !textContentLower.endsWith(".mp3") && !textContentLower.endsWith(".pdf")) {
											if (solrRecord.getIdentifier() == null || !solrRecord.getIdentifier().startsWith("http")) {
												solrRecord.setIdentifier(textContent);
											} else {
												//Keep the longest identifier
												if (solrRecord.getIdentifier().length() < textContent.length()) {
													solrRecord.setIdentifier(textContent);
												}
											}
										} else if (solrRecord.getIdentifier() == null) {
											solrRecord.setIdentifier(textContent);
										}
										break;
									case "dc:creator":
										solrRecord.addCreator(textContent);
										break;
									case "dc:contributor":
										solrRecord.addContributor(textContent);
										break;
									case "dc:description":
									case "mods:abstract":
										solrRecord.setDescription(textContent);
										break;
									case "dc:type":
										solrRecord.setType(textContent);
										break;
									case "dc:subject":
									case "mods:subject":
									case "mods:genre":
										String[] subjects = textContent.split("\\s*;\\s*");
										//Clean the subjects up
										for (int subjectIdx = 0; subjectIdx < subjects.length; subjectIdx++) {
											//noinspection RegExpRedundantEscape
											subjects[subjectIdx] = subjects[subjectIdx].replaceAll("\\[info:.*?\\]", "").trim();
											//MODS has geographic coordinates which show separated by a line feed, cut those off.
											int lineFeedPos = subjects[subjectIdx].indexOf('\n');
											if (lineFeedPos > 0) {
												subjects[subjectIdx] = subjects[subjectIdx].substring(0, lineFeedPos);
											}
										}
										solrRecord.addSubjects(subjects);
										Collections.addAll(collectionSubjects, subjects);
										break;
									case "dc:coverage":
										solrRecord.addCoverage(textContent);
										break;
									case "dc:publisher":
										solrRecord.addPublisher(textContent);
										break;
									case "dc:format":
									case "mods:typeOfResource":
										solrRecord.addFormat(textContent);
										break;
									case "dc:source":
										solrRecord.addSource(textContent);
										break;
									case "dc:language":
									case "mods:language":
										solrRecord.setLanguage(textContent);
										break;
									case "dc:relation":
										solrRecord.addRelation(textContent);
										break;
									case "dc:rights":
										solrRecord.setRights(textContent);
										break;
									case "dc:date":
										addDatesToRecord(solrRecord, textContent, logEntry, dateFormatting);
										break;
									case "mods:originInfo":
										NodeList originInfoFields = metadataFieldElement.getChildNodes();
										for (int k = 0; k < originInfoFields.getLength(); k++) {
											Node curOriginInfoNode = originInfoFields.item(k);
											if (curOriginInfoNode instanceof Element) {
												Element originInfoFieldElement = (Element) curOriginInfoNode;
												String originInfoTag = originInfoFieldElement.getTagName();
												String originInfoTextContent = originInfoFieldElement.getTextContent().trim();

												if (originInfoTag.equals("mods:publisher")) {
													solrRecord.addPublisher(originInfoTextContent);
												} else if (originInfoTag.equals("mods:dateCreated") || originInfoTag.equals("mods:dateIssued")) {
													addDatesToRecord(solrRecord, originInfoTextContent, logEntry, dateFormatting);
												} else {
													logger.warn("Unhandled origin info tag " + originInfoTag + " value = " + originInfoTextContent);
												}

											}
										}
										break;
									case "mods:location":
										NodeList locationFields = metadataFieldElement.getChildNodes();
										for (int k = 0; k < locationFields.getLength(); k++) {
											Node curLocationNode = locationFields.item(k);
											if (curLocationNode instanceof Element) {
												Element locationFieldElement = (Element) curLocationNode;
												String locationTag = locationFieldElement.getTagName();
												String locationTextContent = locationFieldElement.getTextContent().trim();

												if (locationTag.equals("mods:physicalLocation")) {
													solrRecord.addLocation(locationTextContent);
												} else if (locationTag.equals("mods:url")) {
													//Ignore for now
												} else {
													logger.warn("Unhandled location tag " + locationTag + " value = " + locationTextContent);
												}

											}
										}
										break;
									case "mods:recordInfo":
										NodeList recordInfoFields = metadataFieldElement.getChildNodes();
										for (int k = 0; k < recordInfoFields.getLength(); k++) {
											Node curRecordInfoNode = recordInfoFields.item(k);
											if (curRecordInfoNode instanceof Element) {
												Element recordInfoFieldElement = (Element) curRecordInfoNode;
												String recordInfoTag = recordInfoFieldElement.getTagName();
												String recordInfoTextContent = recordInfoFieldElement.getTextContent().trim();

												if (recordInfoTag.equals("mods:recordContentSource")) {
													solrRecord.addSource(recordInfoTextContent);
												} else if (recordInfoTag.equals("mods:recordOrigin")) {
													//Ignore for now
												} else {
													logger.warn("Unhandled location tag " + recordInfoTag + " value = " + recordInfoTextContent);
												}

											}
										}
										break;
									case "mods:name":
										NodeList nameFields = metadataFieldElement.getChildNodes();
										String name = "";
										String role = "";
										for (int k = 0; k < nameFields.getLength(); k++) {
											Node curNameNode = nameFields.item(k);
											if (curNameNode instanceof Element) {
												Element nameFieldElement = (Element) curNameNode;
												String nameInfoTag = nameFieldElement.getTagName();
												String nameInfoTextContent = nameFieldElement.getTextContent().trim();

												if (nameInfoTag.equals("mods:namePart")) {
													name = nameInfoTextContent;
												} else if (nameInfoTag.equals("mods:role")) {
													role = nameInfoTextContent;
												} else {
													logger.warn("Unhandled location tag " + nameInfoTag + " value = " + nameInfoTextContent);
												}

											}
										}
										if (!name.isEmpty()) {
											if (role.equals("Creator") || role.equals("Photographer") || role.equals("Architect") || role.equals("Artist") || role.equals("Engraver")) {
												solrRecord.addCreator(name);
											}else if (role.equals("Contributor") || role.equals("Director") || role.equals("Translator") || role.equals("Addressee") || role.equals("First party") || role.equals("Second party")) {
												solrRecord.addContributor(name);
											//}else if (role.equals("Addressee")) {
												//Ignore for now
											}else{
												logger.warn("Unhandled role " + role);
											}
										}
										break;
									case "mods:relatedItem":
									case "mods:accessCondition":
									case "mods:note":
									case "mods:physicalDescription":
									case "mods:tableOfContents":
										//Ignore this tag for now
										break;
									default:
										logger.warn("Unhandled tag " + metadataTag + " value = " + textContent);
								}
							}
						}
					}
				}
			}
		}
		boolean addedToIndex = false;
		try {
			if (solrRecord.getIdentifier() == null || solrRecord.getTitle() == null) {
				logEntry.incSkipped();
				logger.debug("Skipping record because no identifier was provided.");
			} else {
				boolean subjectMatched = true;
				if (!subjectFilters.isEmpty()) {
					subjectMatched = false;
					for (String curSubject : solrRecord.getSubjects()) {
						for (Pattern curSubjectFilter : subjectFilters) {
							if (curSubjectFilter.matcher(curSubject).find()) {
								subjectMatched = true;
								break;
							}
						}
						if (subjectMatched) {
							break;
						}
					}
				}
				if (!subjectMatched) {
					logger.debug("Skipping record because no subject matched.");
					logEntry.incSkipped();
				} else {
					solrRecord.setCollectionId(collectionId);
					solrRecord.setCollectionName(collectionName);
					try {
						//Check the database to see if this already exists
						getExistingRecordForCollection.setLong(1, collectionId);
						getExistingRecordForCollection.setString(2, solrRecord.getIdentifier());
						ResultSet existingRecordRS = getExistingRecordForCollection.executeQuery();
						if (existingRecordRS.next()) {
							long lastSeen = existingRecordRS.getLong("lastSeen");
							if (lastSeen >= startTime) {
								logEntry.addNote("Record was already processed " + solrRecord.getIdentifier());
								logEntry.incSkipped();
							} else {
								solrRecord.setId(existingRecordRS.getString("id"));
								updateServer.add(solrRecord.getSolrDocument());
								updateLastSeenForRecord.setLong(1, new Date().getTime() / 1000);
								updateLastSeenForRecord.setLong(2, existingRecordRS.getLong("id"));
								updateLastSeenForRecord.executeUpdate();
								addedToIndex = true;
								logEntry.incUpdated();
							}
						} else {
							addOpenArchivesRecord.setLong(1, collectionId);
							addOpenArchivesRecord.setString(2, solrRecord.getIdentifier());
							addOpenArchivesRecord.setLong(3, new Date().getTime() / 1000);
							addOpenArchivesRecord.executeUpdate();
							ResultSet rs = addOpenArchivesRecord.getGeneratedKeys();
							if (rs.next()) {
								solrRecord.setId(rs.getString(1));
								updateServer.add(solrRecord.getSolrDocument());
								addedToIndex = true;
							}
							rs.close();
							logEntry.incAdded();
						}
					} catch (SQLException e) {
						logEntry.incErrors("Error adding record to database", e);
					} catch (SolrServerException e) {
						logEntry.incErrors("Error adding document to solr server", e);
					}
				}
			}
		} catch (IOException e) {
			logEntry.incErrors("I/O Error adding document to solr server", e);
		}
		return addedToIndex;
	}

	private static void addDatesToRecord(OAISolrRecord solrRecord, String textContent, OpenArchivesExtractLogEntry logEntry, long dateFormatting) {
		String[] dateRange;

		if(dateFormatting==1) {
			if (textContent.contains(";")) {
				dateRange = textContent.split(";");
			} else if (textContent.contains(" -- ")) {
				dateRange = textContent.split(" -- ");
			} else {
				textContent = textContent.trim();
				textContent = textContent.replaceAll("ca.\\s+", "");
				textContent = textContent.replaceAll("[\\[/]\"]", "-");
				//TODO: If the textContent contains a space or a T, only use the portion of the date before the space or T
				if (textContent.matches("\\d{2,4}(-\\d{1,2})?(-\\d{1,2})?")) {
					dateRange = new String[]{textContent};
				} else if (textContent.matches("(\\d{1,2}/)?(\\d{1,2}/)?\\d{2,4}")) {
					dateRange = new String[]{textContent};
				}else{
					logEntry.addNote("Unhandled date format " + textContent + " not loading date");
					dateRange = new String[0];
				}
			}
			for (int tmpIndex = 0; tmpIndex < dateRange.length; tmpIndex++) {
				dateRange[tmpIndex] = dateRange[tmpIndex].trim();
				dateRange[tmpIndex] = dateRange[tmpIndex].replaceAll("[\\[/]\"]", "-");
				dateRange[tmpIndex] = dateRange[tmpIndex].replaceAll("ca.\\s+", "");
			}
		}else{
			dateRange = new String[]{textContent}; //no extra format just take as is from Open Archive item
		}
		solrRecord.addDates(dateRange, logger, dateFormatting);
	}

	/**
	 * Parses a raw XML string into a {@link Document} object after cleaning it.
	 * Then, it uses a non-validating {@link DocumentBuilder} with whitespace-ignoring enabled
	 * to parse the cleaned XML into a DOM {@link Document}.
	 *
	 * @param xml The raw XML string to parse.
	 * @return A {@link Document} representing the parsed XML content.
	 * @throws Exception If an error occurs during parsing.
	 */
	private static Document parseXmlString(String xml) throws Exception {
		// Strip any BOM characters and drop everything before the first XML element.
		xml = xml.replace("\uFEFF", "");
		int idx = xml.indexOf('<');
		if (idx > 0) {
			xml = xml.substring(idx);
		}
		xml = xml.trim();
		DocumentBuilderFactory factory = DocumentBuilderFactory.newInstance();
		factory.setValidating(false);
		factory.setIgnoringElementContentWhitespace(true);
		DocumentBuilder builder = factory.newDocumentBuilder();
		return builder.parse(new InputSource(new StringReader(xml)));
	}
}
