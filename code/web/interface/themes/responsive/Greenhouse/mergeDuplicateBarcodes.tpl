{strip}
	<div class="row">
		<div class="col-xs-12">
			<h1 id="pageTitle">{$pageTitleShort}</h1>
		</div>
	</div>
	{if isset($duplicateUsers)}
		<div class="row">
			<div class="col-xs-12">
				<div class="alert alert-info">{translate text="This tool can be used to merge barcodes that have more than one user in the database for it." isAdminFacing=true}</div>
			</div>
		</div>
		{if !empty($setupErrors)}
			<div class="row">
				<div class="col-xs-12">
					{foreach from=$setupErrors item=setupError}
						<div class="alert alert-danger">
							{$setupError}
						</div>
					{/foreach}
				</div>
			</div>
		{else}
			<div class='editor'>
				<div class="row">
					<div class="col-xs-12">
						<div class="form-group">
							<button type="submit" name="submit" id="startMergeButton" onclick="startBarcodeMerge()" class="btn btn-primary">{translate text="Start Merge Process" isAdminFacing=true}</button>
							<button type="submit" name="submit" id="stopMergeButton" onclick="stopBarcodeMerge()" class="btn btn-primary" disabled="disabled">{translate text="Stop Merge Process" isAdminFacing=true}</button>
						</div>
					</div>
				</div>
			</div>
		{/if}
		<div class="row">
			<div class="col-xs-12">
				<h2>{translate text="Barcodes with Duplicates" isAdminFacing=true}</h2>
			</div>
		</div>
		<div class="row">
			<div class="col-xs-2">
				<strong>{translate text="Barcodes" isAdminFacing=true}</strong>
			</div>
			<div class="col-xs-3">
				<strong>{translate text="Usernames" isAdminFacing=true}</strong>
			</div>
			<div class="col-xs-2">
				<strong>{translate text="Old User Id" isAdminFacing=true}</strong>
			</div>
			<div class="col-xs-2">
				<strong>{translate text="New User Id" isAdminFacing=true}</strong>
			</div>
			<div class="col-xs-3">
				<strong>{translate text="Merge Info" isAdminFacing=true}</strong>
			</div>
		</div>
		{foreach from=$duplicateUsers item=barcodeInfo}
			<div class="row barcodeRow" id="barcode_{$barcodeInfo.ils_barcode|escapeCSS}" data-barcode="{$barcodeInfo.ils_barcode}" data-processed="false">
				<div class="col-xs-2">
					{$barcodeInfo.ils_barcode}
				</div>
				<div class="col-xs-3">
					{$barcodeInfo.usernames}
				</div>
				<div class="col-xs-2 oldUserId">
{*					{$barcodeInfo.oldUserId}*}
				</div>
				<div class="col-xs-2 newUserId">
{*					{$barcodeInfo.newUserId}*}
				</div>
				<div class="col-xs-3 mergeInfo">

				</div>
			</div>
		{/foreach}
	{else}
		<p>{translate text="There are no users with duplicate barcodes" isAdminFacing=true}</p>
	{/if}
	{if isset($mergeResults)}
		<div class="row">
			<div class="col-xs-12">
				<h2>{translate text="Merge Results" isAdminFacing=true}</h2>
			</div>
			<div class="col-xs-12">
				<dl>
					<dt>{translate text="Num Users Updated with New Username" isAdminFacing=true}</dt>
					<dd>{$mergeResults.numUsersUpdated}</dd>
				</dl>
				<dl>
					<dt>{translate text="Num Users Updated and Merged" isAdminFacing=true}</dt>
					<dd>{$mergeResults.numUsersMerged}</dd>
				</dl>
				<dl>
					<dt>{translate text="Num Lists Moved" isAdminFacing=true}</dt>
					<dd>{$mergeResults.numListsMoved}</dd>
				</dl>
				<dl>
					<dt>{translate text="Num Reading History Entries Moved" isAdminFacing=true}</dt>
					<dd>{$mergeResults.numReadingHistoryEntriesMoved}</dd>
				</dl>
				<dl>
					<dt>{translate text="Num Ratings & Reviews Moved" isAdminFacing=true}</dt>
					<dd>{$mergeResults.numRatingsReviewsMoved}</dd>
				</dl>
				<dl>
					<dt>{translate text="Num Roles Moved" isAdminFacing=true}</dt>
					<dd>{$mergeResults.numRolesMoved}</dd>
				</dl>
				<dl>
					<dt>{translate text="Num Don't Show Again Moved" isAdminFacing=true}</dt>
					<dd>{$mergeResults.numNotInterestedMoved}</dd>
				</dl>
				<dl>
					<dt>{translate text="Num Linked Primary Users Moved" isAdminFacing=true}</dt>
					<dd>{$mergeResults.numLinkedPrimaryUsersMoved}</dd>
				</dl>
				<dl>
					<dt>{translate text="Num Linked Users Moved" isAdminFacing=true}</dt>
					<dd>{$mergeResults.numLinkedUsersMoved}</dd>
				</dl>
				<dl>
					<dt>{translate text="Num Saved Searches Moved" isAdminFacing=true}</dt>
					<dd>{$mergeResults.numSavedSearchesMoved}</dd>
				</dl>
				<dl>
					<dt>{translate text="Num System Message Dismissals Moved" isAdminFacing=true}</dt>
					<dd>{$mergeResults.numSystemMessageDismissalsMoved}</dd>
				</dl>
				<dl>
					<dt>{translate text="Num Placard Dismissals Moved" isAdminFacing=true}</dt>
					<dd>{$mergeResults.numPlacardDismissalsMoved}</dd>
				</dl>
				<dl>
					<dt>{translate text="Num Materials Request Created By Moved" isAdminFacing=true}</dt>
					<dd>{$mergeResults.numMaterialsRequestsAssignmentsMoved}</dd>
				</dl>
				<dl>
					<dt>{translate text="Num User Messages Moved" isAdminFacing=true}</dt>
					<dd>{$mergeResults.numUserMessagesMoved}</dd>
				</dl>
				<dl>
					<dt>{translate text="Num User Payments Moved" isAdminFacing=true}</dt>
					<dd>{$mergeResults.numUserPaymentsMoved}</dd>
				</dl>
			</div>
			{if count($mergeResults.errors) > 0}
				<div class="col-xs-12">
					<h2>Errors</h2>
				</div>
				<div class="col-xs-12">
					{foreach from=$mergeResults.errors item=error}
						<div class="alert alert-danger">
							{$error}
						</div>
					{/foreach}
				</div>
			{/if}
		</div>
	{/if}
{/strip}
{literal}
	<script type="text/javascript">
		var numProcessing = 0;
		var stopProcessing = 0;
		function startBarcodeMerge() {
			stopProcessing = 0;
			mergeBarcodes();
		}

		function mergeBarcodes() {
			$("#startMergeButton").attr("disabled", "disabled");
			$("#stopMergeButton").removeAttr("disabled")
			var allBarcodeRows = document.getElementsByClassName('barcodeRow');
			for (var i = 0; i < allBarcodeRows.length; i++) {
				var barcodeRow = allBarcodeRows[i];
				mergeBarcode(barcodeRow);
				if (numProcessing >= 5) {
					if (stopProcessing === 0) {
						setTimeout(function () {
							if (stopProcessing === 0) {
								mergeBarcodes();
							}
						}, 1000);
					}
					break;
				}
			}
			stopProcessing = 0;
		}

		function stopBarcodeMerge() {
			stopProcessing = 1;
			$("#stopMergeButton").attr("disabled", "disabled");
			$("#startMergeButton").removeAttr("disabled")
		}

		function mergeBarcode(barcodeRow) {
			if (barcodeRow.getAttribute("data-processed") === "false") {
				numProcessing++;
				var barcodeValue = barcodeRow.getAttribute("data-barcode");
				var barcodeRowId = barcodeRow.getAttribute("id");
				$("#" + barcodeRowId + " .mergeInfo").html("Processing " + barcodeValue);
				barcodeRow.setAttribute("data-processed", "true");
				var url = Globals.path + '/Greenhouse/AJAX?method=mergeBarcode&barcode=' + barcodeValue;
				$.ajax({
					url: url,
					dataType: 'json',
					success: function (data) {
						if (data.success) {
							$("#" + barcodeRowId + " .oldUserId").html(data.oldUserId);
							$("#" + barcodeRowId + " .newUserId").html(data.newUserId);
							$("#" + barcodeRowId + " .mergeInfo").html(data.message);
						}else {
							$("#" + barcodeRowId + " .mergeInfo").html(data.message);
						}
						numProcessing--;
					},
					async: true
				}).fail(function () {
					$("#" + barcodeRowId + " .mergeInfo").html("AJAX error<br/>" + Globals.requestFailedBody);
					numProcessing--;
				});
			}
		}
	</script>
{/literal}