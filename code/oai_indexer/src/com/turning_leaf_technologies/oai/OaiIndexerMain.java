package com.turning_leaf_technologies.oai;

import com.turning_leaf_technologies.config.ConfigUtil;
import com.turning_leaf_technologies.logging.LoggingUtil;
import com.turning_leaf_technologies.strings.StringUtils;
import org.apache.logging.log4j.Logger;
import org.apache.solr.client.solrj.SolrServerException;
import org.apache.solr.client.solrj.impl.BinaryRequestWriter;
import org.apache.solr.client.solrj.impl.ConcurrentUpdateSolrClient;
import org.apache.solr.client.solrj.impl.HttpSolrClient;
import org.ini4j.Ini;
import org.w3c.dom.Document;
import org.w3c.dom.Element;
import org.w3c.dom.Node;
import org.w3c.dom.NodeList;

import javax.xml.parsers.DocumentBuilder;
import javax.xml.parsers.DocumentBuilderFactory;
import java.io.IOException;
import java.io.UnsupportedEncodingException;
import java.net.URLEncoder;
import java.sql.*;
import java.util.*;
import java.util.Date;
import java.util.regex.Pattern;

public class OaiIndexerMain {
	private static Logger logger;

	private static Ini configIni;
	private static ConcurrentUpdateSolrClient updateServer;

	private static Connection aspenConn;

	private static boolean fullReload = false;

	private static PreparedStatement getOpenArchiveCollections;
	private static PreparedStatement addOpenArchivesRecord;
	private static PreparedStatement getExistingRecordsForCollection;
	private static PreparedStatement updateCollectionAfterIndexing;
	private static PreparedStatement deleteOpenArchivesRecord;

	public static void main(String[] args) {
		String serverName;
		if (args.length == 0) {
			serverName = StringUtils.getInputFromCommandLine("Please enter the server name");
			if (serverName.length() == 0) {
				System.out.println("You must provide the server name as the first argument.");
				System.exit(1);
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
		logger.info("Starting " + processName + ": " + startTime.toString());

		// Read the base INI file to get information about the server (current directory/cron/config.ini)
		configIni = ConfigUtil.loadConfigFile("config.ini", serverName, logger);

		//Connect to the aspen database
		connectToDatabase();

		extractAndIndexOaiData();

		logger.info("Finished " + new Date().toString());
		long endTime = new Date().getTime();
		long elapsedTime = endTime - startTime.getTime();
		logger.info("Elapsed Minutes " + (elapsedTime / 60000));
	}

	private static void connectToDatabase() {
		try {
			String databaseConnectionInfo = ConfigUtil.cleanIniValue(configIni.get("Database", "database_aspen_jdbc"));
			aspenConn = DriverManager.getConnection(databaseConnectionInfo);
			getOpenArchiveCollections = aspenConn.prepareStatement("SELECT * FROM open_archives_collection ORDER BY name");
			addOpenArchivesRecord = aspenConn.prepareStatement("INSERT INTO open_archives_record (sourceCollection, permanentUrl) VALUES (?, ?)", PreparedStatement.RETURN_GENERATED_KEYS);
			getExistingRecordsForCollection = aspenConn.prepareStatement("SELECT id, permanentUrl from open_archives_record WHERE sourceCollection = ?");
			updateCollectionAfterIndexing = aspenConn.prepareStatement("UPDATE open_archives_collection SET lastFetched = ?, subjects = ? WHERE id = ?");
			deleteOpenArchivesRecord = aspenConn.prepareStatement("DELETE FROM open_archives_record WHERE id = ?");
		} catch (Exception e) {
			logger.error("Error connecting to aspen database", e);
			System.exit(1);
		}
	}

	private static void extractAndIndexOaiData() {
		String solrPort = configIni.get("Reindex", "solrPort");
		setupSolrClient(solrPort);

		if (fullReload) {
			try {
				updateServer.deleteByQuery("*:*");
				//3-19-2019 Don't commit so the index does not get cleared during run (but will clear at the end).
			} catch (HttpSolrClient.RemoteSolrException rse) {
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
							needsIndexing = lastFetched < (currentTime - 24 * 60 * 60);
							break;
						case "weekly":
							needsIndexing = lastFetched < (currentTime - 7 * 24 * 60 * 60);
							break;
						case "monthly":
							needsIndexing = lastFetched < (currentTime - 30 * 24 * 60 * 60);
							break;
						case "yearly":
							needsIndexing = lastFetched < (currentTime - 3655 * 24 * 60 * 60);
							break;
					}
				}
				if (needsIndexing) {
					long collectionId = collectionsRS.getLong("id");
					String baseUrl = collectionsRS.getString("baseUrl");
					String setName = collectionsRS.getString("setName");
					String subjectFilterString = collectionsRS.getString("subjectFilters");
					boolean loadOneMonthAtATime = collectionsRS.getBoolean("loadOneMonthAtATime");
					ArrayList<Pattern> subjectFilters = new ArrayList<>();
					if (subjectFilterString != null && subjectFilterString.length() > 0) {
						String[] subjectFiltersRaw = subjectFilterString.split("\\s*(\\r\\n|\\n|\\r)\\s*");
						for (String subjectFilter : subjectFiltersRaw) {
							if (subjectFilter.length() > 0) {
								subjectFilters.add(Pattern.compile("(\\b|-)" + subjectFilter.toLowerCase() + "(\\b|-)", Pattern.CASE_INSENSITIVE));
							}
						}
					}

					HashSet<String> scopesToInclude = new HashSet<>();

					//Get a list of libraries and locations that the setting applies to
					getLibrariesForCollectionStmt.setLong(1, collectionId);
					ResultSet librariesForCollectionRS = getLibrariesForCollectionStmt.executeQuery();
					while (librariesForCollectionRS.next()){
						String subdomain = librariesForCollectionRS.getString("subdomain");
						subdomain = subdomain.replaceAll("[^a-zA-Z0-9_]", "");
						scopesToInclude.add(subdomain.toLowerCase());
					}

					getLocationsForCollectionStmt.setLong(1, collectionId);
					ResultSet locationsForCollectionRS = getLocationsForCollectionStmt.executeQuery();
					while (locationsForCollectionRS.next()){
						String subLocation = locationsForCollectionRS.getString("subLocation");
						if (!locationsForCollectionRS.wasNull() && subLocation.length() > 0){
							scopesToInclude.add(subLocation.replaceAll("[^a-zA-Z0-9_]", "").toLowerCase());
						}else {
							String code = locationsForCollectionRS.getString("code");
							scopesToInclude.add(code.replaceAll("[^a-zA-Z0-9_]", "").toLowerCase());
						}
					}

					extractAndIndexOaiCollection(collectionName, collectionId, subjectFilters, baseUrl, setName, currentTime, loadOneMonthAtATime, scopesToInclude);
				}
			}
		} catch (SQLException e) {
			logger.error("Error loading collections", e);
		}
	}

	private static void extractAndIndexOaiCollection(String collectionName, long collectionId, ArrayList<Pattern> subjectFilters, String baseUrl, String setNames, long currentTime, boolean loadOneMonthAtATime, HashSet<String> scopesToInclude) {
		//Get the existing records for the collection
		//Get existing records for the collection
		OpenArchivesExtractLogEntry logEntry = createDbLogEntry(collectionName);

		HashMap<String, ExistingOAIRecord> existingRecords = new HashMap<>();
		if (!fullReload) {
			//Only need to do this if we aren't doing a full reload since the full reload deletes everything
			try {
				updateServer.deleteByQuery("collection_name:\"" + collectionName + "\"");
				//3-19-2019 Don't commit so the index does not get cleared during run (but will clear at the end).
			} catch (HttpSolrClient.RemoteSolrException rse) {
				logger.error("Solr is not running properly, try restarting", rse);
				System.exit(-1);
			} catch (Exception e) {
				logger.error("Error deleting from index", e);
			}
		}

		//Load existing records from the database so we can cleanup later if needed.
		try {
			getExistingRecordsForCollection.setLong(1, collectionId);
			ResultSet existingRecordsRS = getExistingRecordsForCollection.executeQuery();
			while (existingRecordsRS.next()) {
				ExistingOAIRecord existingRecord = new ExistingOAIRecord();
				existingRecord.url = existingRecordsRS.getString("permanentUrl");
				existingRecord.id = existingRecordsRS.getLong("id");
				existingRecords.put(existingRecord.url, existingRecord);
			}
		} catch (Exception e) {
			logger.error("Error loading records for collection " + collectionName, e);
			return;
		}

		int numRecordsLoaded = 0;
		int numRecordsSkipped = 0;

		TreeSet<String> allExistingCollectionSubjects = new TreeSet<>();

		String[] oaiSets = setNames.split(",");
		for (String oaiSet : oaiSets) {
			logger.info("Loading set " + oaiSet);
			//To improve performance, load records for a month at a time
			GregorianCalendar now = new GregorianCalendar();
			//Protocol was invented in 2002 so we are safe starting in 2000 to cover anything from OA version 1
			for (int year = 2000; year <= now.get(GregorianCalendar.YEAR); year++) {
				for (int month = 1; month <= 12; month++) {
					boolean continueLoading = true;
					String resumptionToken = null;
					while (continueLoading) {
						continueLoading = false;

						String oaiUrl;
						if (resumptionToken != null) {
							try {
								oaiUrl = baseUrl + "?verb=ListRecords&resumptionToken=" + URLEncoder.encode(resumptionToken, "UTF-8");
							} catch (UnsupportedEncodingException e) {
								logEntry.incErrors("Error encoding resumption token", e);
								return;
							}
						} else {
							oaiUrl = baseUrl + "?verb=ListRecords&metadataPrefix=oai_dc";
							if (loadOneMonthAtATime) {
								String startDate = year + "-" + String.format("%02d", month) + "-01";
								String endDate = year + "-" + String.format("%02d", month + 1) + "-01";
								if (month == 12) {
									endDate = (year + 1) + "-01-01";
								}
								oaiUrl += "&from=" + startDate + "&until=" + endDate;
							}
							if (oaiSet.length() > 0) {
								try {
									oaiUrl += "&set=" + URLEncoder.encode(oaiSet, "UTF8");
								} catch (UnsupportedEncodingException e) {
									logEntry.incErrors("Error encoding resumption token", e);
									return;
								}
							}

						}
						try {
							logger.info("Loading from " + oaiUrl);
							DocumentBuilderFactory factory = DocumentBuilderFactory.newInstance();
							factory.setValidating(false);
							factory.setIgnoringElementContentWhitespace(true);
							DocumentBuilder builder = factory.newDocumentBuilder();

							Document doc = builder.parse(oaiUrl);
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
										if (indexElement(curRecordElement, existingRecords, collectionId, collectionName, subjectFilters, allExistingCollectionSubjects, logEntry, scopesToInclude)) {
											numRecordsLoaded++;
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
										if (resumptionToken.length() > 0) {
											continueLoading = true;
										}
									}
								}
							}
						} catch (Exception e) {
							logEntry.incErrors("Error parsing OAI data ", e);
						}
						logEntry.saveResults();
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

		if (existingRecords.size() > 0) {
			try {
				ArrayList<Long> idsToDelete = new ArrayList<>();
				for (ExistingOAIRecord existingOAIRecord : existingRecords.values()) {
					if (!existingOAIRecord.processed) {
						idsToDelete.add(existingOAIRecord.id);
					}
				}
				logEntry.addNote("Deleted " + idsToDelete.size() + " records from " + collectionName + ".");
				for (Long idToDelete : idsToDelete) {
					deleteOpenArchivesRecord.setLong(1, idToDelete);
					deleteOpenArchivesRecord.executeUpdate();
					logEntry.incDeleted();
				}
				//3-19-2019 Don't commit so the index does not get cleared during run (but will clear at the end).
			} catch (HttpSolrClient.RemoteSolrException rse) {
				logEntry.incErrors("Solr is not running properly, try restarting", rse);
				System.exit(-1);
			} catch (Exception e) {
				logEntry.incErrors("Error deleting ids from index", e);
			}
		}

		//Now that we are done with all changes, commit them.
		try {
			updateServer.commit(true, true, false);
		} catch (Exception e) {
			logEntry.incErrors("Error in final commit", e);
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
	}

	private static OpenArchivesExtractLogEntry createDbLogEntry(String collectionName) {
		Date startTime = new Date();
		//Remove log entries older than 45 days
		long earliestLogToKeep = (startTime.getTime() / 1000) - (60 * 60 * 24 * 45);
		try {
			int numDeletions = aspenConn.prepareStatement("DELETE from open_archives_export_log WHERE startTime < " + earliestLogToKeep).executeUpdate();
			logger.info("Deleted " + numDeletions + " old log entries");
		} catch (SQLException e) {
			logger.error("Error deleting old log entries", e);
		}

		//Start a log entry
		return new OpenArchivesExtractLogEntry(collectionName, aspenConn, logger);
	}

	private static void setupSolrClient(String solrPort) {
		ConcurrentUpdateSolrClient.Builder solrBuilder = new ConcurrentUpdateSolrClient.Builder("http://localhost:" + solrPort + "/solr/open_archives");
		solrBuilder.withThreadCount(1);
		solrBuilder.withQueueSize(25);
		updateServer = solrBuilder.build();
		updateServer.setRequestWriter(new BinaryRequestWriter());
	}

	private static boolean indexElement(Element curRecordElement, HashMap<String, ExistingOAIRecord> existingRecords, Long collectionId, String collectionName, ArrayList<Pattern> subjectFilters, Set<String> collectionSubjects, OpenArchivesExtractLogEntry logEntry, HashSet<String> scopesToInclude) {
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
					if (curMetadataChild instanceof Element && ((Element) curMetadataChild).getTagName().equals("oai_dc:dc")) {
						Element curMetadataChildElement = (Element) curMetadataChild;

						NodeList metadataFields = curMetadataChildElement.getChildNodes();
						for (int j = 0; j < metadataFields.getLength(); j++) {
							Node curNode = metadataFields.item(j);
							if (curNode instanceof Element) {
								Element metadataFieldElement = (Element) curNode;
								String metadataTag = metadataFieldElement.getTagName();
								String textContent = metadataFieldElement.getTextContent();
								switch (metadataTag) {
									case "dc:title":
										solrRecord.setTitle(textContent);
										break;
									case "dc:identifier":
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
										solrRecord.setCreator(textContent);
										break;
									case "dc:contributor":
										solrRecord.setContributor(textContent);
										break;
									case "dc:description":
										solrRecord.setDescription(textContent);
										break;
									case "dc:type":
										solrRecord.setType(textContent);
										break;
									case "dc:subject":
										String[] subjects = textContent.split("\\s*;\\s*");
										//Clean the subjects up
										for (int subjectIdx = 0; subjectIdx < subjects.length; subjectIdx++) {
											subjects[subjectIdx] = subjects[subjectIdx].replaceAll("\\[info:.*?\\]", "").trim();
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
										solrRecord.addFormat(textContent);
										break;
									case "dc:source":
										solrRecord.addSource(textContent);
										break;
									case "dc:language":
										solrRecord.setLanguage(textContent);
										break;
									case "dc:relation":
										solrRecord.addRelation(textContent);
										break;
									case "dc:rights":
										solrRecord.setRights(textContent);
										break;
									case "dc:date":
										String[] dateRange;
										if (textContent.contains(";")) {
											dateRange = textContent.split(";");
										} else if (textContent.contains(" -- ")) {
											dateRange = textContent.split(" -- ");
										} else {
											textContent = textContent.trim();
											textContent = textContent.replaceAll("ca.\\s+", "");
											textContent = textContent.replaceAll("/", "-");
											if (textContent.matches("\\d{2,4}(-\\d{1,2})?(-\\d{1,2})?")) {
												dateRange = new String[]{textContent};
											}else{
												logEntry.addNote("Unhandled date format " + textContent + " not loading date");
												dateRange = new String[0];
											}
										}
										for (int tmpIndex = 0; tmpIndex < dateRange.length; tmpIndex++) {
											dateRange[tmpIndex] = dateRange[tmpIndex].trim();
											dateRange[tmpIndex] = dateRange[tmpIndex].replaceAll("/", "-");
											dateRange[tmpIndex] = dateRange[tmpIndex].replaceAll("ca.\\s+", "");
										}
										solrRecord.addDates(dateRange);

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
				if (subjectFilters.size() > 0) {
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
						if (existingRecords.containsKey(solrRecord.getIdentifier())) {
							ExistingOAIRecord existingRecord = existingRecords.get(solrRecord.getIdentifier());
							if (existingRecord.processed) {
								logEntry.addNote("Record was already processed " + solrRecord.getIdentifier());
								logEntry.incSkipped();
							} else {
								solrRecord.setId(Long.toString(existingRecord.id));
								updateServer.add(solrRecord.getSolrDocument());
								addedToIndex = true;
								logEntry.incUpdated();
							}
						} else {
							addOpenArchivesRecord.setLong(1, collectionId);
							addOpenArchivesRecord.setString(2, solrRecord.getIdentifier());
							addOpenArchivesRecord.executeUpdate();
							ResultSet rs = addOpenArchivesRecord.getGeneratedKeys();
							if (rs.next()) {
								solrRecord.setId(rs.getString(1));
								updateServer.add(solrRecord.getSolrDocument());
								ExistingOAIRecord existingRecord = new ExistingOAIRecord();
								existingRecord.id = rs.getLong(1);
								existingRecord.url = solrRecord.getIdentifier();
								existingRecords.put(solrRecord.getIdentifier(), existingRecord);
								addedToIndex = true;
							}
							rs.close();
							logEntry.incAdded();
						}
					} catch (SQLException e) {
						logEntry.incErrors("Error adding record to database", e);
					}
				}
			}
		} catch (SolrServerException e) {
			logEntry.incErrors("Error adding document to solr server", e);
		} catch (IOException e) {
			logEntry.incErrors("I/O Error adding document to solr server", e);
		}
		if (addedToIndex && existingRecords.containsKey(solrRecord.getIdentifier())) {
			existingRecords.get(solrRecord.getIdentifier()).processed = true;
		}
		return addedToIndex;
	}
}
