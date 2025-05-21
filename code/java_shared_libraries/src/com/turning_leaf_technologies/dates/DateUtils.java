package com.turning_leaf_technologies.dates;

import com.turning_leaf_technologies.strings.AspenStringUtils;

import java.text.SimpleDateFormat;
import java.util.Calendar;
import java.util.Date;
import java.util.LinkedHashSet;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

public class DateUtils {
	public static Long getDaysSinceAddedForDate(Date curDate) {
		if (curDate == null) {
			return null;
		}
		return ((new Date().getTime() - curDate.getTime()) / (long) (1000 * 60 * 60 * 24));
	}

	public static LinkedHashSet<String> getTimeSinceAddedForDate(Date curDate) {
		if (curDate == null) {
			return null;
		}
		long timeDifferenceDays = (new Date().getTime() - curDate.getTime()) / (long) (1000 * 60 * 60 * 24);
		return getTimeSinceAdded(timeDifferenceDays);
	}

	static LinkedHashSet<String> timeSinceAddedOnOrder = new LinkedHashSet<>();
	static LinkedHashSet<String> timeSinceAddedUnderConsideration = new LinkedHashSet<>();
	static LinkedHashSet<String> timeSinceAddedDay = new LinkedHashSet<>();
	static LinkedHashSet<String> timeSinceAddedWeek = new LinkedHashSet<>();
	static LinkedHashSet<String> timeSinceAddedMonth = new LinkedHashSet<>();
	static LinkedHashSet<String> timeSinceAdded2Months = new LinkedHashSet<>();
	static LinkedHashSet<String> timeSinceAddedQuarter = new LinkedHashSet<>();
	static LinkedHashSet<String> timeSinceAddedSixMonths = new LinkedHashSet<>();
	static LinkedHashSet<String> timeSinceAddedYear = new LinkedHashSet<>();
	static LinkedHashSet<String> timeSinceAddedNone = new LinkedHashSet<>();
	static LinkedHashSet<String> timeSinceAddedInProcess = new LinkedHashSet<>();
	static {
		timeSinceAddedOnOrder.add("On Order");
		timeSinceAddedInProcess.add("In Processing");
		timeSinceAddedUnderConsideration.add("Under Consideration");
		timeSinceAddedYear.add("Year");
		timeSinceAddedSixMonths.add("Six Months");
		timeSinceAddedSixMonths.addAll(timeSinceAddedYear);
		timeSinceAddedQuarter.add("Quarter");
		timeSinceAddedQuarter.addAll(timeSinceAddedSixMonths);
		timeSinceAdded2Months.add("2 Months");
		timeSinceAdded2Months.addAll(timeSinceAddedQuarter);
		timeSinceAddedMonth.add("Month");
		timeSinceAddedMonth.addAll(timeSinceAdded2Months);
		timeSinceAddedWeek.add("Week");
		timeSinceAddedWeek.addAll(timeSinceAddedMonth);
		timeSinceAddedDay.add("Day");
		timeSinceAddedDay.addAll(timeSinceAddedWeek);
	}
	public static LinkedHashSet<String> getTimeSinceAdded(long timeDifferenceDays) {
		// System.out.println("Time Difference Days: " + timeDifferenceDays);
		if (timeDifferenceDays == Integer.MAX_VALUE) {
			return timeSinceAddedUnderConsideration;
		} else if (timeDifferenceDays == -2) {
			return timeSinceAddedInProcess;
		} else if (timeDifferenceDays < 0) {
			return timeSinceAddedOnOrder;
		} else {
			if (timeDifferenceDays <= 1) {
				return timeSinceAddedDay;
			} else if (timeDifferenceDays <= 7) {
				return timeSinceAddedWeek;
			} else if (timeDifferenceDays <= 30) {
				return timeSinceAddedMonth;
			} else if (timeDifferenceDays <= 60) {
				return timeSinceAdded2Months;
			} else if (timeDifferenceDays <= 90) {
				return timeSinceAddedQuarter;
			} else if (timeDifferenceDays <= 180) {
				return timeSinceAddedSixMonths;
			} else if (timeDifferenceDays <= 365) {
				return timeSinceAddedYear;
			} else {
				return timeSinceAddedNone;
			}
		}
	}

	private final static Pattern FOUR_DIGIT_PATTERN_BRACES = Pattern.compile("\\[[12]\\d{3}]");
	private final static Pattern FOUR_DIGIT_PATTERN_ONE_BRACE = Pattern.compile("\\[[12]\\d{3}");
	private final static Pattern FOUR_DIGIT_PATTERN_STARTING_WITH_1_2 = Pattern.compile("(20|19|18|17|16|15)[0-9][0-9]");
	private final static Pattern FOUR_DIGIT_PATTERN_OTHER_1 = Pattern.compile("l\\d{3}");
	private final static Pattern FOUR_DIGIT_PATTERN_OTHER_2 = Pattern.compile("\\[19]\\d{2}");
	private final static Pattern FOUR_DIGIT_PATTERN_OTHER_3 = Pattern.compile("(20|19|18|17|16|15)[0-9][-?0-9]");
	private final static Pattern FOUR_DIGIT_PATTERN_OTHER_4 = Pattern.compile("i.e. (20|19|18|17|16|15)[0-9][0-9]");
	private final static Pattern BC_DATE_PATTERN = Pattern.compile("[0-9]+ [Bb][.]?[Cc][.]?");

	/**
	 * Cleans non-digits from a String
	 *
	 * @param date String to parse
	 * @return Numeric part of date String (or null)
	 */
	public static String cleanDate(final String date) {
		if (date == null || date.isEmpty()) {
			return null;
		}
		Matcher matcher_braces = FOUR_DIGIT_PATTERN_BRACES.matcher(date);

		String cleanDate = null; // raises DD-anomaly

		if (matcher_braces.find()) {
			cleanDate = matcher_braces.group();
			cleanDate = AspenStringUtils.removeOuterBrackets(cleanDate);
		} else {
			Matcher matcher_ie_date = FOUR_DIGIT_PATTERN_OTHER_4.matcher(date);
			if (matcher_ie_date.find()) {
				cleanDate = matcher_ie_date.group().replaceAll("i.e. ", "");
			} else {
				Matcher matcher_one_brace = FOUR_DIGIT_PATTERN_ONE_BRACE.matcher(date);
				if (matcher_one_brace.find()) {
					cleanDate = matcher_one_brace.group();
					cleanDate = AspenStringUtils.removeOuterBrackets(cleanDate);
				} else {
					Matcher matcher_bc_date = BC_DATE_PATTERN.matcher(date);
					if (matcher_bc_date.find()) {
						//cleanDate = null;
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
									cleanDate = matcher_bracket_19_plus_two_digits.group().replaceAll("\\[", "").replaceAll("]", "");
								} else {
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
