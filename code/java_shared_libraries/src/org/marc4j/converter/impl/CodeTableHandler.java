/**
 * Copyright (C) 2002 Bas Peters
 *
 * This file is part of MARC4J
 *
 * MARC4J is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * MARC4J is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with MARC4J; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

package org.marc4j.converter.impl;

import java.util.HashMap;
import java.util.Vector;

import org.xml.sax.Attributes;
import org.xml.sax.helpers.DefaultHandler;

/**
 * <code>CodeTableHandler</code> is a SAX2 <code>ContentHandler</code> that
 * builds a data structure to facilitate AnselToUnicode character conversion.
 * 
 * @author Corey Keith
 * @see DefaultHandler
 */
public class CodeTableHandler extends DefaultHandler {

    private HashMap<Integer, HashMap<Integer, Character>> sets;

    private HashMap<Integer, Character> charset;

    private HashMap<Integer, Vector<Integer>> combiningChars;

    /** Data element identifier */
    private Integer isoCode;

    private Integer marc;

    private Character ucs;

    private boolean useAlt = false;

    private boolean isCombining;

    private Vector<Integer> combining;

    /** StringBuffer to store data */
    private StringBuffer data;

    /**
     * Gets the character sets hashtable.
     * 
     * @return The character sets
     */
    HashMap<Integer, HashMap<Integer, Character>> getCharSets() {
        return sets;
    }

    /**
     * Gets the combining characters.
     * 
     * @return The combining characters
     */
    HashMap<Integer, Vector<Integer>> getCombiningChars() {
        return combiningChars;
    }

    /**
     * An event fired at the start of an element.
     * 
     * @param uri - the uri
     * @param name - the name
     * @param qName - the qName
     * @param attributes - the attributes
     */
    @Override
    public void startElement(final String uri, final String name, final String qName,
            final Attributes attributes) {
        switch (name) {
            case "characterSet":
                charset = new HashMap<>();
                isoCode = Integer.valueOf(attributes.getValue("ISOcode"), 16);
                combining = new Vector<>();
                break;
            case "marc":
	        case "ucs":
	        case "alt":
	        case "isCombining":
		        data = new StringBuffer();
                break;
            case "codeTables":
                sets = new HashMap<>();
                combiningChars = new HashMap<>();
                break;
	        case "code":
                isCombining = false;
                break;
        }
    }

    /**
     * An event fired as characters are consumed.
     * 
     * @param ch - the array of characters that was found
     * @param start - the starts point in ch for where to start copying data
     * @param length - the number of characters to copy
     */
    @Override
    public void characters(final char[] ch, final int start, final int length) {
        if (data != null) {
            data.append(ch, start, length);
        }
    }

    /**
     * An event fired at the end of parsing an element.
     * 
     * @param uri - the uri
     * @param name - the name
     * @param qName - the qName
     */
    @Override
    public void endElement(final String uri, final String name, final String qName) {
        switch (name) {
            case "characterSet":
                sets.put(isoCode, charset);
                combiningChars.put(isoCode, combining);
                combining = null;
                charset = null;
                break;
            case "marc":
                marc = Integer.valueOf(data.toString(), 16);
                break;
            case "ucs":
                if (data.length() > 0) {
                    ucs = (char) Integer.parseInt(data.toString(), 16);
                } else {
                    ucs = null;
                }
                break;
            case "alt":
                if (useAlt && data.length() > 0) {
                    ucs = (char) Integer.parseInt(data.toString(), 16);
                    useAlt = false;
                }
                break;
            case "code":
                if (isCombining) {
                    combining.add(marc);
                }
                charset.put(marc, ucs);
                break;
            case "isCombining":
                if (data.toString().equals("true")) {
                    isCombining = true;
                }
                break;
        }

        data = null;
    }
}
