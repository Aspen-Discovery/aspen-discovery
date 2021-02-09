<form id="selectFieldForm" class="form-horizontal" role="form">
	<div class="form-group">
		<label for="fieldSelector" class="col-xs-12">{translate text="Field to filter by"}</label>
		<div class="col-xs-12">
			<select id="fieldSelector" name="fieldSelector" class="form-control">
				<option value="">{translate text="Select a field"}</option>
				{foreach from=$availableFilters item=field}
					<option value="{$field.property}">{$field.label}</option>
				{/foreach}
			</select>
		</div>
	</div>
</form>