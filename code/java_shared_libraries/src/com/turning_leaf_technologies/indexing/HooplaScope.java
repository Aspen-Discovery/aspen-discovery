package com.turning_leaf_technologies.indexing;

import com.turning_leaf_technologies.util.MaxSizeHashMap;
import org.apache.logging.log4j.Logger;

import java.util.HashMap;

public class HooplaScope {
	private long id;
	private String name;
	private int excludeTitlesWithCopiesFromOtherVendors;
	private boolean includeEBooks;
	private float maxCostPerCheckoutEBooks;
	private boolean includeEComics;
	private float maxCostPerCheckoutEComics;
	private boolean includeEAudiobook;
	private float maxCostPerCheckoutEAudiobook;
	private boolean includeMovies;
	private float maxCostPerCheckoutMovies;
	private boolean includeMusic;
	private float maxCostPerCheckoutMusic;
	private boolean includeTelevision;
	private float maxCostPerCheckoutTelevision;
	private boolean includeBingePass;
	private float maxCostPerCheckoutBingePass;
	private boolean restrictToChildrensMaterial;
	private String[] ratingsToExclude;
	private boolean excludeAbridged;
	private boolean excludeParentalAdvisory;
	private boolean excludeProfanity;

	public long getId() {
		return id;
	}

	public void setId(long id) {
		this.id = id;
	}

	public String getName() {
		return name;
	}

	public void setName(String name) {
		this.name = name;
	}

	public boolean isIncludeEBooks() {
		return includeEBooks;
	}

	void setIncludeEBooks(boolean includeEBooks) {
		this.includeEBooks = includeEBooks;
	}

	public float getMaxCostPerCheckoutEBooks() {
		return maxCostPerCheckoutEBooks;
	}

	void setMaxCostPerCheckoutEBooks(float maxCostPerCheckoutEBooks) {
		this.maxCostPerCheckoutEBooks = maxCostPerCheckoutEBooks;
	}

	public boolean isIncludeEComics() {
		return includeEComics;
	}

	void setIncludeEComics(boolean includeEComics) {
		this.includeEComics = includeEComics;
	}

	public float getMaxCostPerCheckoutEComics() {
		return maxCostPerCheckoutEComics;
	}

	void setMaxCostPerCheckoutEComics(float maxCostPerCheckoutEComics) {
		this.maxCostPerCheckoutEComics = maxCostPerCheckoutEComics;
	}

	public boolean isIncludeEAudiobook() {
		return includeEAudiobook;
	}

	void setIncludeEAudiobook(boolean includeEAudiobook) {
		this.includeEAudiobook = includeEAudiobook;
	}

	public float getMaxCostPerCheckoutEAudiobook() {
		return maxCostPerCheckoutEAudiobook;
	}

	void setMaxCostPerCheckoutEAudiobook(float maxCostPerCheckoutEAudiobook) {
		this.maxCostPerCheckoutEAudiobook = maxCostPerCheckoutEAudiobook;
	}

	public boolean isIncludeMovies() {
		return includeMovies;
	}

	void setIncludeMovies(boolean includeMovies) {
		this.includeMovies = includeMovies;
	}

	public float getMaxCostPerCheckoutMovies() {
		return maxCostPerCheckoutMovies;
	}

	void setMaxCostPerCheckoutMovies(float maxCostPerCheckoutMovies) {
		this.maxCostPerCheckoutMovies = maxCostPerCheckoutMovies;
	}

	public boolean isIncludeMusic() {
		return includeMusic;
	}

	void setIncludeMusic(boolean includeMusic) {
		this.includeMusic = includeMusic;
	}

	public float getMaxCostPerCheckoutMusic() {
		return maxCostPerCheckoutMusic;
	}

	void setMaxCostPerCheckoutMusic(float maxCostPerCheckoutMusic) {
		this.maxCostPerCheckoutMusic = maxCostPerCheckoutMusic;
	}

	public boolean isIncludeTelevision() {
		return includeTelevision;
	}

	void setIncludeTelevision(boolean includeTelevision) {
		this.includeTelevision = includeTelevision;
	}

	public float getMaxCostPerCheckoutTelevision() {
		return maxCostPerCheckoutTelevision;
	}

	void setMaxCostPerCheckoutTelevision(float maxCostPerCheckoutTelevision) {
		this.maxCostPerCheckoutTelevision = maxCostPerCheckoutTelevision;
	}

	public boolean isIncludeBingePass() {
		return includeBingePass;
	}

	public void setIncludeBingePass(boolean includeBingePass) {
		this.includeBingePass = includeBingePass;
	}

	public float getMaxCostPerCheckoutBingePass() {
		return maxCostPerCheckoutBingePass;
	}

	public void setMaxCostPerCheckoutBingePass(float maxCostPerCheckoutBingePass) {
		this.maxCostPerCheckoutBingePass = maxCostPerCheckoutBingePass;
	}

	public boolean isRestrictToChildrensMaterial() {
		return restrictToChildrensMaterial;
	}

	void setRestrictToChildrensMaterial(boolean restrictToChildrensMaterial) {
		this.restrictToChildrensMaterial = restrictToChildrensMaterial;
	}

	private HashMap<String, Boolean> excludedRatings = new HashMap<>();

	public boolean isRatingExcluded(String rating) {
		if (rating.length() == 0) {
			return false;
		}
		Boolean ratingExcluded = excludedRatings.get(rating);
		if (ratingExcluded == null) {
			ratingExcluded = false;
			for (String tmpRatingToExclude : ratingsToExclude) {
				if (tmpRatingToExclude.equals(rating)) {
					ratingExcluded = true;
					break;
				}
			}
			excludedRatings.put(rating, ratingExcluded);
		}
		return ratingExcluded;

	}

	void setRatingsToExclude(String ratingsToExclude) {
		if (ratingsToExclude == null) {
			ratingsToExclude = "";
		}
		this.ratingsToExclude = ratingsToExclude.split("\\|");
	}

	public boolean isExcludeAbridged() {
		return excludeAbridged;
	}

	void setExcludeAbridged(boolean excludeAbridged) {
		this.excludeAbridged = excludeAbridged;
	}

	public boolean isExcludeParentalAdvisory() {
		return excludeParentalAdvisory;
	}

	void setExcludeParentalAdvisory(boolean excludeParentalAdvisory) {
		this.excludeParentalAdvisory = excludeParentalAdvisory;
	}

	public boolean isExcludeProfanity() {
		return excludeProfanity;
	}

	void setExcludeProfanity(boolean excludeProfanity) {
		this.excludeProfanity = excludeProfanity;
	}

	public void setExcludeTitlesWithCopiesFromOtherVendors(int excludeTitlesWithCopiesFromOtherVendors) {
		this.excludeTitlesWithCopiesFromOtherVendors = excludeTitlesWithCopiesFromOtherVendors;
	}

	public int getExcludeTitlesWithCopiesFromOtherVendors() {
		return excludeTitlesWithCopiesFromOtherVendors;
	}

	private String lastIdentifier = null;
	private boolean lastIdentifierResult = false;
	public boolean isOkToAdd(String identifier, String kind, float price, boolean abridged, boolean pa, boolean profanity, boolean children, String rating, Logger logger) {
		if (lastIdentifier != null && lastIdentifier.equals(identifier)){
			return lastIdentifierResult;
		}
		//Filter by kind and price
		boolean okToAdd = true;
		switch (kind) {
			case "EBOOK":
				okToAdd = (includeEBooks && price <= maxCostPerCheckoutEBooks);
				break;
			case "AUDIOBOOK":
				okToAdd = (includeEAudiobook && price <= maxCostPerCheckoutEAudiobook);
				break;
			case "COMIC":
				okToAdd = (includeEComics && price <= maxCostPerCheckoutEComics);
				break;
			case "MOVIE":
				okToAdd = (includeMovies && price <= maxCostPerCheckoutMovies);
				break;
			case "TELEVISION":
				okToAdd = (includeTelevision && price <= maxCostPerCheckoutTelevision);
				break;
			case "MUSIC":
				okToAdd = (includeMusic && price <= maxCostPerCheckoutMusic);
				break;
			case "BINGEPASS":
				okToAdd = (includeBingePass && price <= maxCostPerCheckoutBingePass);
				break;
			default:
				logger.error("Unknown kind " + kind);
		}
		if (okToAdd && excludeAbridged && abridged) {
			okToAdd = false;
		}
		if (okToAdd && excludeParentalAdvisory && pa) {
			okToAdd = false;
		}
		if (okToAdd && excludeProfanity && profanity) {
			okToAdd = false;
		}
		if (okToAdd &&restrictToChildrensMaterial && !children) {
			okToAdd = false;
		}
		if (okToAdd && isRatingExcluded(rating)) {
			okToAdd = false;
		}
		lastIdentifier = identifier;
		lastIdentifierResult = okToAdd;
		return okToAdd;
	}
}
