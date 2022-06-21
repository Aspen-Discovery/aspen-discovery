package com.turning_leaf_technologies.reindexer;

public class ResultWithNotes {
	public boolean result;
	public StringBuilder notes;

	public ResultWithNotes(boolean result, StringBuilder notes){
		this.result = result;
		this.notes = notes;
	}
}
