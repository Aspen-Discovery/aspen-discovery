package org.marmot;

public class Util {
	

	public static String cleanIniValue(String value) {
		if (value == null) {
			return null;
		}
		value = value.trim();
		if (value.startsWith("\"")) {
			value = value.substring(1);
		}
		if (value.endsWith("\"")) {
			value = value.substring(0, value.length() - 1);
		}
		return value;
	}

	public static boolean compareStrings(String value1, String value2) {
		if (value1 == null){
			return value2 == null;
		}else{
			return value1.equals(value2);
		}
	}
}
