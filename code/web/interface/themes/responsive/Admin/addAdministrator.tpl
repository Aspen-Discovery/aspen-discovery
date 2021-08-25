{strip}
<div id="main-content" class="col-tn-12 col-xs-12">
	{if !empty($error)}
		<div class="alert alert-danger">{$error}</div>
	{/if}
	<h1>{translate text='Setup a new administrator' isAdminFacing=true}</h1>
	<form name="addAdministrator" method="post" enctype="multipart/form-data" class="form-horizontal">
		<fieldset>
			<input type="hidden" name="objectAction" value="processNewAdministrator">
			<div class="row form-group">
				<label for="login" class="col-sm-2 control-label">{translate text='Barcode' isAdminFacing=true}</label>
				<div class="col-sm-10">
					<input type="text" name="login" id="login" class="form-control">
				</div>
			</div>
			<div class="alert alert-info">{translate text="Enter the barcode for the user who should be given administration privileges" isAdminFacing=true}</div>

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
					<input type="submit" name="submit" value="{translate text="Update User" inAttribute=true isAdminFacing=true}" class="btn btn-primary">  <a href='/Admin/{$toolName}?objectAction=list' class="btn btn-default">{translate text="Return to List" isAdminFacing=true}</a>
				</div>
			</div>
		</fieldset>
	</form>
</div>
{/strip}