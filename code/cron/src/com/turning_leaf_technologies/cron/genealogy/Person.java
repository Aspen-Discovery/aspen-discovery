package com.turning_leaf_technologies.cron.genealogy;

import java.sql.Connection;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.sql.Statement;

public class Person {
	private String personId;
    private String firstName;
    private String middleName;
    private String lastName;
	private String maidenName;
	private String otherName;
	private String nickName;
	private String birthDateDay;
	private String birthDateMonth;
	private String birthDateYear;
	private String deathDateDay;
	private String deathDateMonth;
	private String deathDateYear;
	private String ageAtDeath;
	private String cemeteryName;
	private String cemeteryLocation;
	private String mortuaryName;
	private String picture;
	private String comments;
    
    private int numObituaries = -1;
    private int numMarriages = -1;
    
    Person(ResultSet rs, boolean loadId){
    	try {
			if (loadId){
				personId = rs.getString("personId");
			}
			firstName = rs.getString("firstName");
	    	if (rs.wasNull()) firstName = null;
	    	middleName = rs.getString("middleName");
	    	if (rs.wasNull()) middleName = null;
	    	lastName = rs.getString("lastName");
	    	if (rs.wasNull()) lastName = null;
	    	maidenName = rs.getString("maidenName");
	    	if (rs.wasNull()) maidenName = null;
	    	otherName = rs.getString("otherName");
	    	if (rs.wasNull()) otherName = null;
	    	nickName = rs.getString("nickName");
	    	if (rs.wasNull()) nickName = null;
	    	
	    	birthDateDay = rs.getString("birthDateDay");
	    	if (rs.wasNull()) birthDateDay = null;
	    	birthDateMonth = rs.getString("birthDateMonth");
	    	if (rs.wasNull()) birthDateMonth = null;
	    	birthDateYear = rs.getString("birthDateYear");
	    	if (rs.wasNull()) birthDateYear = null;
	    	
	    	deathDateDay = rs.getString("deathDateDay");
	    	if (rs.wasNull()) deathDateDay = null;
	    	deathDateMonth = rs.getString("deathDateMonth");
	    	if (rs.wasNull()) deathDateMonth = null;
	    	deathDateYear = rs.getString("deathDateYear");
	    	if (rs.wasNull()) deathDateYear = null;
	    	
	    	ageAtDeath = rs.getString("ageAtDeath");
	    	if (rs.wasNull()) ageAtDeath = null;
	    	cemeteryName = rs.getString("cemeteryName");
	    	if (rs.wasNull()) cemeteryName = null;
	    	cemeteryLocation = rs.getString("cemeteryLocation");
	    	if (rs.wasNull()) cemeteryLocation = null;
	    	mortuaryName = rs.getString("mortuaryName");
	    	if (rs.wasNull()) mortuaryName = null;
	    	picture = rs.getString("picture");
	    	if (rs.wasNull()) picture = null;
	    	comments = rs.getString("comments");
	    	if (rs.wasNull()) comments = null;
		} catch (SQLException e) {
			System.err.println("Error loading person " + e.toString());
		}
    }
    String createMatchingQuery(){
    	StringBuilder matchingQuery = new StringBuilder();
    	matchingQuery.append("SELECT * FROM person where ");
    	if (firstName == null) {matchingQuery.append("firstName IS NULL"); }else{ matchingQuery.append("firstName = '").append(firstName.replaceAll("'", "''")).append("' ");}
    	if (lastName == null) {matchingQuery.append(" AND lastName IS NULL"); }else{ matchingQuery.append(" AND lastName = '").append(lastName.replaceAll("'", "''")).append("' ");}
    	if (middleName == null) {matchingQuery.append(" AND middleName IS NULL"); }else{ matchingQuery.append(" AND middleName = '").append(middleName.replaceAll("'", "''")).append("' ");}
    	if (maidenName == null) {matchingQuery.append(" AND maidenName IS NULL"); }else{ matchingQuery.append(" AND maidenName = '").append(maidenName.replaceAll("'", "''")).append("' ");}
    	if (otherName == null) {matchingQuery.append(" AND otherName IS NULL"); }else{ matchingQuery.append(" AND otherName = '").append(otherName.replaceAll("'", "''")).append("' ");}
    	if (nickName == null) {matchingQuery.append(" AND nickName IS NULL"); }else{ matchingQuery.append(" AND nickName = '").append(nickName.replaceAll("'", "''")).append("' ");}
    	
    	if (birthDateDay == null) {matchingQuery.append(" AND birthDateDay IS NULL"); }else{ matchingQuery.append(" AND birthDateDay = ").append(birthDateDay).append(" ");}
    	if (birthDateMonth == null) {matchingQuery.append(" AND birthDateMonth IS NULL"); }else{ matchingQuery.append(" AND birthDateMonth = ").append(birthDateMonth).append(" ");}
    	if (birthDateYear == null) {matchingQuery.append(" AND birthDateYear IS NULL"); }else{ matchingQuery.append(" AND birthDateYear = ").append(birthDateYear).append(" ");}
    	
    	if (deathDateDay == null) {matchingQuery.append(" AND deathDateDay IS NULL"); }else{ matchingQuery.append(" AND deathDateDay = ").append(deathDateDay).append(" ");}
    	if (deathDateMonth == null) {matchingQuery.append(" AND deathDateMonth IS NULL"); }else{ matchingQuery.append(" AND deathDateMonth = ").append(deathDateMonth).append(" ");}
    	if (deathDateYear == null) {matchingQuery.append(" AND deathDateYear IS NULL"); }else{ matchingQuery.append(" AND deathDateYear = ").append(deathDateYear).append(" ");}

    	if (ageAtDeath == null) {matchingQuery.append(" AND ageAtDeath IS NULL"); }else{ matchingQuery.append(" AND ageAtDeath = '").append(ageAtDeath).append("' ");}
    	if (cemeteryName == null) {matchingQuery.append(" AND cemeteryName IS NULL"); }else{ matchingQuery.append(" AND cemeteryName = '").append(cemeteryName.replaceAll("'", "''")).append("' ");}
    	if (cemeteryLocation == null) {matchingQuery.append(" AND cemeteryLocation IS NULL"); }else{ matchingQuery.append(" AND cemeteryLocation = '").append(cemeteryLocation.replaceAll("'", "''")).append("' ");}
    	if (mortuaryName == null) {matchingQuery.append(" AND mortuaryName IS NULL"); }else{ matchingQuery.append(" AND mortuaryName = '").append(mortuaryName.replaceAll("'", "''")).append("' ");}
    	    	
    	return matchingQuery.toString();
    }
    private int getNumObituaries(Connection conn){
    	if (numObituaries == -1){
    		try {
				Statement stmt2 = conn.createStatement(ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				ResultSet obitRs = stmt2.executeQuery("SELECT count(*) from obituary WHERE personId = " + personId);
				if (obitRs.next()){
					numObituaries = obitRs.getInt(1);
				}
			} catch (SQLException e) {
				System.out.println("Error loading obituaries for person " + personId + " " + e.toString());
			}
    	}
    	return numObituaries;
    }
    private int getNumMarriages(Connection conn){
    	if (numMarriages == -1){
			try {
				Statement stmt2 = conn.createStatement(ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				ResultSet marriageRs = stmt2.executeQuery("SELECT count(*) from marriage WHERE personId = " + personId);
				if (marriageRs.next()){
					numMarriages = marriageRs.getInt(1);
				}
			} catch (SQLException e) {
				System.out.println("Error loading mariages for person " + personId + " " + e.toString());
			}
    	}
    	return numMarriages;
    }
    
    boolean isBetterRecord(Person person2, Connection conn){
    	//Check to see which person is the better of the 2 records
    	//since they may not be exact duplicates of the other.
    	int thisBetterFactors = 0;
    	int comment1Length = (this.comments == null || this.comments.length() == 0) ? 0 : this.comments.length();
    	int comment2Length = (person2.comments == null || person2.comments.length() == 0) ? 0 : person2.comments.length();
    	if (comment1Length != comment2Length) thisBetterFactors += (comment1Length > comment2Length) ? 1 : -1;
    	int picture1Length = (this.picture == null || this.picture.length() == 0) ? 0 : this.picture.length();
    	int picture2Length = (person2.picture == null || person2.picture.length() == 0) ? 0 : person2.picture.length();
    	if (picture1Length != picture2Length) thisBetterFactors += (picture1Length > picture2Length) ? 1 : -1;
    	int numMarriages1 = getNumMarriages(conn);
    	int numMarriages12 = person2.getNumMarriages(conn);
    	if (numMarriages1 != numMarriages12){
    		//Check the marraiges to see which should be used
    		thisBetterFactors += (numMarriages1 > numMarriages12) ? 1 : -1;
    	}
    	int numObits1 = getNumObituaries(conn);
    	int numObits2 = person2.getNumObituaries(conn);
    	if (numObits1 != numObits2){
    		//Check the marraiges to see which should be used
    		thisBetterFactors += (numObits1 > numObits2) ? 1 : -1;
    	}
    	
    	if (thisBetterFactors == 0){
    		System.out.println("No difference between person " + personId + " and " + person2.personId);
    		return false;
    	}else{
    		System.out.println("No person " + personId + " better than " + person2.personId + " by " + thisBetterFactors + " factors.");
    		return thisBetterFactors > 0;
    	}
    }
    public boolean delete(Connection conn){
    	try {
    		System.out.println("Deleting person " + personId);
			Statement deleteStatement = conn.createStatement();
			deleteStatement.execute("DELETE FROM obituary where personId = " + personId);
			deleteStatement.execute("DELETE FROM marriage where personId = " + personId);
			deleteStatement.execute("DELETE FROM person where personId = " + personId);
			return true;
		} catch (SQLException e) {
			System.out.println("Error deleting person " + e.toString());
			return false;
		}
    }
}
