package com.turning_leaf_technologies.events;

import org.apache.commons.lang.StringUtils;

import java.sql.ResultSet;
import java.sql.SQLException;
import java.time.Instant;
import java.time.LocalDateTime;
import java.time.ZoneId;
import java.time.ZoneOffset;
import java.time.format.DateTimeFormatter;
import java.time.format.DateTimeParseException;
import java.time.zone.ZoneRules;
import java.util.*;

class AspenEvent {
	private final long id;
	private final long eventId;
	private final int eventType;
	private final String startDate;
	private final String startTime;
	private final int length;
	private final String name;
	private final String description;
	private final String cover;
	private final long locationId;
	private final String locationCode;
	private final String sublocationName;
	private final String sublocationOverride;
	private final HashSet<String> libraries = new HashSet<>();
	private final long sublocationId;
	private final Boolean status;
	private final Boolean nonPublic;
	private final ArrayList<EventField> fields = new ArrayList<EventField>();

	AspenEvent(ResultSet existingEventsRS) throws SQLException{
		this.id = existingEventsRS.getLong("id"); // The event instance ID
		this.eventId = existingEventsRS.getLong("eventId"); // The parent event ID
		this.eventType = existingEventsRS.getInt("eventTypeId");
		this.startDate = existingEventsRS.getString("date");
		this.startTime = existingEventsRS.getString("time");
		this.length = existingEventsRS.getInt("length");
		this.name = existingEventsRS.getString("title");
		this.description = existingEventsRS.getString("description");
		this.cover = existingEventsRS.getString("cover");
		this.locationId = existingEventsRS.getLong("locationId");
		this.locationCode = existingEventsRS.getString("displayName");
		this.sublocationName = existingEventsRS.getString("sublocationName");
		this.sublocationOverride = existingEventsRS.getString("sublocationOverride");
		this.sublocationId = existingEventsRS.getLong("sublocationId");
		this.status = existingEventsRS.getBoolean("status");
		this.nonPublic = existingEventsRS.getBoolean("private");
	}

	void addField(String name, String value, String[] allowableValues, int type, int facet) {
		this.fields.add(new EventField(name, value, allowableValues, type, facet));
	}

	void addLibrary(String library) {
		libraries.add(library);
	}

	HashSet<String> getLibraries() {
		return this.libraries;
	}

	ArrayList<EventField> getFields() {
		return fields;
	}

	long getId() {
		return id;
	}

	long getParentEventId() { return eventId; }

	int getEventType() { return eventType; }

	public String getStartDate() {
		return startDate;
	}

	public String getStartTime() {
		return startTime;
	}

	public int getLength() {
		return length;
	}

	public String getName() {
		return name;
	}

	public String getDescription() {
		return description;
	}

	public String getCover() {
		return cover;
	}

	public String getCoverUrl(String coverPath) {
		return coverPath + "/aspenEvents/" + cover;
	}

	public long getLocationId() {
		return locationId;
	}

	public String getLocationName() {
		return locationCode;
	}

	public String getSublocationName() {
		if (sublocationOverride != null) {
			return sublocationOverride;
		} else if (sublocationName != null) {
			return sublocationName;
		}
		return "";
	}

	public long getSublocationId() {
		return sublocationId;
	}

	public String getStatus() {
		if (status) {
			return "Active";
		}
		return "Cancelled";
	}

	public String getNonPublic() {
		if (nonPublic) {
			return "private";
		} else {
			return "public";
		}
	}

	private final DateTimeFormatter dtf = DateTimeFormatter.ofPattern("yyyy-MM-dd HH:mm:ss");
	private final ZoneId zoneId = ZoneId.systemDefault();
	private final ZoneRules rules = zoneId.getRules();
	private final ZoneOffset zone = rules.getOffset (Instant.now());

	public Date getStartDateTime(EventsIndexerLogEntry logEntry) {
		try {
			LocalDateTime date = LocalDateTime.parse(startDate + " " + startTime, dtf);
			return Date.from(date.toInstant(zone));
		} catch (DateTimeParseException e) {
			logEntry.incErrors("Error parsing end date from " + startDate, e);
			return null;
		}
	}

	public Date getEndDateTime(EventsIndexerLogEntry logEntry) {
		try {
			LocalDateTime date = LocalDateTime.parse(startDate + " " + startTime, dtf);
			LocalDateTime end = date.plusMinutes(this.length);
			Instant endInstant = end.toInstant(zone);
			return Date.from(endInstant);
		} catch (DateTimeParseException e) {
			logEntry.incErrors("Error parsing end date from " + startDate, e);
			return null;
		}
	}

	class EventField {
		private final String name;
		private final String value;
		private final String[] allowableValues;
		private final int type;
		private final int facet;

		EventField(String name, String value, String[] allowableValues, int type, int facet) {
			this.name = name;
			this.value = value;
			this.allowableValues = allowableValues;
			this.type = type;
			this.facet = facet;
		}

		public String getFacetName() {
			switch (this.facet) {
				case 0:
					return "";
				case 1:
					return "age_group_facet";
				case 2:
					return "program_type_facet";
				case 3:
					return "internal_category";
				case 4:
					return "event_type";
				case 5:
					return "custom_facet_1";
				case 6:
					return "custom_facet_2";
				case 7:
					return "custom_facet_3";
				default:
					return "";
			}
		}

		public String getName() {
			return name;
		}

		public String getSolrFieldName() {
			String sanitized_name = this.name.replaceAll("[^a-zA-Z0-9]", "_");
			switch (this.type) {
				case 0: // Text field
					return "custom_string_" + sanitized_name;
				case 1: // Text area
					return "custom_text_" + sanitized_name;
				case 2: // Checkbox
					return "custom_bool_" + sanitized_name;
				case 3: // Select list
					return "custom_string_" + sanitized_name;
				case 4: // Email
					return "custom_email_" + sanitized_name;
				case 5: // URL
					return "custom_url_" + sanitized_name;
			}
			return sanitized_name;
		}

		public String getRawValue() {
			return value;
		}

		public String getValue() {
			if (allowableValues.length > 0 && StringUtils.isNumeric(value)) {
				try {
					return allowableValues[Integer.parseInt(value)].trim();
				}catch (ArrayIndexOutOfBoundsException e) {
					//MDN 2/6/25 do additional handling and logging if we don't get a good value.
					return "Unknown";
				}
			} else {
				return value;
			}
		}

		public String[] getAllowableValues() {
			return allowableValues;
		}

		public int getType() {
			return type;
		}

		public int getFacet() {
			return facet;
		}
	}
}
