package com.turning_leaf_technologies.indexing;

import com.turning_leaf_technologies.strings.AspenStringUtils;

import javax.print.DocFlavor;
import java.util.ArrayList;

public class VolumeInfo {
	public String bibNumber;
	public String volume;
	public String volumeIdentifier;
	public int displayOrder;
	public ArrayList<String> relatedItems = new ArrayList<>();

	public String getRelatedItemsAsString() {
		return String.join("|", relatedItems);
	}
}
