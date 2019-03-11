package com.turning_leaf_technologies.cron;

import java.sql.Connection;

import org.apache.logging.log4j.Logger;
import org.ini4j.Ini;
import org.ini4j.Profile.Section;

public interface IProcessHandler {
	void doCronProcess(String servername, Ini configIni, Section processSettings, Connection dbConn, CronLogEntry cronEntry, Logger logger );
}
