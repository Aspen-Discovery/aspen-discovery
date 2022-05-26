{strip}
	<div id="main-content" class="col-sm-12">
		<h1>{translate text="Materials Request Dashboard" isAdminFacing=true}</h1>
		{if count($locationsToRestrictTo) > 1}
        	<form name="selectInterface" id="selectInterface" class="form-inline row">
        		<div class="form-group col-tn-6">
        			<label for="location" class="control-label">{translate text="Location to show stats for" isAdminFacing=true}</label>&nbsp;
        			<select id="location" name="location" class="form-control input-sm" onchange="$('#selectInterface').submit()">
        				{foreach from=$locationsToRestrictTo key=id item=curLocation}
        					<option value="{$id}" {if $id == $selectedLocation}selected{/if}>{$curLocation.displayLabel}</option>
        				{/foreach}
        			</select>
        		</div>
        	</form>
        {/if}

<div class="row">
		{foreach from=$allStats key=label item=statusStats}
		<div class="dashboardCategory col-sm-6">
			<div class="row">
				<div class="col-sm-10 col-sm-offset-1">
					<h3 class="dashboardCategoryLabel">{$statusStats.label} <a href="/MaterialsRequest/Graph?status={$statusStats.id}&location={$selectedLocation}" title="{translate text="Show Graph" inAttribute="true" isAdminFacing=true}"> <i class="fas fa-chart-line"></i></a></h3> {* No translation needed *}
				</div>
			</div>
            <div class="row">
                <div class="col-tn-6">
                    <div class="dashboardLabel">{translate text="This Month" isAdminFacing=true}</div>
                    <div class="dashboardValue">{$statusStats.usageThisMonth|number_format}</div>
                </div>
                <div class="col-tn-6">
                    <div class="dashboardLabel">{translate text="Last Month" isAdminFacing=true}</div>
                    <div class="dashboardValue">{$statusStats.usageLastMonth|number_format}</div>
                </div>
                <div class="col-tn-6">
                    <div class="dashboardLabel">{translate text="This Year" isAdminFacing=true}</div>
                    <div class="dashboardValue">{$statusStats.usageThisYear|number_format}</div>
                </div>
                <div class="col-tn-6">
                    <div class="dashboardLabel">{translate text="All Time" isAdminFacing=true}</div>
                    <div class="dashboardValue">{$statusStats.usageAllTime|number_format}</div>
                </div>
            </div>
            </div>
		{/foreach}
</div>
<div class="row">
<div class="col-sm-12" style="padding-top: 2em;">
		<form action="/MaterialsRequest/Dashboard" method="get">
			<input type="hidden" name="location" value="{$selectedLocation}"/>
			<input type="submit" id="exportToExcel" name="exportToExcel" value="{translate text="Export to Excel" isAdminFacing=true}"  class="btn btn-default">
		</form>

		{* Export to Excel option *}
		</div>
</div>
	</div>
{/strip}