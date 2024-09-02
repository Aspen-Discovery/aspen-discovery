{if !empty($loggedIn)}
    {strip}
	{if !empty($profile->_web_note)}
		<div class="row">
			<div id="web_note" class="alert alert-info text-center col-xs-12">{$profile->_web_note}</div>
		</div>
	{/if}
	{if !empty($accountMessages)}
		{include file='systemMessages.tpl' messages=$accountMessages}
	{/if}
	{if !empty($ilsMessages)}
		{include file='ilsMessages.tpl' messages=$ilsMessages}
	{/if}
	{if !empty($updateMessage)}
		<div class="row">
			<div class="col-xs-12">
				<div class="alert alert-success">{translate text=$updateMessage isPublicFacing=true isMetadata=true}</div>
			</div>
		</div>
	{/if}

	<h1>{translate text="Library Card" isPublicFacing=true}</h1>
	<div class="row">
		<div class="col-xs-12" id="library-barcode">
			{if $libraryCardBarcodeStyle != 'none'}
				<svg class="barcode" id="library-barcode-svg">
				</svg>
			{/if}
			<div>
				{$profile->getBarcode()}
			</div>
			{if count($linkedCards) > 0}
				<div>{$profile->displayName|escape}</div>
			{/if}
			{if !empty($showCardExpirationDate) && !empty($expirationDate)}
				{translate text="Expires %1%" 1=$expirationDate|date_format:"%b %d, %Y" isPublicFacing=true}
			{/if}
			{if $showRenewalLink}
				<div class="text-center">
					<a class="btn btn-info btn-sm" href="{$cardRenewalLink}">{translate text="Renew your card" isPublicFacing=true}</a>
				</div>
			{/if}
		</div>
	</div>

	{if !empty($showAlternateLibraryCard)}
		<h1>{translate text=$alternateLibraryCardLabel isPublicFacing=true isAdminEnteredData=true}</h1>
		{if !empty($alternateLibraryCardFormMessage)}
			<div class="row">
				<div class="col-xs-12">{$alternateLibraryCardFormMessage}</div>
			</div>
		{/if}
		{if $alternateLibraryCardStyle != 'none'}
			<div class="row">
				<div class="col-xs-12 text-center" id="library-alternateLibraryCard" style="display: none">
					<svg class="barcode" id="library-alternateLibraryCard-svg">
					</svg>
				</div>
			</div>
		{/if}
		<form name="alternateLibraryCard" method="post" class="form col-xs-12">
			<div class="form-group propertyRow">
				<label for="alternateLibraryCard" class="control-label propertyRow">
					{if !empty($alternateLibraryCardLabel)}
						{translate text=$alternateLibraryCardLabel isPublicFacing=true isAdminEnteredData=true}
					{else}
						{translate text="Alternate Library Card" isPublicFacing=true isAdminEnteredData=false}
					{/if}
				</label>
				<div>
					<input type="text" name="alternateLibraryCard" id="alternateLibraryCard" value="{$profile->alternateLibraryCard}" maxlength="60" class="form-control" onchange="updateAlternateLibraryCardBarcode()">
				</div>
			</div>
			{if !empty($showAlternateLibraryCardPassword)}
				<div class="form-group propertyRow">
					<label for="alternateLibraryCardPassword" class="control-label">
						{if !empty($alternateLibraryCardPasswordLabel)}
							{translate text=$alternateLibraryCardPasswordLabel isPublicFacing=true isAdminEnteredData=true}
						{else}
							{translate text="Password/PIN" isPublicFacing=true isAdminEnteredData=false}
						{/if}
					</label>
					<div>
						<input type="password" name="alternateLibraryCardPassword" id="alternateLibraryCardPassword" value="{$profile->alternateLibraryCardPassword}"  maxlength="60" class="form-control">
					</div>
				</div>
			{/if}
			<div class="form-group">
				<div class="col-md-6 col-md-offset-3 text-center">
					<input type="submit" name="submit" value="{translate text="Update" isPublicFacing=true}" id="alternateLibraryCardFormSubmit" class="btn btn-primary">
				</div>
			</div>
		</form>
	{/if}

	{if count($linkedCards) > 0}
		<h1>{translate text='Linked cards' isPublicFacing=true}</h1>
		{foreach from=$linkedCards item=linkedCard}
			<div class="row">
				<div class="col-xs-12" id="library-barcode">
					{if $libraryCardBarcodeStyle != 'none'}
						<svg class="barcode" id="linked-barcode-svg-{$linkedCard.id}">
						</svg>
					{/if}
					<div>
						{$linkedCard.barcode}
					</div>
					<div>{$linkedCard.fullName}</div>
					{if !empty($showCardExpirationDate) && !empty($linkedCard.expirationDate)}
						{translate text="Expires %1%" 1=$linkedCard.expirationDate|date_format:"%D" isPublicFacing=true}
					{/if}
				</div>
			</div>
		{/foreach}
	{/if}

	{/strip}
	<script src="/js/jsBarcode/JsBarcode.all.min.js"></script>
	<script type="text/javascript">
		$(document).ready(
			function () {ldelim}
				$("#library-barcode-svg").JsBarcode('{$profile->getBarcode()}', {ldelim}format:'{$libraryCardBarcodeStyle}',displayValue:false{rdelim});
				{if !empty($showAlternateLibraryCard)}
				updateAlternateLibraryCardBarcode();
				{/if}
				{foreach from=$linkedCards item=linkedCard}
				$("#linked-barcode-svg-{$linkedCard.id}").JsBarcode('{$linkedCard.barcode}', {ldelim}format:'{$libraryCardBarcodeStyle}',displayValue:false{rdelim});
				{/foreach}
			{rdelim}
		);
        {if !empty($showAlternateLibraryCard)}
		function updateAlternateLibraryCardBarcode(){ldelim}
			var alternateLibraryCardVal = $("#alternateLibraryCard").val();
			var alternateLibraryCardSvg = $("#library-alternateLibraryCard-svg");
			if (alternateLibraryCardVal.length > 0){ldelim}
				{if $alternateLibraryCardStyle != 'none'}
					alternateLibraryCardSvg.JsBarcode(alternateLibraryCardVal, {ldelim}format:'{$alternateLibraryCardStyle}',displayValue:false{rdelim});
				{/if}
				$("#library-alternateLibraryCard").show();
			{rdelim}else{ldelim}
				$("#library-alternateLibraryCard").hide();
			{rdelim}
		{rdelim}
		{/if}
	</script>
{else}
	{translate text="You must sign in to view this information." isPublicFacing=true}<a href='/MyAccount/Login' class="btn btn-primary">{translate text="Sign In" isPublicFacing=true}</a>
{/if}
