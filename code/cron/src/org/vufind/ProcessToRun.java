package org.vufind;

public class ProcessToRun {
	
	private String processName;
	private String processClass;
	private String[] arguments = null;
	private Long lastRunVariableId = null;
	private Long lastRunTime = null;

	public ProcessToRun(String processName, String processClass) {
		this.processName = processName;
		this.processClass = processClass;
	}

	public String getProcessName() {
		return processName;
	}

	public void setProcessName(String processName) {
		this.processName = processName;
	}

	public String getProcessClass() {
		return processClass;
	}

	public void setProcessClass(String processClass) {
		this.processClass = processClass;
	}

	public String[] getArguments() {
		return arguments;
	}

	public void setArguments(String[] arguments) {
		this.arguments = arguments;
	}

	public Long getLastRunVariableId() {
		return lastRunVariableId;
	}

	public void setLastRunVariableId(Long lastRunVariableId) {
		this.lastRunVariableId = lastRunVariableId;
	}

	public void setLastRunTime(Long lastRunTime) {
		this.lastRunTime = lastRunTime;
	}

	public Long getLastRunTime() {
		return lastRunTime;
	}
}
