package com.turning_leaf_technologies.symphony;

import java.util.HashMap;

public class CourseInfo {
	public Long id;
	public String courseLibrary;
	public String courseInstructor;
	public String courseNumber;
	public String courseTitle;
	public boolean stillExists = false;
	public HashMap<String, CourseTitle> existingWorks = new HashMap<>();
	public boolean isUpdated;
	public boolean isDeleted;

	public CourseInfo(Long id, String courseLibrary, String courseInstructor, String courseNumber, String courseTitle, boolean isDeleted){
		this.id = id;
		this.courseLibrary = courseLibrary;
		this.courseInstructor = courseInstructor;
		this.courseNumber = courseNumber;
		this.courseTitle = courseTitle;
		this.isDeleted = isDeleted;
	}

	String thisAsString = null;
	public String toString(){
		if (thisAsString == null) {
			thisAsString = courseLibrary + "-" + courseInstructor + "-" + courseNumber + "-" + courseTitle;
		}
		return thisAsString;
	}
}
