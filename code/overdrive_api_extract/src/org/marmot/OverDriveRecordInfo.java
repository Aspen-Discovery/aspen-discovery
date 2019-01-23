package org.marmot;

import java.util.HashSet;

public class OverDriveRecordInfo {
	//Data from base title call
	private String id;
	private long crossRefId;
	private String mediaType;
	private String title;
	private String subtitle;
	private String series;
	private String primaryCreatorRole;
	private String primaryCreatorName;
	private HashSet<String> formats = new HashSet<String>();
	private String coverImage;
	private HashSet<Long> collections = new HashSet<Long>();
	//Data from metadata call
	private String rawData;

	public String getRawData() {
		return rawData;
	}

	public void setRawData(String rawData) {
		this.rawData = rawData;
	}

	public String getId() {
		return id;
	}
	public void setId(String id) {
		this.id = id.toLowerCase();
	}
	public long getCrossRefId(){
		return crossRefId;
	}
	public void setCrossRefId(long crossRefId){
		this.crossRefId = crossRefId;
	}
	public String getMediaType() {
		return mediaType;
	}
	public void setMediaType(String mediaType) {
		this.mediaType = mediaType;
	}
	public String getTitle() {
		return title;
	}
	public void setTitle(String title) {
		this.title = title.replaceAll("&#174;", "�");
	}
	public String getSeries() {
		return series;
	}
	public void setSeries(String series) {
		this.series = series;
	}
	
	public String getPrimaryCreatorRole() {
		return primaryCreatorRole;
	}
	public void setPrimaryCreatorRole(String primaryCreatorRole) {
		this.primaryCreatorRole = primaryCreatorRole;
	}
	public String getPrimaryCreatorName() {
		return primaryCreatorName;
	}
	public void setPrimaryCreatorName(String primaryCreatorName) {
		this.primaryCreatorName = primaryCreatorName;
	}
	public HashSet<String> getFormats() {
		return formats;
	}
	public String getCoverImage() {
		return coverImage;
	}
	public void setCoverImage(String coverImage) {
		this.coverImage = coverImage;
	}
	public HashSet<Long> getCollections() {
		return collections;
	}

	public String getSubtitle() {
		return subtitle;
	}

	public void setSubtitle(String subtitle) {
		this.subtitle = subtitle;
	}
}
