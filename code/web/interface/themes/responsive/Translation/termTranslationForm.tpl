{strip}
	<div>
		<form method="post" name="translateTermForm" id="translateTermForm" action="/AJAX/JSON" class="form">
			<div>
				<input type="hidden" name="termId" value="{$translationTerm->id}" id="termId">
				<input type="hidden" name="translationId" value="{$translation->id}" id="translationId">
				<input type="hidden" name="method" value="translateTerm">
				{if !empty($englishTranslation)}
					<div class="form-group">
						<label for="englishTranslation" class="control-label">{translate text='English Translation' isAdminFacing=true}</label>
						<textarea id="englishTranslation" name="englishTranslation" class="form-control" readonly>{$englishTranslation->translation}</textarea>
					</div>
				{/if}

				<div class="form-group">
					<label for="translation" class="control-label">{translate text=Translation isAdminFacing=true}</label>
					<textarea id="translation" name="translation" class="form-control required">{$translation->translation}</textarea>
				</div>
			</div>
		</form>
	</div>
{/strip}