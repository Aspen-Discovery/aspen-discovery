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
{/literal}{*
		/*.group0 .groupSearchDetails {*/
			/*border: 1px solid #D8D7D8;*/
			/*border-top: 0px;*/
		/* }*/
		/*.group1 .groupSearchDetails {*/
			/*border: 1px solid #94C632;*/
			/*border-top: 0px;*/
		/* }*/
/*		#searchHolder .group0 {
			border-top : 1px solid #D8D7D8;
			background:url(/images/gradient_grey.gif) repeat-y;
		}
		#searchHolder .group1 {
			border-top: 1px solid #94C632;
			background:url(/images/gradient_green.gif) repeat-y;
		}*/*}{literal}
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
				<li><a href="{$path}/Help/Home?topic=advsearch" class="modalDialogTrigger" {*data-target="#modalDialog"*} data-title="{translate text="Help with Advanced Search"}">{translate text="Help with Advanced Search"}</a></li>
				<li><a href="{$path}/Help/Home?topic=search" class="modalDialogTrigger" {*data-target="#modalDialog"*} data-title="{translate text="Help with Search Operators"}">{translate text="Help with Search Operators"}</a></li>
			</ul>
		</div>

		<form method="get" action="{$path}/Search/Results" id="advSearchForm" class="search">
			<div>
				<div class="advSearchContent">

					<h3>{translate text='Advanced Search'}</h3>

					{if $editErr}
						{assign var=error value="advSearchError_$editErr"}
						<div class="alert alert-warning">{translate text=$error}</div>
					{/if}

					<div id="groupJoin" class="searchGroups">
						<div class="searchGroupDetails">
							{translate text="search_match"} :
							<select name="join"{* class="form-control"*}>
								<option value="AND">{translate text="group_AND"}</option>
								<option value="OR"{if $searchDetails && $searchDetails.0.join == 'OR'} selected="selected"{/if}>{translate text="group_OR"}</option>
							</select>
						</div>
						<strong>{translate text="search_groups"}</strong>:
					</div>

					{* An empty div; This is the target for the javascript that builds this screen *}
					<div id="searchHolder"></div>

					<button class="btn btn-default" onclick="addGroup();return false;"><span class="glyphicon glyphicon-plus"></span>&nbsp;{translate text="add_search_group"}</button>
					<button class="btn btn-default" onclick="resetSearch();return false;"><span class="glyphicon glyphicon-remove-sign"></span>&nbsp;{translate text="Clear Search"}</button>
					{* addGroup() returns the variable nextGroupNumber so the return false is necessary *}
					<input type="submit" name="submit" value="{translate text="Find"}" class="btn btn-primary pull-right">
					<br><br>
					{if $facetList || $illustratedLimit || $showPublicationDate}
						<div class="accordion">
							<div {*id="facet-accordion"*} class="panel panel-default">
									<div class="panel-heading">
										<div class="panel-title {if 1}collapsed{else}expanded{/if}">
											<a href="#facetPanel" data-toggle="collapse" role="button">
											{translate text='Optional Filters'}
											</a>
										</div>
									</div>
								<div id="facetPanel" class="panel-collapse collapse">
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

										<table id="facetTable" class="table table-bordered" summary="{translate text='Limit To'}">
											{if $facetList}
												{foreach from=$facetList item="facetInfo" key="label"}
													<tr>
														<th align="right">{translate text=$label}:</th>
														<td>
															{if $facetInfo.facetName == "publishDate"}
															<div class="form-inline">
																<div class="form-group">
																<label for="publishDateyearfrom" class="yearboxlabel">From: </label>
																<input type="text" size="4" maxlength="4" class="yearbox form-control" name="publishDateyearfrom" id="publishDateyearfrom"
																       value="">
																</div>
																<div class="form-group">
																<label for="publishDateyearto" class="yearboxlabel">To: </label>
																<input type="text" size="4" maxlength="4" class="yearbox form-control" name="publishDateyearto" id="publishDateyearto"
																       value="">
																</div>
															</div>
																<div id="yearDefaultLinks">
																	{assign var=thisyear value=$smarty.now|date_format:"%Y"}
																	Published in the last:<br/>
																	<a onclick="$('#publishDateyearfrom').val('{$thisyear-1}');$('#publishDateyearto').val('');" href='javascript:void(0);'>year</a>
																	&bullet; <a onclick="$('#publishDateyearfrom').val('{$thisyear-5}');$('#publishDateyearto').val('');" href='javascript:void(0);'>5&nbsp;years</a>
																	&bullet; <a onclick="$('#publishDateyearfrom').val('{$thisyear-10}');$('#publishDateyearto').val('');" href='javascript:void(0);'>10&nbsp;years</a>
																</div>
															{elseif $facetInfo.facetName == "lexile_score"}
																<div id="lexile-range"></div>
																<div class="form-inline">
																	<div class="form-group">
																		<label for="lexile_scorefrom" class="yearboxlabel">From: </label>
																		<input type="text" size="4" maxlength="4" class="yearbox form-control" name="lexile_scorefrom"
																		       id="lexile_scorefrom" value="">
																	</div>
																	<div class="form-group">
																		<label for="lexile_scoreto" class="yearboxlabel">To: </label>
																		<input type="text" size="4" maxlength="4" class="yearbox form-control" name="lexile_scoreto"
																		       id="lexile_scoreto" value="">
																	</div>
																</div>
															{elseif $facetInfo.facetName == "accelerated_reader_reading_level"}
																<div class="form-inline">
																	<div class="form-group">
																	<label for="accelerated_reader_reading_levelfrom" class="yearboxlabel">From: </label>
																	<input type="text" size="4" maxlength="4" class="yearbox form-control" name="accelerated_reader_reading_levelfrom" id="accelerated_reader_reading_levelfrom" value="">
																	</div>
																	<div class="form-group">
																	<label for="accelerated_reader_reading_levelto" class="yearboxlabel">To: </label>
																	<input type="text" size="4" maxlength="4" class="yearbox form-control" name="accelerated_reader_reading_levelto" id="accelerated_reader_reading_levelto" value="">
																	</div>
																</div>
															{else}
																<select name="filter[]" class="form-control">
																	{foreach from=$facetInfo.values item="value" key="display"}
																		{if strlen($display) > 0}
																			<option value="{$value.filter|escape}"{if $value.selected} selected="selected"{/if}>{$display|escape|truncate:80}</option>
																		{/if}
																	{/foreach}
																</select>
															{/if}
														</td>
													</tr>
												{/foreach}
											{/if}
											{if $illustratedLimit}
												<tr>
													<th align="right">{translate text="Illustrated"}:</th>
													<td>
														{foreach from=$illustratedLimit item="current"}
														<div class="radio">
														<label>
															<input type="radio" name="illustration"
															       value="{$current.value|escape}"{if $current.selected} checked="checked"{/if}>
															{translate text=$current.text}
															</label>
														</div>
														{/foreach}
													</td>
												</tr>
											{/if}

										</table>
										<input type="submit" name="submit" value="{translate text="Find"}" class="btn btn-primary pull-right">
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
<script type="text/javascript" src="{$path}/services/Search/advanced.js"></script>
{else}
<script type="text/javascript" src="{$path}/services/Search/advanced.min.js"></script>
{/if}
<script type="text/javascript">
	{* Define our search arrays so they are usuable in the javascript *}
	var searchFields = {ldelim}
	{foreach from=$advSearchTypes item=searchDesc key=searchVal}
	"{$searchVal}" : "{translate text=$searchDesc}",
	{/foreach}
	{rdelim};
	var searchJoins = {ldelim}
		AND: '{translate text="search_AND"}'
		,OR: '{translate text="search_OR"}'
		,NOT:'{translate text="search_NOT"}'
		{rdelim};
	var addSearchString = "{translate text="add_search"}";
	var searchLabel     = "{translate text="adv_search_label"}";
	var searchFieldLabel = "{translate text="in"}";
	var deleteSearchGroupString = "{translate text="del_search"}";
	var searchMatch     = "{translate text="search_match"}";
	var searchFormId    = 'advSearchForm';
	{*  Build the form *}
	$(function(){ldelim}
		{if $searchDetails}
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
		{* Highlight Selected Facet Filters *}
		{literal}
		$('#facetTable select').change(function(){
			$(this).parents('tr').css('background-color', ($(this).val() == '') ? '#FFF' : '#EFEFEF')
		}).change();
		{/literal}
	{rdelim});
</script>
