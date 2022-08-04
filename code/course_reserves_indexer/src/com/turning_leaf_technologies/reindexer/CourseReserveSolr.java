package com.turning_leaf_technologies.reindexer;

import com.turning_leaf_technologies.dates.DateUtils;
import com.turning_leaf_technologies.indexing.Scope;
import com.turning_leaf_technologies.strings.AspenStringUtils;
import org.apache.solr.common.SolrInputDocument;

import java.util.Date;
import java.util.HashSet;

class CourseReserveSolr {
	private final CourseReservesIndexer courseReservesIndexer;
	private long id;
	private final HashSet<String> relatedRecordIds = new HashSet<>();
	private String instructor;
	private String title;
	private String courseNumber;
	private String courseTitle;
	private final HashSet<String> contents = new HashSet<>(); //A list of the titles and authors for the list
	private long numTitles = 0;
	private long created;
	private String courseLibrary;
	private String displayLibrary;

	CourseReserveSolr(CourseReservesIndexer courseReservesIndexer) {
		this.courseReservesIndexer = courseReservesIndexer;
	}

	SolrInputDocument getSolrDocument() {
		SolrInputDocument doc = new SolrInputDocument();
		doc.addField("id", id);
		doc.addField("recordtype", "course_reserve");

		doc.addField("alternate_ids", relatedRecordIds);

		doc.addField("title", title);
		doc.addField("title_display", title);
		
		doc.addField("title_sort", AspenStringUtils.makeValueSortable(title));

		doc.addField("library", displayLibrary);

		doc.addField("instructor", instructor);
		doc.addField("instructor_display", instructor);

		doc.addField("course_number", courseNumber);
		doc.addField("course_title", courseTitle);

		doc.addField("table_of_contents", contents);

		doc.addField("popularity", Long.toString(numTitles));
		doc.addField("num_titles", numTitles);

		Date dateAdded = new Date(created * 1000);
		doc.addField("days_since_added", DateUtils.getDaysSinceAddedForDate(dateAdded));

		//Things based on scoping
		int numValidScopes = 0;
		HashSet<String> relevantScopes = new HashSet<>();
		for (Scope scope: courseReservesIndexer.getScopes()) {
			boolean okToInclude = scope.isCourseReserveLibaryIncluded(courseLibrary);
			if (okToInclude) {
				numValidScopes++;
				doc.addField("local_time_since_added_" + scope.getScopeName(), DateUtils.getTimeSinceAddedForDate(dateAdded));
				doc.addField("local_days_since_added_" + scope.getScopeName(), DateUtils.getDaysSinceAddedForDate(dateAdded));
				relevantScopes.add(scope.getScopeName());
			}
		}

		if (numValidScopes == 0){
			return null;
		}else{
			doc.addField("scope_has_related_records", relevantScopes);
			return doc;
		}
	}

	void setTitle(String title) {
		this.title = title;
	}

	void addTitle(String groupedWorkId, Object title, Object author) {
		relatedRecordIds.add("grouped_work" + ":" + groupedWorkId);
		contents.add(title + " - " + author);
		numTitles++;
	}

	void setCreated(long created) {
		this.created = created;
	}

	void setId(long id) {
		this.id = id;
	}

	long getNumTitles(){
		return numTitles;
	}

	public void setCourseNumber(String courseNumber) {
		this.courseNumber = courseNumber;
	}

	public void setCourseTitle(String courseTitle) {
		this.courseTitle = courseTitle;
	}

	public void setInstructor(String instructor) {
		this.instructor = instructor;
	}

	public void setCourseLibrary(String courseLibrary) {
		this.courseLibrary = courseLibrary;
	}

	public void setDisplayLibrary(String displayLibrary) {
		this.displayLibrary = displayLibrary;
	}
}
