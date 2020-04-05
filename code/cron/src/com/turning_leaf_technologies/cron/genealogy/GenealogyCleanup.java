package com.turning_leaf_technologies.cron.genealogy;

import java.io.BufferedReader;
import java.io.FileNotFoundException;
import java.io.FileReader;
import java.io.IOException;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.io.OutputStreamWriter;
import java.net.HttpURLConnection;
import java.net.MalformedURLException;
import java.net.URL;
import java.sql.Connection;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.sql.Statement;
import java.sql.PreparedStatement;


import com.turning_leaf_technologies.cron.CronLogEntry;
import org.ini4j.Ini;
import org.ini4j.Profile.Section;
import com.turning_leaf_technologies.cron.CronProcessLogEntry;
import com.turning_leaf_technologies.cron.IProcessHandler;
import org.apache.logging.log4j.Logger;

import com.opencsv.CSVReader;

@SuppressWarnings("unused")
public class GenealogyCleanup implements IProcessHandler {
	private Connection dbConn;
	private CronProcessLogEntry processLog;

	@Override
	public void doCronProcess(String servername, Ini configIni, Section processSettings, Connection dbConn, CronLogEntry cronEntry, Logger logger) {
		this.dbConn = dbConn;
		processLog = new CronProcessLogEntry(cronEntry, "Genealogy Cleanup", dbConn, logger);
		processLog.saveResults();
		
		deleteDuplicates(configIni, processSettings);
		processLog.saveResults();
		
		importFiles(configIni, processSettings);
		processLog.saveResults();
		
		reindexPeople(configIni, processSettings);
		processLog.saveResults();
		
		optimizeIndex(configIni, processSettings);
		processLog.setFinished();
		processLog.saveResults();
	}

	private void optimizeIndex(Ini configIni, Section processSettings) {
		processLog.addNote("Optimizing genealogy index");
		String body = "<optimize/>";
		if (!doSolrUpdate(processSettings, body)) {
			processLog.incErrors("Genealogy Optimization Failed.");
		}else{
			processLog.incUpdated();
		}
	}

	private boolean doSolrUpdate(Section processSettings, String body) {
		try {
			String genealogyUrl = processSettings.get("genealogyIndex");
			if (genealogyUrl == null || genealogyUrl.length() == 0) {
				System.out.println("Unable to get url for genealogy in GenealogyCleanup section.  Please specify genealogyIndex key.");
				return false;
			}

			URL url = new URL(genealogyUrl + "/update/");
			HttpURLConnection conn = (HttpURLConnection) url.openConnection();
			conn.setDoOutput(true);
			conn.addRequestProperty("Content-Type", "text/xml");
			OutputStreamWriter wr = new OutputStreamWriter(conn.getOutputStream());
			wr.write(body);
			wr.flush();

			// Get the response
			InputStream _is;
			boolean doOutput = false;
			if (conn.getResponseCode() == 200) {
				_is = conn.getInputStream();
			} else {
				System.out.println("Error in update");
				System.out.println("  " + body);
				/* error from server */
				_is = conn.getErrorStream();
				doOutput = true;
			}
			BufferedReader rd = new BufferedReader(new InputStreamReader(_is));
			String line;
			while ((line = rd.readLine()) != null) {
				if (doOutput)
					System.out.println(line);
			}
			wr.close();
			rd.close();
			conn.disconnect();

			return true;
		} catch (MalformedURLException e) {
			System.out.println("Invalid url updating index " + e.toString());
			return false;
		} catch (IOException e) {
			System.out.println("IO Exception updating index " + e.toString());
			e.printStackTrace();
			return false;
		}
	}

	private void reindexPeople(Ini configIni, Section processSettings) {
		String reindexSetting = processSettings.get("reindex");
		if (reindexSetting == null || !reindexSetting.equals("true")) {
			processLog.addNote("Skipping reindexing people because reindex was not true.");
			return;
		}
		String genealogyUrl = processSettings.get("genealogyIndex");
		if (genealogyUrl == null || genealogyUrl.length() == 0) {
			processLog.addNote("Unable to get url for genealogy in GenealogyCleanup section.  Please specify genealogyIndex key.");
			return;
		}
		
		// Clear all existing people from the solr index
		doSolrUpdate(processSettings, "<delete><query>*:*</query></delete>");
		doSolrUpdate(processSettings, "<commit/>");
		doSolrUpdate(processSettings, "<optimize/>");

		// Run through all existing people in the database and index them.
		try {
			Statement peopleStatement = dbConn.createStatement();
			ResultSet personRs = peopleStatement.executeQuery("SELECT personId from person");
			int numPeople = 0;
			while (personRs.next()) {
				int personId = personRs.getInt("personId");
				System.out.println("Reindexing person " + personId);
				reindexPerson(processSettings, dbConn, personId);
				numPeople++;
				processLog.incUpdated();
				if (numPeople % 100 == 0){
					processLog.saveResults();
				}
			}
			personRs.close();
		} catch (SQLException e) {
			System.out.println("Unable to load people to reindex " + e.toString());
			e.printStackTrace();
		}
	}

	private void importFiles(Ini configIni, Section processSettings) {
		String importFile = processSettings.get("importFile");
		if (importFile == null || importFile.length() == 0) {
			processLog.incErrors("Skipping importing people because no importFile was specified.");
			return;
		}
		String genealogyUrl = configIni.get("Genealogy", "url");
		if (genealogyUrl == null || genealogyUrl.length() == 0) {
			processLog.addNote("Unable to get url for genealogy in GenealogyCleanup section.  Please specify genealogyIndex key.");
			return;
		}
		
		//Prepare statements
		PreparedStatement st1;
		PreparedStatement updatePersonStatement;
		PreparedStatement insertPersonStatement;
		PreparedStatement deleteMarriagesStatement;
		PreparedStatement insertMarriageStmt;
		PreparedStatement deleteObitsStatement;
		PreparedStatement insertObitStmt;
		try {
			String personExistsQuery = "SELECT personId, birthDateDay, birthDateMonth, birthDateYear, deathDateDay, deathDateMonth, deathDateYear FROM person where "
				+ " firstName = ?"
				+ " AND lastName = ?"
				+ " AND maidenName = ?"
				+ " AND birthDateDay = ?"
				+ " AND birthDateMonth = ?"
				+ " AND birthDateYear = ?";
			st1 = dbConn.prepareStatement(personExistsQuery);
			String updatePersonQuery = "UPDATE person SET firstName = ?, lastName = ?, maidenName =?, "
				+ " birthDateDay = ?, birthDateMonth = ?, birthDateYear = ?, " + " deathDateDay = ?, deathDateMonth = ?, deathDateYear = ?, "
				+ " ageAtDeath = ?, comments = ? WHERE personId = ?;";
			updatePersonStatement = dbConn.prepareStatement(updatePersonQuery);
			String insertPersonQuery = "INSERT INTO person (firstName, lastName, maidenName, " + " birthDateDay, birthDateMonth, birthDateYear, "
				+ " deathDateDay, deathDateMonth, deathDateYear, " + " ageAtDeath, comments) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);";
			insertPersonStatement = dbConn.prepareStatement(insertPersonQuery, Statement.RETURN_GENERATED_KEYS);
			
			// delete existing marriages and enter new
			String deleteMarriagesQuery = "DELETE FROM marriage where personId = ?";
			deleteMarriagesStatement = dbConn.prepareStatement(deleteMarriagesQuery);
			
			// insert 1st marriage if available
			String insertMarriageQuery = "INSERT INTO marriage (personId, spouseName, marriageDateDay, marriageDateMonth, marriageDateYear, comments) "
					+ " VALUES (?, ?, ?, ?, ?, ?);";
			insertMarriageStmt = dbConn.prepareStatement(insertMarriageQuery);
			
			deleteObitsStatement = dbConn.prepareStatement("DELETE FROM obituary where personId = ?");
			
			String insertObitQuery = "INSERT INTO obituary (personId, source, dateDay, dateMonth, dateYear, sourcePage, contents) "
				+ " VALUES (?, ?, ?, ?, ?, ?, ?);";
			insertObitStmt = dbConn.prepareStatement(insertObitQuery);
		} catch (SQLException e1) {
			processLog.incErrors("Could not prepare statements for importing people ");
			return;
		}	

		try {
			// Open the file and parse it as CSV
			try {
				CSVReader reader = new CSVReader(new FileReader(importFile));
				String[] nextLine;
				String[] headers = null;
				while ((nextLine = reader.readNext()) != null) {
					// headers are the first line
					if (headers == null) {
						headers = nextLine;
					} else {
						// We expect the following headers: MCPLD export
						// "FullName","Last Name","First Name","Maiden Name","Marriage date","Spouse 1","Spouse 2","Born","Died","Age","Obit 1 Paper","Obit 1 Date","Obit 1 Page","Obit 2 Paper","Obit 2 Date","Obit 2 Page","Obit 3 Paper","Obit 3 Date","Obit 3 Page","Comments"
						// insert the person into the database
						String lastName = nextLine[1];
						String firstName = nextLine[2];
						String maidenName = nextLine[3];
						if (maidenName.equals("N/A"))
							maidenName = "N/A";
						DateInfo marriageDate = new DateInfo(nextLine[4]);
						String marriageComment = marriageDate.isNotSet() ? marriageDate.getOriginalDate() : "";
						String spouse1 = nextLine[5];
						String spouse2 = nextLine[6];
						DateInfo birthDate = new DateInfo(nextLine[7]);
						DateInfo deathDate = new DateInfo(nextLine[8]);
						String ageAtDeath = nextLine[9];
						String obit1Source = nextLine[10];
						DateInfo obit1Date = new DateInfo(nextLine[11]);
						String obit1Page = nextLine[12];
						String obit2Source = nextLine[13];
						DateInfo obit2Date = new DateInfo(nextLine[14]);
						String obit2Page = nextLine[15];
						String obit3Source = nextLine[16];
						DateInfo obit3Date = new DateInfo(nextLine[17]);
						String obit3Page = nextLine[18];
						String comments = nextLine[19];
						comments += (birthDate.isNotSet() && birthDate.getOriginalDate().length() > 0 ? ", born: " + birthDate.getOriginalDate() : "");
						comments += (deathDate.isNotSet() && deathDate.getOriginalDate().length() > 0 ? ", died: " + deathDate.getOriginalDate() : "");
						// Check to see if the person already exists.
						
						st1.setString(1, firstName);
						st1.setString(2, lastName);
						st1.setString(3, maidenName);
						st1.setInt(4, birthDate.getDay());
						st1.setInt(5, birthDate.getMonth());
						st1.setInt(6, birthDate.getYear());
						try {
							ResultSet personExistsRs = st1.executeQuery();
							boolean foundMatch = false;
							Integer personId = null;
							if (personExistsRs.next()) {
								// Check to see if we have a match of the birth date and/or death
								// date
								foundMatch = true;
								personId = personExistsRs.getInt("personId");
							}
							if (foundMatch) {
								// System.out.println("updating person " + personId);
								updatePersonStatement.setString(1, firstName);
								updatePersonStatement.setString(2, lastName);
								updatePersonStatement.setString(3, maidenName);
								updatePersonStatement.setInt(4, birthDate.getDay());
								updatePersonStatement.setInt(5, birthDate.getMonth());
								updatePersonStatement.setInt(6, birthDate.getYear());
								updatePersonStatement.setInt(7, deathDate.getDay());
								updatePersonStatement.setInt(8, deathDate.getMonth());
								updatePersonStatement.setInt(9, deathDate.getYear());
								updatePersonStatement.setString(10, ageAtDeath);
								updatePersonStatement.setString(11, comments);
								updatePersonStatement.setInt(12, personId);
								updatePersonStatement.executeUpdate();
								processLog.incUpdated();
							} else {
								// insert information about the person
								insertPersonStatement.setString(1, firstName);
								insertPersonStatement.setString(2, lastName);
								insertPersonStatement.setString(3, maidenName);
								insertPersonStatement.setInt(4, birthDate.getDay());
								insertPersonStatement.setInt(5, birthDate.getMonth());
								insertPersonStatement.setInt(6, birthDate.getYear());
								insertPersonStatement.setInt(7, deathDate.getDay());
								insertPersonStatement.setInt(8, deathDate.getMonth());
								insertPersonStatement.setInt(9, deathDate.getYear());
								insertPersonStatement.setString(10, ageAtDeath);
								insertPersonStatement.setString(11, comments);
								insertPersonStatement.execute();
								ResultSet generatedKeys = insertPersonStatement.getGeneratedKeys();
								if (generatedKeys.next()) {
									personId = generatedKeys.getInt(1);
									// System.out.println("Inserted person " + personId);
									processLog.incUpdated();
								} else {
									processLog.incErrors("Could not retrieve key for inserted person");
								}
								generatedKeys.close();
							}

							if (personId != null) {
								deleteMarriagesStatement.setInt(1, personId);
								deleteMarriagesStatement.execute();
								if ((spouse1 != null && spouse1.length() > 0) || !marriageDate.isNotSet() || (marriageComment != null && marriageComment.length() > 0)) {
									insertMarriageStmt.setInt(1, personId);
									insertMarriageStmt.setString(2, spouse1);
									insertMarriageStmt.setInt(3, marriageDate.getDay());
									insertMarriageStmt.setInt(4, marriageDate.getMonth());
									insertMarriageStmt.setInt(5, marriageDate.getYear());
									insertMarriageStmt.setString(6, marriageComment);
									insertMarriageStmt.executeUpdate();
									// System.out.println("  Added first marriage");
								}
								if (spouse2 != null && spouse2.length() > 0) {
									insertMarriageStmt.setInt(1, personId);
									insertMarriageStmt.setString(2, spouse2);
									insertMarriageStmt.setInt(3, 0);
									insertMarriageStmt.setInt(4, 0);
									insertMarriageStmt.setInt(5, 0);
									insertMarriageStmt.setString(6, "");
									insertMarriageStmt.executeUpdate();
									// System.out.println("  Added second marriage");
								}
								insertMarriageStmt.close();

								// delete existing obits and enter new

								deleteObitsStatement.setInt(1, personId);
								deleteObitsStatement.executeUpdate();
								if (obit1Source.length() > 0 || !obit1Date.isNotSet() || obit1Page.length() > 0) {
									String obituarySource = getObitSource(obit1Source);
									insertObitStmt.setInt(1, personId);
									insertObitStmt.setString(2, obituarySource);
									insertObitStmt.setInt(3, obit1Date.getDay());
									insertObitStmt.setInt(4, obit1Date.getMonth());
									insertObitStmt.setInt(5, obit1Date.getYear());
									insertObitStmt.setString(6, obit1Page);
									insertObitStmt.setString(7, obituarySource.equals("Other") ? obit1Source : "");
									insertObitStmt.executeUpdate();
									// System.out.println("  Added first obit");
								}
								if (obit2Source.length() > 0 || !obit2Date.isNotSet() || obit2Page.length() > 0) {
									String obituarySource = getObitSource(obit2Source);
									insertObitStmt.setInt(1, personId);
									insertObitStmt.setString(2, obituarySource);
									insertObitStmt.setInt(3, obit2Date.getDay());
									insertObitStmt.setInt(4, obit2Date.getMonth());
									insertObitStmt.setInt(5, obit2Date.getYear());
									insertObitStmt.setString(6, obit2Page);
									insertObitStmt.setString(7, obituarySource.equals("Other") ? obit2Source : "");
									insertObitStmt.executeUpdate();
									// System.out.println("  Added second obit");
								}
								if (obit3Source.length() > 0 || !obit3Date.isNotSet() || obit3Page.length() > 0) {
									String obituarySource = getObitSource(obit3Source);
									insertObitStmt.setInt(1, personId);
									insertObitStmt.setString(2, obituarySource);
									insertObitStmt.setInt(3, obit3Date.getDay());
									insertObitStmt.setInt(4, obit3Date.getMonth());
									insertObitStmt.setInt(5, obit3Date.getYear());
									insertObitStmt.setString(6, obit3Page);
									insertObitStmt.setString(7, obituarySource.equals("Other") ? obit1Source : "");
									insertObitStmt.executeUpdate();
									// System.out.println("  Added third obit");
								}
								insertObitStmt.close();
							}

							personExistsRs.close();

							// Reindex the person in solr
							reindexPerson(processSettings, dbConn, personId);
							processLog.incUpdated();
						} catch (Exception e) {
							processLog.incErrors("Error checking if person exists ", e);
							processLog.addNote(st1.toString());
						}
						st1.close();
					}
				}
			} catch (FileNotFoundException e) {
				processLog.incErrors("Could not find the file to import", e);
			} catch (IOException e) {
				processLog.incErrors("Error reading import file ", e);
			}
		} catch (SQLException ex) {
			// handle any errors
			processLog.incErrors("Error importing genealogy data from file", ex);
		}
	}

	private void reindexPerson(Section processSettings, Connection conn, Integer personId) {

		try {
			// Load the person from the database
			Statement personStatement = conn.createStatement();
			ResultSet personRs = personStatement.executeQuery("SELECT * from person where personId = " + personId);
			if (personRs.next()) {
				Statement marriageStatement = conn.createStatement();
				ResultSet marriageRs = marriageStatement.executeQuery("SELECT * from marriage where personId = " + personId);
				StringBuilder marriageFields = new StringBuilder();
				StringBuilder keywords = new StringBuilder();
				while (marriageRs.next()) {
					String spouseName = getFieldForSolr(marriageRs, "spouseName");
					if (spouseName.length() > 0) {
						marriageFields.append("<field name=\"spouseName\">").append(spouseName).append("</field>");
					}
					DateInfo marriageDate = new DateInfo(marriageRs.getInt("marriageDateDay"), marriageRs.getInt("marriageDateMonth"),
							marriageRs.getInt("marriageDateYear"));
					if (!marriageDate.isNotSet()) {
						marriageFields.append("<field name=\"marriageDate\">").append(marriageDate.getSolrDate()).append("</field>");
					}
					String marriageComments = getFieldForSolr(marriageRs, "comments");
					if (marriageComments.length() > 0) {
						marriageFields.append("<field name=\"marriageComments\">").append(marriageComments).append("</field>");
					}
					keywords.append(marriageComments).append(" ");
				}
				marriageRs.close();

				Statement obitStatement = conn.createStatement();
				ResultSet obitRs = obitStatement.executeQuery("SELECT * from obituary where personId = " + personId);
				StringBuilder obitFields = new StringBuilder();
				while (obitRs.next()) {
					String source = getFieldForSolr(obitRs, "source");
					if (source.length() > 0) {
						obitFields.append("<field name=\"obituarySource\">").append(source).append("</field>");
					}
					DateInfo obitDate = new DateInfo(obitRs.getInt("dateDay"), obitRs.getInt("dateMonth"), obitRs.getInt("dateYear"));
					if (!obitDate.isNotSet()) {
						obitFields.append("<field name=\"obituaryDate\">").append(obitDate.getSolrDate()).append("</field>");
					}
					String obituaryText = getFieldForSolr(obitRs, "contents");
					if (obituaryText.length() > 0) {
						obitFields.append("<field name=\"obituaryText\">").append(obituaryText).append("</field>");
					}
					keywords.append(obituaryText).append(" ");
				}
				obitRs.close();

				StringBuilder updateBody = new StringBuilder();
				updateBody.append("<add commitWithin=\"60000\" ><doc>");
				updateBody.append("<field name=\"id\">").append(personId).append("</field>");
				updateBody.append("<field name=\"recordtype\">person</field>");
				String firstName = getFieldForSolr(personRs, "firstName");
				String lastName = getFieldForSolr(personRs, "lastName");
				String middleName = getFieldForSolr(personRs, "middleName");
				String otherName = getFieldForSolr(personRs, "otherName");
				String maidenName = getFieldForSolr(personRs, "maidenName");
				String nickName = getFieldForSolr(personRs, "nickName");
				String cemeteryName = getFieldForSolr(personRs, "cemeteryName");
				String cemeteryLocation = getFieldForSolr(personRs, "cemeteryLocation");
				String mortuaryName = getFieldForSolr(personRs, "mortuaryName");
				String comments = getFieldForSolr(personRs, "comments");
				String title = firstName + " " + lastName + " " + middleName + " " + otherName + " " + maidenName;
				keywords.append(firstName).append(" ").append(lastName).append(" ").append(middleName).append(" ").append(otherName).append(" ").append(maidenName).append(" ").append(nickName).append(" ").append(cemeteryName).append(" ").append(cemeteryLocation).append(" ").append(mortuaryName).append(" ").append(comments);
				if (title.length() > 0) {
					updateBody.append("<field name=\"title\">").append(title).append("</field>");
				}
				if (comments.length() > 0) {
					updateBody.append("<field name=\"comments\">").append(comments).append("</field>");
				}
				if (keywords.length() > 0) {
					updateBody.append("<field name=\"keywords\">").append(keywords).append("</field>");
				}
				if (firstName.length() > 0) {
					updateBody.append("<field name=\"firstName\">").append(firstName).append("</field>");
				}
				if (lastName.length() > 0) {
					updateBody.append("<field name=\"lastName\">").append(lastName).append("</field>");
				}
				if (middleName.length() > 0) {
					updateBody.append("<field name=\"middleName\">").append(middleName).append("</field>");
				}
				if (maidenName.length() > 0) {
					updateBody.append("<field name=\"maidenName\">").append(maidenName).append("</field>");
				}
				if (otherName.length() > 0) {
					updateBody.append("<field name=\"otherName\">").append(otherName).append("</field>");
				}
				if (nickName.length() > 0) {
					updateBody.append("<field name=\"nickName\">").append(nickName).append("</field>");
				}
				DateInfo birthDate = new DateInfo(personRs.getInt("birthDateDay"), personRs.getInt("birthDateMonth"), personRs.getInt("birthDateYear"));
				if (!birthDate.isNotSet()) {
					updateBody.append("<field name=\"birthDate\">").append(birthDate.getSolrDate()).append("</field>");
					updateBody.append("<field name=\"birthYear\">").append(birthDate.getYear()).append("</field>");
				}
				DateInfo deathDate = new DateInfo(personRs.getInt("deathDateDay"), personRs.getInt("deathDateMonth"), personRs.getInt("deathDateYear"));
				if (!deathDate.isNotSet()) {
					updateBody.append("<field name=\"deathDate\">").append(deathDate.getSolrDate()).append("</field>");
					updateBody.append("<field name=\"deathYear\">").append(deathDate.getYear()).append("</field>");
				}
				String ageAtDeath = getFieldForSolr(personRs, "ageAtDeath");
				if (ageAtDeath.length() > 0) {
					updateBody.append("<field name=\"ageAtDeath\">").append(ageAtDeath).append("</field>");
				}
				if (cemeteryName.length() > 0) {
					updateBody.append("<field name=\"cemeteryName\">").append(cemeteryName).append("</field>");
				}
				if (cemeteryLocation.length() > 0) {
					updateBody.append("<field name=\"cemeteryLocation\">").append(getFieldForSolr(personRs, "cemeteryLocation")).append("</field>");
				}
				if (mortuaryName.length() > 0) {
					updateBody.append("<field name=\"mortuaryName\">").append(getFieldForSolr(personRs, "mortuaryName")).append("</field>");
				}
				updateBody.append(marriageFields);
				updateBody.append(obitFields);
				updateBody.append("</doc></add>");
				String updateBodyString = updateBody.toString();
				updateBodyString = updateBodyString.replaceAll("&", "&amp;");

				if (!doSolrUpdate(processSettings, updateBodyString)) {

					System.out.println("Indexed person " + personId + " failed in Solr");

				}

			} else {
				System.out.println("Could not find person " + personId + " to index");
			}
			personRs.close();
			personStatement.close();
		} catch (SQLException e) {
			System.out.println("SQL error occurred updating index " + e.toString());
		}
	}

	private String getFieldForSolr(ResultSet resultSet, String fieldName) {
		try {
			String fieldValue = resultSet.getString(fieldName);
			if (resultSet.wasNull()) {
				fieldValue = "";
			}
			return fieldValue;
		} catch (SQLException e) {
			System.out.println("Could not find field " + fieldName + " " + e.toString());
			return "";
		}
	}

	private String getObitSource(String source) {
		if (source.matches("(?i)[`\\d]?DS.*")) {
			return "Grand Junction Daily Sentinel";
		} else if (source.matches("(?i)EVE.*")) {
			return "Eagle Valley Enterprise";
		} else {
			return "Other";
		}
	}

	private void deleteDuplicates(Ini configIni, Section processSettings) {
		String deleteDuplicates = processSettings.get("deleteDuplicates");
		if (deleteDuplicates == null || !deleteDuplicates.equalsIgnoreCase("true")) {
			processLog.addNote("Skipping deleting duplicates, to activate set deleteDuplicates key to true.");
			return;
		}

		String genealogyUrl = configIni.get("Genealogy", "url");
		if (genealogyUrl == null || genealogyUrl.length() == 0) {
			processLog.incErrors("Unable to get url for genealogy in GenealogyCleanup section.  Please specify genealogyIndex key.");
			return;
		}

		// Process for exact duplicates created as part of the import process
		// (non-exact duplicates)
		try {
			// Get a list of all people where their basic information is identical
			Statement stmt = dbConn.createStatement(ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			String queryStr = "SELECT MIN(personId) as minPersonId, count(personId), firstName, lastName, middleName, maidenName, otherName, nickName, birthDateDay, birthDateMonth, birthDateYear, deathDateDay, deathDateMonth, deathDateYear, ageAtDeath, cemeteryName, cemeteryLocation, mortuaryName, comments, picture "
					+ "FROM person "
					+ "GROUP BY firstName, lastName, middleName, maidenName, otherName, nickName, birthDateDay, birthDateMonth, birthDateYear, deathDateDay, deathDateMonth, deathDateYear, ageAtDeath, cemeteryName, cemeteryLocation, mortuaryName "
					+ "HAVING ( COUNT(personId) > 1 )";
			System.out.println("Query String: " + queryStr);
			ResultSet recordsToCheck = stmt.executeQuery(queryStr);
			System.out.println("Finished query for records to check for duplicates.");
			// loop through all records with duplicate basic information
			while (recordsToCheck.next()) {
				String minPersonId = recordsToCheck.getString("minPersonId");
				System.out.println("Deleting duplicates for record " + minPersonId);
				// Get a list of all records that need to be checked.
				Person duplicatePersonInfo = new Person(recordsToCheck, false);
				String query2 = duplicatePersonInfo.createMatchingQuery();
				Statement stmt2 = dbConn.createStatement(ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				// System.out.println(query2);
				ResultSet duplicatePeopleRs = stmt2.executeQuery(query2);
				Person bestPerson = null;
				int numMatchesFound = 0;
				while (duplicatePeopleRs.next()) {
					numMatchesFound++;
					if (bestPerson == null) {
						bestPerson = new Person(duplicatePeopleRs, true);
					} else {
						Person nextPerson = new Person(duplicatePeopleRs, true);
						if (bestPerson.isBetterRecord(nextPerson, dbConn)) {
							nextPerson.delete(dbConn);
							processLog.incUpdated();
						} else {
							bestPerson.delete(dbConn);
							bestPerson = nextPerson;
							processLog.incUpdated();
						}
					}
				}
				// System.out.println(numMatchesFound +
				// " people found matching record.");
			}
			recordsToCheck.close();

		} catch (SQLException ex) {
			// handle any errors
			processLog.incErrors("Error establishing connection to database ", ex);
			ex.printStackTrace();
		}
	}
}
