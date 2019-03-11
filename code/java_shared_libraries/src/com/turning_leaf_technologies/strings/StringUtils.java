package com.turning_leaf_technologies.strings;

import java.io.*;
import java.nio.charset.StandardCharsets;

public class StringUtils {
    public static String trimTo(int maxCharacters, String stringToTrim) {
        if (stringToTrim == null) {
            return null;
        }
        if (stringToTrim.length() > maxCharacters) {
            stringToTrim = stringToTrim.substring(0, maxCharacters);
        }
        return stringToTrim.trim();
    }

    public static boolean compareStrings(String curLine1, String curLine2) {
        return curLine1 == null && curLine2 == null || !(curLine1 == null || curLine2 == null) && curLine1.equals(curLine2);
    }

    public static String convertStreamToString(InputStream is) throws IOException {
        /*
         * To convert the InputStream to String we use the Reader.read(char[]
         * buffer) method. We iterate until the Reader return -1 which means there's
         * no more data to read. We use the StringWriter class to produce the
         * string.
         */
        if (is != null) {
            Writer writer = new StringWriter();

            char[] buffer = new char[1024];
            try {
                Reader reader = new BufferedReader(new InputStreamReader(is, StandardCharsets.UTF_8));
                int n;
                while ((n = reader.read(buffer)) != -1) {
                    writer.write(buffer, 0, n);
                }
            } finally {
                is.close();
            }
            return writer.toString();
        } else {
            return "";
        }
    }

    public static char convertStringToChar(String subfieldString) {
        char subfield = ' ';
        if (subfieldString.length() > 0)  {
            subfield = subfieldString.charAt(0);
        }
        return subfield;
    }
}
