package org.vufind;

import java.io.File;
import java.util.HashSet;

/**
 * A copy of indexing profile information from the database
 *
 * Pika
 * User: Mark Noble
 * Date: 6/30/2015
 * Time: 10:38 PM
 */
public class IndexingProfile {
	Long id;
	public String name;
	String marcPath;
	String filenamesToInclude;
	String marcEncoding;
	String individualMarcPath;
	int numCharsToCreateFolderFrom;
	boolean createFolderFromLeadingCharacters;
	String groupingClass;
	String recordNumberTag;
	String recordNumberPrefix;
	String itemTag ;
	String formatSource;
	char format;
	char eContentDescriptor;
	String specifiedFormatCategory;
	boolean doAutomaticEcontentSuppression;
	boolean groupUnchangedFiles;

	File getFileForIlsRecord(String recordNumber) {
		String shortId = recordNumber.replace(".", "");
		while (shortId.length() < 9){
			shortId = "0" + shortId;
		}

		String subFolderName;
		if (createFolderFromLeadingCharacters){
			subFolderName        = shortId.substring(0, numCharsToCreateFolderFrom);
		}else{
			subFolderName        = shortId.substring(0, shortId.length() - numCharsToCreateFolderFrom);
		}

		String basePath           = individualMarcPath + "/" + subFolderName;
		createBaseDirectory(basePath);
		String individualFilename = basePath + "/" + shortId + ".mrc";
		return new File(individualFilename);
	}

	private static HashSet<String> basePathsValidated = new HashSet<>();
	private static void createBaseDirectory(String basePath) {
		if (basePathsValidated.contains(basePath)) {
			return;
		}
		File baseFile = new File(basePath);
		if (!baseFile.exists()){
			if (!baseFile.mkdirs()){
				System.out.println("Could not create directory to store individual marc");
			}
		}
		basePathsValidated.add(basePath);
	}
}
