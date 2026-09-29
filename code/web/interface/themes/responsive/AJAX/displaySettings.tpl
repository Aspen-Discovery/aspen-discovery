{strip}
	<form action="" id="displaySettingsForm" method="post">
		{if count($validLanguages) > 1}
			<div class="form-group">
				<label for="preferredLanguage" class="control-label">{translate text='Language to display catalog in' isPublicFacing=true}</label>
				<select id="preferredLanguage" name="preferredLanguage" class="form-control">
					{foreach from=$validLanguages key=languageCode item=language}
						<option value="{$languageCode}"{if $userLang->code==$languageCode} selected="selected"{/if}>
							{$language->displayName|escape}
						</option>
					{/foreach}
				</select>
			</div>
		{else}
			<input type="hidden" id="profileLanguage" name="profileLanguage" value="{$userLang->code}">
		{/if}

		{if count($allActiveThemes) > 1}
			<div class="form-group">
				<label for="preferredTheme" class="control-label">{translate text='Display Mode' isPublicFacing=true}</label>
				<select id="preferredTheme" name="preferredTheme" class="form-control">
					{foreach from=$allActiveThemes key=themeId item=themeName}
						<option value="{$themeId}"{if $activeThemeId==$themeId} selected="selected"{/if}>
							{$themeName}
						</option>
					{/foreach}
				</select>
			</div>
		{else}
			<input type="hidden" id="preferredTheme" name="preferredTheme" value="{$activeThemeId}">
		{/if}
		<div class="form-group propertyRow">
			<label for="preferredTextSize" class="control-label">{translate text='Preferred Text Size' isPublicFacing=true}</label>
			<select id="preferredTextSize" name="preferredTextSize" class="form-control">
				{foreach from=$fontSizeOptions key=fontSize item=fontSizeDisplay}
					<option value="{$fontSize}"{if $displaySettingTextSize==$fontSize} selected="selected"{/if}>
						{$fontSizeDisplay|escape}
					</option>
				{/foreach}
			</select>
		</div>
	</form>
{/strip}
