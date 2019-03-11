package com.turning_leaf_technologies.reindexer;

public class LoanRule {
	private Long loanRuleId;
	private String name;
	private Boolean holdable;
	private Boolean bookable;

	public Boolean getBookable() {
		return bookable;
	}

	public void setBookable(Boolean bookable) {
		this.bookable = bookable;
	}

	public Long getLoanRuleId() {
		return loanRuleId;
	}
	public void setLoanRuleId(Long loanRuleId) {
		this.loanRuleId = loanRuleId;
	}
	public String getName() {
		return name;
	}
	public void setName(String name) {
		this.name = name;
	}
	public Boolean getHoldable() {
		return holdable;
	}
	public void setHoldable(Boolean holdable) {
		this.holdable = holdable;
	}
	
	
}
