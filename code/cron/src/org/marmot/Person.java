package org.marmot;

import java.sql.Connection;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.sql.Statement;
import java.util.ArrayList;

public class Person {
	public String personId;
    public String firstName;
    public String middleName;
    public String lastName;
    public String maidenName;
    public String otherName;
    public String nickName;
    public String birthDateDay;
    public String birthDateMonth;
    public String birthDateYear;
    public String deathDateDay;
    public String deathDateMonth;
    public String deathDateYear;
    public String ageAtDeath;
    public String cemeteryName;
    public String cemeteryLocation;
    public String mortuaryName;
    public String picture;
    public String comments;
    
    private ArrayList<Obituary> obituaries = null;
    private ArrayList<Marriage> marriages = null;
    
    public Person(ResultSet rs, boolean loadId){
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
    public String createMatchingQuery(){
    	StringBuffer matchingQuery = new StringBuffer();
    	matchingQuery.append("SELECT * FROM person where ");
    	if (firstName == null) {matchingQuery.append("firstName IS NULL"); }else{ matchingQuery.append("firstName = '" + firstName.replaceAll("'", "''") + "' ");}
    	if (lastName == null) {matchingQuery.append(" AND lastName IS NULL"); }else{ matchingQuery.append(" AND lastName = '" + lastName.replaceAll("'", "''") + "' ");}
    	if (middleName == null) {matchingQuery.append(" AND middleName IS NULL"); }else{ matchingQuery.append(" AND middleName = '" + middleName.replaceAll("'", "''") + "' ");}
    	if (maidenName == null) {matchingQuery.append(" AND maidenName IS NULL"); }else{ matchingQuery.append(" AND maidenName = '" + maidenName.replaceAll("'", "''") + "' ");}
    	if (otherName == null) {matchingQuery.append(" AND otherName IS NULL"); }else{ matchingQuery.append(" AND otherName = '" + otherName.replaceAll("'", "''") + "' ");}
    	if (nickName == null) {matchingQuery.append(" AND nickName IS NULL"); }else{ matchingQuery.append(" AND nickName = '" + nickName.replaceAll("'", "''") + "' ");}
    	
    	if (birthDateDay == null) {matchingQuery.append(" AND birthDateDay IS NULL"); }else{ matchingQuery.append(" AND birthDateDay = " + birthDateDay + " ");}
    	if (birthDateMonth == null) {matchingQuery.append(" AND birthDateMonth IS NULL"); }else{ matchingQuery.append(" AND birthDateMonth = " + birthDateMonth + " ");}
    	if (birthDateYear == null) {matchingQuery.append(" AND birthDateYear IS NULL"); }else{ matchingQuery.append(" AND birthDateYear = " + birthDateYear + " ");}
    	
    	if (deathDateDay == null) {matchingQuery.append(" AND deathDateDay IS NULL"); }else{ matchingQuery.append(" AND deathDateDay = " + deathDateDay + " ");}
    	if (deathDateMonth == null) {matchingQuery.append(" AND deathDateMonth IS NULL"); }else{ matchingQuery.append(" AND deathDateMonth = " + deathDateMonth + " ");}
    	if (deathDateYear == null) {matchingQuery.append(" AND deathDateYear IS NULL"); }else{ matchingQuery.append(" AND deathDateYear = " + deathDateYear + " ");}

    	if (ageAtDeath == null) {matchingQuery.append(" AND ageAtDeath IS NULL"); }else{ matchingQuery.append(" AND ageAtDeath = '" + ageAtDeath + "' ");}
    	if (cemeteryName == null) {matchingQuery.append(" AND cemeteryName IS NULL"); }else{ matchingQuery.append(" AND cemeteryName = '" + cemeteryName.replaceAll("'", "''") + "' ");}
    	if (cemeteryLocation == null) {matchingQuery.append(" AND cemeteryLocation IS NULL"); }else{ matchingQuery.append(" AND cemeteryLocation = '" + cemeteryLocation.replaceAll("'", "''") + "' ");}
    	if (mortuaryName == null) {matchingQuery.append(" AND mortuaryName IS NULL"); }else{ matchingQuery.append(" AND mortuaryName = '" + mortuaryName.replaceAll("'", "''") + "' ");}
    	    	
    	return matchingQuery.toString();
    }
    public ArrayList<Obituary> getObituaries(Connection conn){
    	if (obituaries == null){
    		obituaries = new ArrayList<Obituary>();
    		try {
				Statement stmt2 = conn.createStatement(ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				ResultSet obitRs = stmt2.executeQuery("SELECT * from obituary WHERE personId = " + personId);
				while (obitRs.next()){
					Obituary curObit = new Obituary(obitRs, true);
					obituaries.add(curObit);
				}
			} catch (SQLException e) {
				System.out.println("Error loading obituaries for person " + personId + " " + e.toString());
			}
    	}
    	return obituaries;
    }
    public ArrayList<Marriage> getMarriages(Connection conn){
    	if (marriages == null){
    		marriages = new ArrayList<Marriage>();
    		try {
				Statement stmt2 = conn.createStatement(ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				ResultSet marriageRs = stmt2.executeQuery("SELECT * from marriage WHERE personId = " + personId);
				while (marriageRs.next()){
					Marriage curMarriage = new Marriage(marriageRs, true);
					marriages.add(curMarriage);
				}
			} catch (SQLException e) {
				System.out.println("Error loading mariages for person " + personId + " " + e.toString());
			}
    	}
    	return marriages;
    }
    
    public boolean isBetterRecord(Person person2, Connection conn){
    	//Check to see which person is the better of the 2 records
    	//since hey may not be exact duplicates of the other. 
    	int thisBetterFactors = 0;
    	int comment1Length = (this.comments == null || this.comments.length() == 0) ? 0 : this.comments.length();
    	int comment2Length = (person2.comments == null || person2.comments.length() == 0) ? 0 : person2.comments.length();
    	if (comment1Length != comment2Length) thisBetterFactors += (comment1Length > comment2Length) ? 1 : -1;
    	int picture1Length = (this.picture == null || this.picture.length() == 0) ? 0 : this.picture.length();
    	int picture2Length = (person2.picture == null || person2.picture.length() == 0) ? 0 : person2.picture.length();
    	if (picture1Length != picture2Length) thisBetterFactors += (picture1Length > picture2Length) ? 1 : -1;
    	ArrayList<Marriage> marriages1 = getMarriages(conn);
    	ArrayList<Marriage> marriages2 = person2.getMarriages(conn);
    	if (marriages1.size() != marriages2.size()){
    		//Check the marraiges to see which should be used
    		if (marriages1.size() != marriages2.size()) thisBetterFactors += (marriages1.size() > marriages2.size()) ? 1 : -1;
    	}
    	ArrayList<Obituary> obits1 = getObituaries(conn);
    	ArrayList<Obituary> obits2 = person2.getObituaries(conn);
    	if (obits1.size() != obits2.size()){
    		//Check the marraiges to see which should be used
    		if (obits1.size() != obits2.size()) thisBetterFactors += (obits1.size() > obits2.size()) ? 1 : -1;
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
