{strip}
<style>
	{literal}
	.advSearchContent h3 {
		margin-bottom: 20px;
	}
	.groupSearchHolder .row {
		padding: 2px 0;
	}
	.searchLabel {
		font-weight: bold;
		text-align: right;
	}
	.addSearch {
		/*padding: 0 0 4px 102px;*/
		padding-bottom: 4px;
	}
	.addSearch div {
		padding-left: 0;
	}
	.group .groupSearchDetails {
		width: 100%
		/*text-align: right;*/
		padding: 3px 5px;
	}
	.groupSearchDetails .join {
		padding: 5px;
		font-weight: bold;
	}
	.groupSearchDetails .join,
	.groupSearchDetails .delete {
		padding-right: 5px;
		float: right;
	}
	#searchHolder .group {
		margin-bottom: 10px;
	}
	#groupJoin {
		margin-bottom: 10px;
		padding: 2px 5px;
	}
	#groupJoin .searchGroupDetails {
		float: right;
	}
	#groupJoin strong {
		font-size: 125%;
	}
	.keepFilters input {
		vertical-align: middle;
	}
	#facetTable {
		width: auto;
		margin-left: auto;
		margin-right: auto;
	}
	#facetTable .form-inline .form-control {
		width: auto;
		margin: auto 4px;
	}
{/literal}
</style>
<div id="page-content" class="content">
	<div id="main-content" class="advSearchContent">

		<div class="dropdown pull-right">
			<button class="btn btn-info dropdown-toggle" type="button" id="SearchTips" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
				{translate text="Search Tips" isPublicFacing=true}
				&nbsp;<span class="caret"></span>
			</button>
			<ul class="dropdown-menu" aria-labelledby="SearchTips">
				<li><a href="/Help/Home?topic=advsearch" class="modalDialogTrigger" {*data-target="#modalDialog"*} data-title="{translate text="Help with Advanced Search" inAttribute=true isPublicFacing=true}">{translate text="Help with Advanced Search" isPublicFacing=true}</a></li>
				<li><a href="/Help/Home?topic=search" class="modalDialogTrigger" {*data-target="#modalDialog"*} data-title="{translate text="Help with Search Operators" inAttribute=true isPublicFacing=true}">{translate text="Help with Search Operators" isPublicFacing=true}</a></li>
			</ul>
		</div>

		<form method="get" action="/Search/Results" id="advSearchForm" class="search">
			<div>
				<div class="advSearchContent">

					<h1>{translate text='Advanced Search' isPublicFacing=true}</h1>

					{if !empty($editErr)}
						{assign var=error value="advSearchError_$editErr"}
						<div class="alert alert-warning">{translate text=$error isPublicFacing=true}</div>
					{/if}

					<div id="groupJoin" class="searchGroups">
						<div class="searchGroupDetails">
							<label for="join">{translate text="Match" isPublicFacing=true}</label>
							<select id="join" name="join"{* class="form-control"*}>
								<option value="AND">{translate text="ALL Groups" isPublicFacing=true}</option>
								<option value="OR"{if !empty($searchDetails) && $searchDetails.0.join == 'OR'} selected="selected"{/if}>{translate text="ANY Groups" isPublicFacing=true}</option>
							</select>
						</div>
						<strong>{translate text="Search Groups" isPublicFacing=true}</strong>
					</div>

					{* An empty div; This is the target for the javascript that builds this screen *}
					<div id="searchHolder"></div>

					<button class="btn btn-default" onclick="addGroup();return false;"><span class="glyphicon glyphicon-plus"></span>&nbsp;{translate text="Add Search Group" isPublicFacing=true}</button>
					<button class="btn btn-default" onclick="resetSearch();return false;"><span class="glyphicon glyphicon-remove-sign"></span>&nbsp;{translate text="Clear Search" isPublicFacing=true}</button>
					{* addGroup() returns the variable nextGroupNumber so the return false is necessary *}
					<input type="submit" name="submit" value="{translate text="Find" inAttribute=true isPublicFacing=true inAttribute=true}" class="btn btn-primary pull-right">
					<br><br>
					{if $facetList || $showPublicationDate}
						<div class="accordion">
							<div {*id="facet-accordion"*} class="panel panel-default">
								<div class="panel-heading">
									<div class="panel-title {if empty($hasSelectedFacet)}collapsed{else}expanded{/if}">
										<a href="#facetPanel" data-toggle="collapse" role="button">
										{translate text='Optional Filters' isPublicFacing=true}
										</a>
									</div>
								</div>
								<div id="facetPanel" class="panel-collapse {if empty($hasSelectedFacet)}collapse{/if}">
									<div class="panel-body">

										<div class="alert alert-info">
											{translate text="The filters below are optional. Only set the filters needed to narrow your search." isPublicFacing=true}
										</div>

										{if !empty($facetList)}
											{foreach from=$facetList item="facetInfo"}
												<div class="row form-group">
													<div class="col-sm-3">
														<strong>{translate text=$facetInfo.facetLabel isPublicFacing=true}</strong>
													</div>
													<div class="col-sm-9">
														{if $facetInfo.facetName == "publishDate" || $facetInfo.facetName == "publishDateSort"}
															<div class="row">
																<div class="col-xs-6 col-md-4 col-lg-3">
																	<label for="{$facetInfo.facetName}yearfrom" class="yearboxlabel">{translate text="From" isPublicFacing=true} </label>
																	<input type="text" size="4" maxlength="4" class="yearbox form-control" name="{$facetInfo.facetName}yearfrom" id="{$facetInfo.facetName}yearfrom" value="" aria-label="Publication Date From">
																</div>
																<div class="col-xs-6 col-md-4 col-lg-3">
																	<label for="{$facetInfo.facetName}yearto" class="yearboxlabel">{translate text="To" isPublicFacing=true} </label>
																	<input type="text" size="4" maxlength="4" class="yearbox form-control" name="{$facetInfo.facetName}yearto" id="{$facetInfo.facetName}yearto" value="" aria-label="Publication Date To">
																</div>
															</div>
															<div id="yearDefaultLinks row">
																<div class="col-xs-12">
																	{assign var=thisyear value=$smarty.now|date_format:"%Y"}
																	{translate text="Published in the last" isPublicFacing=true}<br/>
																	<a onclick="$('#{$facetInfo.facetName}yearfrom').val('{$thisyear-1}');$('#{$facetInfo.facetName}yearto').val('');" href='javascript:void(0);'>{translate text="year" isPublicFacing=true}</a>
																	&bullet; <a onclick="$('#{$facetInfo.facetName}yearfrom').val('{$thisyear-5}');$('#{$facetInfo.facetName}yearto').val('');" href='javascript:void(0);'>{translate text="5 years" isPublicFacing=true}</a>
																	&bullet; <a onclick="$('#{$facetInfo.facetName}yearfrom').val('{$thisyear-10}');$('#{$facetInfo.facetName}yearto').val('');" href='javascript:void(0);'>{translate text="10 years" isPublicFacing=true}</a>
																</div>`
															</div>
														{elseif $facetInfo.facetName == "lexile_score"}
															<div class="row">
																<div class="col-xs-6 col-md-4 col-lg-3">
																	<label for="lexile_scorefrom" class="yearboxlabel">{translate text="From" isPublicFacing=true} </label>
																	<input type="text" size="4" maxlength="4" class="yearbox form-control" name="lexile_scorefrom" id="lexile_scorefrom" value="" aria-label="Lexile Score From">
																</div>
																<div class="col-xs-6 col-md-4 col-lg-3">
																	<label for="lexile_scoreto" class="yearboxlabel">{translate text="To" isPublicFacing=true} </label>
																	<input type="text" size="4" maxlength="4" class="yearbox form-control" name="lexile_scoreto" id="lexile_scoreto" value="" aria-label="Lexile Score To">
																</div>
															</div>
														{elseif $facetInfo.facetName == "accelerated_reader_point_value"}
															<div class="row">
																<div class="col-xs-6 col-md-4 col-lg-3">
																	<label for="accelerated_reader_point_valuefrom" class="yearboxlabel">{translate text="From" isPublicFacing=true} </label>
																	<input type="text" size="4" maxlength="4" class="yearbox form-control" name="accelerated_reader_point_valuefrom" id="accelerated_reader_point_valuefrom" value="" aria-label="Accelerated Reader Points From">
																</div>
																<div class="col-xs-6 col-md-4 col-lg-3">
																	<label for="accelerated_reader_point_valueto" class="yearboxlabel">{translate text="To" isPublicFacing=true} </label>
																	<input type="text" size="4" maxlength="4" class="yearbox form-control" name="accelerated_reader_point_valueto" id="accelerated_reader_point_valueto" value="" aria-label="Accelerated Reader Points To">
																</div>
															</div>
                                                        {elseif $facetInfo.facetName == "accelerated_reader_reading_level"}
															<div class="row">
																<div class="col-xs-6 col-md-4 col-lg-3">
																	<label for="accelerated_reader_reading_levelfrom" class="yearboxlabel">{translate text="From" isPublicFacing=true} </label>
																	<input type="text" size="4" maxlength="4" class="yearbox form-control" name="accelerated_reader_reading_levelfrom" id="accelerated_reader_reading_levelfrom" value="" aria-label="Accelerated Reader Level From">
																</div>
																<div class="col-xs-6 col-md-4 col-lg-3">
																	<label for="accelerated_reader_reading_levelto" class="yearboxlabel">{translate text="To" isPublicFacing=true} </label>
																	<input type="text" size="4" maxlength="4" class="yearbox form-control" name="accelerated_reader_reading_levelto" id="accelerated_reader_reading_levelto" value="" aria-label="Accelerated Reader Level To">
																</div>
															</div>
                                                        {else}
															<select name="filter[]" class="form-control" aria-label="{translate text=$facetInfo.facetLabel inAttribute=true isPublicFacing=true}">
																{foreach from=$facetInfo.values item="value" key="display"}
																	{if strlen($display) > 0}
																		<option value="{$value.filter|escape}"{if !empty($value.selected)} selected="selected"{/if}>{$value.display|escape|truncate:80}</option>
																	{/if}
																{/foreach}
															</select>
														{/if}
													</div>
												</div>
											{/foreach}
										{/if}
										<input type="submit" name="submit" value="{translate text="Find" inAttribute=true isPublicFacing=true}" class="btn btn-primary pull-right">
									</div>
								</div>
							</div>
						</div>

					{/if}
				</div>
			</div>
		</form>
	</div>
</div>
{/strip}
{if !empty($debugJs)}
<script type="text/javascript" src="/services/Search/advanced.js"></script>
{else}
<script type="text/javascript" src="/services/Search/advanced.min.js"></script>
{/if}
<script type="text/javascript">
	{* Define our search arrays so they are usuable in the javascript *}
	var searchFields = {ldelim}
	{foreach from=$advSearchTypes item=searchDesc key=searchVal}
	"{$searchVal}" : "{translate text=$searchDesc inAttribute=true isPublicFacing=true}",
	{/foreach}
	{rdelim};
	var searchJoins = {ldelim}
		AND: "{translate text="All Terms (AND)" inAttribute=true isPublicFacing=true}"
		,OR: "{translate text="Any Terms (OR)" inAttribute=true isPublicFacing=true}"
		,NOT:"{translate text="No Terms (NOT)" inAttribute=true isPublicFacing=true}"
		{rdelim};
	var addSearchString = "{translate text="Add Search Field" inAttribute=true isPublicFacing=true}";
	var searchLabel     = "{translate text="Search for" inAttribute=true isPublicFacing=true}";
	var searchFieldLabel = "{translate text="in" inAttribute=true isPublicFacing=true}";
	var deleteSearchGroupString = "{translate text="Remove Search Group" inAttribute=true isPublicFacing=true}";
	var searchMatch     = "{translate text="Match" inAttribute=true isPublicFacing=true}";
	var searchFormId    = 'advSearchForm';
	{*  Build the form *}
	$(function(){ldelim}
		{if !empty($searchDetails)}
			{foreach from=$searchDetails item=searchGroup}
				{foreach from=$searchGroup.group item=search name=groupLoop}
					{if $smarty.foreach.groupLoop.iteration == 1}
					var new_group = addGroup('{$search.lookfor|escape:"javascript"}', '{$search.field|escape:"javascript"}', '{$search.bool}');
					{else}
					addSearch(new_group, '{$search.lookfor|escape:"javascript"}', '{$search.field|escape:"javascript"}');
					{/if}
				{/foreach}
			{/foreach}
		{else}
			var new_group = addGroup();
			addSearch(new_group);
			addSearch(new_group);
		{/if}
	{rdelim});
</script>
