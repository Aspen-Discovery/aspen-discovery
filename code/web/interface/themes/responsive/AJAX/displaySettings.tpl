{strip}
	<form action="" id="displaySettingsForm" method="post" class="form-horizontal">
		{if count($validLanguages) > 1}
			<div class="form-group">
				<div class="col-xs-4"><label for="preferredLanguage" class="control-label">{translate text='Language to display catalog in' isPublicFacing=true}</label></div>
				<div class="col-xs-8">
					<select id="preferredLanguage" name="preferredLanguage" class="form-control">
						{foreach from=$validLanguages key=languageCode item=language}
							<option value="{$languageCode}"{if $userLang->code==$languageCode} selected="selected"{/if}>
								{$language->displayName}
							</option>
						{/foreach}
					</select>
				</div>
			</div>
		{else}
			<input type="hidden" id="profileLanguage" name="profileLanguage" value="{$userLang->code}">
		{/if}

		{if count($allActiveThemes) > 1}
			<div class="form-group">
				<div class="col-xs-4"><label for="preferredLanguage" class="control-label">{translate text='Display Mode' isPublicFacing=true}</label></div>
				<div class="col-xs-8">
					<select id="preferredTheme" name="preferredTheme" class="form-control">
						{foreach from=$allActiveThemes key=themeId item=themeName}
							<option value="{$themeId}"{if $activeThemeId==$themeId} selected="selected"{/if}>
								{$themeName}
							</option>
						{/foreach}
					</select>
				</div>
			</div>
		{else}
			<input type="hidden" id="preferredTheme" name="preferredTheme" value="{$activeThemeId}">
		{/if}
	</form>
{/strip}