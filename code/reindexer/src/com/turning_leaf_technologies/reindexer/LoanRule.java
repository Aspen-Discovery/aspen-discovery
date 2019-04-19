package com.turning_leaf_technologies.reindexer;

public class LoanRule {
	private Long loanRuleId;
	private String name;
	private Boolean holdable;
	private Boolean bookable;

	Boolean getBookable() {
		return bookable;
	}

	void setBookable(Boolean bookable) {
		this.bookable = bookable;
	}

	Long getLoanRuleId() {
		return loanRuleId;
	}
	void setLoanRuleId(Long loanRuleId) {
		this.loanRuleId = loanRuleId;
	}
	public String getName() {
		return name;
	}
	public void setName(String name) {
		this.name = name;
	}
	Boolean getHoldable() {
		return holdable;
	}
	void setHoldable(Boolean holdable) {
		this.holdable = holdable;
	}
}
