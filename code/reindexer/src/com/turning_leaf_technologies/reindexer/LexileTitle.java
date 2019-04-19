package com.turning_leaf_technologies.reindexer;

import java.util.HashSet;

public class LexileTitle {
	private String title;
	private String author;
	private String lexileCode;
	private String lexileScore;
	private String series;
	private HashSet<String> awards = new HashSet<>();
	private String description;

	@SuppressWarnings("unused")
	String getDescription() {
		return description;
	}

	void setDescription(String description) {
		this.description = description;
	}

	public String getTitle() {
		return title;
	}

	public void setTitle(String title) {
		this.title = title;
	}

	@SuppressWarnings("unused")
	String getAuthor() {
		return author;
	}

	void setAuthor(String author) {
		this.author = author;
	}

	String getLexileCode() {
		return lexileCode;
	}

	void setLexileCode(String lexileCode) {
		this.lexileCode = lexileCode;
	}

	String getLexileScore() {
		return lexileScore;
	}

	void setLexileScore(String lexileScore) {
		this.lexileScore = lexileScore;
	}

	String getSeries() {
		return series;
	}

	void setSeries(String series) {
		this.series = series;
	}

	HashSet<String> getAwards() {
		return awards;
	}

	void setAwards(String awards) {
		//Remove anything in quotes
		if (awards != null && awards.length() > 0){
			awards = awards.replaceAll("\\(.*?\\)", "");
			String[] individualAwards = awards.split(",");
			for (String individualAward : individualAwards){
				this.awards.add(individualAward.trim());
			}
		}
	}
}
