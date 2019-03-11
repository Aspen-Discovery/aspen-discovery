package com.turning_leaf_technologies.reindexer;

import java.util.HashSet;

/**
 * Additional information about a relevant loan rule includes additional information about what PTypes the loan rule is relevant for
 *
 * Pika
 * User: Mark Noble
 * Date: 8/26/2015
 * Time: 2:51 PM
 */
public class RelevantLoanRule {
	private HashSet<Long>	patronTypes;
	private LoanRule loanRule;

	public RelevantLoanRule(LoanRule loanRule, HashSet<Long> patronTypes) {
		this.loanRule = loanRule;
		this.patronTypes = patronTypes;
	}

	public HashSet<Long> getPatronTypes() {
		return patronTypes;
	}

	public LoanRule getLoanRule() {
		return loanRule;
	}
}
