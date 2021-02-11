<div class="row">
	<div class="col-xs-12 col-md-9">
		<h1 id="pageTitle">{$pageTitleShort}</h1>
	</div>
	<div class="col-xs-12 col-md-3 help-link">
        {if $instructions}<a href="{$instructions}"><img src="/images/silk/help.png" alt="Help" /> Documentation</a>{/if}
	</div>
</div>

{if $lastError}
	<div class="alert alert-danger">
		{$lastError}
	</div>
{/if}

{if $canCompare || $canAddNew || $canBatchUpdate || $canFilter || !empty($customListActions)}
<form action="" method="get" id='propertiesListForm' class="form-inline">
{/if}
	{if $canSort && count($sortableFields) > 0}
		<div class="row">
			<div class="col-xs-12">
				<label for="sort">{translate text='Sort by'}</label>
				<select name="sort" id="sort" onchange="return AspenDiscovery.changeSort();" class="form-control form-control-sm">
					{foreach from=$sortableFields item=field}
						{capture assign=fieldValueAsc}{$field.property} asc{/capture}
                        {capture assign=fieldValueDesc}{$field.property} desc{/capture}
						<option value="{$fieldValueAsc}" {if $fieldValueAsc == $sort}selected="selected"{/if}>{$field.label} {translate text="Ascending"}</option>
						<option value="{$fieldValueDesc}" {if $fieldValueDesc == $sort}selected="selected"{/if}>{$field.label} {translate text="Descending"}</option>
					{/foreach}
				</select>
			</div>
		</div>
	{/if}
	{if $canFilter}
		<div id="filtersList" class="">
			<div id="filters-accordion" class="panel-group">
				<div class="panel {if count($appliedFilters) > 0}active{/if}" id="filtersPanel">
					<a data-toggle="collapse" href="#filtersPanelBody">
						<div class="panel-heading">
							<div class="panel-title">
								{translate text="Filters"}
							</div>
						</div>
					</a>

					<div id="filtersPanelBody" class="panel-collapse collapse {if count($appliedFilters) > 0}in{/if}">
						<div class="panel-body">
							<div id="activeFilters">
								{foreach from=$appliedFilters key=filterName item=appliedFilter}
									{include file='DataObjectUtil/filterField.tpl' filterField=$appliedFilter.field}
								{/foreach}
							</div>
							<div id="filterActions">
								<div class="row">
									<div class="col-tn-5 col-xs-3"><button class="btn btn-default btn-sm" onclick="return AspenDiscovery.Admin.addFilterRow('{$module}', '{$toolName}');">{translate text="Add Filter"}</button></div>
									<div class="col-tn-5 col-xs-3 col-tn-offset-2 col-xs-offset-6 text-right"><button class="btn btn-default btn-sm" onclick="$('#objectAction').val('list');$('#propertiesListForm').submit();">{translate text="Apply Filters"}</button></div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	{/if}

	<div class='adminTableRegion'>
		<table class="adminTable table table-striped table-condensed smallText table-sticky" id="adminTable" aria-label="List of Objects">
			<thead>
				<tr>
					{if $canCompare || $canBatchUpdate}
						<th>{translate text='Select'}</th>
					{/if}
					{foreach from=$structure item=property key=id}
						{if !isset($property.hideInLists) || $property.hideInLists == false}
						<th><span title='{$property.description}'>{$property.label|translate}</span></th>
						{/if}
					{/foreach}
					<th>{translate text='Actions'}</th>
				</tr>
			</thead>
			<tbody>
				{if isset($dataList) && is_array($dataList)}
					{foreach from=$dataList item=dataItem key=id}
					<tr class='{cycle values="odd,even"} {$dataItem->class}'>
						{if $canCompare || $canBatchUpdate}
							<td><input type="checkbox" class="selectedObject" name="selectedObject[{$id}]" aria-label="Select Item {$id}"> </td>
						{/if}
						{foreach from=$structure item=property}
							{assign var=propName value=$property.property}
							{assign var=propValue value=$dataItem->$propName}

							{if !isset($property.hideInLists) || $property.hideInLists == false}
								<td aria-label="{$dataItem} {$propName}{if empty($propValue)} - empty{/if}">
								{if $property.type == 'label'}
									{if $dataItem->class != 'objectDeleted'}
										{if $propName == $dataItem->getPrimaryKey()}<a href='/{$module}/{$toolName}?objectAction=edit&amp;id={$id}'>{/if}
										{$propValue}
										{if $propName == $dataItem->getPrimaryKey()}</a>{/if}
									{/if}
								{elseif $property.type == 'regularExpression'}
									{$propValue|escape}
								{elseif $property.type == 'text' || $property.type == 'hidden' || $property.type == 'file' || $property.type == 'integer' || $property.type == 'email' || $property.type == 'url'}
									{$propValue}
								{elseif $property.type == 'date'}
									{$propValue|date_format}
								{elseif $property.type == 'timestamp'}
									{if $propValue == 0}
										{if empty($property.unsetLabel)}
											{translate text="Never"}
										{else}
											{translate text=$property.unsetLabel}
										{/if}
									{else}
										{$propValue|date_format:"%D %T"}
									{/if}
								{elseif $property.type == 'partialDate'}
									{assign var=propNameMonth value=$property.propNameMonth}
									{assign var=propMonthValue value=$dataItem->$propNameMonth}
									{assign var=propNameDay value=$property.propNameDay}
									{assign var=propDayValue value=$dataItem->$propDayValue}
									{assign var=propNameYear value=$property.propNameYear}
									{assign var=propYearValue value=$dataItem->$propNameYear}
									{if $propMonthValue}$propMonthValue{else}??{/if}/{if $propDayValue}$propDayValue{else}??{/if}/{if $propYearValue}$propYearValue{else}??{/if}
								{elseif $property.type == 'currency'}
									{assign var=propDisplayFormat value=$property.displayFormat}
									${$propValue|string_format:$propDisplayFormat}
								{elseif $property.type == 'enum'}
									{foreach from=$property.values item=propertyName key=propertyValue}
										{if $propValue == $propertyValue}{$propertyName}{/if}
									{/foreach}
								{elseif $property.type == 'multiSelect'}
									{if is_array($propValue) && count($propValue) > 0}
										{foreach from=$property.values item=propertyName key=propertyValue}
											{if array_key_exists($propertyValue, $propValue)}{$propertyName}<br/>{/if}
										{/foreach}
									{else}
										No values selected
									{/if}
								{elseif $property.type == 'oneToMany'}
									{if is_array($propValue) && count($propValue) > 0}
										{$propValue|@count}
									{else}
										Not set
									{/if}
								{elseif $property.type == 'checkbox'}
									{if ($propValue == 1)}Yes{else}No{/if}
								{elseif $property.type == 'image'}
									<img src="{$property.displayUrl}{$dataItem->id}" class="img-responsive" alt="{$propName}">
								{else}
									Unknown type to display {$property.type}
								{/if}
								</td>
							{/if}
						{/foreach}
						{if $dataItem->class != 'objectDeleted'}
							<td>
								<div class="btn-group-vertical">
								<a href='/{$module}/{$toolName}?objectAction=edit&amp;id={$id}' class="btn btn-default btn-sm" aria-label="Edit Item {$id}">{translate text="Edit"}</a>
								<a href='/{$module}/{$toolName}?objectAction=history&amp;id={$id}' class="btn btn-default btn-sm" aria-label="History for Item {$id}">{translate text="History"}</a>
								{if $additionalActions}
									{foreach from=$additionalActions item=action}
										<a href='{$action.path}&amp;id={$id}' class="btn btn-default btn-sm" aria-label="{$action.name} for Item {$id}">{$action.name|translate}</a>
									{/foreach}
								{/if}
								</div>
							</td>
						{/if}
					</tr>
					{/foreach}
			{/if}
			</tbody>
		</table>
	</div>

	{if $pageLinks.all}<div class="text-center">{$pageLinks.all}</div>{/if}

	<input type='hidden' name='objectAction' id='objectAction' value='' />
	{if $canCompare}
		<div class="btn-group">
			<button type='submit' value='compare' class="btn btn-default" onclick="$('#objectAction').val('compare');return AspenDiscovery.Admin.validateCompare();">{translate text='Compare'}</button>
		</div>
	{/if}
	{if $canBatchUpdate}
		<div class="btn-group">
			<button type='submit' value='batchUpdate' class="btn btn-default" onclick="return AspenDiscovery.Admin.showBatchUpdateFieldForm('{$module}', '{$toolName}', 'selected')">{translate text='Batch Update Selected'}</button>
		</div>
		<div class="btn-group">
			<button type='submit' value='batchUpdate' class="btn btn-default" onclick="return AspenDiscovery.Admin.showBatchUpdateFieldForm('{$module}', '{$toolName}', 'all')">{translate text='Batch Update All'}</button>
		</div>
	{/if}
	{if $canAddNew}
		<div class="btn-group">
			<button type='submit' value='addNew' class="btn btn-primary" onclick="$('#objectAction').val('addNew')">{translate text='Add New'}</button>
		</div>
	{/if}
	<div class="btn-group">
		{foreach from=$customListActions item=customAction}
			<button type='submit' value='{$customAction.action}' class="btn btn-default" onclick="$('#objectAction').val('{$customAction.action}')">{$customAction.label}</button>
		{/foreach}
	</div>
{if $canCompare || $canAddNew || $canBatchUpdate || $canFilter|| !empty($customListActions)}
</form>
{/if}

{*{if isset($dataList) && is_array($dataList) && count($dataList) > 5}*}
{*<script type="text/javascript">*}
{*	{literal}*}
{*	$("#adminTable").tablesorter({cssAsc: 'sortAscHeader', cssDesc: 'sortDescHeader', cssHeader: 'unsortedHeader', widgets:['zebra', 'filter'] });*}
{*	{/literal}*}
{*</script>*}
{*{/if}*}
