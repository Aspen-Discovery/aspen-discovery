package org.solrmarc.solr;

import java.io.IOException;
import java.lang.reflect.Field;
import java.lang.reflect.InvocationTargetException;
import java.lang.reflect.Method;
import java.util.Collection;
import java.util.Iterator;
import java.util.Map;

public class SolrCoreProxy implements SolrProxy {
	Object		solrCore										= null;
	Object		genericCoreContainerObject	= null;
	Object		updateHandler								= null;
	Object		deleteUpdateCommand					= null;
	Object		commitUpdateCommand					= null;
	Object		addUpdateCommand						= null;
	Object		documentBuilder							= null;
	Class<?>	solrExceptionClass					= null;

	public SolrCoreProxy(Object solrCore, Object genericCoreContainerObject) {
		this.solrCore = solrCore;
		this.genericCoreContainerObject = genericCoreContainerObject;
	}

	/** return the solrCore as an Object. Public b/c it's used by test code */
	public Object getCore() {
		return solrCore;
	}

	public boolean checkSchemaField(String fldName, String fieldOrType, String conditionName) {
		final String conditionNames[] = { "indexed", "stored", "multiValued", "omitNorms", "storeTermVector", "isTokenized" };
		String nameCheck = null;
		for (String name : conditionNames) {
			if (name.equals(conditionName)) {
				nameCheck = name;
				break;
			}
		}
		if (nameCheck == null) {
			return (false);
		}
		String schemaMethod = fieldOrType.equals("type") ? "getFieldType" : "getFieldOrNull";
		try {
			Object schema = solrCore.getClass().getMethod("getSchema").invoke(solrCore);
			Object schemaField = schema.getClass().getMethod(schemaMethod, String.class).invoke(schema, fldName);
			if (schemaField != null) {
				Object booleanResp = schemaField.getClass().getMethod(nameCheck).invoke(schemaField);
				if (booleanResp instanceof Boolean) {
					return (((Boolean) booleanResp).booleanValue());
				}
			}
		} catch (Exception e) {
			// TODO Auto-generated catch block
			e.printStackTrace();
		}
		return (false);
	}

	/*
	 * (non-Javadoc)
	 * 
	 * @see org.org.org.solrmarc.solr.SolrProxy#isSolrException(java.lang.Exception)
	 */
	public boolean isSolrException(Exception e) {
		if (solrExceptionClass == null) {
			try {
				solrExceptionClass = Class.forName("org.apache.solr.common.SolrException");
			} catch (ClassNotFoundException e1) {
				e1.printStackTrace();
			}
		}
		return ((solrExceptionClass != null) ? solrExceptionClass.isInstance(e) : false);
	}

	/*
	 * (non-Javadoc)
	 * 
	 * @see org.org.org.solrmarc.solr.SolrProxy#addDoc(java.util.Map, boolean, boolean)
	 */
	public String addDoc(Map<String, Object> fieldsMap, boolean verbose, boolean addDocToIndex) throws IOException {
		initializeAddDocObjects();

		invokeDocBuilderMethodNoArgs("startDoc");

		Iterator<String> keys = fieldsMap.keySet().iterator();
		while (keys.hasNext()) {
			String fldName = keys.next();
			Object fldValObject = fieldsMap.get(fldName);

			// add field to doc, whether it be a single value or a Collection
			if (fldValObject instanceof String) {
				invokeDocBuilderAddField(fldName, (String) fldValObject);
			} else if (fldValObject instanceof Collection) {
				Iterator<?> valIter = ((Collection) fldValObject).iterator();
				while (valIter.hasNext()) {
					Object nextItem = valIter.next();
					if (nextItem != null) {
						invokeDocBuilderAddField(fldName, nextItem.toString());
					}
				}
			}
		}

		invokeDocBuilderMethodNoArgs("endDoc");

		Object doc = invokeDocBuilderMethodNoArgs("getDoc");

		if (addDocToIndex) {
			setObjFldVal(addUpdateCommand, "doc", doc);
			setObjFldVal(addUpdateCommand, "allowDups", false);
			setObjFldVal(addUpdateCommand, "overwriteCommitted", true);
			setObjFldVal(addUpdateCommand, "overwritePending", true);

			invokeUpdateHandlerMethodNoArgs("addDoc", addUpdateCommand);
		}

		if (verbose || !addDocToIndex)
			return doc.toString().replaceAll("> ", "> \n");
		else
			return (null);
	}

	/*
	 * (non-Javadoc)
	 * 
	 * @see org.org.org.solrmarc.solr.SolrProxy#delete(java.lang.String, boolean, boolean)
	 */
	public void delete(String id, boolean fromCommitted, boolean fromPending) throws IOException {
		initializeDeleteObjects();

		setObjFldVal(deleteUpdateCommand, "id", id);
		setObjFldVal(deleteUpdateCommand, "fromCommitted", fromCommitted);
		setObjFldVal(deleteUpdateCommand, "fromPending", fromPending);

		invokeUpdateHandlerMethodNoArgs("delete", deleteUpdateCommand);
	}

	/*
	 * (non-Javadoc)
	 * 
	 * @see org.org.org.solrmarc.solr.SolrProxy#deleteAllDocs()
	 */
	public void deleteAllDocs() throws IOException {
		initializeDeleteObjects();

		setObjFldVal(deleteUpdateCommand, "query", "*:*");
		setObjFldVal(deleteUpdateCommand, "fromCommitted", true);
		setObjFldVal(deleteUpdateCommand, "fromPending", true);

		invokeUpdateHandlerMethodNoArgs("deleteByQuery", deleteUpdateCommand);
		commit(true);

		updateHandler = null;

		// try
		// {
		// Thread.sleep(1000);
		// }
		// catch (InterruptedException e)
		// {
		// // TODO Auto-generated catch block
		// e.printStackTrace();
		// }
		//
	}

	/*
	 * (non-Javadoc)
	 * 
	 * @see org.org.org.solrmarc.solr.SolrProxy#commit(boolean)
	 */
	public void commit(boolean optimize) throws IOException {
		initializeCommitObjects();
		setObjFldVal(commitUpdateCommand, "optimize", optimize);
		setObjFldVal(commitUpdateCommand, "waitFlush", true);
		setObjFldVal(commitUpdateCommand, "waitSearcher", true);
		invokeUpdateHandlerMethodNoArgs("commit", commitUpdateCommand);
	}

	/*
	 * (non-Javadoc)
	 * 
	 * @see org.org.org.solrmarc.solr.SolrProxy#close()
	 */
	public void close() {
		try {
			Method closeMethod = solrCore.getClass().getMethod("close");
			closeMethod.invoke(solrCore);
			if (genericCoreContainerObject != null) {
				Method shutdownMethod = genericCoreContainerObject.getClass().getMethod("shutdown");
				shutdownMethod.invoke(genericCoreContainerObject);
			}
		} catch (Exception e) {
			System.err.println("Error: Problem invoking close in SolrCoreProxy");
			throw new SolrRuntimeException("Error: Problem invoking close  in SolrCoreProxy", e);
		}
	}

	/**
	 * given an Object, a field name and a value for the field, set the object's
	 * field to the value
	 * 
	 * @param object
	 *          - the Object containing the field
	 * @param fldName
	 *          - the name of the field in the object
	 * @param fldVal
	 *          - the value to be assigned to the field
	 */
	private void setObjFldVal(Object object, String fldName, Object fldVal) {
		try {
			Field field = object.getClass().getField(fldName);
			field.set(object, fldVal);
		} catch (Exception e) {
			e.printStackTrace();
		}
	}

	/**
	 * invoke a method with no arguments on the documentBuilder object
	 * 
	 * @param methodName
	 */
	private Object invokeDocBuilderMethodNoArgs(String methodName) {
		Object result;
		try {
			result = documentBuilder.getClass().getMethod(methodName).invoke(documentBuilder);
		} catch (Exception e) {
			String errmsg = "Error: Problem invoking " + methodName + " in SolrCoreProxy";
			System.err.println(errmsg);
			throw new SolrRuntimeException(errmsg, e);
		}
		return result;
	}

	/**
	 * add a field to the Document being created with documentBuilder
	 * 
	 * @param fieldName
	 *          the name of the field to add
	 * @param fieldVal
	 *          the value of the field to add, as a String
	 */
	private void invokeDocBuilderAddField(String fieldName, String fieldVal) {
		try {
			documentBuilder.getClass().getMethod("addField", String.class, String.class).invoke(documentBuilder, fieldName, fieldVal);
		} catch (Exception e) {
			String errmsg = "Error: Problem invoking addField in SolrCoreProxy";
			System.err.println(errmsg);
			// e.printStackTrace();
			throw new SolrRuntimeException(errmsg, e);
		}
	}

	/**
	 * invoke a method with no arguments on the updateHandler object
	 */
	private void invokeUpdateHandlerMethodNoArgs(String methodName, Object commandObject) throws IOException {
		String errmsg = "Error: Problem invoking " + methodName + " on updateHandler via SolrCoreProxy";

		try {
			Method method = updateHandler.getClass().getMethod(methodName, commandObject.getClass());
			method.invoke(updateHandler, commandObject);
		} catch (InvocationTargetException e) {
			Throwable cause = e.getCause();
			if (cause instanceof IOException) throw (IOException) cause;
			System.err.println(errmsg);
			throw new SolrRuntimeException(errmsg, e);
		} catch (Exception e) {
			System.err.println(errmsg);
			throw new SolrRuntimeException(errmsg, e);
		}
	}

	/**
	 * initialize the objects need to add documents to the index, if they aren't
	 * already initialized
	 */
	private void initializeAddDocObjects() {
		initializeUpdateHandler();
		initializeAddUpdateCommand();
		initializeDocBuilder();
	}

	/**
	 * initialize the objects need to delete docs from the index, if they aren't
	 * already initialized
	 */
	private void initializeDeleteObjects() {
		initializeUpdateHandler();
		try {
			if (deleteUpdateCommand == null) {
				Class<?> deleteUpdateCommandClass = Class.forName("org.apache.solr.update.DeleteUpdateCommand");
				deleteUpdateCommand = deleteUpdateCommandClass.getConstructor().newInstance();
			}
		} catch (Exception e) {
			String errmsg = "Error: Problem creating DeleteUpdateCommand in SolrCoreProxy";
			System.err.println(errmsg);
			throw new SolrRuntimeException(errmsg, e);
		}
	}

	/**
	 * initialize the objects need to commit changes to index, if they aren't
	 * already initialized
	 */
	private void initializeCommitObjects() {
		initializeUpdateHandler();
		try {
			if (commitUpdateCommand == null) {
				Class<?> commitUpdateCommandClass = Class.forName("org.apache.solr.update.CommitUpdateCommand");
				commitUpdateCommand = commitUpdateCommandClass.getConstructor(boolean.class).newInstance(false);
			}
		} catch (Exception e) {
			String errmsg = "Error: Problem creating CommitUpdateCommand in SolrCoreProxy";
			System.err.println(errmsg);
			throw new SolrRuntimeException(errmsg, e);
		}
	}

	/**
	 * initialize addUpdateCommand object if it doesn't yet exist, or clear it if
	 * it already exists
	 */
	private void initializeAddUpdateCommand() {
		try {
			if (addUpdateCommand == null) {
				Class<?> addUpdateCommandClass = Class.forName("org.apache.solr.update.AddUpdateCommand");
				addUpdateCommand = addUpdateCommandClass.getConstructor().newInstance();
			} else {
				addUpdateCommand.getClass().getMethod("clear").invoke(addUpdateCommand);
			}
		} catch (Exception e) {
			String errmsg = "Error: Problem creating AddUpdateCommand in SolrCoreProxy";
			System.err.println(errmsg);
			throw new SolrRuntimeException(errmsg, e);
		}
	}

	/**
	 * initialize updateHandler object by getting it from solrCore, if it doesn't
	 * exist yet
	 */
	private void initializeUpdateHandler() {
		try {
			if (updateHandler == null) {
				Method updateHandlerMethod = solrCore.getClass().getMethod("getUpdateHandler");
				updateHandler = updateHandlerMethod.invoke(solrCore);
			}
		} catch (Exception e) {
			String errmsg = "Error: Problem creating updateHandler in SolrCoreProxy";
			System.err.println(errmsg);
			throw new SolrRuntimeException(errmsg, e);
		}
	}

	/**
	 * initialize documentBuilder object if it doesn't yet exist
	 */
	private void initializeDocBuilder() {
		try {
			if (documentBuilder == null) {
				Class<?> indexSchemaClass = Class.forName("org.apache.solr.schema.IndexSchema");
				Object indexSchema = solrCore.getClass().getMethod("getSchema").invoke(solrCore);

				Class<?> documentBuilderClass = Class.forName("org.apache.solr.update.DocumentBuilder");
				documentBuilder = documentBuilderClass.getConstructor(indexSchemaClass).newInstance(indexSchema);
			}
		} catch (Exception e) {
			String errmsg = "Error: Problem creating documentBuilder in SolrCoreProxy";
			System.err.println(errmsg);
			throw new SolrRuntimeException(errmsg, e);
		}
	}

}
