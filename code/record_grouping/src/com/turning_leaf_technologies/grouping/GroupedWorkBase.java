package com.turning_leaf_technologies.grouping;

import com.turning_leaf_technologies.logging.BaseLogEntry;
import org.apache.logging.log4j.Logger;
import org.apache.logging.log4j.LogManager;

import java.math.BigInteger;
import java.security.MessageDigest;
import java.security.NoSuchAlgorithmException;

/**
 * Superclass for all Grouped Works which have different normalization rules.
 */
public abstract class GroupedWorkBase {
	private static Logger logger	= LogManager.getLogger(GroupedWorkBase.class);
	protected BaseLogEntry logEntry;

	//The id of the work within the database.
	String permanentId;

	String fullTitle = "";              //Up to 100 chars
	String originalAuthorName = "";
	protected String author = "";             //Up to 50  chars
	String groupingCategory = "";   //Up to 25  chars
	private String uniqueIdentifier = null;
	private RecordGroupingProcessor processor;

	public GroupedWorkBase(RecordGroupingProcessor processor){
		this.processor = processor;
		this.logEntry = processor.getLogEntry();
	}

	//Load authorities
	//private static HashMap<String, String> authorAuthorities = new HashMap<>();
	//private static HashMap<String, String> titleAuthorities = new HashMap<>();

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

				String authoritativeAuthor = getAuthoritativeAuthor();
				//TODO: Delete this if block
				if (!authoritativeAuthor.equals(this.author)){
					logger.warn("Authoritative author " + authoritativeAuthor + " used for " + fullTitle);
				}
				if (author.equals("")){
					idGenerator.update("--null--".getBytes());
				}else{
					idGenerator.update(authoritativeAuthor.getBytes());
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
			authoritativeTitle = processor.getAuthoritativeTitle(fullTitle);
		}
		return authoritativeTitle;
	}

	abstract void setTitle(String title, int numNonFilingCharacters, String subtitle, String partInformation);

	abstract String getAuthor();

	private String authoritativeAuthor = null;
	String getAuthoritativeAuthor() {
		if (authoritativeAuthor == null) {
			authoritativeAuthor = processor.getAuthoritativeAuthor(author);
		}
		return authoritativeAuthor;
	}

	abstract void setAuthor(String author);

	abstract void overridePermanentId(String groupedWorkPermanentId);

	abstract void setGroupingCategory(String groupingCategory);

	abstract String getGroupingCategory();

	void makeUnique(String primaryIdentifier) {
		uniqueIdentifier = primaryIdentifier;
	}
}
