{strip}
	<form enctype="multipart/form-data" method="post" action="/Greenhouse/AJAX" id="scheduleUpdateForm" class="form-horizontal" role="form">
		<input type="hidden" name="sitesToUpdate" id="sitesToUpdate" value="{$allBatchUpdateSites}">
		<div class="form-group">
            <label for="updateToVersion" class="col-sm-4">{translate text='Update to Version' isAdminFacing=true}</label>
            <div class="col-sm-8">
                <select name="updateToVersion" id="updateToVersion" class="form-control" aria-label="{translate text="Update to Version" isAdminFacing=true}">
                    {foreach from=$releases item=release}
                        <option value="{$release.version}">{$release.name} ({$release.date})</option>
                    {/foreach}
                </select>
            </div>
        </div>
        <div class="form-group">
            <label for="updateType" class="col-sm-4">{translate text='Update Type' isAdminFacing=true}</label>
            <div class="col-sm-8">
                <div class="row">
                    <div class="col-sm-3"><label for="updateTypePatch"><input type="radio" name="updateType" value="patch" id="updateTypePatch" checked> {translate text="Patch" isPublicFacing=true}</label></div>
                    <div class="col-sm-7"><label for="updateTypeComplete"><input type="radio" name="updateType" value="complete" id="updateTypeComplete"> {translate text="Complete/Full (Slower)" isPublicFacing=true}</label></div>
                </div>
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-4">{translate text='Run Update' isAdminFacing=true}</label>
            <div class="col-sm-8">
	            <div class="row">
	                <div class="col-sm-3"><label for="runTypeScheduled"><input type="radio" name="runType" value="scheduled" id="runTypeScheduled" checked onchange="$('#runUpdateOnField').show()"> {translate text="Later" isPublicFacing=true}</label></div>
	                <div class="col-sm-7"><label for="runTypeNow"><input type="radio" name="runType" value="now" id="runTypeNow" onchange="$('#runUpdateOnField').hide()"> {translate text="Now" isPublicFacing=true}</label></div>
	            </div>
            </div>
        </div>
        <div id="runUpdateOnField" class="form-group">
            <label for="runUpdateOn" class="col-sm-4">{translate text='When To Update' isAdminFacing=true}</label>
            <div class="col-sm-8"><input class="form-control" name="runUpdateOn" id="runUpdateOn"></div>
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