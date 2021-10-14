package com.turning_leaf_technologies.cron.genealogy;

import java.text.ParseException;
import java.text.SimpleDateFormat;
import java.util.Calendar;
import java.util.Date;

/**
 * Parses a date and returns information about it.
 * This is NOT currently Thread Safe
 */
class DateInfo {
	private String originalDate;
	private Calendar parsedDate = null;
	private boolean daySet = false;
	private boolean monthSet = false;
	private boolean yearSet = false;
	private boolean notSet = true;
	
	private static SimpleDateFormat format1 = new SimpleDateFormat("dd MMM yyyy"); //24 JUN 1895 used for marriage date birth date and death date
	private static SimpleDateFormat format2 = new SimpleDateFormat("yyyy");
	private static SimpleDateFormat format3 = new SimpleDateFormat("MMM yyyy");
	private static SimpleDateFormat format4 = new SimpleDateFormat("MM/dd/yyyy H:mm:ss");
	private static SimpleDateFormat format5 = new SimpleDateFormat("M/d/yyyy H:mm:ss");
	private static SimpleDateFormat format6 = new SimpleDateFormat("M/d/yyyy");
	private static SimpleDateFormat solrFormat = new SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss'Z'");
	
	DateInfo(int day, int month, int year){
		if (day != 0 || month != 0 || year != 0) {
			parsedDate = Calendar.getInstance();
			if (day > 0){
				daySet = true;
				parsedDate.set(Calendar.DATE, day);
			}
			if (month > 0){
				monthSet = true;
				parsedDate.set(Calendar.MONTH, month);
			}
			if (year > 0){
				yearSet = true;
				parsedDate.set(Calendar.YEAR, year);
			}
			notSet = false;
		}
	}
	DateInfo(String originalDate){
		this.originalDate = originalDate;
		if (originalDate == null || originalDate.length() == 0 || originalDate.matches("NOT LISTED|NOT LSITED|NOT GIVEN|N/D")){
			return;
		}
		try {
			Date tmpDate = format1.parse(originalDate);
			if (tmpDate != null){
				parsedDate = Calendar.getInstance();
				parsedDate.setTime(tmpDate);
				daySet = true;
				monthSet = true;
				yearSet = true;
				notSet = false;
				return;
			}
		} catch (ParseException e) {
			//Ignore and check the next format.
		}
		
		try {
			Date tmpDate = format3.parse(originalDate);
			if (tmpDate != null){
				parsedDate = Calendar.getInstance();
				parsedDate.setTime(tmpDate);
				monthSet = true;
				yearSet = true;
				notSet = false;
				return;
			}
		} catch (ParseException e) {
			//Ignore and check the next format.
		}
		try {
			Date tmpDate = format4.parse(originalDate);
			if (tmpDate != null){
				parsedDate = Calendar.getInstance();
				parsedDate.setTime(tmpDate);
				daySet = true;
				monthSet = true;
				yearSet = true;
				notSet = false;
				return;
			}
		} catch (ParseException e) {
			//Ignore and check the next format.
		}
		try {
			Date tmpDate = format5.parse(originalDate);
			if (tmpDate != null){
				parsedDate = Calendar.getInstance();
				parsedDate.setTime(tmpDate);
				daySet = true;
				monthSet = true;
				yearSet = true;
				notSet = false;
				return;
			}
		} catch (ParseException e) {
			//Ignore and check the next format.
		}
		try {
			Date tmpDate = format6.parse(originalDate);
			if (tmpDate != null){
				parsedDate = Calendar.getInstance();
				parsedDate.setTime(tmpDate);
				daySet = true;
				monthSet = true;
				yearSet = true;
				notSet = false;
				return;
			}
		} catch (ParseException e) {
			//Ignore and check the next format.
		}
		try {
			Date tmpDate = format2.parse(originalDate);
			if (tmpDate != null){
				parsedDate = Calendar.getInstance();
				parsedDate.setTime(tmpDate);
				yearSet = true;
				notSet = false;
			}
		} catch (ParseException e) {
			//Ignore and check the next format.
		}
	}

	String getOriginalDate() {
		return originalDate;
	}

	boolean isNotSet() {
		return notSet;
	}
	int getDay(){
		if (daySet) {
			return parsedDate.get(Calendar.DATE);
		}else{
			return 0;
		}
	}
	int getMonth(){
		if (monthSet) {
			return parsedDate.get(Calendar.MONTH) + 1;
		}else{
			return 0;
		}
	}
	int getYear(){
		if (yearSet) {
			return parsedDate.get(Calendar.YEAR);
		}else{
			return 0;
		}
	}
	String getSolrDate(){
		return solrFormat.format(parsedDate.getTime());
	}
}
