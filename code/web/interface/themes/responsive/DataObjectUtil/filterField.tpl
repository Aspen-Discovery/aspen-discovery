{strip}
{assign var=filterName value=$filterField.property}
<div class="row" id="filter_{$filterField.property}">
	<div class="col-xs-3">
		<label>{translate text=$filterField.label isAdminFacing=true}</label>
	</div>
	{if !empty($appliedFilters.$filterName)}
		{assign var=appliedFilter value=$appliedFilters.$filterName}
	{else}
		{assign var=appliedFilter value=''}
	{/if}
	{if $filterField.type == 'text' || $filterField.type == 'label'}
		<div class="col-xs-3">
			{assign var=label value='Type of filtering for '+$filterField.label}
			<select name="filterType[{$filterField.property}]" class="form-control form-control-sm filterType" aria-label="{translate text=$label inAttribute=true isAdminFacing=true}">
				<option value="contains" {if !empty($appliedFilter) && $appliedFilter.filterType == 'contains'}selected="selected"{/if}>{translate text="Contains" isAdminFacing=true}</option>
				<option value="matches" {if !empty($appliedFilter) && $appliedFilter.filterType == 'matches'}selected="selected"{/if}>{translate text="Matches" isAdminFacing=true}</option>
				<option value="startsWith" {if !empty($appliedFilter) && $appliedFilter.filterType == 'startsWith'}selected="selected"{/if}>{translate text="Starts With" isAdminFacing=true}</option>
			</select>
		</div>
		<div class="col-xs-5">
			<input type="text" name="filterValue[{$filterField.property}]" class="form-control form-control-sm filterValue" aria-label="Filtering for {$filterField.label|escape:css}" {if !empty($appliedFilter)}value="{$appliedFilter.filterValue}"{/if}/>
		</div>
	{elseif $filterField.type == 'timestamp'}
		<div class="col-xs-3">
			{assign var=label value='Type of filtering for '+$filterField.label}
			<select name="filterType[{$filterField.property}]" id="filterType_{$filterField.property}" class="form-control form-control-sm filterType" aria-label="{translate text=$label inAttribute=true isAdminFacing=true}" onchange="AspenDiscovery.Admin.setDateFilterFieldVisibility('{$filterField.property}')">
				<option value="afterTime" {if !empty($appliedFilter) && $appliedFilter.filterType == 'afterTime'}selected="selected"{/if}>{translate text="After" isAdminFacing=true}</option>
				<option value="beforeTime" {if !empty($appliedFilter) && $appliedFilter.filterType == 'beforeTime'}selected="selected"{/if}>{translate text="Before" isAdminFacing=true}</option>
				<option value="betweenTimes" {if !empty($appliedFilter) && $appliedFilter.filterType == 'betweenTimes'}selected="selected"{/if}>{translate text="Between" isAdminFacing=true}</option>
			</select>
		</div>
		<div class="col-xs-5">
			{assign var=label value='Filtering for '+$filterField.label}
			<input type="text" name="filterValue[{$filterField.property}]" id="filterValue_{$filterField.property}" class="form-control form-control-sm filterValue" aria-label="" {if !empty($appliedFilter)}value="{$appliedFilter.filterValue|date_format:"%Y-%m-%d %H:%M"}"{/if}/>
			<input type="text" name="filterValue2[{$filterField.property}]" id="filterValue2_{$filterField.property}" class="form-control form-control-sm filterValue" aria-label="" {if !empty($appliedFilter)}value="{$appliedFilter.filterValue2|date_format:"%Y-%m-%d %H:%M"}"{/if}/>
			<script type="text/javascript">
				$(document).ready(function(){ldelim}
					rome(filterValue_{$filterField.property});
					rome(filterValue2_{$filterField.property});
					AspenDiscovery.Admin.setDateFilterFieldVisibility('{$filterField.property}');
				{rdelim});
			</script>
		</div>
	{elseif $filterField.type == 'checkbox'}
		<div class="col-xs-8">
			{assign var=label value='Type of filtering for '+$filterField.label}
			<input type="hidden" name="filterType[{$filterField.property}]" id="filterType_{$filterField.property}" value="matches"/>
			<select name="filterValue[{$filterField.property}]" class="form-control form-control-sm filterType" aria-label="{translate text=$label inAttribute=true isAdminFacing=true}">
				<option value="1" {if !empty($appliedFilter) && $appliedFilter.filterValue == '1'}selected="selected"{/if}>{translate text="Selected" isAdminFacing=true}</option>
				<option value="0" {if !empty($appliedFilter) && $appliedFilter.filterValue == '0'}selected="selected"{/if}>{translate text="Deselected" isAdminFacing=true}</option>
			</select>
		</div>
	{elseif $filterField.type == 'enum'}
		<div class="col-xs-8">
			<input type="hidden" name="filterType[{$filterField.property}]" id="filterType_{$filterField.property}" value="matches"/>
			{assign var=label value='Filtering for '+$filterField.label}
			<select name="filterValue[{$filterField.property}]" class="form-control form-control-sm filterType" aria-label="{translate text=$label inAttribute=true isAdminFacing=true}">
				{foreach from=$filterField.values item=propertyName key=propertyValue}
					<option value="{$propertyValue}" {if !empty($appliedFilter) && $appliedFilter.filterValue == $propertyValue}selected="selected"{/if}>{if !empty($property.translateValues)}{translate text=$propertyName inAttribute=true isPublicFacing=$property.isPublicFacing isAdminFacing=$property.isAdminFacing }{else}{$propertyName}{/if}</option>
				{/foreach}
			</select>
		</div>
	{else}
		<div class="col-xs-8">
			{translate text="Unhandled filter type %1%" 1=$filterField.type isAdminFacing=true}
		</div>
	{/if}
	<div class="col-xs-1 text-right">
		<button class="btn btn-sm btn-danger" onclick="$('#filter_{$filterField.property}').remove();return false;" aria-label="{translate text="Delete" isAdminFacing=true}"><i class="fas fa-sm fa-trash-alt"></i></button>
	</div>
</div>
{/strip}