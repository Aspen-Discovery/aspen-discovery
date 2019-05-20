<div id="main-content">
	{*<h1>{$shortPageTitle}</h1>*}

	<div class="btn-group">
		<a class="btn btn-sm btn-default" href="/ILS/TranslationMaps?objectAction=edit&amp;id={$id}">Edit Map</a>
		{foreach from=$additionalObjectActions item=action}
			{if $smarty.server.REQUEST_URI != $action.url}
				<a class="btn btn-default btn-sm" href='{$action.url}'>{$action.text}</a>
			{/if}
		{/foreach}
		<a class="btn btn-sm btn-default" href='/ILS/TranslationMaps?objectAction=list'>Return to List</a>
	</div>
	<h2>{$mapName}</h2>
	<div class="helpTextUnsized well">
		<p>Translation map values can be loaded from either an INI formatted record
			or from a CSV formatted record.
		</p>
		<dl class="dl-horizontal">
			<dt>INI :</dt> <dd><code>value = translation</code></dd>

			<dt>CSV :</dt> <dd><code>value, translation</code></dd>
		</dl>

		<div class="alert alert-info">
			<ul>
				<li>	The translation and value can optionally have quotes surrounding it. <code>"value" = "translation"</code></li>
				<li>		Lines starting with # will be ignored as comment lines.<code>#value = translation</code><br>
					(Values that are or start with # must be entered manually.)</li>
				<li>		It is important to include values that have empty translations i.e. <code>value = </code></li>
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
			<input type="submit" name="reload" value="Append/Overwrite Values" class="btn btn-primary" onclick="setObjectAction('doAppend')">
			<input type="submit" name="reload" value="Reload Map Values" class="btn btn-danger" onclick="if(confirm('Confirm Map Reload? This will erase all current translations for this map.'))setObjectAction('doReload');else return false;">
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
