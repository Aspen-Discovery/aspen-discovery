	<div id="main-content">
		<h1>{$pageTitleShort}</h1>
		<a class="btn btn-sm btn-default" href='/ILS/LoanRuleDeterminers?objectAction=list'>Return to List</a>
		<div class="helpTextUnsized"><p>To reload loan rule determiners:
		<ol>
		<li>Open Millennium</li>
		<li>Go to the Loan Rules configuration page (In Circulation module go to Admin &gt; Parameters &gt; Circulation &gt; Loan Rule Determiner.)</li>
		<li>Copy the entire table by highlighting it and pressing Ctrl+C.</li>
		<li>Paste the data in the text area below.</li>
		<li>Select the Reload Data button.</li>
		</ol>
		</p></div>
		<form name="importLoanRules" action="/ILS/LoanRuleDeterminers" method="post">
			<div>
				<input type="hidden" name="objectAction" value="doLoanRuleDeterminerReload" />
				<textarea rows="20" cols="80" name="loanRuleDeterminerData"></textarea>
				<br/>
				<input type="submit" name="reload" value="Reload Data" class="btn btn-primary"/>
			</div>
		</form>

	</div>
