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
				{translate text="Search Tips"}
				&nbsp;<span class="caret"></span>
			</button>
			<ul class="dropdown-menu" aria-labelledby="SearchTips">
				<li><a href="/Help/Home?topic=advsearch" class="modalDialogTrigger" {*data-target="#modalDialog"*} data-title="{translate text="Help with Advanced Search" inAttribute=true}">{translate text="Help with Advanced Search"}</a></li>
				<li><a href="/Help/Home?topic=search" class="modalDialogTrigger" {*data-target="#modalDialog"*} data-title="{translate text="Help with Search Operators" inAttribute=true}">{translate text="Help with Search Operators"}</a></li>
			</ul>
		</div>

		<form method="get" action="/Search/Results" id="advSearchForm" class="search">
			<div>
				<div class="advSearchContent">

					<h1>{translate text='Advanced Search'}</h1>

					{if $editErr}
						{assign var=error value="advSearchError_$editErr"}
						<div class="alert alert-warning">{translate text=$error}</div>
					{/if}

					<div id="groupJoin" class="searchGroups">
						<div class="searchGroupDetails">
							<label for="join">{translate text="search_match"}</label>
							<select id="join" name="join"{* class="form-control"*}>
								<option value="AND">{translate text="group_AND"}</option>
								<option value="OR"{if $searchDetails && $searchDetails.0.join == 'OR'} selected="selected"{/if}>{translate text="group_OR"}</option>
							</select>
						</div>
						<strong>{translate text="search_groups"}</strong>
					</div>

					{* An empty div; This is the target for the javascript that builds this screen *}
					<div id="searchHolder"></div>

					<button class="btn btn-default" onclick="addGroup();return false;"><span class="glyphicon glyphicon-plus"></span>&nbsp;{translate text="add_search_group"}</button>
					<button class="btn btn-default" onclick="resetSearch();return false;"><span class="glyphicon glyphicon-remove-sign"></span>&nbsp;{translate text="Clear Search"}</button>
					{* addGroup() returns the variable nextGroupNumber so the return false is necessary *}
					<input type="submit" name="submit" value="{translate text="Find" inAttribute=true}" class="btn btn-primary pull-right">
					<br><br>
					{if $facetList || $showPublicationDate}
						<div class="accordion">
							<div {*id="facet-accordion"*} class="panel panel-default">
								<div class="panel-heading">
									<div class="panel-title {if !$hasSelectedFacet}collapsed{else}expanded{/if}">
										<a href="#facetPanel" data-toggle="collapse" role="button">
										{translate text='Optional Filters'}
										</a>
									</div>
								</div>
								<div id="facetPanel" class="panel-collapse {if !$hasSelectedFacet}collapse{/if}">
									<div class="panel-body">

										<div class="alert alert-info">
											The filters below are optional. Only set the filters needed to narrow your search.
										</div>

										{*//TODO Is this in use?? *}
										{if $formatCategoryLimit}
											<div class="advancedSearchFacetDetails">
												<div class="advancedSearchFacetHeader">{translate text=$formatCategoryLimit.label}</div>
												<div class="advancedSearchFacetList">
													{foreach from=$formatCategoryLimit item="value" key="display"}
														{if $value.filter != ""}
															<div class="advancedSearchFacetFormatCategory">
																<div><input id="categoryValue_{$display|lower|replace:' ':''}" type="radio"
																            name="filter[]"
																            value="{$value.filter|escape}"{if $value.selected} checked="checked"{/if}>
																	<label for="categoryValue_{$display|lower|replace:' ':''}">
																		<span class="categoryValue categoryValue_{$display|lower|replace:' ':''}">{translate text=$display}</span>
																	</label>
																</div>
															</div>
														{/if}
													{/foreach}
												</div>
											</div>
										{/if}

										{if $facetList}
											{foreach from=$facetList item="facetInfo" key="label"}
												<div class="row form-group">
													<div class="col-sm-3">
														<strong>{translate text=$label}</strong>
													</div>
													<div class="col-sm-9">
														{if $facetInfo.facetName == "publishDate"}
															<div class="row">
																<div class="col-xs-6 col-md-4 col-lg-3">
																	<label for="publishDateyearfrom" class="yearboxlabel">From </label>
																	<input type="text" size="4" maxlength="4" class="yearbox form-control" name="publishDateyearfrom" id="publishDateyearfrom" value="" aria-label="Publication Date From">
																</div>
																<div class="col-xs-6 col-md-4 col-lg-3">
																	<label for="publishDateyearto" class="yearboxlabel">To </label>
																	<input type="text" size="4" maxlength="4" class="yearbox form-control" name="publishDateyearto" id="publishDateyearto" value="" aria-label="Publication Date To">
																</div>
															</div>
															<div id="yearDefaultLinks row">
																<div class="col-xs-12">
																	{assign var=thisyear value=$smarty.now|date_format:"%Y"}
																	Published in the last<br/>
																	<a onclick="$('#publishDateyearfrom').val('{$thisyear-1}');$('#publishDateyearto').val('');" href='javascript:void(0);'>year</a>
																	&bullet; <a onclick="$('#publishDateyearfrom').val('{$thisyear-5}');$('#publishDateyearto').val('');" href='javascript:void(0);'>5&nbsp;years</a>
																	&bullet; <a onclick="$('#publishDateyearfrom').val('{$thisyear-10}');$('#publishDateyearto').val('');" href='javascript:void(0);'>10&nbsp;years</a>
																</div>`
															</div>
														{elseif $facetInfo.facetName == "lexile_score"}
															<div class="row">
																<div class="col-xs-6 col-md-4 col-lg-3">
																	<label for="lexile_scorefrom" class="yearboxlabel">From </label>
																	<input type="text" size="4" maxlength="4" class="yearbox form-control" name="lexile_scorefrom" id="lexile_scorefrom" value="" aria-label="Lexile Score From">
																</div>
																<div class="col-xs-6 col-md-4 col-lg-3">
																	<label for="lexile_scoreto" class="yearboxlabel">To </label>
																	<input type="text" size="4" maxlength="4" class="yearbox form-control" name="lexile_scoreto" id="lexile_scoreto" value="" aria-label="Lexile Score To">
																</div>
															</div>
														{elseif $facetInfo.facetName == "accelerated_reader_point_value"}
															<div class="row">
																<div class="col-xs-6 col-md-4 col-lg-3">
																	<label for="accelerated_reader_point_valuefrom" class="yearboxlabel">From </label>
																	<input type="text" size="4" maxlength="4" class="yearbox form-control" name="accelerated_reader_point_valuefrom" id="accelerated_reader_point_valuefrom" value="" aria-label="Accelerated Reader Points From">
																</div>
																<div class="col-xs-6 col-md-4 col-lg-3">
																	<label for="accelerated_reader_point_valueto" class="yearboxlabel">To </label>
																	<input type="text" size="4" maxlength="4" class="yearbox form-control" name="accelerated_reader_point_valueto" id="accelerated_reader_point_valueto" value="" aria-label="Accelerated Reader Points To">
																</div>
															</div>
                                                        {elseif $facetInfo.facetName == "accelerated_reader_reading_level"}
															<div class="row">
																<div class="col-xs-6 col-md-4 col-lg-3">
																	<label for="accelerated_reader_reading_levelfrom" class="yearboxlabel">From </label>
																	<input type="text" size="4" maxlength="4" class="yearbox form-control" name="accelerated_reader_reading_levelfrom" id="accelerated_reader_reading_levelfrom" value="" aria-label="Accelerated Reader Level From">
																</div>
																<div class="col-xs-6 col-md-4 col-lg-3">
																	<label for="accelerated_reader_reading_levelto" class="yearboxlabel">To </label>
																	<input type="text" size="4" maxlength="4" class="yearbox form-control" name="accelerated_reader_reading_levelto" id="accelerated_reader_reading_levelto" value="" aria-label="Accelerated Reader Level To">
																</div>
															</div>
                                                        {else}
															<select name="filter[]" class="form-control" aria-label="{translate text=$label inAttribute=true}">
																{foreach from=$facetInfo.values item="value" key="display"}
																	{if strlen($display) > 0}
																		<option value="{$value.filter|escape}"{if $value.selected} selected="selected"{/if}>{$value.display|escape|truncate:80}</option>
																	{/if}
																{/foreach}
															</select>
														{/if}
													</div>
												</div>
											{/foreach}
										{/if}
										<input type="submit" name="submit" value="{translate text="Find" inAttribute=true}" class="btn btn-primary pull-right">
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
{if $debugJs}
<script type="text/javascript" src="/services/Search/advanced.js"></script>
{else}
<script type="text/javascript" src="/services/Search/advanced.min.js"></script>
{/if}
<script type="text/javascript">
	{* Define our search arrays so they are usuable in the javascript *}
	let searchFields = {ldelim}
	{foreach from=$advSearchTypes item=searchDesc key=searchVal}
	"{$searchVal}" : "{translate text=$searchDesc inAttribute=true}",
	{/foreach}
	{rdelim};
	let searchJoins = {ldelim}
		AND: '{translate text="search_AND" inAttribute=true}'
		,OR: '{translate text="search_OR" inAttribute=true}'
		,NOT:'{translate text="search_NOT" inAttribute=true}'
		{rdelim};
	let addSearchString = "{translate text="add_search" inAttribute=true}";
	let searchLabel     = "{translate text="adv_search_label" inAttribute=true}";
	let searchFieldLabel = "{translate text="in" inAttribute=true}";
	let deleteSearchGroupString = "{translate text="del_search" inAttribute=true}";
	let searchMatch     = "{translate text="search_match" inAttribute=true}";
	let searchFormId    = 'advSearchForm';
	{*  Build the form *}
	$(function(){ldelim}
		{if $searchDetails}
			{foreach from=$searchDetails item=searchGroup}
				{foreach from=$searchGroup.group item=search name=groupLoop}
					{if $smarty.foreach.groupLoop.iteration == 1}
					let new_group = addGroup('{$search.lookfor|escape:"javascript"}', '{$search.field|escape:"javascript"}', '{$search.bool}');
					{else}
					addSearch(new_group, '{$search.lookfor|escape:"javascript"}', '{$search.field|escape:"javascript"}');
					{/if}
				{/foreach}
			{/foreach}
		{else}
		let new_group = addGroup();
			addSearch(new_group);
			addSearch(new_group);
		{/if}
	{rdelim});
</script>
