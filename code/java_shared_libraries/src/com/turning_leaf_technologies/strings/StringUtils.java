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

    public static String stripNonValidXMLCharacters(String in) {
        StringBuilder out = new StringBuilder(); // Used to hold the output.
        char current; // Used to reference the current character.

        if (in == null || ("".equals(in))) return ""; // vacancy test.
        for (int i = 0; i < in.length(); i++) {
            current = in.charAt(i); // NOTE: No IndexOutOfBoundsException caught here; it should not happen.
            if ((current == 0x9) ||
                    (current == 0xA) ||
                    (current == 0xD) ||
                    ((current >= 0x20) && (current <= 0xD7FF)) ||
                    ((current >= 0xE000) && (current <= 0xFFFD))) {
                out.append(current);
            }
        }
        return out.toString();
    }
}
