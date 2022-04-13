<div class="row">
	<div class="col-xs-12 col-md-9">
		<h1 id="pageTitle">{$pageTitleShort}</h1>
	</div>
	<div class="col-xs-12 col-md-3 help-link">
        {if $instructions}<a href="{$instructions}"><i class="fas fa-question-circle"></i>&nbsp;{translate text="Documentation" isAdminFacing=true}</a>{/if}
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
				<label for="sort">{translate text='Sort by' isAdminFacing=true}</label>
				<select name="sort" id="sort" onchange="return AspenDiscovery.changeSort();" class="form-control form-control-sm">
					{foreach from=$sortableFields item=field}
						{capture assign=fieldValueAsc}{$field.property} asc{/capture}
                        {capture assign=fieldValueDesc}{$field.property} desc{/capture}
						<option value="{$fieldValueAsc}" {if $fieldValueAsc == $sort}selected="selected"{/if}>{translate text="%1% Ascending" 1=$field.label translateParameters=true isAdminFacing=true}</option>
						<option value="{$fieldValueDesc}" {if $fieldValueDesc == $sort}selected="selected"{/if}>{translate text="%1% Descending" 1=$field.label translateParameters=true  isAdminFacing=true}</option>
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
								{translate text="Filters" isAdminFacing=true}
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
									<div class="col-tn-5 col-xs-3"><button class="btn btn-default btn-sm" onclick="return AspenDiscovery.Admin.addFilterRow('{$module}', '{$toolName}');"><i class="fas fa-plus"></i> {translate text="Add Filter" isAdminFacing=true}</button></div>
									<div class="col-tn-5 col-xs-3 col-tn-offset-2 col-xs-offset-6 text-right"><button class="btn btn-default btn-sm" onclick="$('#objectAction').val('list');$('#propertiesListForm').submit();"><i class="fas fa-filter"></i> {translate text="Apply Filters" isAdminFacing=true}</button></div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	{/if}

	<div class='adminTableRegion fixed-height-table'>
		<table class="adminTable table table-striped table-condensed smallText table-sticky" id="adminTable" aria-label="List of Objects">
			<thead>
				<tr>
					{if $canCompare || $canBatchUpdate}
						<th>{translate text='Select' isAdminFacing=true}</th>
					{/if}
					{foreach from=$structure item=property key=id}
						{if !isset($property.hideInLists) || $property.hideInLists == false}
						<th><span title='{$property.description}'>{translate text=$property.label isAdminFacing=true}</span></th>
						{/if}
					{/foreach}
					<th>{translate text='Actions' isAdminFacing=true}</th>
				</tr>
			</thead>
			<tbody>
				{if isset($dataList) && is_array($dataList)}
					{foreach from=$dataList item=dataItem key=id}
						{assign var=canEdit value=$dataItem->canActiveUserEdit()}
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
										{if $dataItem->canActiveUserEdit()}
											{if $propName == $dataItem->getPrimaryKey()}<a class="btn btn-default btn-sm" href='/{$module}/{$toolName}?objectAction=edit&amp;id={$id}'>
											<i class="fas fa-pencil-alt fa-xs" style="padding-right: .5em"></i>{/if}
											{$propValue}
											{if $propName == $dataItem->getPrimaryKey()}</a>{/if}
										{else}
											{$propValue}
										{/if}
									{/if}
								{elseif $property.type == 'regularExpression' || $property.type =='multilineRegularExpression'}
									{$propValue|escape}
								{elseif $property.type == 'text' || $property.type == 'hidden' || $property.type == 'file' || $property.type == 'integer' || $property.type == 'email' || $property.type == 'url'}
									{$propValue}
								{elseif $property.type == 'date'}
									{$propValue|date_format}
								{elseif $property.type == 'timestamp'}
									{if $propValue == 0}
										{if empty($property.unsetLabel)}
											{translate text="Never" isAdminFacing=true}
										{else}
											{translate text=$property.unsetLabel isAdminFacing=true}
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
									{if ($propValue == 1)}{translate text="Yes" isAdminFacing=true}{elseif ($propValue == 0)}{translate text="No" isAdminFacing=true}{else}{$propValue}{/if}
								{elseif $property.type == 'image'}
									<img src="{$property.displayUrl}{$dataItem->id}" class="img-responsive" alt="{$propName}">
								{elseif $property.type == 'textarea'}
									{$propValue|truncate:255:'...'}
								{else}
									{translate text="Unknown type to display %1%" 1=$property.type isAdminFacing=true}
								{/if}
								</td>
							{/if}
						{/foreach}
						{if $dataItem->class != 'objectDeleted'}
							<td>
								<div class="btn-group-vertical">
								{if $dataItem->canActiveUserEdit()}
									<a href='/{$module}/{$toolName}?objectAction=edit&amp;id={$id}' class="btn btn-default btn-sm" aria-label="Edit Item {$id}"><i class="fas fa-pencil-alt"></i> {translate text="Edit" isAdminFacing=true}</a>
								{/if}
								{if $dataItem->getAdditionalListActions()}
									{foreach from=$dataItem->getAdditionalListActions() item=action}
										<a href='{$action.url}' class="btn btn-default btn-sm" aria-label="{$action.text} for Item {$id}">{translate text=$action.text isAdminFacing=true}</a>
									{/foreach}
								{/if}
								{if $dataItem->canActiveUserEdit()}
									<a href='/{$module}/{$toolName}?objectAction=history&amp;id={$id}' class="btn btn-default btn-sm" aria-label="History for Item {$id}"><i class="fas fa-history"></i> {translate text="History" isAdminFacing=true}</a>
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
			<button type='submit' value='compare' class="btn btn-default" onclick="$('#objectAction').val('compare');return AspenDiscovery.Admin.validateCompare();">{translate text='Compare' isAdminFacing=true}</button>
		</div>
	{/if}
	{if $canBatchUpdate}
		<div class="btn-group">
			<button type='submit' value='batchUpdate' class="btn btn-default" onclick="return AspenDiscovery.Admin.showBatchUpdateFieldForm('{$module}', '{$toolName}', 'selected')">{translate text='Batch Update Selected' isAdminFacing=true}</button>
		</div>
		<div class="btn-group">
			<button type='submit' value='batchUpdate' class="btn btn-default" onclick="return AspenDiscovery.Admin.showBatchUpdateFieldForm('{$module}', '{$toolName}', 'all')">{translate text='Batch Update All' isAdminFacing=true}</button>
		</div>
	{/if}
	{if $canAddNew}
		<div class="btn-group">
			<button type='submit' value='addNew' class="btn btn-primary" onclick="$('#objectAction').val('addNew')"><i class="fas fa-plus"></i> {translate text='Add New' isAdminFacing=true}</button>
		</div>
	{/if}
	<div class="btn-group">
		{foreach from=$customListActions item=customAction}
			<button type='submit' value='{$customAction.action}' class="btn btn-default" onclick="$('#objectAction').val('{$customAction.action}')">{translate text=$customAction.label isAdminFacing=true}</button>
		{/foreach}
	</div>
{if $canCompare || $canAddNew || $canBatchUpdate || $canFilter|| !empty($customListActions)}
</form>
{/if}

{if $showQuickFilterOnPropertiesList && isset($dataList) && is_array($dataList) && count($dataList) > 5}
<script type="text/javascript">
	{literal}
	$("#adminTable").tablesorter({cssAsc: 'sortAscHeader', cssDesc: 'sortDescHeader', cssHeader: 'unsortedHeader', widgets:['zebra', 'filter'] });
	{/literal}
</script>
{/if}
