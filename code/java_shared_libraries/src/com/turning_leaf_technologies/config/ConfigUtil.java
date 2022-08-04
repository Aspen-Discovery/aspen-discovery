package com.turning_leaf_technologies.config;

import com.turning_leaf_technologies.strings.AspenStringUtils;
import org.apache.logging.log4j.Logger;
import org.ini4j.Ini;
import org.ini4j.InvalidFileFormatException;
import org.ini4j.Profile;

import java.io.File;
import java.io.FileNotFoundException;
import java.io.FileReader;
import java.io.IOException;

public class ConfigUtil {
    public static Ini loadConfigFile(String filename, String serverName, Logger logger){
        //First load the default config file
        String configName = "../../sites/default/conf/" + filename;
        logger.info("Loading configuration from " + configName);
        File configFile = new File(configName);
        if (!configFile.exists()) {
            logger.error("Could not find configuration file " + configName);
            System.exit(1);
        }

        // Parse the configuration file
        Ini ini = new Ini();
        try {
            ini.load(new FileReader(configFile));
        } catch (InvalidFileFormatException e) {
            logger.error("Configuration file is not valid.  Please check the syntax of the file.", e);
        } catch (FileNotFoundException e) {
            logger.error("Configuration file could not be found.  You must supply a configuration file in conf called config.ini.", e);
        } catch (IOException e) {
            logger.error("Configuration file could not be read.", e);
        }

        //Now override with the site specific configuration
        String siteSpecificFilename = "../../sites/" + serverName + "/conf/" + filename;
        logger.info("Loading site specific config from " + siteSpecificFilename);
        File siteSpecificFile = new File(siteSpecificFilename);
        if (!siteSpecificFile.exists()) {
            logger.error("Could not find server specific config file");
            System.exit(1);
        }
        try {
            Ini siteSpecificIni = new Ini();
            siteSpecificIni.load(new FileReader(siteSpecificFile));
            for (Profile.Section curSection : siteSpecificIni.values()){
                for (String curKey : curSection.keySet()){
                    //logger.debug("Overriding " + curSection.getName() + " " + curKey + " " + curSection.get(curKey));
                    //System.out.println("Overriding " + curSection.getName() + " " + curKey + " " + curSection.get(curKey));
                    ini.put(curSection.getName(), curKey, curSection.get(curKey));
                }
            }
            if (filename.equals("config.ini")) {
                //Also load password files if they exist
                String siteSpecificPassword = "../../sites/" + serverName + "/conf/config.pwd.ini";
                logger.info("Loading password config from " + siteSpecificPassword);
                File siteSpecificPasswordFile = new File(siteSpecificPassword);
                if (siteSpecificPasswordFile.exists()) {
                    Ini siteSpecificPwdIni = new Ini();
                    siteSpecificPwdIni.load(new FileReader(siteSpecificPasswordFile));
                    for (Profile.Section curSection : siteSpecificPwdIni.values()) {
                        for (String curKey : curSection.keySet()) {
                            ini.put(curSection.getName(), curKey, curSection.get(curKey));
                        }
                    }
                }
            }
        } catch (InvalidFileFormatException e) {
            logger.error("Site Specific config file is not valid.  Please check the syntax of the file.", e);
        } catch (IOException e) {
            logger.error("Site Specific config file could not be read.", e);
        }

        return ini;
    }

    public static String cleanIniValue(String value) {
        if (value == null) {
            return null;
        }
        if (value.lastIndexOf(';') > 0){
            value = value.substring(0, value.lastIndexOf(';'));
        }
        value = value.trim();

        if (value.startsWith("\"")) {
            value = value.substring(1);
        }
        if (value.endsWith("\"")) {
            value = value.substring(0, value.length() - 1);
        }
        return value;
    }

    public static char getSubfieldIndicatorFromConfig(Ini configIni, String subfieldName) {
        String subfieldString = configIni.get("Reindex", subfieldName);
        return AspenStringUtils.convertStringToChar(subfieldString);
    }


}
