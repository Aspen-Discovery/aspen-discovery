{strip}
{assign var=filterName value=$filterField.property}
<div class="row" id="filter_{$filterField.property}">
	<div class="col-xs-3">
		<label>{$filterField.label}</label>
	</div>
	{if !empty($appliedFilters.$filterName)}
		{assign var=appliedFilter value=$appliedFilters.$filterName}
	{else}
		{assign var=appliedFilter value=''}
	{/if}
	{if $filterField.type == 'text' || $filterField.type == 'label'}
		<div class="col-xs-3">
			<select name="filterType[{$filterField.property}]" class="form-control form-control-sm filterType" aria-label="Type of filtering for {$filterField.label|escape:css}">
				<option value="contains" {if !empty($appliedFilter) && $appliedFilter.filterType == 'contains'}selected="selected"{/if}>{translate text="Contains"}</option>
				<option value="matches" {if !empty($appliedFilter) && $appliedFilter.filterType == 'matches'}selected="selected"{/if}>{translate text="Matches"}</option>
				<option value="startsWith" {if !empty($appliedFilter) && $appliedFilter.filterType == 'startsWith'}selected="selected"{/if}>{translate text="Starts With"}</option>
			</select>
		</div>
		<div class="col-xs-5">
			<input type="text" name="filterValue[{$filterField.property}]" class="form-control form-control-sm filterValue" aria-label="Filtering for {$filterField.label|escape:css}" {if !empty($appliedFilter)}value="{$appliedFilter.filterValue}"{/if}/>
		</div>
	{elseif $filterField.type == 'timestamp'}
		<div class="col-xs-3">
			<select name="filterType[{$filterField.property}]" class="form-control form-control-sm filterType" aria-label="Type of filtering for {$filterField.label|escape:css}">
				<option value="afterTime" {if !empty($appliedFilter) && $appliedFilter.filterType == 'afterTime'}selected="selected"{/if}>{translate text="After"}</option>
				<option value="beforeTime" {if !empty($appliedFilter) && $appliedFilter.filterType == 'beforeTime'}selected="selected"{/if}>{translate text="Before"}</option>
			</select>
		</div>
		<div class="col-xs-5">
			<input type="text" name="filterValue[{$filterField.property}]" id="filterValue_{$filterField.property}" class="form-control form-control-sm filterValue" aria-label="Filtering for {$filterField.label|escape:css}" {if !empty($appliedFilter)}value="{$appliedFilter.filterValue|date_format:"%Y-%m-%d %H:%M"}"{/if}/>
			<script type="text/javascript">
				$(document).ready(function(){ldelim}
					rome(filterValue_{$filterField.property});
				{rdelim});
			</script>
		</div>
	{elseif $filterField.type == 'checkbox'}
		<div class="col-xs-8">
			<input type="hidden" name="filterType[{$filterField.property}]" id="filterType_{$filterField.property}" value="matches"/>
			<select name="filterValue[{$filterField.property}]" class="form-control form-control-sm filterType" aria-label="Type of filtering for {$filterField.label|escape:css}">
				<option value="1" {if !empty($appliedFilter) && $appliedFilter.filterValue == '1'}selected="selected"{/if}>{translate text="Selected"}</option>
				<option value="0" {if !empty($appliedFilter) && $appliedFilter.filterValue == '0'}selected="selected"{/if}>{translate text="Deselected"}</option>
			</select>
		</div>
	{elseif $filterField.type == 'enum'}
		<div class="col-xs-8">
			<input type="hidden" name="filterType[{$filterField.property}]" id="filterType_{$filterField.property}" value="matches"/>
			<select name="filterValue[{$filterField.property}]" class="form-control form-control-sm filterType" aria-label="Type of filtering for {$filterField.label|escape:css}">
				{foreach from=$filterField.values item=propertyName key=propertyValue}
					<option value="{$propertyValue}" {if !empty($appliedFilter) && $appliedFilter.filterValue == $propertyValue}selected="selected"{/if}>{$propertyName}</option>
				{/foreach}
			</select>
		</div>
	{else}
		<div class="col-xs-8">
			&nbsp;Unhandled filter type {$filterField.type}
		</div>
	{/if}
	<div class="col-xs-1 text-right">
		<button class="btn btn-sm btn-danger" onclick="$('#filter_{$filterField.property}').remove();return false;" aria-label="{translate text="Delete"}"><i class="fas fa-sm fa-trash-alt"></i></button>
	</div>
</div>
{/strip}