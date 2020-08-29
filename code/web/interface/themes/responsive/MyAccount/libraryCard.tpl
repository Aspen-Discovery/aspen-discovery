{if $loggedIn}
    {strip}
	{if !empty($profile->_web_note)}
		<div class="row">
			<div id="web_note" class="alert alert-info text-center col-xs-12">{$profile->_web_note}</div>
		</div>
	{/if}

	<h1>{translate text="Library Card"}</h1>
	<div class="row">
		<div class="col-xs-12" id="library-barcode">
			{if $libraryCardBarcodeStyle != 'none'}
				<svg class="barcode" id="library-barcode-svg">
				</svg>
			{/if}
			<div>
				{$profile->getBarcode()}
			</div>
		</div>
	</div>

	{if $showAlternateLibraryCard}
		<h1>{$alternateLibraryCardLabel|translate}</h1>
		{if $alternateLibraryCardStyle != 'none'}
			<div class="row">
				<div class="col-xs-12 text-center" id="library-alternateLibraryCard" style="display: none">
					<svg class="barcode" id="library-alternateLibraryCard-svg">
					</svg>
				</div>
			</div>
		{/if}
		<form name="alternateLibraryCard" method="post" class="form-horizontal">
			<div class="form-group">
				<label for="alternateLibraryCard" class="control-label col-xs-12 col-sm-4">{$alternateLibraryCardLabel|translate} </label>
				<div class="col-xs-12 col-sm-8">
					<input type="text" name="alternateLibraryCard" id="alternateLibraryCard" value="{$user->alternateLibraryCard}" maxlength="60" class="form-control" onchange="updateAlternateLibraryCardBarcode()">
				</div>
			</div>
			{if $showAlternateLibraryCardPassword}
				<div class="form-group">
					<label for="alternateLibraryCardPassword" class="control-label col-xs-12 col-sm-4">{$alternateLibraryCardPasswordLabel|translate} </label>
					<div class="col-xs-12 col-sm-8">
						<input type="password" name="alternateLibraryCardPassword" id="alternateLibraryCardPassword" value="{$user->alternateLibraryCardPassword}"  maxlength="60" class="form-control">
					</div>
				</div>
			{/if}
			<div class="form-group">
				<div class="col-xs-12 col-sm-offset-4 col-sm-8">
					<input type="submit" name="submit" value="Update" id="alternateLibraryCardFormSubmit" class="btn btn-primary">
				</div>
			</div>
		</form>
	{/if}
	{/strip}
	<script src="/js/jsBarcode/JsBarcode.all.min.js"></script>
	<script type="text/javascript">
		$(document).ready(
			function () {ldelim}
				$("#library-barcode-svg").JsBarcode('{$profile->getBarcode()}', {ldelim}format:'{$libraryCardBarcodeStyle}',displayValue:false{rdelim});
				{if $showAlternateLibraryCard}
				updateAlternateLibraryCardBarcode();
				{/if}
			{rdelim}
		);
        {if $showAlternateLibraryCard}
		function updateAlternateLibraryCardBarcode(){ldelim}
			let alternateLibraryCardVal = $("#alternateLibraryCard").val();
			let alternateLibraryCardSvg = $("#library-alternateLibraryCard-svg");
			if (alternateLibraryCardVal.length > 0){ldelim}
				alternateLibraryCardSvg.JsBarcode(alternateLibraryCardVal, {ldelim}format:'{$alternateLibraryCardStyle}',displayValue:false{rdelim});
				$("#library-alternateLibraryCard").show();
			{rdelim}else{ldelim}
				$("#library-alternateLibraryCard").hide();
			{rdelim}
		{rdelim}
		{/if}
	</script>
{else}
	{translate text="login_to_view_account_notice" defaultText="You must login to view this information. Click <a href="/MyAccount/Login">here</a> to login."}
{/if}
