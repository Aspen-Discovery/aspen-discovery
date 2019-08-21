package com.turning_leaf_technologies.dates;

import com.turning_leaf_technologies.strings.StringUtils;

import java.text.SimpleDateFormat;
import java.util.Calendar;
import java.util.Date;
import java.util.LinkedHashSet;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

public class DateUtils {
    public static Integer getDaysSinceAddedForDate(Date curDate){
        if (curDate == null){
            return null;
        }
        return (int)((indexDate.getTime() - curDate.getTime()) / (1000 * 60 * 60 * 24));
    }
    private static Date indexDate = new Date();
    public static Date getIndexDate(){
        return indexDate;
    }
    public static LinkedHashSet<String> getTimeSinceAddedForDate(Date curDate) {
        if (curDate == null) {
            return null;
        }
        long timeDifferenceDays = (indexDate.getTime() - curDate.getTime())
                / (1000 * 60 * 60 * 24);
        return getTimeSinceAdded(timeDifferenceDays);
    }
    public static LinkedHashSet<String> getTimeSinceAdded(long timeDifferenceDays){
        // System.out.println("Time Difference Days: " + timeDifferenceDays);
        LinkedHashSet<String> result = new LinkedHashSet<>();
        if (timeDifferenceDays < 0) {
            result.add("On Order");
        }
        if (timeDifferenceDays <= 1) {
            result.add("Day");
        }
        if (timeDifferenceDays <= 7) {
            result.add("Week");
        }
        if (timeDifferenceDays <= 30) {
            result.add("Month");
        }
        if (timeDifferenceDays <= 60) {
            result.add("2 Months");
        }
        if (timeDifferenceDays <= 90) {
            result.add("Quarter");
        }
        if (timeDifferenceDays <= 180) {
            result.add("Six Months");
        }
        if (timeDifferenceDays <= 365) {
            result.add("Year");
        }
        return result;
    }

    private final static Pattern FOUR_DIGIT_PATTERN_BRACES							= Pattern.compile("\\[[12]\\d{3}\\]");
    private final static Pattern				FOUR_DIGIT_PATTERN_ONE_BRACE					= Pattern.compile("\\[[12]\\d{3}");
    private final static Pattern				FOUR_DIGIT_PATTERN_STARTING_WITH_1_2	= Pattern.compile("(20|19|18|17|16|15)[0-9][0-9]");
    private final static Pattern				FOUR_DIGIT_PATTERN_OTHER_1						= Pattern.compile("l\\d{3}");
    private final static Pattern				FOUR_DIGIT_PATTERN_OTHER_2						= Pattern.compile("\\[19\\]\\d{2}");
    private final static Pattern				FOUR_DIGIT_PATTERN_OTHER_3						= Pattern.compile("(20|19|18|17|16|15)[0-9][-?0-9]");
    private final static Pattern				FOUR_DIGIT_PATTERN_OTHER_4						= Pattern.compile("i.e. (20|19|18|17|16|15)[0-9][0-9]");
    private final static Pattern				BC_DATE_PATTERN												= Pattern.compile("[0-9]+ [Bb][.]?[Cc][.]?");

    /**
     * Cleans non-digits from a String
     *
     * @param date
     *          String to parse
     * @return Numeric part of date String (or null)
     */
    public static String cleanDate(final String date) {
        if (date == null || date.length() == 0){
            return null;
        }
        Matcher matcher_braces = FOUR_DIGIT_PATTERN_BRACES.matcher(date);

        String cleanDate = null; // raises DD-anomaly

        if (matcher_braces.find()) {
            cleanDate = matcher_braces.group();
            cleanDate = StringUtils.removeOuterBrackets(cleanDate);
        } else{
            Matcher matcher_ie_date = FOUR_DIGIT_PATTERN_OTHER_4.matcher(date);
            if (matcher_ie_date.find()) {
                cleanDate = matcher_ie_date.group().replaceAll("i.e. ", "");
            } else {
                Matcher matcher_one_brace = FOUR_DIGIT_PATTERN_ONE_BRACE.matcher(date);
                if (matcher_one_brace.find()) {
                    cleanDate = matcher_one_brace.group();
                    cleanDate = StringUtils.removeOuterBrackets(cleanDate);
                } else {
                    Matcher matcher_bc_date = BC_DATE_PATTERN.matcher(date);
                    if (matcher_bc_date.find()) {
                        cleanDate = null;
                    } else {
                        Matcher matcher_start_with_1_2 = FOUR_DIGIT_PATTERN_STARTING_WITH_1_2.matcher(date);
                        if (matcher_start_with_1_2.find()) {
                            cleanDate = matcher_start_with_1_2.group();
                        } else {
                            Matcher matcher_l_plus_three_digits = FOUR_DIGIT_PATTERN_OTHER_1.matcher(date);
                            if (matcher_l_plus_three_digits.find()) {
                                cleanDate = matcher_l_plus_three_digits.group().replaceAll("l", "1");
                            } else {
                                Matcher matcher_bracket_19_plus_two_digits = FOUR_DIGIT_PATTERN_OTHER_2.matcher(date);
                                if (matcher_bracket_19_plus_two_digits.find()) {
                                    cleanDate = matcher_bracket_19_plus_two_digits.group().replaceAll("\\[", "").replaceAll("\\]", "");
                                } else{
                                    Matcher matcher_three_digits_plus_unk = FOUR_DIGIT_PATTERN_OTHER_3.matcher(date);
                                    if (matcher_three_digits_plus_unk.find()) {
                                        cleanDate = matcher_three_digits_plus_unk.group().replaceAll("[-?]", "0");
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        if (cleanDate != null) {
            Calendar calendar = Calendar.getInstance();
            SimpleDateFormat dateFormat = new SimpleDateFormat("yyyy");
            String thisYear = dateFormat.format(calendar.getTime());
            try {
                if (Integer.parseInt(cleanDate) > Integer.parseInt(thisYear) + 1) cleanDate = null;
            } catch (NumberFormatException nfe) {
                cleanDate = null;
            }
        }
        return cleanDate;
    }
}
