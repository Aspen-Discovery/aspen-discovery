package org.vufind;

import java.util.HashSet;

/**
 * Contains information about the lexile information related to a title.
 * Pika
 * User: Mark Noble
 * Date: 3/6/14
 * Time: 8:56 AM
 */
public class LexileTitle {
	private String title;
	private String author;
	private String lexileCode;
	private String lexileScore;
	private String series;
	private HashSet<String> awards = new HashSet<>();
	private String description;

	public String getDescription() {
		return description;
	}

	public void setDescription(String description) {
		this.description = description;
	}

	public String getTitle() {
		return title;
	}

	public void setTitle(String title) {
		this.title = title;
	}

	public String getAuthor() {
		return author;
	}

	public void setAuthor(String author) {
		this.author = author;
	}

	public String getLexileCode() {
		return lexileCode;
	}

	public void setLexileCode(String lexileCode) {
		this.lexileCode = lexileCode;
	}

	public String getLexileScore() {
		return lexileScore;
	}

	public void setLexileScore(String lexileScore) {
		this.lexileScore = lexileScore;
	}

	public String getSeries() {
		return series;
	}

	public void setSeries(String series) {
		this.series = series;
	}

	public HashSet<String> getAwards() {
		return awards;
	}

	public void setAwards(String awards) {
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
