<form id="copyFacetGroupForm" class="form-horizontal" role="form">
	<input type="hidden" name="facetGroupId" id="facetGroupId" value="{$facetId}"/>

	<div class="form-group col-xs-12">
        <label for="groupName" class="control-label">{translate text="Name for New Group" isAdminFacing=true}</label>
        <input type="text" id="groupName" name="groupName" class="form-control">
	</div>

	<div class="form-group col-xs-12">
        <label for="displaySettingsSelector" class="control-label">{translate text="Apply to Grouped Work Display Settings" isAdminFacing=true}</label>
        <select id="displaySettingsSelector" name="displaySettingsSelector" class="form-control">
            <option value="-1">{translate text="None" isAdminFacing=true}</option>
            {foreach from=$displaySettings item=displaySetting}
                <option value="{$displaySetting.id}">{translate text=$displaySetting.name isAdminFacing=true}</option>
            {/foreach}
        </select>
    </div>

</form>