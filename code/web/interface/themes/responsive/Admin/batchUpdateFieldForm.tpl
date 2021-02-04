<form id="batchUpdateFieldForm" class="form-horizontal" role="form">
	<div class="form-group">
		<label for="fieldSelector" class="col-xs-12">{translate text="Field to Update"}</label>
		<div class="col-xs-12">
			<select id="fieldSelector" name="fieldSelector" class="form-control" onchange="$('.batch-update-field').hide();$('#batch-update-field-' + $('#fieldSelector').val()).show()">
				<option value="">{translate text="Select a field"}</option>
				{foreach from=$batchFormatFields item=field}
					<option value="{$field.property}">{$field.label}</option>
				{/foreach}
			</select>
		</div>
	</div>

	{* Render all controls that could be updated *}
	{assign var=addFormGroupToProperty value=false}
	{foreach from=$batchFormatFields item=field}
		{assign var=property value=$field}
		<div class="row batch-update-field" id="batch-update-field-{$field.property}" style="display: none">
			<div class="col-xs-12">
				{include file="DataObjectUtil/property.tpl"}
			</div>
		</div>
	{/foreach}
    {assign var=addFormGroupToProperty value=true}

</form>