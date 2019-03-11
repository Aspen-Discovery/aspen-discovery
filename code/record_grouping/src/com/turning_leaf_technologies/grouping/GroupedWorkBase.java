package com.turning_leaf_technologies.grouping;

import com.opencsv.CSVReader;
import org.apache.logging.log4j.Logger;
import org.apache.logging.log4j.LogManager;

import java.io.File;
import java.io.FileReader;
import java.io.IOException;
import java.math.BigInteger;
import java.security.MessageDigest;
import java.security.NoSuchAlgorithmException;
import java.util.HashMap;
import java.util.HashSet;

/**
 * Superclass for all Grouped Works which have different normalization rules.
 */
public abstract class GroupedWorkBase {
	private static Logger logger	= LogManager.getLogger(GroupedWorkBase.class);

	//The id of the work within the database.
	String permanentId;

	String fullTitle = "";              //Up to 100 chars
	String originalAuthorName = "";
	protected String author = "";             //Up to 50  chars
	String groupingCategory = "";   //Up to 25  chars
	private String uniqueIdentifier = null;

	//Load authorities
	private static HashMap<String, String> authorAuthorities = new HashMap<>();
	private static HashMap<String, String> titleAuthorities = new HashMap<>();

	static {
		loadAuthorities();
	}

	String getPermanentId() {
		if (this.permanentId == null){
			StringBuilder permanentId;
			try {
				MessageDigest idGenerator = MessageDigest.getInstance("MD5");
				String fullTitle = getAuthoritativeTitle();
				if (fullTitle.equals("")){
					idGenerator.update("--null--".getBytes());
				}else{
					idGenerator.update(fullTitle.getBytes());
				}

				String author = getAuthoritativeAuthor();
				if (author.equals("")){
					idGenerator.update("--null--".getBytes());
				}else{
					idGenerator.update(author.getBytes());
				}
				if (groupingCategory.equals("")){
					idGenerator.update("--null--".getBytes());
				}else{
					idGenerator.update(groupingCategory.getBytes());
				}
				if (uniqueIdentifier != null){
					idGenerator.update(uniqueIdentifier.getBytes());
				}
				permanentId = new StringBuilder(new BigInteger(1, idGenerator.digest()).toString(16));
				while (permanentId.length() < 32){
					permanentId.insert(0, "0");
				}
				//Insert -'s for formatting
				this.permanentId = permanentId.substring(0, 8) + "-" + permanentId.substring(8, 12) + "-" + permanentId.substring(12, 16) + "-" + permanentId.substring(16, 20) + "-" + permanentId.substring(20);
			} catch (NoSuchAlgorithmException e) {
				System.out.println("Error generating permanent id" + e.toString());
			}
		}
		//System.out.println("Permanent Id is " + this.permanentId);
		return this.permanentId;
	}

	abstract String getTitle();

	private String authoritativeTitle;
	String getAuthoritativeTitle() {
		if (authoritativeTitle == null) {
			if (titleAuthorities.containsKey(fullTitle)) {
				authoritativeTitle = titleAuthorities.get(fullTitle);
			} else {
				authoritativeTitle = fullTitle;
			}
		}
		return authoritativeTitle;
	}

	abstract void setTitle(String title, int numNonFilingCharacters, String subtitle);

	abstract String getAuthor();

	private String authoritativeAuthor = null;
	String getAuthoritativeAuthor() {
		if (authoritativeAuthor == null) {
			if (authorAuthorities.containsKey(author)) {
				authoritativeAuthor = authorAuthorities.get(author);
			} else {
				authoritativeAuthor = author;
			}
		}
		return authoritativeAuthor;
	}

	abstract void setAuthor(String author);

	abstract void overridePermanentId(String groupedWorkPermanentId);

	abstract void setGroupingCategory(String groupingCategory);

	abstract String getGroupingCategory();

	HashSet<String> getAlternateAuthorNames() {
		HashSet<String> alternateNames = new HashSet<>();
		String displayName = AuthorNormalizer.getDisplayName(originalAuthorName);
		if (displayName != null && displayName.length() > 0){
			alternateNames.add(AuthorNormalizer.getNormalizedName(displayName));
		}
		String parentheticalName = AuthorNormalizer.getParentheticalName(originalAuthorName);
		if (parentheticalName != null && parentheticalName.length() > 0){
			alternateNames.add(AuthorNormalizer.getNormalizedName(parentheticalName));
			//Finally, try making the parenthetical name a display name
			String displayName2 = AuthorNormalizer.getDisplayName(parentheticalName);
			if (displayName2 != null && displayName2.length() > 0){
				alternateNames.add(AuthorNormalizer.getNormalizedName(displayName2));
			}
		}

		return alternateNames;
	}

	private static void loadAuthorities() {
		logger.info("Loading authorities");
		try {
			CSVReader csvReader = new CSVReader(new FileReader(new File("../record_grouping/author_authorities.properties")));
			String[] curLine = csvReader.readNext();
			while (curLine != null){
				if (curLine.length >= 2){
					authorAuthorities.put(curLine[0], curLine[1]);
				}
				curLine = csvReader.readNext();
			}
		} catch (IOException e) {
			logger.error("Unable to load author authorities", e);
		}
		try {
			CSVReader csvReader = new CSVReader(new FileReader(new File("../record_grouping/title_authorities.properties")));
			String[] curLine = csvReader.readNext();
			while (curLine != null){
				if (curLine.length >= 2){
					titleAuthorities.put(curLine[0], curLine[1]);
				}
				curLine = csvReader.readNext();
			}
		} catch (IOException e) {
			logger.error("Unable to load title authorities", e);
		}
		logger.info("Done loading authorities");
	}

	String getOriginalAuthor() {
		return originalAuthorName;
	}

	void makeUnique(String primaryIdentifier) {
		uniqueIdentifier = primaryIdentifier;
	}
}
