{strip}
	<form enctype="multipart/form-data" method="post" action="/Greenhouse/AJAX" id="scheduleUpdateForm" class="form-horizontal" role="form">
		<input type="hidden" name="siteToUpdate" id="siteToUpdate" value="{$siteToUpdate->id}">
		<div class="form-group">
			<label for="currentVersion" class="col-sm-3">{translate text='Current Version' isAdminFacing=true}</label>
			<div class="col-sm-9">{$siteToUpdate->version}</div>
		</div>
		<div class="form-group">
            <label for="updateToVersion" class="col-sm-3">{translate text='Update to Version' isAdminFacing=true}</label>
            <div class="col-sm-9">
                <select name="updateToVersion" id="updateToVersion" class="form-control" aria-label="{translate text="Update to Version" isAdminFacing=true}">
                    {foreach from=$releases item=release}
                        {if $siteToUpdate->getSiteTypeName() == "Library Partner Test"}
                            <option value="{$release.version}" {if $release.dateTesting > $smarty.now|date_format:"%Y-%m-%d"}disabled{/if}>{$release.name} {if $release.dateTesting > $smarty.now|date_format:"%Y-%m-%d"}- Not available until {$release.dateTesting|date_format:"%b %d, %Y"}{else}({$release.dateTesting|date_format:"%b %d, %Y"}){/if}</option>
                        {elseif $siteToUpdate->getSiteTypeName() == "Library Partner" || $siteToUpdate->getSiteTypeName() == "Demo"}
                            <option value="{$release.version}" {if $release.date > $smarty.now|date_format:"%Y-%m-%d"}disabled{/if}>{$release.name}  {if $release.date > $smarty.now|date_format:"%Y-%m-%d"}- Not available until {$release.date|date_format:"%b %d, %Y"}{else}({$release.date|date_format:"%b %d, %Y"}){/if}</option>
                        {else}
                            <option value="{$release.version}">{$release.name} ({$release.date|date_format:"%b %d, %Y"})</option>
                        {/if}
                    {/foreach}
                </select>
            </div>
        </div>
        <div class="form-group">
            <label for="updateType" class="col-sm-3">{translate text='Update Type' isAdminFacing=true}</label>
            <div class="col-sm-9">
                <div class="row">
                    <div class="col-sm-2"><label for="updateTypePatch"><input type="radio" name="updateType" value="patch" id="updateTypePatch" checked> {translate text="Patch" isPublicFacing=true}</label></div>
                    <div class="col-sm-10"><label for="updateTypeComplete"><input type="radio" name="updateType" value="complete" id="updateTypeComplete"> {translate text="Complete/Full (Slower)" isPublicFacing=true}</label></div>
                </div>
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-3">{translate text='Run Update' isAdminFacing=true}</label>
            <div class="col-sm-9">
	            <div class="row">
	            	<div class="col-sm-2"><label for="runTypeScheduled"><input type="radio" name="runType" value="scheduled" id="runTypeScheduled" checked onchange="$('#runUpdateOnField').show()"> {translate text="Later" isPublicFacing=true}</label></div>
	                <div class="col-sm-10"><label for="runTypeNow"><input type="radio" name="runType" value="now" id="runTypeNow" onchange="$('#runUpdateOnField').hide()"> {translate text="Now" isPublicFacing=true}</label></div>
	            </div>
            </div>
        </div>
        <div id="runUpdateOnField" class="form-group">
            <label for="runUpdateOn" class="col-sm-3">{translate text='When To Update' isAdminFacing=true}</label>
            <div class="col-sm-9"><input class="form-control" name="runUpdateOn" id="runUpdateOn" placeholder={$smarty.now|date_format:"%Y-%m-%d"}></div>
            <script type="text/javascript">
                $(document).ready(function(){ldelim}
                    rome(runUpdateOn);
                {rdelim});
            </script>
        </div>
	</form>
	<script type="application/javascript">
        {literal}
        $("#scheduleUpdateForm").validate({
            submitHandler: function(){
                AspenDiscovery.Admin.scheduleUpdate()
            }
        });
        {/literal}
    </script>
{/strip}