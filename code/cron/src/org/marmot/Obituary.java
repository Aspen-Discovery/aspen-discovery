package org.marmot;

import java.sql.ResultSet;
import java.sql.SQLException;

public class Obituary {
	public String obituaryId;
    public String personId;
    public String source;
    public String dateDay;
    public String dateMonth;
    public String dateYear;
    public String sourcePage;
    public String contents;
    public String picture;
    
    public Obituary(ResultSet rs, boolean loadId){
    	try {
			if (loadId){
				obituaryId = rs.getString("obituaryId");
			}
			personId = rs.getString("personId");
	    	if (rs.wasNull()) personId = null;
	    	source = rs.getString("source");
	    	if (rs.wasNull()) source = null;
	    	sourcePage = rs.getString("sourcePage");
	    	if (rs.wasNull()) sourcePage = null;
	    	
	    	dateDay = rs.getString("dateDay");
	    	if (rs.wasNull()) dateDay = null;
	    	dateMonth = rs.getString("dateMonth");
	    	if (rs.wasNull()) dateMonth = null;
	    	dateYear = rs.getString("dateYear");
	    	if (rs.wasNull()) dateYear = null;
	    	
	    	picture = rs.getString("picture");
	    	if (rs.wasNull()) picture = null;
	    	contents = rs.getString("contents");
	    	if (rs.wasNull()) contents = null;
		} catch (SQLException e) {
			System.err.println("Error loading obituary " + e.toString());
		}
    }
}
