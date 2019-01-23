{*<div id="page-content" *}{*class="col-xs-12 col-sm-8 col-md-9 col-lg-9" defined by container*}{*>*}
<form action="" method="post" id="offlineCircForm">
	<div id="main-content" class="full-result-content">
		<h2>Offline Circulation</h2>

		{if $error}
			<div class="alert alert-danger">
				{$error}
			</div>
		{/if}

		{if $results}
			<div class="alert alert-info" id="offline-circulation-result">
				{$results}
			</div>
		{/if}

		<div class="row">
			<div class="col-xs-3">
				<div><label for="login">{$ILSname} Username</label>:</div>
				<div><input type="text" name="login" id="login" value="{$lastLogin}" class="required" onchange="clearOfflineCircResults();"> </div>
			</div>
			<div class="col-xs-3">
				<div><label for="password1">{$ILSname} Password</label>:</div>
				<div><input type="password" name="password1" id="password1" value="{$lastPassword1}" class="required" onchange="clearOfflineCircResults();"></div>
			</div>
			<div class="col-xs-4">
				<label for="showPwd" class="checkbox">
					<input type="checkbox" id="showPwd" name="showPwd" onclick="return VuFind.pwdToText('password1')">
					Show {$ILSname} Password
				</label>
			</div>
		</div>
		<div class="row">
			<fieldset>
				<legend class="col-xs-12" style="margin-top: 10px">Checkout titles</legend>
				<div class="col-xs-12">
					<div><label for="patronBarcode">Patron Barcode</label>:</div>
					<div><input type="text" name="patronBarcode" id="patronBarcode" class="required" onchange="clearOfflineCircResults();"></div>
				</div>
				<div class="col-xs-12">
					<div><label for="barcodesToCheckOut">Enter barcodes to check out (one per line)</label>:</div>
					<textarea rows="10" cols="20" name="barcodesToCheckOut" id="barcodesToCheckOut" class="required" onchange="clearOfflineCircResults();"></textarea>
				</div>
				<div class="col-xs-12">
					<button name="submit" class="btn btn-primary pull-right">Submit Offline Checkouts</button>
				</div>
			</fieldset>
		</div>
	</div>

</form>
<div class="well" style="margin:10px 0;">
	<p>This Offline Circulation functionality is intended to be used to checkout titles to patrons while connectivity to the ILS is not available or not ready for usage.</p>
	<p>To use this functionality, enter the same {$ILSname} username and password that you use while logging in to the {$ILSname} Client. You will only need to do this once per session.</p>
	<p>When a patron arrives at the circulation desk, first enter their barcode either by typing it in or scanning it. If you do not have their barcode or the patron does not know it, enter their name and the transaction can be manually processed once the system is back online.</p>
	<p>Next scan or enter the barcode of each item to be checked out, one per line. Once all items have been entered, press the "Submit Offline Checkouts" button.</p>
	<p>After submitting the form, check the page to ensure that no errors occurred saving the checkout transaction. If errors occurred, they are displayed at the top of the screen in red.</p>
	<p>When the ILS is back online, all transactions will be automatically processed and library staff will be given a list of exceptions that need to be handled manually.</p>
	<p class="alert alert-warning">Note: This form does not check that the barcodes that were entered are correct or properly formatted. Those errors will only be detected once the ILS is online and the transactions are processed.</p>
</div>

{literal}
<script type="text/javascript">
	function clearOfflineCircResults(){
		$("#offline-circulation-result").hide();
	}
	function checkCptKey(e)
	{
		var shouldBubble = true;
		switch (e.keyCode)
		{
			// user pressed the Tab
			case 9:
			{
				//$(".cptIcdProcedureSelect").toggleClass("cptIcdProcedureSelectVisible");
				//shouldBubble = false;
				break;
			};
			// user pressed the Enter
			case 13:
			{
				//$(".cptIcdProcedureSelect").toggleClass("cptIcdProcedureSelectVisible");
				shouldBubble = false;
				break;
			};
			// user pressed the ESC
			case 27:
			{
				//$(".cptIcdProcedureSelect").toggleClass("cptIcdProcedureSelectVisible");
				break;
			};
		};
		/* this propagates the jQuery event if true */
		return shouldBubble;
	};
	/* user pressed special keys while in Selector
	*
	* Probably disables return key from barcode scanner. plb 6-26-2015
	*
	* */
	$("#patronBarcode").keydown(function(e)
	{
		return checkCptKey(e, $(this));
	});
	$("#offlineCircForm").validate();

</script>
{/literal}