package com.turning_leaf_technologies.cloud_library;

import org.xml.sax.helpers.DefaultHandler;

class CloudLibraryAvailabilityTypeHandler extends DefaultHandler {
	private final CloudLibraryAvailabilityType availabilityType;
	private String nodeContents = "";

	CloudLibraryAvailabilityTypeHandler(CloudLibraryAvailabilityType availabilityType) {
		this.availabilityType = availabilityType;
	}

	public void characters(char[] ch, int start, int length) {
		nodeContents += new String(ch, start, length);
	}

	public void endElement(String uri, String localName, String qName) {
		if (qName.equals("AvailabilityType")) {
			if (nodeContents.equals("PREPUB")) {
				availabilityType.setAvailabilityType(0);
			} else{
				availabilityType.setAvailabilityType(1);
			}
		}
		nodeContents = "";
	}
}
