package com.turning_leaf_technologies.cron.marc_manipulation;

import com.turning_leaf_technologies.cron.CronLogEntry;
import com.turning_leaf_technologies.cron.CronProcessLogEntry;
import com.turning_leaf_technologies.cron.IProcessHandler;
import com.turning_leaf_technologies.config.ConfigUtil;
import org.apache.logging.log4j.Logger;
import org.ini4j.Ini;
import org.ini4j.Profile;
import org.marc4j.MarcPermissiveStreamReader;
import org.marc4j.MarcReader;
import org.marc4j.marc.Record;

import java.io.File;
import java.io.FileInputStream;
import java.sql.Connection;
import java.util.ArrayList;

@SuppressWarnings("unused")
public class SplitMarcExport implements IProcessHandler {

	@Override
	public void doCronProcess(String servername, Ini configIni, Profile.Section processSettings, Connection dbConn, CronLogEntry cronEntry, Logger logger) {
		CronProcessLogEntry processLog = new CronProcessLogEntry(cronEntry, "Split Marc Records", dbConn, logger);
		try{
			String marcPath = ConfigUtil.cleanIniValue(configIni.get("Reindex", "marcPath"));
			String splitMarcPath = ConfigUtil.cleanIniValue(processSettings.get("splitMarcPath"));
			String itemTag = ConfigUtil.cleanIniValue(configIni.get("Reindex", "itemTag"));
			char locationSubfield = ConfigUtil.cleanIniValue(configIni.get("Reindex", "locationSubfield")).charAt(0);
			if (splitMarcPath == null){
				processLog.incErrors("Did not find path to store the split marc files, please add splitMarcPath to the configuration file.");
			}

			String marcEncoding = configIni.get("Reindex", "marcEncoding");

			//Determine what splits to do
			ArrayList<MarcSplitOption> splitOptions = new ArrayList<>();
			int curSplit = 1;
			while (true){
				if (processSettings.containsKey("split_" + curSplit + "_filename")){
					MarcSplitOption splitOption = new MarcSplitOption();
					splitOption.setFilename(splitMarcPath, ConfigUtil.cleanIniValue(processSettings.get("split_" + curSplit + "_filename")), logger);
					splitOption.setLocationsToInclude(ConfigUtil.cleanIniValue(processSettings.get("split_" + curSplit + "_locations")));
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
							processLog.incErrors("Error loading catalog bibs on record " + numRecordsRead + " the last record processed was " + lastRecordProcessed, e);
						}
					}
				}
			}
		} catch (Exception e) {
			processLog.incErrors("Error splitting marc records ", e);
		}finally{
			processLog.setFinished();
			processLog.saveResults();
		}
	}
}
