package org.marmot;

import java.sql.ResultSet;
import java.sql.SQLException;

public class Marriage {
	public String marriageId;
    public String personId;
    public String spouseName;
    public String spouseId;
    public String marriageDateDay;
    public String marriageDateMonth;
    public String marriageDateYear;
    public String comments;
    
    public Marriage(ResultSet rs, boolean loadId){
    	try {
			if (loadId){
				marriageId = rs.getString("marriageId");
			}
			personId = rs.getString("personId");
	    	if (rs.wasNull()) personId = null;
	    	spouseName = rs.getString("spouseName");
	    	if (rs.wasNull()) spouseName = null;
	    	spouseId = rs.getString("spouseId");
	    	if (rs.wasNull()) spouseId = null;
	    	
	    	marriageDateDay = rs.getString("marriageDateDay");
	    	if (rs.wasNull()) marriageDateDay = null;
	    	marriageDateMonth = rs.getString("marriageDateMonth");
	    	if (rs.wasNull()) marriageDateMonth = null;
	    	marriageDateYear = rs.getString("marriageDateYear");
	    	if (rs.wasNull()) marriageDateYear = null;
	    	
	    	comments = rs.getString("comments");
	    	if (rs.wasNull()) comments = null;
		} catch (SQLException e) {
			System.err.println("Error loading marriage " + e.toString());
		}
    }
}
