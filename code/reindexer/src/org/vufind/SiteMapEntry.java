package org.vufind;


/**
 * Created by jabedo on 9/25/2016.
 *
 * A single entry within a site map.
 */
class SiteMapEntry implements Comparable {

	/*
	*
	* The ownership is determined by scope and the sitemap will be loaded by scope.
	* So you need to either have a set of SiteMaps (1 per scope) or update SiteMapEntry to include the scope and then have a list of works within the SiteMapEntry.  The first option is probably better.
	* When you add a grouped work to a SiteMap you will need to loop through all of the scopes that you are building sitemaps for and check each scope to see if the record isLibraryOwned.  The logic will be similar to the logic in: updateIndexingStats.
	*
	*/
	private Long Id;
	private String permanentId;
	private double popularity;

	public Long getId() {
		return Id;
	}

	String getPermanentId() {
		return permanentId;
	}

	SiteMapEntry(Long Id, String permanentId, Double popularity) {
		this.permanentId = permanentId;
		this.Id = Id;
		this.popularity = popularity;
	}

	@Override
	public int compareTo(Object o) {
		//compare object based on popularity
		SiteMapEntry toCompare = (SiteMapEntry) o;
		return Double.compare(toCompare.popularity, this.popularity);
	}
}
