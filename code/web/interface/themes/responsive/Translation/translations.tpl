<div id="main-content" class="col-md-12">
	<h3>Translations</h3>
	<form class="form-inline row">
		<div class="form-group col-xs-12">
			{if $translationModeActive}
				<button class="btn btn-primary" type="submit" name="stopTranslationMode">{translate text="Exit Translation Mode"}</button>
			{else}
				<button class="btn btn-primary" type="submit" name="startTranslationMode">{translate text="Start Translation Mode"}</button>
			{/if}

			<button class="btn btn-primary" type="submit" name="exportAllTranslations">{translate text="Export All Translations"}</button>
			<button class="btn btn-primary" type="submit" name="importTranslations">{translate text="Import Translations"}</button>
		</div>
	</form>

	<form method="post">
		{foreach from=$allTerms item=term}
			<div class="row" id="term_{$term->id}">
				<div class="col-sm-1">{$term->id}</div>
				<div class="col-sm-3"><label for="translation_{$term->id}">{$term->term}</label></div>
				<div class="col-sm-4">
					<input type="hidden" name="translation_changed[{$term->id}]" id="translation_changed_{$term->id}" value="0">
					<textarea class="form-control" rows="1" cols="40" name="translation[{$term->id}]" id="translation_{$term->id}" onchange="$('#translation_changed_{$term->id}').val(1)">{$term->translation}</textarea>
				</div>
				<div class="col-sm-3">
					<a href="{$term->samplePageUrl}">{$term->samplePageUrl}</a>
				</div>
				<div class="col-sm-1">
					<a href="#" onclick="return AspenDiscovery.deleteTranslationTerm('{$term->id}');">
						{* On delete action, also remove class 'required' to turn off form validation of the deleted input; so that the form can be submitted by the user  *}
						<img src="{$path}/images/silk/delete.png" alt="delete term {$term->term|escape}">
					</a>
				</div>
			</div>
		{/foreach}
		<div class="form-group">
			<button type="submit" name="submit" class="btn btn-primary">{translate text="Save Translations"}</button>
		</div>
	</form>
</div>