/**
 * Copyright (C) 2004 Bas Peters
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

package org.marc4j.marc;

import java.io.Serializable;
import java.util.List;

import org.marc4j.MarcError;

/**
 * Represents a MARC record.
 *
 * @author Bas Peters
 */
public interface Record extends Serializable {

    /**
     * Sets the identifier. The purpose of this identifier is to provide an
     * identifier for persistency.
     *
     * @param id the identifier
     */
    void setId(Long id);

    /**
     * Returns the identifier.
     *
     * @return Long - the identifier
     */
    Long getId();

    /**
     * Sets the type of record.
     *
     * @param type the type of record
     */
    void setType(String type);

    /**
     * Returns the type of record.
     *
     * @return String - the type of record
     */
    String getType();

    /**
     * Adds a <code>VariableField</code>.
     *
     * @param field the <code>VariableField</code>
     * @throws IllegalAddException when the parameter is not a <code>VariableField</code>
     *             instance
     */
    void addVariableField(VariableField field);

    /**
     * Removes a variable field from the collection.
     *
     * @param field the variable field
     */
    void removeVariableField(VariableField field);

    /**
     * Returns a list of variable fields
     *
     * @return List - the variable fields
     */
    List<VariableField> getVariableFields();

    /**
     * Returns a list of control fields
     *
     * @return List - the control fields
     */
    List<ControlField> getControlFields();

    /**
     * Returns the first instance of a data field by tag number
     *
     * @return DataField - the data field
     */
    DataField getDataField(String tag);

    /**
     * Returns a list of data fields
     *
     * @return List - the data fields
     */
    List<DataField> getDataFields();

    /**
     * Returns the control number field or <code>null</code> if no control
     * number field is available.
     *
     * @return ControlField - the control number field
     */
    ControlField getControlNumberField();

    /**
     * Returns the control number or <code>null</code> if no control number is
     * available.
     * 
     * This method returns the data for a <code>ControlField</code> with tag
     * 001.
     *
     * @return String - the control number
     */
    String getControlNumber();

    /**
     * Returns the first instance of the variable field with the given tag.
     *
     * @param tag - the tag of the field to return
     * @return the first field that matches the supplied tag
     */
    VariableField getVariableField(String tag);

    /**
     * Returns a list of variable fields with the given tag.
     *
     * @param tag - the tag of the field to return
     * @return a list of fields that matches the supplied tag
     */
    List<VariableField> getVariableFields(String tag);

    /**
     * Gets a {@link List} of {@link DataField}s with the supplied tag.
     */
    List<DataField> getDataFields(final String tag);

    /**
     * Gets a {@link List} of {@link DataField}s with the supplied tag.
     */
    List<ControlField> getControlFields(final String tag);

    /**
     * Returns a list of variable fields for the given tags.
     * <p>
     * For example:
     *
     * <pre>
     * String tags = { &quot;100&quot;, &quot;245&quot;, &quot;260&quot;, &quot;300&quot; };
     *
     * List fields = record.getVariableFields(tags);
     * </pre>
     * @param tags - an array of tags any fields that match will be returned
     * @return a list of fields that matches one of the supplied tags
     */
    List<VariableField> getVariableFields(String[] tags);

    /**
     * Returns a list of variable fields for the given tags.
     * <p>
     * For example:
     *
     * <pre>
     * String tags = { &quot;100&quot;, &quot;245&quot;, &quot;260&quot;, &quot;300&quot; };
     *
     * List fields = record.getDataFields(tags);
     * </pre>
     * @param tags - an array of tags any fields that match will be returned
     * @return a list of fields that matches one of the supplied tags
     */
    List<DataField> getDataFields(String[] tags);

    /**
     * Returns the <code>Leader</code>.
     *
     * @return Leader - the <code>Leader</code>
     */
    Leader getLeader();

    /**
     * Sets the <code>Leader</code>.
     *
     * @param leader the <code>Leader</code>
     */
    void setLeader(Leader leader);

    /**
     * Returns a List of VariableField objects that have a data element that
     * matches the given regular expression.
     * <p>
     * See {@link java.util.regex.Pattern} for more information about Java
     * regular expressions.
     * </p>
     *
     * @param pattern the regular expression
     * @return List - the result list
     */
    List<VariableField> find(String pattern);

    /**
     * Returns a List of VariableField objects with the given tag that have a
     * data element that matches the given regular expression.
     * <p>
     * See {@link java.util.regex.Pattern} for more information about Java
     * regular expressions.
     * </p>
     *
     * @param tag the tag value
     * @param pattern the regular expression
     * @return List - the result list
     */
    List<VariableField> find(String tag, String pattern);

    /**
     * Returns a List of VariableField objects with the given tags that have a
     * data element that matches the given regular expression.
     * <p>
     * See {@link java.util.regex.Pattern} for more information about Java
     * regular expressions.
     * </p>
     *
     * @param tag the tag values
     * @param pattern the regular expression
     * @return List - the result list
     */
    List<VariableField> find(String[] tag, String pattern);

    /**
     * Logs an error message using the stated severity level.  Uses the values passed  
     * in id, field, and subfield to note the location of the error.
     * 
     * @param field - the tag of the field currently being processed
     * @param subfield - the subfield tag of the subfield currently being processed
     * @param severity - An indication of the relative severity of the error that was 
     *                      encountered.
     * @param message - A descriptive message about the error that was encountered.
     */
    void addError(String field, String subfield, int severity, String message);

    /**
     * Copies a List of errors into the current error handler
     * 
     * @param newErrors - A list of Errors.
     */
    void addErrors(List<MarcError> newErrors);

    /**
     * Returns true if any errors were found for this record
     *
     * @return true if any errors were found for this record
     */
    boolean hasErrors();

    /**
     * Returns the errors found for this record
     *
     * @return List of all Errors that were found for this record
     */
    List<MarcError> getErrors();

}
