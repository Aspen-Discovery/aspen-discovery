package com.turning_leaf_technologies.cron;

class ProcessToRun {
	
	private String processName;
	private String processClass;
	private String[] arguments = null;
	private Long lastRunVariableId = null;
	private Long lastRunTime = null;

	ProcessToRun(String processName, String processClass) {
		this.processName = processName;
		this.processClass = processClass;
	}

	String getProcessName() {
		return processName;
	}

	String getProcessClass() {
		return processClass;
	}

	String[] getArguments() {
		return arguments;
	}

	void setArguments(String[] arguments) {
		this.arguments = arguments;
	}

	Long getLastRunVariableId() {
		return lastRunVariableId;
	}

	void setLastRunVariableId(Long lastRunVariableId) {
		this.lastRunVariableId = lastRunVariableId;
	}

	void setLastRunTime(Long lastRunTime) {
		this.lastRunTime = lastRunTime;
	}

	Long getLastRunTime() {
		return lastRunTime;
	}
}
