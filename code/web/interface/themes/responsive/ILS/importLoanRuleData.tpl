	<div id="main-content">
		<h1>{$pageTitleShort}</h1>
		<a class="btn btn-sm btn-default" href='/Admin/LoanRules?objectAction=list'>Return to List</a>
		<div class="helpTextUnsized"><p>To reload loan rules:
		<ol>
		<li>Open Millennium</li>
		<li>Go to the Loan Rules configuration page (In Circulation module go to Admin &gt; Parameters &gt; Circulation &gt; Loan Rules.)</li>
		<li>Copy the entire table by highlighting it and pressing Ctrl+C.</li>
		<li>Paste the data in the text area below.</li>
		<li>Select the Reload Data button.</li>
		</ol> 
		</p></div>
		<form name="importLoanRules" action="/ILS/LoanRules" method="post">
			<div>
				<input type="hidden" name="objectAction" value="doLoanRuleReload" />
				<textarea rows="20" cols="80" name="loanRuleData"></textarea>
				<br/>
				<input type="submit" name="reload" value="Reload Data" class="btn btn-primary"/>
			</div>
		</form>

	</div>
