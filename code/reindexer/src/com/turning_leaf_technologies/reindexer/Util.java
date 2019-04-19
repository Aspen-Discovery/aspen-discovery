package com.turning_leaf_technologies.reindexer;

import java.io.*;
import java.nio.ByteBuffer;
import java.nio.channels.FileChannel;
import java.util.*;

public class Util {
	static byte[] readFileBytes(String filename) throws IOException {
		FileInputStream f = new FileInputStream( filename );
		FileChannel fileChannel = f.getChannel();
		long fileSize = fileChannel.size();
		byte[] fileBytes = new byte[(int)fileSize];
		ByteBuffer buffer = ByteBuffer.wrap(fileBytes);
		fileChannel.read(buffer);
		fileChannel.close();
		f.close();
		return fileBytes;
	}

	static String getCRSeparatedString(Object values) {
		StringBuilder crSeparatedString = new StringBuilder();
		if (values instanceof String){
			crSeparatedString.append((String)values);
		}else if (values instanceof Iterable){
			@SuppressWarnings("unchecked")
			Iterable<String> valuesIterable = (Iterable<String>)values;
			for (String curValue : valuesIterable) {
				if (crSeparatedString.length() > 0) {
					crSeparatedString.append("\r\n");
				}
				crSeparatedString.append(curValue);
			}
		}
		return crSeparatedString.toString();
	}
	
	static String getCRSeparatedStringFromSet(Set<String> values) {
		if (values.size() == 0){
			return "";
		}else if (values.size() == 1){
			return values.iterator().next();
		}
		StringBuilder crSeparatedString = new StringBuilder();
		for (String curValue : values) {
			if (crSeparatedString.length() > 0) {
				crSeparatedString.append("\r\n");
			}
			crSeparatedString.append(curValue);
		}
		return crSeparatedString.toString();
	}

	static String getCRSeparatedString(HashSet<String> values) {
		if (values.size() == 0){
			return "";
		}else if (values.size() == 1){
			return values.iterator().next();
		}
		StringBuilder crSeparatedString = new StringBuilder();
		for (String curValue : values) {
			if (crSeparatedString.length() > 0) {
				crSeparatedString.append("\r\n");
			}
			crSeparatedString.append(curValue);
		}
		return crSeparatedString.toString();
	}

	static String getCsvSeparatedString(Set<String> values) {
		if (values.size() == 0){
			return "";
		}else if (values.size() == 1){
			return values.iterator().next();
		}
		StringBuilder crSeparatedString = new StringBuilder();
		for (String curValue : values) {
			if (crSeparatedString.length() > 0) {
				crSeparatedString.append(",");
			}
			crSeparatedString.append(curValue);
		}
		return crSeparatedString.toString();
	}

	static String getCsvSeparatedStringFromLongs(Set<Long> values) {
		StringBuilder crSeparatedString = new StringBuilder();
		for (Long curValue : values) {
			if (crSeparatedString.length() > 0) {
				crSeparatedString.append(",");
			}
			crSeparatedString.append(curValue.toString());
		}
		return crSeparatedString.toString();
	}

	static boolean isNumeric(String stringToTest) {
		if (stringToTest == null){
			return false;
		}
		if (stringToTest.length() == 0){
			return false;
		}
		int numDecimals = 0;
		for (char curChar : stringToTest.toCharArray()){
			if (!Character.isDigit(curChar) && curChar != '.'){
				return false;
			}if (curChar == '.'){
				numDecimals++;
			}
		}
		return numDecimals <= 1;
	}

	static String getCleanDetailValue(String value) {
		return value == null ? "" : value;
	}

	static String convertISBN10to13(String isbn10) {
		if (isbn10.length() != 10){
			return null;
		}
		String isbn = "978" + isbn10.substring(0, 9);
		//Calculate the 13 digit checksum
		int sumOfDigits = 0;
		for (int i = 0; i < 12; i++){
			int multiplier = 1;
			if (i % 2 == 1){
				multiplier = 3;
			}
			int curDigit = Integer.parseInt(Character.toString(isbn.charAt(i)));
			sumOfDigits += multiplier * curDigit;
		}
		int modValue = sumOfDigits % 10;
		int checksumDigit;
		if (modValue == 0){
			checksumDigit = 0;
		}else{
			checksumDigit = 10 - modValue;
		}
		return  isbn + checksumDigit;
	}

	static void addToMapWithCount(HashMap<String, Integer> map, String elementToAdd){
		if (map.containsKey(elementToAdd)){
			map.put(elementToAdd, map.get(elementToAdd) + 1);
		}else{
			map.put(elementToAdd, 1);
		}
	}
}
