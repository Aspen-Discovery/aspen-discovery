{strip}
	<div class="navbar navbar-skinny">
		<form method="post" name="languagePreferencesForm" id="languagePreferencesForm" action="/AJAX/JSON" class="form form-inline pull-right">
			<input type="hidden" name="method" value="saveLanguagePreferences">
			<div class="form-group">
				<label for="searchPreferenceLanguage" class="control-label">{translate text="Prefer materials in %1%?" 1=$userLang->displayName isPublicFacing=true}</label>&nbsp;
				<select name="searchPreferenceLanguage" id="searchPreferenceLanguage" class="form-control-sm">
					<option value="0" {if $searchPreferenceLanguage == 0}selected{/if}>{translate text="No, show interfiled with other languages" isPublicFacing=true inAttribute=true}</option>
					<option value="1" {if $searchPreferenceLanguage == 1}selected{/if}>{translate text="Yes, show above other languages" isPublicFacing=true inAttribute=true}</option>
					<option value="2" {if $searchPreferenceLanguage == 2}selected{/if}>{translate text="Yes, only show preferred language" isPublicFacing=true inAttribute=true}</option>
				</select>
			</div>
			<button type="submit" class="btn btn-sm btn-default" onclick='return AspenDiscovery.saveLanguagePreferences()'>{translate text="Apply" isPublicFacing=true}</button>
		</form>
	</div>
{/strip}