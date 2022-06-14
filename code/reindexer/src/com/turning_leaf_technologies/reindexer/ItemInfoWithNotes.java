package com.turning_leaf_technologies.reindexer;

public class ItemInfoWithNotes {
	public ItemInfo itemInfo;
	public StringBuilder notes;
	public ItemInfoWithNotes(ItemInfo itemInfo, StringBuilder notes){
		this.itemInfo = itemInfo;
		this.notes = notes;
	}
}
