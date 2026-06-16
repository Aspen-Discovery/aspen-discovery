package org.aspen_discovery.reindexer;

import java.sql.ResultSet;
import java.sql.SQLException;
import java.text.Normalizer;
import java.util.Collection;
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
	private final HashMap<String, SeriesMemberVolume> volumes = new HashMap<>();
	private boolean isIndexed;
	private final int priorityScore;
	private int version;
	private boolean foundInCurrentIndex = false;
	private boolean deleted;

	/**
	 * Constructor for use when loading series that are already linked to a Grouped Work via a Series Member
	 */
	public SeriesMember(ResultSet seriesMemberRS) throws SQLException {
		this.seriesId = seriesMemberRS.getLong("seriesId");
		this.seriesPermanentId = seriesMemberRS.getString("seriesPermanentId");
		this.groupedWorkSeriesTitle = seriesMemberRS.getString("groupedWorkSeriesTitle");
		this.author = seriesMemberRS.getString("author");
		this.seriesLanguage = seriesMemberRS.getString("seriesLanguage");
		this.volumes.put(seriesMemberRS.getString("volume"), new SeriesMemberVolume(seriesMemberRS.getString("volume"), seriesMemberRS.getBoolean("deleted"), seriesMemberRS.getBoolean("userAdded")));
		this.priorityScore =  seriesMemberRS.getInt("priorityScore");
		this.isIndexed = seriesMemberRS.getBoolean("isIndexed");
		this.version = seriesMemberRS.getInt("version");
		this.deleted = seriesMemberRS.getBoolean("deleted");
	}

	/**
	 * Constructor to use when loading a version 1 series that is not linked to a grouped work.
	 * Useful for linking grouped works to existing series so duplicates aren't created
	 *
	 * Note: This does NOT contain info about the linked member since there isn't one currently
	 */
	public SeriesMember(long seriesId, String groupedWorkSeriesTitle, String author, boolean isIndexed, boolean deleted) {
		this.seriesId = seriesId;
		this.groupedWorkSeriesTitle = groupedWorkSeriesTitle;
		this.author = author;
		this.priorityScore = 0;
		this.version = 1;
		this.isIndexed = isIndexed;
		this.deleted = deleted;
		this.seriesPermanentId = null;
		this.seriesLanguage = null;
	}

	/**
	 * Constructor to use when loading a version 2 series that is not linked to a grouped work.
	 * Useful for linking grouped works to existing series so duplicates aren't created
	 *
	 * Note: This does NOT contain info about the linked member since there isn't one currently
	 */
	public SeriesMember(long seriesId, String seriesPermanentId, String groupedWorkSeriesTitle, String author, String seriesLanguage, boolean isIndexed, boolean deleted) {
		this.seriesId = seriesId;
		this.groupedWorkSeriesTitle = groupedWorkSeriesTitle;
		this.author = author;
		this.priorityScore = 0;
		this.version = 1;
		this.isIndexed = isIndexed;
		this.deleted = deleted;
		this.seriesPermanentId = seriesPermanentId;
		this.seriesLanguage = seriesLanguage;
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

	public Collection<SeriesMemberVolume> getSeriesVolumes() {
		return volumes.values();
	}

	public void setVolumeFoundInIndex(String volumeId) {
		if (this.volumes.containsKey(volumeId)) {
			this.volumes.get(volumeId).setFoundInIndex(true);
		}else{
			SeriesMemberVolume volume = new SeriesMemberVolume();
			volume.setVolume(volumeId);
			volume.setFoundInIndex(true);
			this.volumes.put(volumeId, volume);
		}
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

	public void addVolume(String volume, boolean deleted, boolean userAdded) {
		this.volumes.put(volume, new SeriesMemberVolume(volume, deleted, userAdded));
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
		return this.volumes.get(volume).isFoundInIndex();
	}

	@SuppressWarnings("unused")
	public String getSeriesLanguage() {
		return seriesLanguage;
	}
}
