
package org.marc4j;

import java.util.Objects;
import java.util.Properties;

public class MarcReaderConfig {

    private boolean permissiveReader;

    private String defaultEncoding;

    private boolean to_utf_8;

    private String combineConsecutiveRecordsFields = null;

    private String combineRecordsLeftField = null;

    private String combineRecordsRightField = null;

    private String unicodeNormalize = null;

    private String includeIfPresent = null;

    private String includeIfMissing = null;

    private String marcDeleteSubfields = null;

    private String marcRemapFile = null;

    public MarcReaderConfig(Properties configProps) {
        setCombineConsecutiveRecordsFields(configProps.getProperty("marc.combine_records"),
                configProps.getProperty("marc.combine_records.left_field"), configProps
                        .getProperty("marc.combine_records.right_field"));

        setPermissiveReader(Boolean.parseBoolean(configProps.getProperty("marc.permissive")));

        setDefaultEncoding(configProps.getProperty("marc.default_encoding"));

        setToUtf8(Boolean.parseBoolean(configProps.getProperty("marc.to_utf_8")));

        setUnicodeNormalize(configProps.getProperty("marc.unicode_normalize"));

        setFilterParams(configProps.getProperty("marc.include_if_present"), configProps
                .getProperty("marc.include_if_missing"));

        setDeleteSubfieldSpec(configProps.getProperty("marc.delete_subfields"));

        setMarcRemapFilename(configProps.getProperty("marc.reader.remap"));
    }

    public MarcReaderConfig() {
        defaultEncoding = null;
        permissiveReader = false;
        to_utf_8 = false;
        combineConsecutiveRecordsFields = null;
        unicodeNormalize = null;
    }

    public boolean isPermissiveReader() {
        return permissiveReader;
    }

    @SuppressWarnings("UnusedReturnValue")
    public MarcReaderConfig setPermissiveReader(boolean permissiveReader) {
        this.permissiveReader = permissiveReader;
        return this;
    }

    public String getDefaultEncoding() {
	    return Objects.requireNonNullElseGet(defaultEncoding, () -> (isPermissiveReader()) ? "BESTGUESS" : "MARC8");
    }

    @SuppressWarnings("UnusedReturnValue")
    public MarcReaderConfig setDefaultEncoding(final String defaultEncoding) {
        if (defaultEncoding == null) {
            this.defaultEncoding = null;
        } else {
            this.defaultEncoding = defaultEncoding.trim();
        }
        return this;
    }

    public boolean toUtf8() {
        return to_utf_8;
    }

    @SuppressWarnings("UnusedReturnValue")
    public MarcReaderConfig setToUtf8(boolean to_utf_8) {
        this.to_utf_8 = to_utf_8;
        return this;
    }

    public String getCombineConsecutiveRecordsFields() {
        return combineConsecutiveRecordsFields;
    }

    public String getCombineRecordsLeftField() {
        return combineRecordsLeftField;
    }

    public String getCombineRecordsRightField() {
        return combineRecordsRightField;
    }

    public MarcReaderConfig setCombineConsecutiveRecordsFields(
            final String combineConsecutiveRecordsFields, final String leftField,
            final String rightField) {
        this.combineConsecutiveRecordsFields = combineConsecutiveRecordsFields;

        if (combineConsecutiveRecordsFields != null && combineConsecutiveRecordsFields.isEmpty()) {
            this.combineConsecutiveRecordsFields = null;
        }
        combineRecordsLeftField = leftField;
        combineRecordsRightField = rightField;

        return this;
    }

    @SuppressWarnings("UnusedReturnValue")
    public MarcReaderConfig setCombineConsecutiveRecordsFields(final String combineConsecutiveRecordsFieldsStr) {
        if (combineConsecutiveRecordsFieldsStr == null ||
            combineConsecutiveRecordsFieldsStr.isEmpty() ) {
            return(setCombineConsecutiveRecordsFields(null, null, null));
        }
        String[] combineParameters = combineConsecutiveRecordsFieldsStr.split("::",3);
        String fieldList =  combineParameters.length >= 1 ? combineParameters[0] : null;
        String leftField =  combineParameters.length >= 2 ? combineParameters[1] : null;
        String rightField = combineParameters.length >= 3 ? combineParameters[2] : null;
        return(setCombineConsecutiveRecordsFields(fieldList, leftField, rightField));
    }

    public String getUnicodeNormalize() {
        return unicodeNormalize;
    }

    /**
     * Map the allowed  parameter (unicodeNormalize2) is not null compare it against
     * the valid values and return the correct value to use as the parameter
     * @param unicodeNormalizeStr - String specifying the type of normalization to perform
     *         null or any undefined str value will indicate no normalization
     * @return the MarcReaderConfig object for chaining purposes.
     */
    @SuppressWarnings("UnusedReturnValue")
    public MarcReaderConfig setUnicodeNormalize(final String unicodeNormalizeStr) {
        if (unicodeNormalizeStr == null) {
            unicodeNormalize = null;
        } else if (unicodeNormalizeStr.equalsIgnoreCase("KC") || unicodeNormalizeStr
                .equalsIgnoreCase("CompatibilityCompose")) {
            unicodeNormalize = "KC";
        } else if (unicodeNormalizeStr.equalsIgnoreCase("C") || unicodeNormalizeStr
                .equalsIgnoreCase("Compose") || unicodeNormalizeStr.equalsIgnoreCase("true")) {
            unicodeNormalize = "C";
        } else if (unicodeNormalizeStr.equalsIgnoreCase("D") || unicodeNormalizeStr
                .equalsIgnoreCase("Decompose")) {
            unicodeNormalize = "D";
        } else if (unicodeNormalizeStr.equalsIgnoreCase("KD") || unicodeNormalizeStr
                .equalsIgnoreCase("CompatibilityDecompose")) {
            unicodeNormalize = "KD";
        } else {
            unicodeNormalize = null;
        }
        return this;
    }

    public String getIncludeIfPresent() {
        return (includeIfPresent);
    }

    public String getIncludeIfMissing() {
        return (includeIfMissing);
    }

    public boolean shouldFilter() {
        return (includeIfPresent != null || includeIfMissing != null);
    }

    @SuppressWarnings("UnusedReturnValue")
    public MarcReaderConfig setFilterParams(final String ifPresent, final String ifMissing) {
        includeIfPresent = ifPresent;
        includeIfMissing = ifMissing;
        return this;
    }

    public String getDeleteSubfieldSpec() {
        return (marcDeleteSubfields);
    }

    @SuppressWarnings("UnusedReturnValue")
    public MarcReaderConfig setDeleteSubfieldSpec(final String marcDeleteSubfields) {
        if (marcDeleteSubfields != null) {
            if (marcDeleteSubfields.equals("nomap")) {
                this.marcDeleteSubfields = null;
            } else {
                this.marcDeleteSubfields = marcDeleteSubfields.trim();
            }
        } else {
            this.marcDeleteSubfields = null;
        }
        return this;
    }

    public String getMarcRemapFilename() {
        return (marcRemapFile);
    }

    @SuppressWarnings("UnusedReturnValue")
    public MarcReaderConfig setMarcRemapFilename(final String marcRemapFile) {
        if (marcRemapFile != null) {
            if (marcRemapFile.equals("nomap")) {
                this.marcRemapFile = null;
            } else {
                this.marcRemapFile = marcRemapFile.trim();
            }
        } else {
            this.marcRemapFile = null;
        }
        return this;
    }
}
