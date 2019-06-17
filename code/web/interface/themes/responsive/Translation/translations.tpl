<div id="main-content" class="col-md-12">
	<h3>Translations</h3>
	<form class="form-inline row">
		<div class="form-group col-xs-12">
			{if $translationModeActive}
				<button class="btn btn-primary" type="submit" name="stopTranslationMode">{translate text="Exit Translation Mode"}</button>
			{else}
				<button class="btn btn-primary" type="submit" name="startTranslationMode">{translate text="Start Translation Mode"}</button>
			{/if}
		</div>
	</form>

</div>