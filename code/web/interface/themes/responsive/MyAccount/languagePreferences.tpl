{strip}
	<div>
		<form method="post" name="languagePreferencesForm" id="languagePreferencesForm" action="{$path}/AJAX/JSON" class="form">
			<input type="hidden" name="method" value="saveLanguagePreferences">
			<p class="alert alert-info">
				{translate text="set_language_preferences_notice" defaultText="You have updated your language preferences to %1%." 1=$userLang->displayName}
			</p>
			<div class="form-group">
				<label for="searchPreferenceLanguage">{translate text="Do you want prefer materials in %1%?" 1=$userLang->displayName}</label>
				<select name="searchPreferenceLanguage" id="searchPreferenceLanguage" class="form-control">
					<option value="0">{translate text='language_preference_interfiled' defaultText="No, show materials interfiled with materials in other languages"}</option>
					<option value="1">{translate text='language_preference_above' defaultText="Yes, show materials above other languages"}</option>
					<option value="2">{translate text='language_preference_only_preferred' defaultText="Yes, only show materials in my preferred language"}</option>
				</select>
			</div>
		</form>
	</div>
{/strip}