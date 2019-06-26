{strip}
<div id="record{$summId|escape}" class="resultsList row">
	<div class="imageColumn col-md-3">
		<div class="selectTitle hidden-phone col-md-4">
			<label for="selected{if $summId}{$summId}{/if}" class="resultIndex checkbox"><strong>{$resultIndex}</strong>
				<input type="checkbox" name="selected[{$summId|escape:"url"}]" id="selected{$summId|escape:"url"}" style="display:none" />&nbsp;
			</label>
		</div>

		<div class="col-md-7 text-center">
			<a href="{$path}/Person/{$summId}">
			{if $summPicture}
			<img src="{$path}/files/thumbnail/{$summPicture}" class="alignleft listResultImage" alt="{translate text='Picture' inAttribute=true}"/><br />
			{else}
			<img src="{$path}/interface/themes/responsive/images/person.png" class="alignleft listResultImage" alt="{translate text='No Cover Image' inAttribute=true}"/><br />
			{/if}
			</a>
		</div>
	</div>

	<div class="col-md-9">
		<div class="row">
			{if $summScore}({$summScore}) {/if}
			<strong>
				<a href="{$path}/Person/{$summId}" class="title">{if !$summTitle}{translate text='Title not available'}{else}{$summTitle|removeTrailingPunctuation|truncate:180:"..."|highlight}{/if}</a>
				{if $summTitleStatement}
					<div class="searchResultSectionInfo">
					{$summTitleStatement|removeTrailingPunctuation|truncate:180:"..."|highlight}
					</div>
				{/if}
			</strong>
		</div>

		<div class="row">
			<div class="resultDetails col-md-9">
				{if $birthDate}
					<div class="row">
						<div class='result-label col-md-3'>{translate text="Born"} </div>
						<div class="col-md-9 result-value">{$birthDate}</div>
					</div>
				{/if}
				{if $deathDate}
					<div class="row">
						<div class='result-label col-md-3'>{translate text="Died"} </div>
						<div class="col-md-9 result-value">{$deathDate}</div>
					</div>
				{/if}
				{if $numObits}
					<div class="row">
						<div class='result-label col-md-3'>{translate text="Num. Obits"} </div>
						<div class="col-md-9 result-value">{$numObits}</div>
					</div>
				{/if}
				{if $dateAdded}
					<div class="row">
						<div class='result-label col-md-3'>{translate text="Added"} </div>
						<div class="col-md-9 result-value">{$dateAdded|date_format}</div>
					</div>
				{/if}
				{if $lastUpdate}
					<div class="row">
						<div class='result-label col-md-3'>{translate text="Last Updated"} </div>
						<div class="col-md-9 result-value">{$lastUpdate|date_format}</div>
					</div>
				{/if}
				
			</div>
		</div>
	</div>
</div>
{/strip}