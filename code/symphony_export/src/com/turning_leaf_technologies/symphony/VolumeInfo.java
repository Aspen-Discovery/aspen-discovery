package com.turning_leaf_technologies.symphony;

import com.turning_leaf_technologies.strings.StringUtils;

import javax.print.DocFlavor;
import java.util.ArrayList;

public class VolumeInfo {
	public String bibNumber;
	public String volume;
	public ArrayList<String> relatedItems = new ArrayList<>();

	public String getRelatedItemsAsString() {
		return String.join("|", relatedItems);
	}
}
