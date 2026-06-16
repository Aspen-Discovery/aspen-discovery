package org.aspen_discovery.reindexer;

import java.math.BigInteger;
import java.security.MessageDigest;
import java.security.NoSuchAlgorithmException;
import java.text.Normalizer;
import java.util.HashSet;

public class SeriesInfo {
	private long id;
	private int version;
	private String permanentId;
	private final String seriesName;
	private String author = "";
	private String language;
	private int priorityScore;
	private boolean isTraced = false;

	private boolean isIndexed = true;

	private final HashSet<String> seriesVolumes = new HashSet<>();

	public SeriesInfo(String seriesName) {
		this.seriesName = seriesName;
		this.version = 1;
	}

	public SeriesInfo(String seriesName, String author, String language, GroupedWorkIndexer indexer) {
		this.seriesName = seriesName;
		this.author = author;
		this.setLanguage(language, indexer);
		this.version = 2;
	}

	// Getters and Setters

	public long getId() {
		return id;
	}

	public void setId(long id) {
		this.id = id;
	}

	public String getSeriesName() {
		return seriesName;
	}

	private String normalizedSeriesName;
	public String getNormalizedSeriesName() {
		if (normalizedSeriesName == null) {
			normalizedSeriesName = Normalizer.normalize(this.seriesName, Normalizer.Form.NFKD).replaceAll("\\p{M}", "").toLowerCase();
		}
		return normalizedSeriesName;
	}

	public int getVersion() {
		return version;
	}

	public void setVersion(int version) {
		this.version = version;
	}

	public String getAuthor() {
		return author;
	}

	public void setAuthor(String author) {
		this.author = author;
	}

	private String normalizedAuthor;
	public String getNormalizedAuthor() {
		if (normalizedAuthor == null) {
			normalizedAuthor = Normalizer.normalize(this.getAuthor(), Normalizer.Form.NFKD)
				.replaceAll("[^\\w\\s]", "")
				.strip()
				.replaceAll("\\s+", " ");
		}
		return normalizedAuthor;
	}

	public String getLanguage() {
		return language;
	}

	public void setLanguage(String language, GroupedWorkIndexer indexer) {
		String seriesLanguage = indexer.translateSystemValue("language_to_three_letter_code", language, "series");
		if (seriesLanguage == null || seriesLanguage.length() != 3 || seriesLanguage.contains(" ")) {
			seriesLanguage = "unk";
		}
		this.language = seriesLanguage;
		this.key = null;
		this.permanentId = null;
	}

	public int getPriorityScore() {
		return priorityScore;
	}

	public boolean isIndexed() {
		return isIndexed;
	}

	public void setIndexed(boolean indexed) {
		isIndexed = indexed;
	}

	String getPermanentId() {
		if (permanentId == null){
			StringBuilder tmpPermanentId;
			try {
				MessageDigest idGenerator = MessageDigest.getInstance("MD5");

				if (seriesName.isEmpty()){
					idGenerator.update("--null--".getBytes());
				}else{
					idGenerator.update(seriesName.getBytes());
				}
				if (author.isEmpty()){
					idGenerator.update("--null--".getBytes());
				}else{
					idGenerator.update(author.getBytes());
				}
				tmpPermanentId = new StringBuilder(new BigInteger(1, idGenerator.digest()).toString(16));
				while (tmpPermanentId.length() < 32){
					tmpPermanentId.insert(0, "0");
				}
				//Insert -'s for formatting
				permanentId = tmpPermanentId.substring(0, 8) + "-" + tmpPermanentId.substring(8, 12)  + "-" + tmpPermanentId.substring(12, 16) + "-" + tmpPermanentId.substring(16, 20) + "-" + tmpPermanentId.substring(20) + "-" + language;
			} catch (NoSuchAlgorithmException e) {
				System.out.println("Error generating permanent id" + e);
			}
		}
		return permanentId;
	}

	private String key = null;
	public String getKey() {
		if (key == null) {
			if (version == 1) {
				key = this.seriesName;
			}else{
				key = this.getPermanentId();
			}
			key = key.toLowerCase();
		}
		return key;
	}

	/**
	 * Add the Grouped Work as a member.
	 * If the volume is blank, it will be combined with a non-blank volume if one exists.
	 * If the volume is not blank it will override the blank volume.
	 * We can get multiple volumes for the same record within a series
	 */
	public void addVolume(String volume) {
		if (volume.isEmpty()) {
			//Blank string, only add if the volumes is null
			if (seriesVolumes.isEmpty()) {
				seriesVolumes.add(volume);
			}
		}else{
			seriesVolumes.add(volume);
			//Remove the blank entry if we have one
			seriesVolumes.remove("");
		}
	}

	public HashSet<String> getVolumes() {
		return seriesVolumes;
	}

	public boolean isTraced() {
		return isTraced;
	}

	/**
	 * If at least one instance of the series is traced, we will mark it as traced and add it
	 */
	public void setTraced(boolean traced) {
		if (traced) {
			isTraced = true;
		}
	}

	public void addPriority(int priority) {
		this.priorityScore += priority;
	}
}
