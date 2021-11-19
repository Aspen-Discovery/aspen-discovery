<div id="main-content">
	<div class="btn-group">
		<a class="btn btn-sm btn-default" href="/ILS/TranslationMaps?objectAction=edit&amp;id={$id}">{translate text="Edit Map" isAdminFacing=true}</a>
		{foreach from=$additionalObjectActions item=action}
			{if $smarty.server.REQUEST_URI != $action.url}
				<a class="btn btn-default btn-sm" href='{$action.url}'>{translate text=$action.text isAdminFacing=true}</a>
			{/if}
		{/foreach}
		<a class="btn btn-sm btn-default" href='/ILS/TranslationMaps?objectAction=list'><i class="fas fa-arrow-alt-circle-left"></i> {translate text="Return to List" isAdminFacing=true}</a>
	</div>
	<h2>{$mapName}</h2>
	<div class="helpTextUnsized well">
		<p>{translate text="Translation map values can be loaded from either an INI formatted record or from a CSV formatted record." isAdminFacing=true}
		</p>
		<dl class="dl-horizontal">
			<dt>{translate text="INI" isAdminFacing=true}</dt> <dd><code>{translate text="value = translation" isAdminFacing=true}</code></dd>

			<dt>{translate text="CSV" isAdminFacing=true}</dt> <dd><code>{translate text="value, translation" isAdminFacing=true}</code></dd>
		</dl>

		<div class="alert alert-info">
			<ul>
				<li>	{translate text='The translation and value can optionally have quotes surrounding it.' isAdminFacing=true} <br/><code>{translate text='"value" = "translation"' isAdminFacing=true}</code></li>
				<li>	{translate text='Lines starting with # will be ignored as comment lines.' isAdminFacing=true}<br/><code>{translate text='#value = translation' isAdminFacing=true}</code><br>
					{translate text='(Values that are or start with # must be entered manually.)' isAdminFacing=true}</li>
				<li>	{translate text='It is important to include values that have empty translations. ' isAdminFacing=true}<br/><code>{translate text='value = ' isAdminFacing=true}</code></li>
			</ul>
		</div>

	</div>
	<form name="importTranslationMaps" action="/ILS/TranslationMaps" method="post" id="importTranslationMaps">
		<div>
			<input type="hidden" name="objectAction" value="doAppend" id="objectAction">
			<input type="hidden" name="id" value="{$id}">
			<p>
				<textarea rows="20" cols="80" name="translationMapData" class="form-control"></textarea>
			</p>
			<input type="submit" name="reload" value="{translate text="Append/Overwrite Values" isAdminFacing=true}" class="btn btn-primary" onclick="setObjectAction('doAppend')">
			<input type="submit" name="reload" value="{translate text="Reload Map Values" isAdminFacing=true}" class="btn btn-danger" onclick="if(confirm('{translate text="Confirm Map Reload? This will erase all current translations for this map." isAdminFacing=true}'))setObjectAction('doReload');else return false;">
		</div>
	</form>
</div>

<script type="text/javascript">
	{literal}
	function setObjectAction(newValue){
		$("#objectAction").val(newValue);
	}
	{/literal}
</script>
