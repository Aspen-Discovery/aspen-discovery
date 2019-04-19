package com.turning_leaf_technologies.reindexer;

import java.util.HashSet;

class RelevantLoanRule {
	private HashSet<Long>	patronTypes;
	private LoanRule loanRule;

	RelevantLoanRule(LoanRule loanRule, HashSet<Long> patronTypes) {
		this.loanRule = loanRule;
		this.patronTypes = patronTypes;
	}

	HashSet<Long> getPatronTypes() {
		return patronTypes;
	}

	LoanRule getLoanRule() {
		return loanRule;
	}
}
