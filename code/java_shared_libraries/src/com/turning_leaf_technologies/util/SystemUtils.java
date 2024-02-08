package com.turning_leaf_technologies.util;

import com.turning_leaf_technologies.strings.AspenStringUtils;
import org.apache.logging.log4j.Logger;
import org.ini4j.Ini;

import java.io.BufferedReader;
import java.io.FileReader;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

public class SystemUtils {
	public static boolean hasLowMemory(Ini configIni, Logger logger) {
		if (configIni.get("System", "operatingSystem").equalsIgnoreCase("linux")){
			try {
				@SuppressWarnings("SpellCheckingInspection") BufferedReader bufferedReader = new BufferedReader(new FileReader("/proc/meminfo"));
				long freeMem = 0;
				long totalMem = 0;
				String memInfoLine = bufferedReader.readLine();
				Pattern memTotalRegex = Pattern.compile("MemTotal:\\s+(\\d+)\\skB", Pattern.DOTALL);
				Pattern freeMemoryRegex = Pattern.compile("MemAvailable:\\s+(\\d+)\\skB", Pattern.DOTALL);
				while (memInfoLine != null){
					Matcher memTotalMatcher = memTotalRegex.matcher(memInfoLine);
					if (memTotalMatcher.find()) {
						totalMem = Long.parseLong(memTotalMatcher.group(1)) * 1024;
					}
					Matcher freeMemoryMatcher = freeMemoryRegex.matcher(memInfoLine);
					if (freeMemoryMatcher.find()) {
						freeMem = Long.parseLong(freeMemoryMatcher.group(1)) * 1024;
					}
					memInfoLine = bufferedReader.readLine();
				}
				bufferedReader.close();
				float percentMemoryAvailable = (float)freeMem / (float)totalMem;
				logger.info("Free memory: " + freeMem + " total memory: " + totalMem + " percent memory usage: " + percentMemoryAvailable );

				//These mimic what we alert for in getIndexStatus with an additional buffer to prevent the alert
				if (freeMem < 1500000000){
					return true;
				}else {
					return percentMemoryAvailable < .075 && freeMem < 3000000000L;
				}
			}catch (Exception e){
				logger.error("Error determining if we have low memory", e);
				return false;
			}
		}else{
			//Can't easily determine for Windows
			return false;
		}
	}

	public static void printMemoryStats(Logger logger) {
		Runtime runtime = Runtime.getRuntime();
		logger.error("Max: " + AspenStringUtils.formatBytes(runtime.maxMemory()) + " Total: " + AspenStringUtils.formatBytes(runtime.totalMemory()) + " Used: " + (AspenStringUtils.formatBytes(runtime.totalMemory() - runtime.freeMemory())) + " Free: " + AspenStringUtils.formatBytes(runtime.freeMemory()));
	}
}
