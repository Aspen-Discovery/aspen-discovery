{if isset($propertiesListWarningMessage) && !empty($propertiesListWarningMessage)}
	<div class="alert alert-warning" role="alert">
		{$propertiesListWarningMessage nofilter} {* Use nofilter as the message contains HTML. *}
	</div>
{/if}

<div class="row">
	<div class="col-xs-12 col-md-9">
		<h1 id="pageTitle">{$pageTitleShort}</h1>
	</div>
	<div class="col-xs-12 col-md-3 help-link">
		{if !empty($instructions)}<a href="{$instructions}" target="_blank"><i class="fas fa-question-circle" role="presentation"></i>&nbsp;{translate text="Documentation" isAdminFacing=true}</a>{/if}
	</div>
</div>

{if !empty($updateMessage)}
	<div class="alert {if !empty($updateMessageIsError)}alert-danger{else}alert-info{/if}">
		{$updateMessage}
	</div>
{/if}

{if $canCompare || $canAddNew || $canBatchUpdate || $canFilter || !empty($customListActions) || $canBatchDelete || $canFetchFromCommunity}
<form action="" method="get" id='propertiesListForm' class="form-inline">
{/if}
	{if !empty($hiddenFields)}
		{foreach from=$hiddenFields item=fieldValue key=fieldName}
			<input type="hidden" name="{$fieldName}" value="{$fieldValue}">
		{/foreach}
	{/if}
	{if !empty($canSort) && count($sortableFields) > 0}
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
	{if !empty($canFilter)}
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
						<div class="panel-body" style="padding-bottom: 10px; padding-top: 10px;">
							<div id="activeFilters">
								{foreach from=$appliedFilters key=filterName item=appliedFilter}
									{include file='DataObjectUtil/filterField.tpl' filterField=$appliedFilter.field}
								{/foreach}
							</div>
						</div>
						<div class="panel-body-tools" style="padding-bottom: 10px; padding-top: 10px;">
							<div id="filterActions">
								<div class="row">
									<div class="col-tn-6 col-xs-6 text-left" style="padding-bottom: 10px; padding-top: 10px; left: 10px;">
										<button class="btn btn-default btn-sm" type="button" id="addFilterButton" onclick="return AspenDiscovery.Admin.addFilterRow('{$module}', '{$toolName}');" style="padding-top: 5px; padding-bottom: 5px;"><i class="fas fa-plus"></i> {translate text="Add Filter" isAdminFacing=true}</button>
									</div>

									<div class="col-tn-6 col-xs-6 text-right" style="padding-top: 10px; padding-bottom: 10px; right:10px;">
										<button class="btn btn-default btn-sm" type="submit" id="applyFilterButton" onclick="$('#objectAction').val('list');$('#propertiesListForm').submit();"><i class="fas fa-filter"></i> {translate text="Apply Filters" isAdminFacing=true}</button>
									</div>
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
					{if $canCompare || $canBatchUpdate || $canExportToCSV}
						<th>{translate text='Select' isAdminFacing=true}</th>
					{/if}
					{foreach from=$structure item=property key=id}
						{if (!isset($property.hideInLists) || $property.hideInLists == false) && $property.type != 'section'}
						<th><span {if !empty($property.description)}title='{$property.description}'{/if}>{translate text=$property.label isAdminFacing=true}</span></th>
						{/if}
					{/foreach}
					<th>{translate text='Actions' isAdminFacing=true}</th>
				</tr>
			</thead>
			<tbody>
				{if isset($dataList) && is_array($dataList)}
					{foreach from=$dataList item=dataItem key=id}
						{assign var=canEdit value=$dataItem->canActiveUserEdit()}
					<tr class='{cycle values="odd,even"} {if !empty($dataItem->class)}{$dataItem->class}{/if}'>
						{if $canCompare || $canBatchUpdate || $canExportToCSV}
							<td><input type="checkbox" class="selectedObject" name="selectedObject[{$id}]" aria-label="Select Item {$id}"> </td>
						{/if}
						{foreach from=$structure item=property}
							{if (!isset($property.hideInLists) || $property.hideInLists == false) && $property.type != 'section'}
								{assign var=propName value=$property.property}
								{assign var=propValue value=$dataItem->$propName}
								<td aria-label="{if !empty($dataItem) && !is_array($dataItem)}{$dataItem|escape} {/if}{$propName}{if empty($propValue)} - empty{/if}">
								{if $property.type == 'label'}
									{if empty($dataItem->class) || $dataItem->class != 'objectDeleted'}
										{if $dataItem->canActiveUserEdit()}
											{if $propName == $dataItem->getPrimaryKey()}<a class="btn btn-default btn-sm" href='/{$module}/{$toolName}?objectAction=edit&amp;id={$id}'>
											<i class="fas fa-pencil-alt fa-xs" style="padding-right: .5em"></i>{/if}
											{if empty($propValue)}
												{translate text="Not Set" isAdminFacing=true}
											{elseif is_array($propValue)}
												{implode subject=$propValue glue=", "}
											{else}
												{$propValue|escape}
											{/if}
											{if $propName == $dataItem->getPrimaryKey()}</a>{/if}
										{else}
											{$propValue|escape}
										{/if}
									{/if}
								{elseif $property.type == 'regularExpression' || $property.type =='multilineRegularExpression'}
									{$propValue|escape}
								{elseif $property.type == 'text' || $property.type == 'hidden' || $property.type == 'file' || $property.type == 'integer' || $property.type == 'email' || $property.type == 'url'}
									{$propValue|escape}
								{elseif $property.type == 'date'}
									{$propValue|date_format}
								{elseif $property.type == 'time'}
									{$propValue|date_format:"%I:%M %p"}
								{elseif $property.type == 'duration'}
									{$propValue|escape}
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
								{elseif $property.type == 'dayMonth'}
									{$propValue|date_format:"%M %J"}
								{elseif $property.type == 'partialDate'}
									{assign var=propNameMonth value=$property.propNameMonth}
									{assign var=propMonthValue value=$dataItem->$propNameMonth}
									{assign var=propNameDay value=$property.propNameDay}
									{assign var=propDayValue value=$dataItem->$propDayValue}
									{assign var=propNameYear value=$property.propNameYear}
									{assign var=propYearValue value=$dataItem->$propNameYear}
									{if !empty($propMonthValue)}$propMonthValue{else}??{/if}/{if !empty($propDayValue)}$propDayValue{else}??{/if}/{if !empty($propYearValue)}$propYearValue{else}??{/if}
								{elseif $property.type == 'currency'}
									{assign var=propDisplayFormat value=$property.displayFormat}
									${$propValue|string_format:$propDisplayFormat}
								{elseif $property.type == 'enum'}
									{if array_key_exists($propValue,$property.values)}
										{$property.values.$propValue}
									{elseif !empty($property.allValues) && array_key_exists($propValue,$property.allValues)}
										{$property.allValues.$propValue}
									{else}
										{*No value provided, leave blank*}
									{/if}
								{elseif $property.type == 'multiSelect'}
									{if is_array($propValue) && count($propValue) > 0}
										{foreach from=$property.values item=propertyName key=propertyValue}
											{if array_key_exists($propertyValue, $propValue)}{$propertyName|escape}<br/>{/if}
										{/foreach}
									{else}
										{translate text="No values selected" isAdminFacing='true'}
									{/if}
								{elseif $property.type == 'oneToMany'}
									{if is_array($propValue) && count($propValue) > 0}
										{$propValue|@count}
									{else}
										{translate text="Not set" isAdminFacing='true'}
									{/if}
								{elseif $property.type == 'checkbox'}
									{if ($propValue == 1)}{translate text="Yes" isAdminFacing=true}{elseif ($propValue == 0)}{translate text="No" isAdminFacing=true}{else}{$propValue}{/if}
								{elseif $property.type == 'image'}
									<img src="{$property.displayUrl}{$dataItem->id}" class="img-responsive" alt="{$propName}">
								{elseif $property.type == 'html'}
									{$propValue|strip_tags|truncate:255:'...'}
								{elseif $property.type == 'textarea'}
									{$propValue|truncate:255:'...'}
								{else}
									{translate text="Unknown type to display %1%" 1=$property.type isAdminFacing=true}
								{/if}
								</td>
							{/if}
						{/foreach}
						{if empty($dataItem->class) || $dataItem->class != 'objectDeleted'}
							<td>
								<div class="btn-group-vertical">
								{if $dataItem->canActiveUserEdit()}
									<a href='/{$module}/{$toolName}?objectAction=edit&amp;id={$id}' class="btn btn-default btn-sm" aria-label="Edit Item {$id}"><i class="fas fa-pencil-alt"></i> {translate text="Edit" isAdminFacing=true}</a>
								{/if}
								{if $dataItem->getAdditionalListActions()}
									{foreach from=$dataItem->getAdditionalListActions() item=action}
										<a href='{$action.url}' {if !empty($action.onclick)}onclick="{$action.onclick}"{/if} class="btn btn-default btn-sm{if !empty($action.class)} {$action.class}{/if}" aria-label="{$action.text} for Item {$id}" {if !empty($action.target) && $action.target == "_blank"}target="_blank" {/if}>{if !empty($action.target) && $action.target == "_blank"}<i class="fas fa-external-link-alt" role="presentation"></i> {/if} {translate text=$action.text isAdminFacing=true}</a>
									{/foreach}
								{/if}
								{if $dataItem->getAdditionalListJavascriptActions()}
									{foreach from=$dataItem->getAdditionalListJavascriptActions() item=action}
										<a class="btn btn-default btn-sm" aria-label="{$action.text} for Item {$id}" onclick="{$action.onClick}">{if !empty($action.icon)}<i class="fas {$action.icon}"></i> {/if} {translate text=$action.text isAdminFacing=true}</a>
									{/foreach}
								{/if}
								{if $dataItem->canActiveUserEdit() && $showHistoryLinks}
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

	{if !empty($pageLinks.all)}<div class="text-center">{$pageLinks.all}</div>{/if}

	{if $canCompare || $canBatchUpdate || $canExportToCSV}
		<div class="btn-group">
			<button type='button' class="btn btn-default" onclick="$('.selectedObject').prop( 'checked', true );return false">{translate text='Select All' isAdminFacing=true}</button>
			<button type='button' class="btn btn-default" onclick="$('.selectedObject').prop( 'checked', false );return false">{translate text='Deselect All' isAdminFacing=true}</button>
		</div>
	{/if}

	<input type='hidden' name='objectAction' id='objectAction' value='' />
	{if !empty($canCompare)}
		<div class="btn-group">
			<button type='submit' value='compare' class="btn btn-default" onclick="$('#objectAction').val('compare');return AspenDiscovery.Admin.validateCompare();">{translate text='Compare' isAdminFacing=true}</button>
		</div>
	{/if}
	{if !empty($canBatchUpdate)}
		<div class="btn-group">
			<button type='submit' value='batchUpdate' class="btn btn-default" onclick="return AspenDiscovery.Admin.showBatchUpdateFieldForm('{$module}', '{$toolName}', 'selected')">{translate text='Batch Update Selected' isAdminFacing=true}</button>
			<button type='submit' value='batchUpdate' class="btn btn-default" onclick="return AspenDiscovery.Admin.showBatchUpdateFieldForm('{$module}', '{$toolName}', 'all')">{translate text='Batch Update All' isAdminFacing=true}</button>
		</div>
	{/if}
	{if !empty($canExportToCSV) && !empty($dataList)}
		<div class="btn-group">
			<input type='submit' name='exportToCSV' value="{translate text='Export Selected to CSV' isAdminFacing=true inAttribute=true}" class="btn btn-default" onclick="$('#objectAction').val('exportSelectedToCSV');" />
			<input type='submit' name='exportToCSV' value="{translate text='Export to CSV' isAdminFacing=true inAttribute=true}" class="btn btn-default" onclick="$('#objectAction').val('exportToCSV');" />
		</div>
	{/if}
	{if !empty($canAddNew)}
		<div class="btn-group">
			<button type='submit' value='addNew' class="btn btn-primary" onclick="$('#objectAction').val('addNew')"><i class="fas fa-plus"></i> {translate text='Add New' isAdminFacing=true}</button>
		</div>
	{/if}
	{if !empty($canFetchFromCommunity)}
		<div class="btn-group">
			<button type='submit' value='findGreenhouseContent' class="btn btn-default" onclick="return AspenDiscovery.Admin.showFindCommunityContentForm('{$module}', '{$toolName}', '{$objectType}')"><i class="fas fa-file-download"></i> {translate text='Import Community Content' isAdminFacing=true}</button>
		</div>
	{/if}
	{if !empty($customListActions)}
		<div class="row" style="padding-top: 1em">
			<div class="btn-group col-sm-12">
				{foreach from=$customListActions item=customAction}
					<button type='submit' value='{$customAction.action}' class="btn btn-default" onclick="$('#objectAction').val('{$customAction.action}'){if !empty($customAction.onclick)};{$customAction.onclick}{/if}">{translate text=$customAction.label isAdminFacing=true}</button>
				{/foreach}
			</div>
		</div>
	{/if}

	{if !empty($customListPanel)}
		{include file=$customListPanel}
	{/if}

	{if !empty($canDelete) && $canBatchDelete}
	<div class="row" style="padding-top: 1em">
		<div class="btn-group btn-group-sm col-sm-12">
			<button type='submit' value='batchDelete' class="btn btn-danger" onclick="return AspenDiscovery.Admin.showBatchDeleteForm('{$module}', '{$toolName}', 'selected')"><i class="fas fa-trash"></i> {translate text='Batch Delete Selected' isAdminFacing=true}</button>
			{if $canDeleteAll}
			<button type='submit' value='batchDelete' class="btn btn-danger" onclick="return AspenDiscovery.Admin.showBatchDeleteForm('{$module}', '{$toolName}', 'all')"><i class="fas fa-trash"></i> {translate text='Delete All' isAdminFacing=true}</button>
			{/if}
		</div>
	</div>
	{/if}
{if $canCompare || $canAddNew || $canBatchUpdate || $canFilter|| !empty($customListActions) || $canBatchDelete || $canFetchFromCommunity}
</form>
{/if}

{if !empty($showQuickFilterOnPropertiesList) && isset($dataList) && is_array($dataList) && count($dataList) > 5}
<script type="text/javascript">
	{literal}
	$("#adminTable").tablesorter({cssAsc: 'sortAscHeader', cssDesc: 'sortDescHeader', cssHeader: 'unsortedHeader', widgets:['zebra', 'filter'] });
	{/literal}
</script>
{/if}
