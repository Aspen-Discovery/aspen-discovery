package com.turning_leaf_technologies.symphony;

public class CourseInfo {
	public Long id;
	public String courseLibrary;
	public String courseInstructor;
	public String courseNumber;
	public String courseTitle;
	public boolean stillExists = false;

	public CourseInfo(Long id, String courseLibrary, String courseInstructor, String courseNumber, String courseTitle){
		this.id = id;
		this.courseLibrary = courseLibrary;
		this.courseInstructor = courseInstructor;
		this.courseNumber = courseNumber;
		this.courseTitle = courseTitle;
	}

	String thisAsString = null;
	public String toString(){
		if (thisAsString == null) {
			thisAsString = courseLibrary + "-" + courseInstructor + "-" + courseNumber + "-" + courseTitle;
		}
		return thisAsString;
	}
}
