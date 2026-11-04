{assign var=sourceVariableName value="`$propName`Default"}
{if is_null($object->$sourceVariableName) || $object->$sourceVariableName === ''}
	{assign var=fontSource value=1}
{else}
	{assign var=fontSource value=$object->$sourceVariableName}
{/if}
{assign var=uploadProperty value=$property.uploadProperty|default:''}
<div id="{$propName}-sources">
	<label class="radio-inline">
		<input type="radio" name="{$propName}-source" value="1" {if $fontSource == 1}checked="checked"{/if} {if !empty($property.readOnly)}disabled{/if} onchange="AspenDiscovery.Admin.updateFontSource('{$propName}', '{$uploadProperty}')">
		{translate text="Use default" isAdminFacing=true}{if !empty($property.inheritedFont)} ({$property.inheritedFont|escape}){/if}
	</label>
	<label class="radio-inline">
		<input type="radio" name="{$propName}-source" value="0" {if $fontSource == 0}checked="checked"{/if} {if !empty($property.readOnly)}disabled{/if} onchange="AspenDiscovery.Admin.updateFontSource('{$propName}', '{$uploadProperty}')">
		{translate text="Google Fonts" isAdminFacing=true}
	</label>
	{if !empty($uploadProperty)}
		<label class="radio-inline">
			<input type="radio" name="{$propName}-source" value="2" {if $fontSource == 2}checked="checked"{/if} {if !empty($property.readOnly)}disabled{/if} onchange="AspenDiscovery.Admin.updateFontSource('{$propName}', '{$uploadProperty}')">
			{translate text="Uploaded file" isAdminFacing=true}
		</label>
	{/if}
</div>
{if !empty($property.inheritedFontNote)}<span id="{$propName}-default-note" class="help-block" style="margin-top:0"><small><i class="fas fa-info-circle"></i> {$property.inheritedFontNote|escape}</small></span>{/if}
<div class="row" id="{$propName}-google" style="margin-top: .5em;">
	<div class="col-sm-7">
		<input type="text" name='{$propName}' id='{$propName}' value="{$propValue|escape}" data-suggestions='{json_encode($property.validFonts)|escape:"html"}' data-empty-message="{$property.emptyNameMessage|escape}" data-not-found-message="{$property.notFoundMessage|escape}" maxlength="100" autocomplete="off" class='form-control font {if !empty($property.required)}required{/if}' {if !empty($property.readOnly)}readonly{/if} onchange="AspenDiscovery.Admin.loadGoogleFontPreview('{$propName}', true)">
		<div id="{$propName}-warning" class="text-danger" style="display: none; margin-top: .25em;"></div>
	</div>
	<div class="col-sm-5">
		<div id="{$propName}-sample-text" style="font-family: {$propValue|escape},arial; font-size: {if !empty($property.previewFontSize)}{$property.previewFontSize}{else}var(--aspen-base-font-size){/if}">
			English, Español, 中文(简体), עברית
		</div>
	</div>
</div>
<script type="text/javascript">
	$().ready(function () {ldelim}
		AspenDiscovery.Admin.initFontSuggestions('{$propName}');
		AspenDiscovery.Admin.updateFontSource('{$propName}', '{$uploadProperty}');
	{rdelim});
</script>
