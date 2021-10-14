{strip}
	{if count($validLanguages) > 1}
		<div id="language-selection-header" class="navbar navbar-skinny" role="form">
			<div id="language-selection-dropdown" class="pull-right form-inline">
				<div class="form-group">
					<select aria-label="{translate text="Select a language for the catalog" inAttribute=true isPublicFacing=true}" id="selected-language" class="form-control-sm" onchange="return AspenDiscovery.setLanguage();">
						{foreach from=$validLanguages key=languageCode item=language}
							<option value="{$languageCode}"{if $userLang->code==$languageCode} selected="selected"{/if}>
								{$language->displayName}
							</option>
						{/foreach}
					</select>
				</div>
			</div>
		</div>
	{/if}
{/strip}