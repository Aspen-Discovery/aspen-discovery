package com.turning_leaf_technologies.file;

import org.apache.logging.log4j.Logger;

import java.io.File;
import java.io.FileReader;
import java.io.IOException;
import java.nio.file.Files;
import java.util.zip.CRC32;

public class JarUtil {
	public static long getChecksumForJar(Logger logger, String jarName, String jarPath) {
		long checksumValue = 0;
		try {
			CRC32 checksumCalculator = new CRC32();
			File myJar = new File(jarPath);
			FileReader myJarReader = new FileReader(myJar);

			checksumCalculator.update(Files.readAllBytes(myJar.toPath()));
			checksumValue = checksumCalculator.getValue();
		} catch (IOException e) {
			logger.error("Error determining checksum for "  + jarName);
		}
		return checksumValue;
	}
}

