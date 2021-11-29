package com.turning_leaf_technologies.symphony;

public class CourseTitle {
	public Long id;
	public String groupedWorkPermanentId;
	public boolean stillExists = false;

	public CourseTitle(long id, String sourceId) {
		this.id = id;
		this.groupedWorkPermanentId = sourceId;
	}
}
