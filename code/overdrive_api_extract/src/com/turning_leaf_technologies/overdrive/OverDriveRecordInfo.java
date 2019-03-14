package com.turning_leaf_technologies.overdrive;

import java.util.HashSet;

class OverDriveRecordInfo {
	//Data from base title call
	private String id;
	private long crossRefId;
	private String mediaType;
	private String title;
	private String subtitle;
	private String series;
	private String primaryCreatorRole;
	private String primaryCreatorName;
	private HashSet<String> formats = new HashSet<>();
	private String coverImage;
	private HashSet<Long> collections = new HashSet<>();
	//Data from metadata call
	private String rawData;

	String getRawData() {
		return rawData;
	}

	void setRawData(String rawData) {
		this.rawData = rawData;
	}

	String getId() {
		return id;
	}
	void setId(String id) {
		this.id = id.toLowerCase();
	}
	long getCrossRefId(){
		return crossRefId;
	}
	void setCrossRefId(long crossRefId){
		this.crossRefId = crossRefId;
	}
	String getMediaType() {
		return mediaType;
	}
	void setMediaType(String mediaType) {
		this.mediaType = mediaType;
	}
	String getTitle() {
		return title;
	}
	void setTitle(String title) {
		this.title = title.replaceAll("&#174;", "�");
	}
	String getSeries() {
		return series;
	}
	void setSeries(String series) {
		this.series = series;
	}
	
	String getPrimaryCreatorRole() {
		return primaryCreatorRole;
	}
	void setPrimaryCreatorRole(String primaryCreatorRole) {
		this.primaryCreatorRole = primaryCreatorRole;
	}
	String getPrimaryCreatorName() {
		return primaryCreatorName;
	}
	void setPrimaryCreatorName(String primaryCreatorName) {
		this.primaryCreatorName = primaryCreatorName;
	}
	HashSet<String> getFormats() {
		return formats;
	}
	String getCoverImage() {
		return coverImage;
	}
	void setCoverImage(String coverImage) {
		this.coverImage = coverImage;
	}
	HashSet<Long> getCollections() {
		return collections;
	}

	String getSubtitle() {
		return subtitle;
	}

	void setSubtitle(String subtitle) {
		this.subtitle = subtitle;
	}
}
