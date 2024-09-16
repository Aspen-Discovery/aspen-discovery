package com.turning_leaf_technologies.oai;

import com.turning_leaf_technologies.dates.DateInfo;
import com.turning_leaf_technologies.strings.AspenStringUtils;
import org.apache.commons.lang3.time.DateParser;
import org.apache.logging.log4j.Logger;
import org.apache.solr.common.SolrInputDocument;

import java.util.Collections;
import java.util.Date;
import java.util.HashSet;
import java.util.regex.Pattern;

class OAISolrRecord {
	private String identifier;
	private String type;
	private String title;
	private final HashSet<String> creators = new HashSet<>();
	private final HashSet<String> contributors = new HashSet<>();
	private final HashSet<String> subjects = new HashSet<>();
	private String description;
	private final HashSet<String> coverage = new HashSet<>();
	private final HashSet<String> publisher = new HashSet<>();
	private final HashSet<String> format = new HashSet<>();
	private String language;
	private final HashSet<String> source = new HashSet<>();
	private final HashSet<String> relation = new HashSet<>();

	private final HashSet<String> locations = new HashSet<>();
	private String rights;
	private final HashSet<String> date = new HashSet<>();
	private String id;
	private String collection_name;
	private long collection_id;
	private HashSet<String> scopesToInclude;

	void setIdentifier(String identifier) {
		this.identifier = identifier;
	}

	void setType(String type) {
		this.type = type;
	}

	void setTitle(String title) {
		this.title = title;
	}

	void addCreator(String creator) {
		this.creators.add(creator);
	}

	void setDescription(String description) {
		this.description = description;
	}

	void setLanguage(String language) {
		this.language = language;
	}

	void setRights(String rights) {
		this.rights = rights;
	}

	void addContributor(String contributor) {
		this.contributors.add(contributor);
	}

	void setScopesToInclude(HashSet<String> scopesToInclude) {
		this.scopesToInclude = scopesToInclude;
	}

	Pattern datePattern = Pattern.compile("\\d{2,4}(-\\d{2,4}){0,2}");

	void addDates(String[] dates, Logger logger, long dateFormatting) {
		if(dateFormatting==1) {
			for (String date : dates) {
				if (AspenStringUtils.isNumeric(date)) {
					this.date.add(new DateInfo(1, 1, Integer.parseInt(date)).getSolrDate());
				} else {
					DateInfo dateInfo = new DateInfo(date);
					if (!dateInfo.isNotSet()) {
						this.date.add(dateInfo.getSolrDate());
					} else {
						logger.debug("Could not parse date " + date);
					}
				}
			}
		}else{
			this.date.add(dates[0]);
		}
	}

	void addLocation(String location) {
		locations.add(location);
	}

	SolrInputDocument getSolrDocument() {
		SolrInputDocument doc = new SolrInputDocument();
		doc.addField("id", this.id);
		doc.addField("identifier", this.identifier);
		doc.addField("type", type);
		doc.addField("collection_id", collection_id);
		doc.addField("collection_name", collection_name);
		doc.addField("last_indexed", new Date());
		doc.addField("title", title);
		doc.addField("creator", creators);
		doc.addField("contributor", contributors);
		doc.addField("subject", subjects);
		doc.addField("description", description);
		doc.addField("coverage", coverage);
		doc.addField("publisher", publisher);
		doc.addField("format", format);
		doc.addField("language", language);
		doc.addField("source", source);
		doc.addField("relation", relation);
		doc.addField("rights", rights);
		doc.addField("date", date);
		doc.addField("geographic", locations);
		doc.addField("scope_has_related_records", scopesToInclude);
		return doc;
	}

	void addSubjects(String[] subjects) {
		Collections.addAll(this.subjects, subjects);
	}

	void addCoverage(String coverage) {
		this.coverage.add(coverage);
	}

	void addPublisher(String publisher) {
		this.publisher.add(publisher);
	}

	void addFormat(String format) {
		this.format.add(format);
	}

	void addSource(String source) {
		this.source.add(source);
	}

	void addRelation(String relation) {
		this.relation.add(relation);
	}

	String getIdentifier() {
		return identifier;
	}

	String getTitle() {
		return title;
	}

	void setId(String id) {
		this.id = id;
	}

	void setCollectionName(String collection_name) {
		this.collection_name = collection_name;
	}

	void setCollectionId(long collection_id) {
		this.collection_id = collection_id;
	}

	HashSet<String> getSubjects() {
		return subjects;
	}
}
