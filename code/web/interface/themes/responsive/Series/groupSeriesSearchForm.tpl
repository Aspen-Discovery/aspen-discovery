{strip}
<form method="post" action="" name="popupForm" class="form-horizontal" id="groupWithForm">
	<div class="alert alert-info">
		{translate text="This form will allow you to group the current series with another series.  The other series will become the primary series and this series will be removed from the index and added to the primary." isAdminFacing=true}
	</div>
	<div class="alert alert-info">
		<div class="row">
			<div class="col-tn-12">
				{translate text="You are grouping series %1%" 1=$id isAdminFacing=true}
			</div>
		</div>
		<div class="row">
			<div class="col-tn-3">
				{translate text="Title" isAdminFacing=true}
			</div>
			<div class="col-tn-9">
				<strong>{$series->groupedWorkSeriesTitle}</strong>
			</div>
		</div>
		<div class="row">
			<div class="col-tn-3">
				{translate text="Author" isAdminFacing=true}
			</div>
			<div class="col-tn-9">
				<strong>{$series->author}</strong>
			</div>
		</div>
	</div>
	<input type="hidden" name="id" id="id" value="{$id}"/>
	<div class="form-group">
		<label for="searchResultToGroupWith" class="col-sm-12">{translate text="Enter the search result number to be the primary series" isAdminFacing=true} </label>
		<div class="col-tn-12">
			<select name="searchResultToGroupWith" id="searchResultToGroupWith" class="form-control" onchange="$('#seriesToGroupWithId').val($('#searchResultToGroupWith option:selected').val());">
				{foreach from=$availableSeries item="seriesDescription" key="seriesId"}
					<option value="{$seriesId}">{$seriesDescription}</option>
				{/foreach}
			</select>
			<input type="hidden" name="seriesToGroupWithId" id="seriesToGroupWithId">
		</div>
	</div>
</form>
{/strip}
