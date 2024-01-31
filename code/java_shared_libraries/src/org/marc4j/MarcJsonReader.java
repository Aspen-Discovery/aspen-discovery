
package org.marc4j;

import java.io.InputStream;
import java.io.InputStreamReader;
import java.io.Reader;
import java.util.regex.Pattern;

import org.marc4j.marc.ControlField;
import org.marc4j.marc.DataField;
import org.marc4j.marc.MarcFactory;
import org.marc4j.marc.Record;
import org.marc4j.marc.Subfield;
import org.marc4j.util.JsonParser;

public class MarcJsonReader implements MarcReader {

    MarcFactory factory;

    JsonParser parser;

    int parserLevel = 0;

    public final static int NO_ARRAY = 0;

    // These are used in MARC-in-JSON
    public final static int FIELDS_ARRAY = 1;

    public final static int SUBFIELDS_ARRAY = 2;

    // These are used in MARC-JSON
    public final static int CONTROLFIELD_ARRAY = 3;

    public final static int DATAFIELD_ARRAY = 4;

    public final static int SUBFIELD_ARRAY = 5;

    /**
     * Creates a MarcJsonReader from a supplied {@link InputStream}
     * 
     * @param is - an InputStream to read
     */
    public MarcJsonReader(final InputStream is) {
        parser = new JsonParser(/*JsonParser.OPT_INTERN_KEYWORDS |*/
                        JsonParser.OPT_UNQUOTED_KEYWORDS |
                        JsonParser.OPT_SINGLE_QUOTE_STRINGS);
        parser.setInput("MarcInput", new InputStreamReader(is), false);
        factory = MarcFactory.newInstance();
    }

    /**
     * Creates a MarcJsonReader from the supplied {@link Reader}.
     * 
     * @param in - A Reader to use for input
     */
    public MarcJsonReader(final Reader in) {
        parser = new JsonParser(0);
        parser.setInput("MarcInput", in, false);
        factory = MarcFactory.newInstance();
    }

    /**
     * Returns <code>true</code> if there is a next record; else,
     * <code>false</code>.
     */
    @Override
    public boolean hasNext() {
        int code = parser.getEventCode();

        if (code == 0 || code == JsonParser.EVT_OBJECT_ENDED) {
            code = parser.next();
        }

        if (code == JsonParser.EVT_OBJECT_BEGIN) {
            return true;
        }

        if (code == JsonParser.EVT_INPUT_ENDED) {
            return false;
        }

        throw new MarcException("Malformed JSON input");
    }

    static Pattern threeAlphaNumerics = Pattern.compile("[A-Z0-9][A-Z0-9][A-Z0-9]");
    static Pattern singleAlphaNumeric = Pattern.compile("[a-z0-9]");
    static Pattern forwardSlash = Pattern.compile("⁄");
    /**
     * Returns the next {@link Record}.
     */
    @Override
    public Record next() {
        int code = parser.getEventCode();
        Record record = null;
        ControlField cf = null;
        DataField df = null;
        Subfield sf = null;
        int inArray = NO_ARRAY;

        while (true) {
            final String memberName= parser.getMemberName();

            switch (code) {
                case JsonParser.EVT_OBJECT_BEGIN:
                    if (parserLevel == 0) {
                        record = factory.newRecord();
                    } else if (inArray == FIELDS_ARRAY && threeAlphaNumerics.matcher(memberName).matches()) {
                        df = factory.newDataField();
                        df.setTag(memberName);
                    }

                    parserLevel++;
                    break;
                case JsonParser.EVT_OBJECT_ENDED:
                    parserLevel--;
                    if (parserLevel == 0) {
                        return record;
                    } else if (inArray == FIELDS_ARRAY && threeAlphaNumerics.matcher(memberName).matches()) {
	                    assert record != null;
	                    record.addVariableField(df);
                        df = null;
                    } else if (inArray == DATAFIELD_ARRAY && memberName.equals("datafield")) {
	                    assert record != null;
	                    record.addVariableField(df);
                        df = null;
                    }

                    break;
                case JsonParser.EVT_ARRAY_BEGIN:
	                switch (memberName) {
		                case "fields":
			                inArray = FIELDS_ARRAY;
			                break;
		                case "subfields":
			                inArray = SUBFIELDS_ARRAY;
			                break;
		                case "controlfield":
			                inArray = CONTROLFIELD_ARRAY;
			                break;
		                case "datafield":
			                inArray = DATAFIELD_ARRAY;
			                break;
		                case "subfield":
			                inArray = SUBFIELD_ARRAY;
			                break;
	                }

                    break;
                case JsonParser.EVT_ARRAY_ENDED:
	                switch (memberName) {
		                case "fields":
		                case "controlfield":
		                case "datafield":
			                inArray = NO_ARRAY;
			                break;
		                case "subfields":
			                inArray = FIELDS_ARRAY;
			                break;
		                case "subfield":
			                inArray = DATAFIELD_ARRAY;
			                break;
	                }

                    break;
                case JsonParser.EVT_OBJECT_MEMBER:
                    String value = parser.getMemberValue();
                    if (JsonParser.isQuoted(value)) {
                        value = JsonParser.stripQuotes(value);
                    }

                    value = forwardSlash.matcher(value).replaceAll("/");

	                char ind1 = !value.isEmpty() ? value.charAt(0) : ' ';
	                if (memberName.equals("ind1")) {
	                    assert df != null;
	                    df.setIndicator1(ind1);
                    } else if (memberName.equals("ind2")) {
	                    assert df != null;
	                    df.setIndicator2(ind1);
                    } else if (memberName.equals("leader")) {
	                    assert record != null;
	                    record.setLeader(factory.newLeader(value));
                    } else if (inArray == FIELDS_ARRAY && threeAlphaNumerics.matcher(memberName).matches()) {
                        cf = factory.newControlField(memberName, value);
	                    assert record != null;
	                    record.addVariableField(cf);
                    } else if (inArray == SUBFIELDS_ARRAY && singleAlphaNumeric.matcher(memberName).matches()) {
                        sf = factory.newSubfield(memberName.charAt(0), value);
	                    assert df != null;
	                    df.addSubfield(sf);
                    } else if (inArray == CONTROLFIELD_ARRAY && memberName.equals("tag")) {
                        cf = factory.newControlField();
                        cf.setTag(value);
                    } else if (inArray == CONTROLFIELD_ARRAY && memberName.equals("data")) {
	                    assert cf != null;
	                    cf.setData(value);
	                    assert record != null;
	                    record.addVariableField(cf);
                    } else if (inArray == DATAFIELD_ARRAY && memberName.equals("tag")) {
                        df = factory.newDataField();
                        df.setTag(value);
                    } else if (inArray == DATAFIELD_ARRAY && memberName.equals("ind")) {
	                    assert df != null;
	                    df.setIndicator1(ind1);
                        df.setIndicator2(value.length() > 1 ? value.charAt(1) : ' ');
                    } else if (inArray == SUBFIELD_ARRAY && memberName.equals("code")) {
                        sf = factory.newSubfield();
                        sf.setCode(value.charAt(0));
                    } else if (inArray == SUBFIELD_ARRAY && memberName.equals("data")) {
	                    assert sf != null;
	                    sf.setData(value);
	                    assert df != null;
	                    df.addSubfield(sf);
                    }

                    break;
                case JsonParser.EVT_INPUT_ENDED:
                    throw new MarcException("Premature end of input in JSON file");
            }
            code = parser.next();
        }

        // return record;
    }

}
