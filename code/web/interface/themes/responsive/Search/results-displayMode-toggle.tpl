{strip}
	{* User's viewing mode toggle switch *}
	<div class="row" id="selected-browse-label">{* browse styling replicated here *}
		<div class="btn-group btn-group-sm" data-toggle="buttons">
			<label for="covers" title="Covers" class="btn btn-sm btn-default"><input onchange="VuFind.Searches.toggleDisplayMode(this.id)" type="radio" id="covers">
				<span class="thumbnail-icon"></span><span> Covers</span>
			</label>
			<label for="list" title="Lists" class="btn btn-sm btn-default"><input onchange="VuFind.Searches.toggleDisplayMode(this.id)" type="radio" id="list">
				<span class="list-icon"></span><span> List</span>
			</label>
		</div>
		<div class="btn-group" id="hideSearchCoversSwitch"{if $displayMode != 'list'} style="display: none;"{/if}>
			<label for="hideCovers" class="checkbox{* control-label*}"> Hide Covers
				<input id="hideCovers" type="checkbox" onclick="VuFind.Account.toggleShowCovers(!$(this).is(':checked'))" {if $showCovers == false}checked="checked"{/if}>
			</label>
		</div>
	</div>
{/strip}