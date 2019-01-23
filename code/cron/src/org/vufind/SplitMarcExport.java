package org.vufind;

import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.ini4j.Profile;
import org.marc4j.MarcPermissiveStreamReader;
import org.marc4j.MarcReader;
import org.marc4j.marc.Record;

import java.io.File;
import java.io.FileInputStream;
import java.sql.Connection;
import java.util.ArrayList;
import java.util.HashMap;

/**
 * Splits a MARC export based on location codes
 *
 * VuFind-Plus
 * User: Mark Noble
 * Date: 11/21/2014
 * Time: 5:25 PM
 */
public class SplitMarcExport implements IProcessHandler{

	@Override
	public void doCronProcess(String servername, Ini configIni, Profile.Section processSettings, Connection vufindConn, Connection econtentConn, CronLogEntry cronEntry, Logger logger) {
		CronProcessLogEntry processLog = new CronProcessLogEntry(cronEntry.getLogEntryId(), "Split Marc Records");
		processLog.saveToDatabase(vufindConn, logger);
		try{
			String marcPath = Util.cleanIniValue(configIni.get("Reindex", "marcPath"));
			String splitMarcPath = Util.cleanIniValue(processSettings.get("splitMarcPath"));
			String itemTag = Util.cleanIniValue(configIni.get("Reindex", "itemTag"));
			char locationSubfield = Util.cleanIniValue(configIni.get("Reindex", "locationSubfield")).charAt(0);
			if (splitMarcPath == null){
				logger.error("Did not find path to store the split marc files, please add splitMarcPath to the configuration file.");
			}

			String marcEncoding = configIni.get("Reindex", "marcEncoding");

			//Determine what splits to do
			ArrayList<MarcSplitOption> splitOptions = new ArrayList<MarcSplitOption>();
			int curSplit = 1;
			while (true){
				if (processSettings.containsKey("split_" + curSplit + "_filename")){
					MarcSplitOption splitOption = new MarcSplitOption();
					splitOption.setFilename(splitMarcPath, Util.cleanIniValue(processSettings.get("split_" + curSplit + "_filename")));
					splitOption.setLocationsToInclude(Util.cleanIniValue(processSettings.get("split_" + curSplit + "_locations")));
					splitOption.setItemTag(itemTag);
					splitOption.setLocationSubfield(locationSubfield);
					splitOptions.add(splitOption);
					curSplit++;
				}else{
					break;
				}
			}

			File[] catalogBibFiles = new File(marcPath).listFiles();
			int numRecordsRead = 0;
			if (catalogBibFiles != null) {
				String lastRecordProcessed = "";
				for (File curBibFile : catalogBibFiles) {
					if (curBibFile.getName().endsWith(".mrc") || curBibFile.getName().endsWith(".marc")) {
						try {
							FileInputStream marcFileStream = new FileInputStream(curBibFile);
							MarcReader catalogReader = new MarcPermissiveStreamReader(marcFileStream, true, true, marcEncoding);
							while (catalogReader.hasNext()) {
								Record curBib = catalogReader.next();

								//Check the items within the marc record to see if they should be kept or discarded
								for (MarcSplitOption splitter: splitOptions){
									splitter.processRecord(curBib);
								}
							}
							marcFileStream.close();
							for (MarcSplitOption splitter: splitOptions){
								splitter.close();
							}
						} catch (Exception e) {
							logger.error("Error loading catalog bibs on record " + numRecordsRead + " the last record processed was " + lastRecordProcessed, e);
						}
					}
				}
			}
		} catch (Exception e) {
			logger.error("Error splitting marc records", e);
			processLog.addNote("Error splitting marc records " + e.toString());
		}finally{
			processLog.setFinished();
			processLog.saveToDatabase(vufindConn, logger);
		}
	}
}
