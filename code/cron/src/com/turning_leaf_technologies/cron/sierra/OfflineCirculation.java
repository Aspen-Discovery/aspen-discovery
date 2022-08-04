package com.turning_leaf_technologies.cron.sierra;

import com.turning_leaf_technologies.cron.CronLogEntry;
import com.turning_leaf_technologies.cron.CronProcessLogEntry;
import com.turning_leaf_technologies.cron.IProcessHandler;
import com.turning_leaf_technologies.net.NetworkUtils;
import com.turning_leaf_technologies.net.WebServiceResponse;
import com.turning_leaf_technologies.strings.AspenStringUtils;
import org.apache.logging.log4j.Logger;
import org.ini4j.Ini;
import org.ini4j.Profile;
import org.json.JSONException;
import org.json.JSONObject;

import java.io.IOException;
import java.io.InputStream;
import java.net.*;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.Date;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

@SuppressWarnings("unused")
public class OfflineCirculation implements IProcessHandler {
	private CronProcessLogEntry processLog;
	private Logger logger;
	private CookieManager manager = new CookieManager();
	private String ils = "Millennium";
	@Override
	public void doCronProcess(String servername, Ini configIni, Profile.Section processSettings, Connection dbConn, CronLogEntry cronEntry, Logger logger) {
		this.logger = logger;
		processLog = new CronProcessLogEntry(cronEntry, "Offline Circulation", dbConn, logger);
		processLog.saveResults();

		ils = configIni.get("Catalog", "ils");

		manager.setCookiePolicy(CookiePolicy.ACCEPT_ALL);
		CookieHandler.setDefault(manager);

		//process checkouts and check ins (do this before holds)
		processOfflineCirculationEntries(configIni, dbConn);

		//process holds
		processOfflineHolds(configIni, dbConn);

		processLog.setFinished();
		processLog.saveResults();
	}

	/**
	 * Enters any holds that were entered while the catalog was offline
	 *
	 * @param configIni   Configuration information for Aspen
	 * @param dbConn Connection to the database
	 */
	private void processOfflineHolds(Ini configIni, Connection dbConn) {
		processLog.addNote("Processing offline holds");
		try {
			PreparedStatement holdsToProcessStmt = dbConn.prepareStatement("SELECT offline_hold.*, cat_username, cat_password from offline_hold LEFT JOIN user on user.id = offline_hold.patronId where status='Not Processed' order by timeEntered ASC");
			PreparedStatement updateHold = dbConn.prepareStatement("UPDATE offline_hold set timeProcessed = ?, status = ?, notes = ? where id = ?");
			String baseUrl = configIni.get("Site", "url");
			ResultSet holdsToProcessRS = holdsToProcessStmt.executeQuery();
			while (holdsToProcessRS.next()){
				processOfflineHold(updateHold, baseUrl, holdsToProcessRS);
			}
		} catch (SQLException e) {
			processLog.incErrors("Error processing offline holds ", e);
		}

	}

	private void processOfflineHold(PreparedStatement updateHold, String baseUrl, ResultSet holdsToProcessRS) throws SQLException {
		long holdId = holdsToProcessRS.getLong("id");
		updateHold.clearParameters();
		updateHold.setLong(1, new Date().getTime() / 1000);
		updateHold.setLong(4, holdId);
		try {
			String patronBarcode = URLEncoder.encode(holdsToProcessRS.getString("patronBarcode"), "UTF-8");
			String patronName = holdsToProcessRS.getString("cat_username");
			if (patronName == null || patronName.length() == 0){
				patronName = holdsToProcessRS.getString("patronName");
			}
			patronName = URLEncoder.encode(patronName, "UTF-8");
			String bibId = URLEncoder.encode(holdsToProcessRS.getString("bibId"), "UTF-8");
			String itemId = holdsToProcessRS.getString("itemId");
			URL placeHoldUrl;
			if (itemId != null && itemId.length() > 0){
				placeHoldUrl = new URL(baseUrl + "/API/UserAPI?method=placeItemHold&username=" + patronName + "&password=" + patronBarcode + "&bibId=" + bibId + "&itemId=" + itemId);
			}else{
				placeHoldUrl = new URL(baseUrl + "/API/UserAPI?method=placeHold&username=" + patronName + "&password=" + patronBarcode + "&bibId=" + bibId);
			}

			Object placeHoldDataRaw = placeHoldUrl.getContent();
			if (placeHoldDataRaw instanceof InputStream) {
				String placeHoldDataJson = AspenStringUtils.convertStreamToString((InputStream) placeHoldDataRaw);
				processLog.addNote("Result = " + placeHoldDataJson);
				JSONObject placeHoldData = new JSONObject(placeHoldDataJson);
				JSONObject result = placeHoldData.getJSONObject("result");
				if (result.getBoolean("success")){
					updateHold.setString(2, "Hold Succeeded");
				}else{
					updateHold.setString(2, "Hold Failed");
				}
				if (result.has("holdMessage")){
					updateHold.setString(3, result.getString("holdMessage"));
				}else{
					updateHold.setString(3, result.getString("message"));
				}
			}
			processLog.incUpdated();
		} catch (JSONException e) {
			processLog.incErrors("Error Loading JSON response for placing hold " + holdId, e);
			updateHold.setString(2, "Hold Failed");
			updateHold.setString(3, "Error Loading JSON response for placing hold " + holdId + " - " + e.toString());

		} catch (IOException e) {
			processLog.incErrors("Error processing offline hold " + holdId, e);
			updateHold.setString(2, "Hold Failed");
			updateHold.setString(3, "Error processing offline hold " + holdId + " - " + e.toString());
		}
		try {
			updateHold.executeUpdate();
		} catch (SQLException e) {
			processLog.incErrors("Error updating hold status for hold " + holdId, e);
		}
	}

	/**
	 * Processes any checkouts and check-ins that were done while the system was offline.
	 *
	 * @param configIni   Configuration information for Aspen
	 * @param dbConn Connection to the database
	 */
	private void processOfflineCirculationEntries(Ini configIni, Connection dbConn) {
		processLog.addNote("Processing offline checkouts and check-ins");
		try {
			PreparedStatement circulationEntryToProcessStmt = dbConn.prepareStatement("SELECT offline_circulation.* from offline_circulation where status='Not Processed' order by timeEntered ASC");
			PreparedStatement updateCirculationEntry = dbConn.prepareStatement("UPDATE offline_circulation set timeProcessed = ?, status = ?, notes = ? where id = ?");
			String baseUrl = configIni.get("Catalog", "linking_url") + "/iii/airwkst";
			ResultSet circulationEntriesToProcessRS = circulationEntryToProcessStmt.executeQuery();
			int numProcessed = 0;
			while (circulationEntriesToProcessRS.next()){
				processOfflineCirculationEntry(updateCirculationEntry, baseUrl, circulationEntriesToProcessRS);
				numProcessed++;
			}
			if (numProcessed > 0) {
				//Logout of the system
				NetworkUtils.getURL(baseUrl + "/airwkstcore?action=AirWkstReturnToWelcomeAction", logger);
			}
		} catch (SQLException e) {
			processLog.incErrors("Error processing offline holds ", e);
		}
	}

	private void processOfflineCirculationEntry(PreparedStatement updateCirculationEntry, String baseAirpacUrl, ResultSet circulationEntriesToProcessRS) throws SQLException {
		long circulationEntryId = circulationEntriesToProcessRS.getLong("id");
		updateCirculationEntry.clearParameters();
		updateCirculationEntry.setLong(1, new Date().getTime() / 1000);
		updateCirculationEntry.setLong(4, circulationEntryId);
		String itemBarcode = circulationEntriesToProcessRS.getString("itemBarcode");
		String login = circulationEntriesToProcessRS.getString("login");
		String loginPassword = circulationEntriesToProcessRS.getString("loginPassword");
		String initials = circulationEntriesToProcessRS.getString("initials");
		String initialsPassword = circulationEntriesToProcessRS.getString("initialsPassword");
		String type = circulationEntriesToProcessRS.getString("type");
		OfflineCirculationResult result;
		if (type.equals("Check In")){
			result = processOfflineCheckIn(baseAirpacUrl, login, loginPassword, initials, initialsPassword, itemBarcode);
		} else{
			String patronBarcode = circulationEntriesToProcessRS.getString("patronBarcode");
			result = processOfflineCheckout(baseAirpacUrl, login, loginPassword, initials, initialsPassword, itemBarcode, patronBarcode);
		}
		if (result.isSuccess()){
			processLog.incUpdated();
			updateCirculationEntry.setString(2, "Processing Succeeded");
		}else{
			processLog.incErrors("Processing failed");
			updateCirculationEntry.setString(2, "Processing Failed");
		}
		updateCirculationEntry.setString(3, result.getNote());
		updateCirculationEntry.executeUpdate();
	}

	private String lastLogin;
	private String lastInitials;
	private String lastPatronBarcode;
	private boolean lastPatronHadError;
	private OfflineCirculationResult processOfflineCheckout(String baseAirpacUrl, String login, String loginPassword, String initials, String initialsPassword, String itemBarcode, String patronBarcode) {
		OfflineCirculationResult result = new OfflineCirculationResult();
		try{
			//Login to airpac (login)
			NetworkUtils.getURL(baseAirpacUrl + "/", logger);

			//logger.debug("Home page Response\r\n" + homePageResponse.getMessage());
			boolean bypassLogin = true;
			WebServiceResponse loginResponse = null;
			if (lastLogin == null || !lastLogin.equals(login)){
				bypassLogin = false;
				if (lastLogin != null){
					//Logout of the system
					NetworkUtils.getURL(baseAirpacUrl + "/airwkstcore?action=AirWkstReturnToWelcomeAction", logger);
				}
				lastLogin = login;
			}
			if (!bypassLogin){
				String loginParams = "action=ValidateAirWkstUserAction" +
						"&login=" + login +
						"&loginpassword=" + loginPassword +
						"&nextaction=null" +
						"&purpose=null" +
						"&submit.x=47" +
						"&submit.y=8" +
						"&subpurpose=null" +
						"&validationstatus=needlogin";
				loginResponse = NetworkUtils.postToURL(baseAirpacUrl + "/airwkstcore?" + loginParams, null, "text/html", baseAirpacUrl + "/", logger);
			}

			if (bypassLogin || (loginResponse.isSuccess() && (loginResponse.getMessage().contains("needinitials")) || ils.equalsIgnoreCase("sierra"))){
				WebServiceResponse initialsResponse;
				boolean bypassInitials = true;
				if (ils.equalsIgnoreCase("millennium") && (lastInitials == null || lastInitials.equals(initials))){
					bypassInitials = false;
					lastInitials = initials;
				}
				if (!bypassInitials){
					//Login to airpac (initials)
					String initialsParams = "action=ValidateAirWkstUserAction" +
							"&initials=" + initials +
							"&initialspassword=" + initialsPassword +
							"&nextaction=null" +
							"&purpose=null" +
							"&submit.x=47" +
							"&submit.y=8" +
							"&subpurpose=null" +
							"&validationstatus=needinitials";
					initialsResponse = NetworkUtils.postToURL(baseAirpacUrl + "/airwkstcore?" + initialsParams, null, "text/html", baseAirpacUrl + "/airwkstcore", logger);
				}else{
					initialsResponse = loginResponse;
				}
				if (bypassInitials || initialsResponse.isSuccess() && initialsResponse.getMessage().contains("Check Out")){
					//Go to the checkout page
					if (lastPatronBarcode == null || !lastPatronBarcode.equals(patronBarcode) || lastPatronHadError){
						if (lastPatronBarcode != null){
							//Go back to the home page
							NetworkUtils.getURL(baseAirpacUrl, logger);
						}
						lastPatronBarcode = patronBarcode;
						lastPatronHadError = false;
					}
					//Get checkout page response
					NetworkUtils.getURL(baseAirpacUrl + "/?action=GetAirWkstUserInfoAction&purpose=checkout", logger);
					String patronBarcodeParams = "action=LogInAirWkstPatronAction" +
							"&patronbarcode=" + patronBarcode +
							"&purpose=checkout" +
							"&submit.x=42" +
							"&submit.y=12" +
							"&sourcebrowse=airwkstpage";
					WebServiceResponse patronBarcodeResponse = NetworkUtils.postToURL(baseAirpacUrl + "/airwkstcore?" + patronBarcodeParams, null, "text/html", baseAirpacUrl + "/", logger);

					if ((patronBarcodeResponse.isSuccess() && patronBarcodeResponse.getMessage().contains("Please scan item barcode"))){
						lastPatronHadError = false;
						String itemBarcodeParams = "action=GetAirWkstItemOneAction" +
								"&prevscreen=AirWkstItemRequestPage" +
								"&purpose=checkout" +
								"&searchstring=" + itemBarcode +
								"&searchtype=b" +
								"&sourcebrowse=airwkstpage";
						WebServiceResponse itemBarcodeResponse = NetworkUtils.postToURL(baseAirpacUrl + "/airwkstcore?" + itemBarcodeParams, null, "text/html", baseAirpacUrl + "/", logger);
						if (itemBarcodeResponse.isSuccess()){
							Pattern Regex = Pattern.compile("<h3 class=\"error\">(.*?)</h3>", Pattern.CANON_EQ);
							Matcher RegexMatcher = Regex.matcher(itemBarcodeResponse.getMessage());
							if (RegexMatcher.find()) {
								String error = RegexMatcher.group(1);
								result.setSuccess(false);
								result.setNote(error);
							}else{
								//Everything seems to have worked
								result.setSuccess(true);
							}
						} else {
							logger.debug("Item Barcode response\r\n" + itemBarcodeResponse.getMessage());
							result.setSuccess(false);
							result.setNote("Could not process check out because the item response was not successful");
						}
					} else if (patronBarcodeResponse.isSuccess() && patronBarcodeResponse.getMessage().contains("<h[123] class=\"error\">")){
						lastPatronHadError = true;
						Pattern regex = Pattern.compile("<h[123] class=\"error\">(.*?)</h[123]>");
						Matcher matcher = regex.matcher(patronBarcodeResponse.getMessage());
						if (matcher.find()) {
							String error = matcher.group(1);
							result.setSuccess(false);
							result.setNote(error);
						}else{
							result.setSuccess(false);
							result.setNote("Unknown error loading patron");
						}
					} else {
						lastPatronHadError = true;
						logger.debug("Patron Barcode response\r\n" + patronBarcodeResponse.getMessage());
						result.setSuccess(false);
						result.setNote("Could not process check out because the patron could not be logged in");
					}
				} else{
					logger.debug("Initials response\r\n" + initialsResponse.getMessage());
					result.setSuccess(false);
					result.setNote("Could not process check out because initials were incorrect");
				}


			} else{
				logger.debug("Login response\r\n" + loginResponse.getMessage());
				result.setSuccess(false);
				result.setNote("Could not process check out because login information was incorrect");
			}
		}catch(Exception e){
			result.setSuccess(false);
			result.setNote("Unexpected error processing check in " + e.toString());
		}

		return result;
	}

	private OfflineCirculationResult processOfflineCheckIn(String baseAirpacUrl, String login, String loginPassword, String initials, String initialsPassword, String itemBarcode) {
		OfflineCirculationResult result = new OfflineCirculationResult();
		try{
			//Login to airpac (login), get home page
			NetworkUtils.getURL(baseAirpacUrl + "/", logger);
			String loginParams = "action=ValidateAirWkstUserAction" +
					"&login=" + login +
					"&loginpassword=" + loginPassword +
					"&nextaction=null" +
					"&purpose=null" +
					"&submit.x=47" +
					"&submit.y=8" +
					"&subpurpose=null" +
					"&validationstatus=needlogin";
			WebServiceResponse loginResponse = NetworkUtils.postToURL(baseAirpacUrl + "/airwkstcore?" + loginParams, null, "text/html", baseAirpacUrl + "/", logger);
			if (loginResponse.isSuccess() && loginResponse.getMessage().contains("needinitials")){
				//Login to airpac (initials)
				String initialsParams = "action=ValidateAirWkstUserAction" +
						"&initials=" + initials +
						"&initialspassword=" + initialsPassword +
						"&nextaction=null" +
						"&purpose=null" +
						"&submit.x=47" +
						"&submit.y=8" +
						"&subpurpose=null" +
						"&validationstatus=needinitials";
				WebServiceResponse initialsResponse = NetworkUtils.postToURL(baseAirpacUrl + "/airwkstcore?" + initialsParams, null, "text/html", baseAirpacUrl + "/airwkstcore", logger);
				if (initialsResponse.isSuccess() && initialsResponse.getMessage().contains("Check In")){
					//Go to the checkin page
					NetworkUtils.getURL(baseAirpacUrl + "/?action=GetAirWkstUserInfoAction&purpose=fullcheckin", logger);
					//Process the barcode
					String checkinParams = "action=GetAirWkstItemOneAction" +
							"&prevscreen=AirWkstItemRequestPage" +
							"&purpose=fullcheckin" +
							"&searchstring=" + itemBarcode +
							"&searchtype=b" +
							"&sourcebrowse=airwkstpage";
					WebServiceResponse checkinResponse = NetworkUtils.postToURL(baseAirpacUrl + "/airwkstcore?" + checkinParams, null, "text/html", baseAirpacUrl + "/", logger);
					if (checkinResponse.isSuccess()){
						Pattern Regex = Pattern.compile("<h3 class=\"error\">(.*?)</h3>", Pattern.CANON_EQ);
						Matcher RegexMatcher = Regex.matcher(checkinResponse.getMessage());
						if (RegexMatcher.find()) {
							String error = RegexMatcher.group(1);
							result.setSuccess(false);
							result.setNote(error);
						}else{
							//Everything seems to have worked
							result.setSuccess(true);
						}
					} else {
						result.setSuccess(false);
						result.setNote("Could not process check in because check in page did not load properly");
					}
				} else{
					result.setSuccess(false);
					result.setNote("Could not process check in because initials were incorrect");
				}
			} else{
				result.setSuccess(false);
				result.setNote("Could not process check in because login information was incorrect");
			}
		}catch(Exception e){
			result.setSuccess(false);
			result.setNote("Unexpected error processing check in " + e.toString());
		}

		return result;
	}
}
