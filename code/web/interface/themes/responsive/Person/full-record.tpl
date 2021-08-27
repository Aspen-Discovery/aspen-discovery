{strip}

	{* Search Navigation *}
	{include file="GroupedWork/search-results-navigation.tpl"}

	{if !empty($error)}
		<div class="row">
			<div class="alert alert-danger">
				{$error}
			</div>
		</div>
	{/if}

	<h1>
		{$person->firstName|escape} {$person->middleName|escape}{if $person->nickName} "{$person->nickName|escape}"{/if}{if $person->maidenName} ({$person->maidenName}){/if} {$person->lastName|escape}
	</h1>
	{if $userIsAdmin}
		<div class="btn-toolbar">
			<div class="btn-group">
				<a href='/Admin/People?objectAction=edit&amp;id={$id}' title='Edit this person' class='btn btn-xs btn-default'>
					{translate text="Edit"}
				</a>
				<a href='/Admin/Marriages?objectAction=add&amp;personId={$id}' title='Add a Marriage' class='btn btn-xs btn-default'>
					{translate text="Add Marriage"}
				</a>
				<a href='/Admin/Obituaries?objectAction=add&amp;personId={$id}' title='Add an Obituary' class='btn btn-xs btn-default'>
					{translate text="Add Obituary"}
				</a>
			</div>
			<a href='/Admin/People?objectAction=delete&amp;id={$id}' title='Delete this person' class='btn btn-xs btn-danger' onclick='return confirm("Removing this person will permanently remove them from the system.	Are you sure?")'>
				{translate text="Delete"}
			</a>
		</div>
	{/if}
	{* Display Book Cover *}
	<div class="row">
		<div class="col-xs-4 col-sm-5 col-md-4 col-lg-3 text-center">
			{if $disableCoverArt != 1}
				<div id="recordCover" class="text-center">
					{if $person->picture}
						<a target='_blank' href='/files/original/{$person->picture|escape}' aria-hidden="true"><img src="/files/medium/{$person->picture|escape}" class="alignleft listResultImage" alt="{translate text='Picture' inAttribute=true}"></a><br>
					{else}
						<img src="/interface/themes/responsive/images/person.png" class="alignleft listResultImage" alt="{translate text='No Cover Image' inAttribute=true isPublicFacing=true}" aria-hidden="true"><br>
					{/if}
				</div>
			{/if}
		</div>
		<div {*id="main-content"*} class="col-xs-8 col-sm-7 col-md-8 col-lg-9">
			{if $person->otherName}
				<div class='personDetail'><span class='result-label'>{translate text="Other Names"} </span><span class='personDetailValue'>{$person->otherName|escape}</span></div>
			{/if}
			{if $birthDate}
				<div class='personDetail'><span class='result-label'>{translate text="Birth Date"} </span><span class='personDetailValue'>{$birthDate}</span></div>
			{/if}
			{if $deathDate}
				<div class='personDetail'><span class='result-label'>{translate text="Death Date"} </span><span class='personDetailValue'>{$deathDate}</span></div>
			{/if}
			{if $person->ageAtDeath}
				<div class='personDetail'><span class='result-label'>{translate text="Age at Death"} </span><span class='personDetailValue'>{$person->ageAtDeath|escape}</span></div>
			{/if}
			{if $person->sex}
				<div class='personDetail'><span class='result-label'>{translate text="Gender"} </span><span class='personDetailValue'>{$person->sex|escape}</span></div>
			{/if}
			{if $person->race}
				<div class='personDetail'><span class='result-label'>{translate text="Race"} </span><span class='personDetailValue'>{$person->race|escape}</span></div>
			{/if}
			{if $person->veteranOf}
				{implode subject=$person->veteranOf glue=", " assign='veteranOf'}
				<div class='personDetail'><span class='result-label'>{translate text="Veteran Of"} </span><span class='personDetailValue'>{$veteranOf}</span></div>
			{/if}
			{if $person->causeOfDeath}
				<div class='personDetail'><span class='result-label'>{translate text="Cause of Death"} </span><span class='personDetailValue'>{$person->causeOfDeath|escape}</span></div>
			{/if}
		</div>
	</div>
	{if count($marriages) > 0 || $userIsAdmin}
		<h2 class="blockhead">{translate text="Marriages"}</h2>
		{foreach from=$marriages item=marriage}
			<div class="marriageTitle">
				{$marriage.spouseName}{if $marriage.formattedMarriageDate} - {$marriage.formattedMarriageDate}{/if}
				{if $userIsAdmin}
					<div class="btn-toolbar">
						<a href='/Admin/Marriages?objectAction=edit&amp;id={$marriage.marriageId}' title='Edit this Marriage' class='btn btn-xs btn-default'>
		                    {translate text="Edit"}
						</a>
						<a href='/Admin/Marriages?objectAction=delete&amp;id={$marriage.marriageId}' title='Delete this Marriage' onclick='return confirm("Removing this marriage will permanently remove it from the system.	Are you sure?")' class='btn btn-xs btn-danger'>
							{translate text="Delete"}
						</a>
					</div>
				{/if}
			</div>
			{if $marriage.comments}
				<div class="marriageComments">{$marriage.comments|escape}</div>
			{/if}
		{/foreach}

	{/if}
	{if $person->cemeteryName || $person->cemeteryLocation || $person->mortuaryName || $person->cemeteryAvenue || $person->lot || $person->block || $person->grave || $person->addition}
		<h2 class="blockhead">Burial Details</h2>
		{if $person->cemeteryName}
		<div class='personDetail'><span class='result-label'>{translate text="Cemetery Name"} </span><span class='personDetailValue'>{$person->cemeteryName}</span></div>
		{/if}
		{if $person->cemeteryLocation}
		<div class='personDetail'><span class='result-label'>{translate text="Cemetery Location"} </span><span class='personDetailValue'>{$person->cemeteryLocation}</span></div>
		{/if}
		{if $person->cemeteryAvenue}
			<div class='personDetail'><span class='result-label'>{translate text="Cemetery Avenue"} </span><span class='personDetailValue'>{$person->cemeteryAvenue}</span></div>
		{/if}
		{if $person->addition || $person->lot || $person->block || $person->grave}
		<div class='personDetail'><span class='result-label'>{translate text="Burial Location"}</span>
		<span class='personDetailValue'>
			{if $person->addition}{translate text="Addition"} {$person->addition}{if $person->block || $person->lot || $person->grave}, {/if}{/if}
			{if $person->block}{translate text="Block"} {$person->block}{if $person->lot || $person->grave}, {/if}{/if}
			{if $person->lot}{translate text="Lot"} {$person->lot}{if $person->grave}, {/if}{/if}
			{if $person->grave}{translate text="Grave"} {$person->grave}{/if}
		</span></div>
		{if $person->tombstoneInscription}
		<div class='personDetail'><span class='result-label'>{translate text="Tombstone Inscription"} </span><div class='personDetailValue'>{$person->tombstoneInscription}</div></div>
		{/if}
		{/if}
		{if $person->mortuaryName}
		<div class='personDetail'><span class='result-label'>{translate text="Mortuary Name"} </span><span class='personDetailValue'>{$person->mortuaryName}</span></div>
		{/if}
	{/if}
	{if count($obituaries) > 0 || $userIsAdmin}
		<h2 class="blockhead">{translate text="Obituaries"}</h2>
		{foreach from=$obituaries item=obituary}
			<div class="obituaryTitle">
			{$obituary.source}{if $obituary.sourcePage} page {$obituary.sourcePage}{/if}{if $obituary.formattedObitDate} - {$obituary.formattedObitDate}{/if}
			{if $userIsAdmin}
				<div class="btn-toolbar">
					<a href='/Admin/Obituaries?objectAction=edit&amp;id={$obituary.obituaryId}' title='Edit this Obituary' class='btn btn-xs btn-default'>
						{translate text="Edit"}
					</a>
					<a href='/Admin/Obituaries?objectAction=delete&amp;id={$obituary.obituaryId}' title='Delete this Obituary' onclick='return confirm("Removing this obituary will permanently remove it from the system.	Are you sure?")' class='btn btn-xs btn-danger'>
						{translate text="Delete"}
					</a>
				</div>
			{/if}
			</div>
			{if $obituary.contents && $obituary.picture}
				<div class="obituaryText">{if $obituary.picture|escape}<a href='/files/original/{$obituary.picture|escape}'><img class='obitPicture' src='/files/medium/{$obituary.picture|escape}'></a>{/if}{$obituary.contents|escape}</div>
				<div class="clearer"></div>
			{elseif $obituary.contents}
				<div class="obituaryText">{$obituary.contents|escape|replace:"\r":"<br>"}</div>
				<div class="clearer"></div>
			{elseif $obituary.picture}
				<div class="obituaryPicture">{if $obituary.picture|escape}<a href='/files/original/{$obituary.picture|escape}'><img class='obitPicture' src='/files/medium/{$obituary.picture|escape}'></a>{/if}</div>
				<div class="clearer"></div>
			{/if}

		{/foreach}

	{/if}
	{if $person->ledgerVolume || $person->ledgerYear || $person->ledgerEntry}
		<h2 class="blockhead">{translate text="Ledger Information"}</h2>
		{if $person->ledgerVolume}
			<div class='personDetail'><span class='result-label'>{translate text="Volume"}</span><span class='result-value-bold'>{$person->ledgerVolume}</span></div>
		{/if}
		{if $person->ledgerYear}
			<div class='personDetail'><span class='result-label'>{translate text="Year"}</span><span class='personDetailValue'>{$person->ledgerYear}</span></div>
		{/if}
		{if $person->ledgerYear}
			<div class='personDetail'><span class='result-label'>{translate text="Entry"}</span><span class='personDetailValue'>{$person->ledgerEntry}</span></div>
		{/if}
	{/if}
	<h2 class="blockhead">{translate text="Comments"}</h2>
	{if $person->comments}
	<div class='personComments'>{$person->comments|escape}</div>
	{else}
	<div class='personComments'>{translate text="No comments found."}</div>
	{/if}
{/strip}