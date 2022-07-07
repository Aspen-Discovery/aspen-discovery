{if count($allSites) > 1}
	<form name="selectInterface" id="selectInterface" class="form-inline row">
		<div class="form-group col-tn-12">
			<label for="site" class="control-label">{translate text="Site to show stats for" isAdminFacing=true}</label>&nbsp;
			<select id="site" name="site" class="form-control input-sm" onchange="$('#selectInterface').submit()">
				{foreach from=$allSites key=siteId item=siteName}
					<option value="{$siteId}" {if $siteId == $selectedSite}selected{/if}>{$siteName}</option>
				{/foreach}
			</select>
		</div>
	</form>
{/if}