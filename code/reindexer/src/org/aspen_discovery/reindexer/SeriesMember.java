package org.aspen_discovery.reindexer;

import java.sql.ResultSet;
import java.sql.SQLException;
import java.text.Normalizer;
import java.util.HashMap;
import java.util.Set;

/**
 * Series Members from the database
 */
public class SeriesMember {
	private final long seriesId;
	private final String seriesPermanentId;
	private final String groupedWorkSeriesTitle;
	private String author;
	@SuppressWarnings("FieldCanBeLocal")
	private final String seriesLanguage;
	//A list of volumes found in the database with a flag for if it was found in the current index
	private final HashMap<String, Boolean> volumes = new HashMap<>();
	private boolean isIndexed;
	private final int priorityScore;
	private int version;
	private boolean foundInCurrentIndex = false;
	private boolean deleted;

	public SeriesMember(ResultSet seriesMemberRS) throws SQLException {
		this.seriesId = seriesMemberRS.getLong("seriesId");
		this.seriesPermanentId = seriesMemberRS.getString("seriesPermanentId");
		this.groupedWorkSeriesTitle = seriesMemberRS.getString("groupedWorkSeriesTitle");
		this.author = seriesMemberRS.getString("author");
		this.seriesLanguage = seriesMemberRS.getString("seriesLanguage");
		this.volumes.put(seriesMemberRS.getString("volume"), false);
		this.priorityScore =  seriesMemberRS.getInt("priorityScore");
		this.isIndexed = seriesMemberRS.getBoolean("isIndexed");
		this.version = seriesMemberRS.getInt("version");
		this.deleted = seriesMemberRS.getBoolean("deleted");

	}

	public long getSeriesId() {
		return seriesId;
	}

	public String getSeriesPermanentId() {
		return seriesPermanentId;
	}

	private String normalizedSeriesName;
	public String getNormalizedSeriesName() {
		if (normalizedSeriesName == null) {
			normalizedSeriesName = Normalizer.normalize(this.groupedWorkSeriesTitle, Normalizer.Form.NFKD).replaceAll("\\p{M}", "").toLowerCase();
		}
		return normalizedSeriesName;
	}

	public String getAuthor() {
		return author;
	}

	private String normalizedAuthor;
	public String getNormalizedAuthor() {
		if (normalizedAuthor == null) {
			if (this.getAuthor() == null || this.getAuthor().isEmpty()) {
				normalizedAuthor = "";
			}else {
				normalizedAuthor = Normalizer.normalize(this.getAuthor(), Normalizer.Form.NFKD)
					.replaceAll("[^\\w\\s]", "")
					.strip()
					.replaceAll("\\s+", " ");
			}
		}
		return normalizedAuthor;
	}

	public void setAuthor(String author) {
		this.author = author;
	}

	public Set<String> getVolumes() {
		return volumes.keySet();
	}

	public void setVolumeFoundInIndex(String volumeId) {
		this.volumes.put(volumeId, true);
	}

	public boolean isIndexed() {
		return isIndexed;
	}

	public void setIndexed(boolean indexed) {
		isIndexed = indexed;
	}

	public int getVersion() {
		return version;
	}

	public void setVersion(int version) {
		this.version = version;
	}

	public void addVolume(String volume) {
		this.volumes.put(volume, false);
	}

	public boolean isDeleted() {
		return deleted;
	}

	public void setDeleted(boolean deleted) {
		this.deleted = deleted;
	}

	public boolean isFoundInCurrentIndex() {
		return foundInCurrentIndex;
	}

	public void setFoundInCurrentIndex(boolean foundInCurrentIndex) {
		this.foundInCurrentIndex = foundInCurrentIndex;
	}

	public int getPriorityScore() {
		return priorityScore;
	}

	public boolean volumeFoundInIndex(String volume) {
		return this.volumes.get(volume);
	}

	@SuppressWarnings("unused")
	public String getSeriesLanguage() {
		return seriesLanguage;
	}
}
