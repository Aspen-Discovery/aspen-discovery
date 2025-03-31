{strip}
	<div class="row">
		<div class="col-xs-12">
			<h1 id="pageTitle">{$pageTitleShort}</h1>
		</div>
	</div>
	{if isset($results)}
		<div class="row">
			<div class="col-xs-12">
				<div class="alert {if !empty($results.success)}alert-success{else}alert-danger{/if}">
					{$results.message}
				</div>
			</div>
		</div>
	{/if}
	<form id="sipTestForm" method="get" role="form">
		<div class='editor'>
			<div class="form-group">
				<label for="sipHost" class="control-label">{translate text='SIP Host' isAdminFacing=true}</label>
				<input type="text" id="sipHost" name="sipHost" class="form-control" value="{if !empty($sipHost)}{$sipHost}{/if}">
			</div>
			<div class="form-group">
				<label for="sipPort" class="control-label">{translate text='SIP Port' isAdminFacing=true}</label>
				<input type="text" id="sipPort" name="sipPort" class="form-control" value="{if !empty($sipPort)}{$sipPort}{/if}">
			</div>
			<div class="form-group">
				<label for="sipUser" class="control-label">{translate text='SIP User' isAdminFacing=true}</label>
				<input type="text" id="sipUser" name="sipUser" class="form-control" value="{if !empty($sipUser)}{$sipUser}{/if}">
			</div>
			<div class="form-group">
				<label for="sipPassword" class="control-label">{translate text='SIP Password' isAdminFacing=true}</label>
				<input type="text" id="sipPassword" name="sipPassword" class="form-control" value="{if !empty($sipPassword)}{$sipPassword}{/if}">
			</div>
			<div class="form-group">
				<label for="patronBarcode" class="control-label">{translate text='Patron Barcode' isAdminFacing=true}</label>
				<input type="text" id="patronBarcode" name="patronBarcode" class="form-control" value="{if !empty($patronBarcode)}{$patronBarcode}{/if}">
			</div>
			<div class="form-group">
				<label for="patronPin" class="control-label">{translate text='Patron PIN' isAdminFacing=true}</label>
				<input type="text" id="patronPin" name="patronPin" class="form-control" value="{if !empty($patronPin)}{$patronPin}{/if}">
			</div>


			<div class="form-group">
				<button type="submit" id="testConnection" name="testConnection" class="btn btn-primary">{translate text="Test Connection" isAdminFacing=true}</button>
			</div>
		</div>
	</form>
{/strip}
