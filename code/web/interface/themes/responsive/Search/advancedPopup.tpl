{strip}
{if $savedSearch}
	{$searchTerms|@print_r}
{/if}
<div>
	<form id="advancedPopup" class="modal-form form-horizontal form-mod" method="get" action="{$path}/Union/Search">
		<fieldset>
			<div id="advancedSearchRows">
				<div class="form-group advancedRow" id="group1" data-row_number="1">

					<div class="col-xs-1">
						<input type="hidden" name="groupStart[1]" id="groupStart1Input" class="groupStartInput" title="Start Group"/>
						<button id="groupStart1" data-toggle="button" data-hidden_element="#groupStart1Input" onclick="return VuFind.toggleHiddenElementWithButton(this);" class="btn btn-sm btn-default groupStartButton">(</button>
					</div>
					<div class="col-xs-3">
						<select name="searchType[1]" class="searchType form-control">
							{foreach from=$basicSearchTypes item=searchDesc key=searchVal}
								<option value="{$searchVal}">{$searchDesc|translate}</option>
							{/foreach}
							<option value="">---</option>
							{foreach from=$advSearchTypes item=searchDesc key=searchVal}
								<option value="{$searchVal}">{$searchDesc|translate}</option>
							{/foreach}
							<option value="">---</option>
							{foreach from=$facetList item="facetLabel" key="filterName"}
								<option value="{$filterName}" data-is_facet="true">{$facetLabel|translate}</option>
							{/foreach}
						</select>
					</div>
					<div class="col-xs-3">
						<input type="text" name="lookfor[1]" class="lookfor form-control input-sm" title="Search For" placeholder="Search for" data-provide="typeahead" data-source='VuFind.Searches.getSpellingSuggestion();' autocomplete="off"/>
					</div>
					<div class="col-xs-1">
						<input type="hidden" name="groupEnd[1]" id="groupEnd1Input" title="End Group" class="groupEndInput"/>
						<button id="groupEnd1" data-toggle="button" data-hidden_element="#groupEnd1Input" onclick="return VuFind.toggleHiddenElementWithButton(this);" class="btn btn-sm btn-default groupEndButton">)</button>
					</div>

					{/strip} {strip}
					<div class="col-xs-2">
						<select name="join[1]" class="form-control joinOption input-sm">
							<option value="AND">{translate text="AND"}</option>
							<option value="AND NOT">{translate text="AND NOT"}</option>
							<option value="OR">{translate text="OR"}</option>
							<option value="OR NOT">{translate text="OR NOT"}</option>
						</select>
					</div>
					<div class="col-xs-2">
						<button name="addCriteria" class="btn btn-sm btn-default addCriteria" onclick="return VuFind.Searches.addAdvancedGroup(this);" title="Add Criteria">
							<span class="glyphicon glyphicon-plus-sign"></span>
						</button>
						<button name="deleteCriteria" class="btn btn-sm btn-default deleteCriteria" onclick="return VuFind.Searches.deleteAdvancedGroup(this);" title="Delete Criteria">
							<span class="glyphicon glyphicon-minus-sign"></span>
						</button>
					</div>
				</div>
			</div>
		</fieldset>
	</form>
</div>
{/strip}
{literal}
<script type="text/javascript">
	$(document).ready(function(){
		{/literal}
		{foreach from=$searchGroups key=groupIndex item=searchGroup}
		VuFind.Searches.searchGroups[{$groupIndex}] = {literal}{{/literal}
			groupStart: '{$searchGroup.groupStart}',
			lookfor: '{$searchGroup.lookfor}',
			searchType: '{$searchGroup.searchType}',
			groupEnd: '{$searchGroup.groupEnd}',
			join: '{$searchGroup.join}'

			{literal}}{/literal};
		{/foreach}
		{literal}

		VuFind.Searches.loadSearchGroups();
	});
</script>
{/literal}