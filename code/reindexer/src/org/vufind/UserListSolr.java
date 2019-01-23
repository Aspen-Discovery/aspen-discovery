package org.vufind;

import org.apache.solr.common.SolrInputDocument;

import java.util.Date;
import java.util.HashSet;

/**
 * Description goes here
 * Pika
 * User: Mark Noble
 * Date: 5/15/14
 * Time: 9:34 AM
 */
public class UserListSolr {
	private final GroupedWorkIndexer groupedWorkIndexer;
	private long id;
	private HashSet<String> relatedRecordIds = new HashSet<>();
	private String author;
	private String title;
	private String contents = ""; //A list of the titles and authors for the list
	private String description;
	private long numTitles = 0;
	private long created;
	private long owningLibrary;
	private String owningLocation;
	private boolean ownerHasListPublisherRole = false;

	public UserListSolr(GroupedWorkIndexer groupedWorkIndexer) {
		this.groupedWorkIndexer = groupedWorkIndexer;
	}

	public SolrInputDocument getSolrDocument(int availableAtBoostValue, int ownedByBoostValue) {
		SolrInputDocument doc = new SolrInputDocument();
		doc.addField("id", "list" + id);
		doc.addField("recordtype", "list");

		doc.addField("record_details", relatedRecordIds);

		doc.addField("title", title);
		doc.addField("title_display", title);
		
		doc.addField("title_sort", Util.makeValueSortable(title));

		doc.addField("author", author);

		doc.addField("table_of_contents", contents);
		doc.addField("description", description);
		doc.addField("keywords", description);

		//TODO: Should we count number of views to determine popularity?
		doc.addField("popularity", Long.toString(numTitles));
		doc.addField("num_holdings", numTitles);
		doc.addField("num_titles", numTitles);

		Date dateAdded = new Date(created * 1000);
		doc.addField("days_since_added", Util.getDaysSinceAddedForDate(dateAdded));

		//Do things based on scoping
		for (Scope scope: groupedWorkIndexer.getScopes()) {
			boolean okToInclude;
			if (scope.isLibraryScope()) {
				okToInclude = (scope.getPublicListsToInclude() == 2) || //All public lists
						((scope.getPublicListsToInclude() == 1) && (scope.getLibraryId() == owningLibrary)) || //All lists for the current library
						((scope.getPublicListsToInclude() == 3) && ownerHasListPublisherRole && (scope.getLibraryId() == owningLibrary)) || //All lists for list publishers at the current library
						((scope.getPublicListsToInclude() == 4) && ownerHasListPublisherRole) //All lists for list publishers
						;
			} else {
				okToInclude = (scope.getPublicListsToInclude() == 3) || //All public lists
						((scope.getPublicListsToInclude() == 1) && (scope.getLibraryId() == owningLibrary)) || //All lists for the current library
						((scope.getPublicListsToInclude() == 2) && scope.getScopeName().equals(owningLocation)) || //All lists for the current location
						((scope.getPublicListsToInclude() == 4) && ownerHasListPublisherRole && (scope.getLibraryId() == owningLibrary)) || //All lists for list publishers at the current library
						((scope.getPublicListsToInclude() == 5) && ownerHasListPublisherRole && scope.getScopeName().equals(owningLocation)) || //All lists for list publishers the current location
						((scope.getPublicListsToInclude() == 6) && ownerHasListPublisherRole) //All lists for list publishers
						;
			}
			if (okToInclude) {
				doc.addField("local_time_since_added_" + scope.getScopeName(), Util.getTimeSinceAddedForDate(dateAdded));
				doc.addField("local_days_since_added_" + scope.getScopeName(), Util.getDaysSinceAddedForDate(dateAdded));
				doc.addField("format_" + scope.getScopeName(), "list");
				doc.addField("format_category_" + scope.getScopeName(), "list");
				doc.addField("scope_has_related_records", scope.getScopeName());
			}
		}

		return doc;
	}

	public void setTitle(String title) {
		this.title = title;
	}

	public void setDescription(String description) {
		this.description = description;
	}

	public void setAuthor(String author) {
		this.author = author;
	}

	public void addListTitle(String groupedWorkId, Object title, Object author) {
		relatedRecordIds.add("grouped_work:" + groupedWorkId);
		if (contents.length() > 0){
			contents += "\r\n";
		}
		contents += title + " - " + author;
		numTitles++;
	}

	public void setCreated(long created) {
		this.created = created;
	}

	public void setId(long id) {
		this.id = id;
	}

	public void setOwningLocation(String owningLocation) {
		this.owningLocation = owningLocation;
	}

	public void setOwningLibrary(long owningLibrary) {
		this.owningLibrary = owningLibrary;
	}

	public void setOwnerHasListPublisherRole(boolean ownerHasListPublisherRole){
		this.ownerHasListPublisherRole = ownerHasListPublisherRole;
	}
}
