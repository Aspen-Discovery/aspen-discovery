{strip}
<div id="main-content" class="col-tn-12 col-xs-12">
	<h1>{translate text='Setup a new administrator' isAdminFacing=true}</h1>
	{if !empty($errors)}
		{foreach from=$errors item=error key=barcode}
			<div class="alert alert-danger">{$barcode}: {$error}</div>
		{/foreach}
	{/if}
	{if !empty($updateMessage)}
		<div class="alert {if !empty($updateMessageIsError)}alert-danger{else}alert-info{/if}">
			{$updateMessage}
		</div>
	{/if}
	<form name="addAdministrator" method="post" enctype="multipart/form-data" class="form-horizontal">
		<fieldset>
			<input type="hidden" name="objectAction" value="processNewAdministrator">
			<div class="row form-group">
				<label for="login" class="col-sm-2 control-label">{translate text='Barcode(s)' isAdminFacing=true}</label>
				<div class="col-sm-10">
					<textarea name="login" id="login" class="form-control"></textarea>
				</div>
			</div>
			<div class="alert alert-info">{translate text="Enter the barcode(s) for the user who should be given administration privileges.  To create multiple administrators at once, enter each barcode on it's own line." isAdminFacing=true}</div>
			{if $ils == 'Evergreen'}
				<div class="alert alert-warning">{translate text="Evergreen does not support lookups of patrons by barcode without the password.  Administrators must login prior to granting them Admin access." isAdminFacing=true}</div>
			{/if}

			<div class="form-group">
				{assign var=property value=$structure.roles}
				{assign var=propName value=$property.property}
				<label for='{$propName}' class="control-label">{translate text="Roles" isAdminFacing=true}</label>
				<div class="controls">
					{* Display the list of roles to add *}
					{if isset($property.listStyle) && $property.listStyle == 'checkbox'}
						{foreach from=$property.values item=propertyName key=propertyValue}
							<label class="checkbox">
								<input name='{$propName}[{$propertyValue}]' type="checkbox" value='{$propertyValue}' {if is_array($propValue) && in_array($propertyValue, array_keys($propValue))}checked="checked"{/if} >{$propertyName}
							</label>
						{/foreach}
					{else}
						<select name='{$propName}' id="{$propName}" multiple="multiple">
						{foreach from=$property.values item=propertyName key=propertyValue}
							<option value='{$propertyValue}' {if $propValue == $propertyValue}selected="selected"{/if}>{$propertyName}</option>
						{/foreach}
						</select>
					{/if}
				</div>
			</div>
			<div class="form-group">
				<div class="controls">
					<input type="submit" name="submit" value="{translate text="Update User" inAttribute=true isAdminFacing=true}" class="btn btn-primary">  <a href='/Admin/{$toolName}?objectAction=list' class="btn btn-default"><i class="fas fa-arrow-alt-circle-left"></i> {translate text="Return to List" isAdminFacing=true}</a>
				</div>
			</div>
		</fieldset>
	</form>
</div>
{/strip}