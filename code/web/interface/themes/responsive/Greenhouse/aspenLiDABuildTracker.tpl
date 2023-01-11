{strip}
	<div class="row">
		<div class="col-xs-12 col-md-9">
			<h1 id="pageTitle">{$pageTitleShort}</h1>
		</div>
	</div>

	<form class="form well" id="aspenLiDABuildTracker" style="padding-bottom:1em">
		<div class="row align-middle">
			<div class="col-xs-12 col-md-2">
		        <div class="btn-group form-check form-switch" id="showUnsupportedOnlySwitch">
		              <input class="form-check-input" type="checkbox" id="showUnsupportedOnly" name="showUnsupportedOnly" {if $showUnsupportedOnly}checked{/if} style="position: relative; top: 5px">
		              <label class="form-check-label" style="line-height: 18px" for="showUnsupportedOnly">{translate text='Show Unsupported Only' isPublicFacing=true}</label>
		        </div>
		    </div>
			<div class="col-xs-12 col-md-2">
                <div class="form-group">
					<label for="appToShow">{translate text='Application to Show' isAdminFacing=true}</label>
					<select name="appToShow" id="appToShowSelect" class="form-control input-sm">
                      {foreach from=$appToShowOptions item=propertyName key=propertyValue}
                        <option value='{$propertyValue}' {if !empty($appToShow) && ($appToShow == $propertyValue)} selected='selected'{/if}>{translate text=$propertyName inAttribute=true isAdminFacing=true}</option>
                      {/foreach}
                    </select>
                </div>
			</div>
			<div class="col-xs-12 col-md-2">
                <div class="form-group">
					<label for="channelToShow">{translate text='Channel to Show' isAdminFacing=true}</label>
					<select name="channelToShow" id="channelToShowSelect" class="form-control input-sm">
                      {foreach from=$channelToShowOptions item=propertyName key=propertyValue}
                        <option value='{$propertyValue}' {if !empty($channelToShow) && ($channelToShow == $propertyValue)} selected='selected'{/if}>{translate text=$propertyName inAttribute=true isAdminFacing=true}</option>
                      {/foreach}
                    </select>
                </div>
            </div>
			<div class="col-xs-12 col-md-2">
                <div class="form-group">
					<label for="platformToShow">{translate text='Platform to Show' isAdminFacing=true}</label>
					<select name="platformToShow" id="platformToShowSelect" class="form-control input-sm">
                      {foreach from=$platformToShowOptions item=propertyName key=propertyValue}
                        <option value='{$propertyValue}' {if !empty($platformToShow) && ($platformToShow == $propertyValue)} selected='selected'{/if}>{translate text=$propertyName inAttribute=true isAdminFacing=true}</option>
                      {/foreach}
                    </select>
                </div>
            </div>
            <div class="col-xs-12 col-md-2">
                <div class="form-group">
                    <label for="versionToShow">{translate text='Version to Show' isAdminFacing=true}</label>
                    <input class="form-control input-sm" type='text' name='versionToShow' id='versionToShow' value="{$versionToShow}"/>
                </div>
            </div>
			<div class="col-xs-12 col-md-2">
				<div class="btn-group btn-group-sm btn-group-justified" role="group">
				<div class="btn-group" role="group">
					<button class="btn btn-primary" type="submit">{translate text="Apply" isAdminFacing=true}</button>
                </div>
                <div class="btn-group" role="group">
                    <a class="btn btn-default" href="{$url}/Greenhouse/AspenLiDABuildTracker">{translate text="Reset" isAdminFacing=true}</a>
                </div>
                </div>
			</div>
		</div>
		<script type="text/javascript">
			{literal}
			$(function(){ $('input[type="checkbox"][data-switch]').bootstrapSwitch()});
			{/literal}
		</script>
	</form>

	<div class="buildTrackerRegion">
		<table class="table table-striped table-condensed table-sticky" id="buildTrackerTable" aria-label="{translate text="List of Aspen LiDA builds" inAttribute=true isAdminFacing=true}">
			<thead>
				<tr>
					<th>{translate text="Name" isAdminFacing=true}</th>
					<th>{translate text="Version" isAdminFacing=true}</th>
					<th>{translate text="Build" isAdminFacing=true}</th>
					<th>{translate text="Patch" isAdminFacing=true}</th>
					<th>{translate text="Channel" isAdminFacing=true}</th>
					<th>{translate text="Platform" isAdminFacing=true}</th>
					<th>{translate text="Completed at" isAdminFacing=true}</th>
					<th>{translate text="EAS Update" isAdminFacing=true}</th>
					<th>{translate text="Supported" isAdminFacing=true}</th>
					<th>{translate text="Download" isAdminFacing=true}</th>
				</tr>
			</thead>
			<tbody>
				{foreach from=$allBuilds item="build"}
					<tr {if $build.isSupported}class="success"{else} class="danger"{/if}>
						<td style="vertical-align: middle">{$build.name}</td>
						<td style="vertical-align: middle">{$build.version}</td>
						<td style="vertical-align: middle">{$build.buildVersion}</td>
						<td style="vertical-align: middle">{$build.patch}</td>
						<td style="vertical-align: middle">{translate text=$build.channel isAdminFacing=true}</td>
						<td style="vertical-align: middle">
							{if $build.platform == 'android'}
								<i class="fab fa-android"></i>
							{else}
								<i class="fab fa-apple"></i>
							{/if} {$build.platform}
						</td>
						<td style="vertical-align: middle">
							{if $build.updateCreated}
								{$build.updateCreated|date_format:"%D %I:%M %p"}
							{else}
								{$build.completedAt|date_format:"%D %I:%M %p"}
							{/if}
						</td>
						<td style="vertical-align: middle">
                            {if $build.isEASUpdate}
                                <i class="fas fa-check-square"></i> {translate text="Yes" isAdminFacing=true}
                            {/if}
                        </td>
						<td style="vertical-align: middle">
							{if $build.isSupported}
								<i class="fas fa-check-square"></i> {translate text="Yes" isAdminFacing=true}
							{else}
								<i class="fas fa-exclamation-triangle"></i> {translate text="No" isAdminFacing=true}
							{/if}
						</td>
						<td style="vertical-align: middle"><a class="btn btn-default btn-sm" href="{$build.artifact}"><i class="fas fa-download"></i> {translate text="Download" isAdminFacing=true} .{file_ext url=$build.artifact}</a></td>
					</tr>
				{/foreach}
			</tbody>
		</table>
	</div>
{/strip}

<script type="text/javascript">
{literal}
	$("#buildTrackerTable").tablesorter({cssAsc: 'sortAscHeader', cssDesc: 'sortDescHeader', cssHeader: 'unsortedHeader', widgets:['zebra', 'filter'] });
{/literal}
</script>