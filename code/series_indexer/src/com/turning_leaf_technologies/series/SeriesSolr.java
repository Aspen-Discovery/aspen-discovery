package com.turning_leaf_technologies.series;

import com.turning_leaf_technologies.dates.DateUtils;
import com.turning_leaf_technologies.indexing.Scope;
import com.turning_leaf_technologies.strings.AspenStringUtils;
import org.apache.solr.common.SolrDocument;
import org.apache.solr.common.SolrInputDocument;

import java.util.Collections;
import java.util.Date;
import java.util.HashSet;

class SeriesSolr {
	private final SeriesIndexer seriesIndexer;
	private long id;
	private final HashSet<String> relatedRecordIds = new HashSet<>();
	private final HashSet<String> authors = new HashSet<>();
	private final HashSet<String> formats = new HashSet<>();
	private final HashSet<String> formatCategories = new HashSet<>();
	private final HashSet<String> eContentSource = new HashSet<>();
	private final HashSet<String> subjects = new HashSet<>();
	private int fiction = 0;
	private int nonFiction = 0;
	private final HashSet<String> languages = new HashSet<>();
	private String title;
	private final HashSet<String> contents = new HashSet<>(); //A list of the titles and authors for the list
	private String description;
	private final HashSet<String> audiences = new HashSet<>();
	private long numTitles = 0;
	private long created;
	private long dateUpdated;

	SeriesSolr(SeriesIndexer seriesIndexer) {
		this.seriesIndexer = seriesIndexer;
	}

	SolrInputDocument getSolrDocument() {
		SolrInputDocument doc = new SolrInputDocument();
		doc.addField("id", id);
		doc.addField("recordtype", "series");

		doc.addField("alternate_ids", relatedRecordIds);

		doc.addField("title", title);
		doc.addField("title_display", title);

		doc.addField("title_sort", AspenStringUtils.makeValueSortable(title));

		doc.addField("author", authors);
		if (authors.size() > 3) {
			doc.addField("author_display", "Various");
		} else {
			doc.addField("author_display", authors);
		}

		processScopedDynamicField(eContentSource, "econtent_source", doc);
		processScopedDynamicField(formats, "format", doc);
		processScopedDynamicField(formatCategories, "format_category", doc);

		doc.addField("subject", subjects);
		doc.addField("literary_form", fiction > nonFiction ? "Fiction" : "Non Fiction");
		doc.addField("language", languages);
		doc.addField("audience", audiences);

		doc.addField("table_of_contents", contents);
		doc.addField("description", description);
		doc.addField("keywords", description);

		doc.addField("num_titles", numTitles);

		Date dateAdded = new Date(created * 1000);
		doc.addField("days_since_added", DateUtils.getDaysSinceAddedForDate(dateAdded));

		Date dateUpdatedDate = new Date(dateUpdated * 1000);
		doc.addField("days_since_updated", DateUtils.getDaysSinceAddedForDate(dateUpdatedDate));

		int numValidScopes = 0;
		HashSet<String> relevantScopes = new HashSet<>();
		for (Scope scope: seriesIndexer.getScopes()) {
			numValidScopes++;
			doc.addField("local_time_since_added_" + scope.getScopeName(), DateUtils.getTimeSinceAddedForDate(dateAdded));
			doc.addField("local_days_since_added_" + scope.getScopeName(), DateUtils.getDaysSinceAddedForDate(dateAdded));

			doc.addField("local_time_since_updated_" + scope.getScopeName(), DateUtils.getTimeSinceAddedForDate(dateUpdatedDate));
			doc.addField("local_days_since_updated_" + scope.getScopeName(), DateUtils.getDaysSinceAddedForDate(dateUpdatedDate));
			relevantScopes.add(scope.getScopeName());
		}

		if (numValidScopes == 0){
			return null;
		}else{
			doc.addField("scope_has_related_records", relevantScopes);
			return doc;
		}
	}

	void processScopedDynamicField(HashSet<String> field, String solrFieldName, SolrInputDocument doc) {
		for (String value : field) {
			if (value.contains("#")) {
				String[] parts = value.split("#");
				if (parts.length == 2) {
					doc.addField(solrFieldName + "_" + parts[0], parts[1]);
				}
			}
		}
	}

	void setTitle(String title) {
		this.title = title;
	}

	void setDescription(String description) {
		this.description = description;
	}

	void setAudiences(String[] audiences) {
		Collections.addAll(this.audiences, audiences);
	}

	void setAudience(String audiences) {
		this.audiences.add(audiences);
	}

	void addListTitle(@SuppressWarnings("SameParameterValue") String source, String groupedWorkId, Object title, Object author, SolrDocument work) {
		relatedRecordIds.add(source + ":" + groupedWorkId);
		contents.add(title + " - " + author);
		authors.add(author.toString());
		if (work.containsKey("format")) {
			for (Object value : work.getFieldValues("format")) {
				formats.add(value.toString());
			}
		}
		if (work.containsKey("format_category")) {
			for (Object value : work.getFieldValues("format_category")) {
				formatCategories.add(value.toString());
			}
		}
		if (work.containsKey("subject")) {
			for (Object value : work.getFieldValues("subject")) {
				subjects.add(value.toString());
			}
		}
		if (work.containsKey("language")) {
			for (Object value : work.getFieldValues("language")) {
				languages.add(value.toString());
			}
		}
		// Pick the one with the most results, fiction or nonfiction
		if (work.containsKey("literary_form")) {
			String literaryForm = work.getFieldValue("literary_form").toString();
			if (literaryForm.equals("[Fiction]")) {
				fiction++;
			} else {
				nonFiction++;
			}
		}
		if (work.containsKey("econtent_source")) {
			for (Object value : work.getFieldValues("econtent_source")) {
				eContentSource.add(value.toString());
			}
		}
		numTitles++;
	}

	void setCreated(long created) {
		this.created = created;
	}

	void setId(long id) {
		this.id = id;
	}

	public void setDateUpdated(long dateUpdated) {
		this.dateUpdated = dateUpdated;
	}
}
