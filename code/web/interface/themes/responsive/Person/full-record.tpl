{strip}

	{* Search Navigation *}
	{include file="GroupedWork/search-results-navigation.tpl"}

	{if !empty($error)}
		<div class="full-record-error row">
			<div class="alert alert-danger">
				{$error}
			</div>
		</div>
	{/if}

	<h1 class="full-record-title">
		{$person->firstName|escape} {$person->middleName|escape}{if $person->nickName} "{$person->nickName|escape}"{/if}{if $person->maidenName} ({$person->maidenName}){/if} {$person->lastName|escape}
	</h1>
	{if !empty($userIsAdmin)}
		<div class="btn-toolbar">
			<div class="btn-group">
				<a href='/Admin/People?objectAction=edit&amp;id={$id}' title='Edit this person' class='btn btn-xs btn-default'>
					{translate text="Edit" isAdminFacing=true}
				</a>
				<a href='/Admin/Marriages?objectAction=add&amp;personId={$id}' title='Add a Marriage' class='btn btn-xs btn-default'>
					{translate text="Add Marriage" isAdminFacing=true}
				</a>
				<a href='/Admin/Obituaries?objectAction=add&amp;personId={$id}' title='Add an Obituary' class='btn btn-xs btn-default'>
					{translate text="Add Obituary" isAdminFacing=true}
				</a>
			</div>
			<a href='/Admin/People?objectAction=delete&amp;id={$id}' title='Delete this person' class='btn btn-xs btn-danger' onclick='return confirm("{translate text="Removing this person will permanently remove them from the system.	Are you sure?" isAdminFacing=true}")'>
				{translate text="Delete" isAdminFacing=true}
			</a>
		</div>
	{/if}
	{* Display Book Cover *}
	<div class="full-record-main row">
		<div class="col-xs-4 col-sm-5 col-md-4 col-lg-3 text-center">
			{if $disableCoverArt != 1}
				<div id="recordCover" class="text-center">
					{if $person->picture}
						<a target='_blank' href='/files/original/{$person->picture|escape}' aria-hidden="true"><img src="/files/medium/{$person->picture|escape}" class="alignleft listResultImage" alt="{translate text='Picture' inAttribute=true isPublicFacing=true}"></a><br>
					{else}
						<img src="/interface/themes/responsive/images/person.png" class="alignleft listResultImage" alt="{translate text='No Cover Image' inAttribute=true isPublicFacing=true}" aria-hidden="true"><br>
					{/if}
				</div>
			{/if}
		</div>
		<div {*id="main-content"*} class="full-record-main-content col-xs-8 col-sm-7 col-md-8 col-lg-9">
			{if $person->otherName}
				<div class='record-other-name personDetail'><span class='result-label'>{translate text="Other Names" isPublicFacing=true} </span><span class='personDetailValue'>{$person->otherName|escape}</span></div>
			{/if}
			{if !empty($birthDate)}
				<div class='full-record-birth-date personDetail'><span class='result-label'>{translate text="Birth Date" isPublicFacing=true} </span><span class='personDetailValue'>{$birthDate}</span></div>
			{/if}
			{if !empty($deathDate)}
				<div class='full-record-death-date personDetail'><span class='result-label'>{translate text="Death Date" isPublicFacing=true} </span><span class='personDetailValue'>{$deathDate}</span></div>
			{/if}
			{if $person->ageAtDeath}
				<div class='full-record-age-at-death personDetail'><span class='result-label'>{translate text="Age at Death" isPublicFacing=true} </span><span class='personDetailValue'>{$person->ageAtDeath|escape}</span></div>
			{/if}
			{if $person->sex}
				<div class='full-record-sex personDetail'><span class='result-label'>{translate text="Gender" isPublicFacing=true} </span><span class='personDetailValue'>{$person->sex|escape}</span></div>
			{/if}
			{if $person->race}
				<div class='full-record-race personDetail'><span class='result-label'>{translate text="Race" isPublicFacing=true} </span><span class='personDetailValue'>{$person->race|escape}</span></div>
			{/if}
			{if $person->veteranOf}
				{implode subject=$person->veteranOf glue=", " assign='veteranOf'}
				<div class='full-record-veteran-of personDetail'><span class='result-label'>{translate text="Veteran Of" isPublicFacing=true} </span><span class='personDetailValue'>{$veteranOf}</span></div>
			{/if}
			{if $person->causeOfDeath}
				<div class='full-record-cause-of-death personDetail'><span class='result-label'>{translate text="Cause of Death" isPublicFacing=true} </span><span class='personDetailValue'>{$person->causeOfDeath|escape}</span></div>
			{/if}
		</div>
	</div>
	{if count($marriages) > 0 || $userIsAdmin}
		<h2 class="full-record-marriages blockhead">{translate text="Marriages" isPublicFacing=true}</h2>
		{foreach from=$marriages item=marriage}
			<div class="full-record-marriage-title marriageTitle">
				{$marriage.spouseName}{if !empty($marriage.formattedMarriageDate)} - {$marriage.formattedMarriageDate}{/if}
				{if !empty($userIsAdmin)}
					<div class="btn-toolbar">
						<a href='/Admin/Marriages?objectAction=edit&amp;id={$marriage.marriageId}' title='Edit this Marriage' class='btn btn-xs btn-default'>
							{translate text="Edit" isAdminFacing=true}
						</a>
						<a href='/Admin/Marriages?objectAction=delete&amp;id={$marriage.marriageId}' title='Delete this Marriage' onclick='return confirm("{translate text="Removing this marriage will permanently remove it from the system.	Are you sure?" isAdminFacing=true}")' class='btn btn-xs btn-danger'>
							{translate text="Delete" isAdminFacing=true}
						</a>
					</div>
				{/if}
			</div>
			{if !empty($marriage.comments)}
				<div class="full-record-marriage-comments marriageComments">{$marriage.comments|escape}</div>
			{/if}
		{/foreach}

	{/if}
	{if $person->cemeteryName || $person->cemeteryLocation || $person->mortuaryName || $person->cemeteryAvenue || $person->lot || $person->block || $person->grave || $person->addition}
		<h2 class="full-record-burial-details blockhead">{translate text="Burial Details" isPublicFacing=true}</h2>
		{if $person->cemeteryName}
		<div class='full-record-cemetery-name personDetail'><span class='result-label'>{translate text="Cemetery Name" isPublicFacing=true} </span><span class='personDetailValue'>{$person->cemeteryName}</span></div>
		{/if}
		{if $person->cemeteryLocation}
		<div class='full-record-cemetery-location personDetail'><span class='result-label'>{translate text="Cemetery Location" isPublicFacing=true} </span><span class='personDetailValue'>{$person->cemeteryLocation}</span></div>
		{/if}
		{if $person->cemeteryAvenue}
			<div class='full-record-cemetery-avenue personDetail'><span class='result-label'>{translate text="Cemetery Avenue" isPublicFacing=true} </span><span class='personDetailValue'>{$person->cemeteryAvenue}</span></div>
		{/if}
		{if $person->addition || $person->lot || $person->block || $person->grave}
		<div class='full-record-burial-location personDetail'><span class='result-label'>{translate text="Burial Location" isPublicFacing=true}</span>
		<span class='personDetailValue'>
			{if $person->addition}{translate text="Addition" isPublicFacing=true} {$person->addition}{if $person->block || $person->lot || $person->grave}, {/if}{/if}
			{if $person->block}{translate text="Block" isPublicFacing=true} {$person->block}{if $person->lot || $person->grave}, {/if}{/if}
			{if $person->lot}{translate text="Lot" isPublicFacing=true} {$person->lot}{if $person->grave}, {/if}{/if}
			{if $person->grave}{translate text="Grave" isPublicFacing=true} {$person->grave}{/if}
		</span></div>
		{if $person->tombstoneInscription}
		<div class="full-record-tombstone-inscription personDetail"><span class='result-label'>{translate text="Tombstone Inscription" isPublicFacing=true} </span><div class='personDetailValue'>{$person->tombstoneInscription}</div></div>
		{/if}
		{/if}
		{if $person->mortuaryName}
		<div class='full-record-mortuary-name personDetail'><span class='result-label'>{translate text="Mortuary Name" isPublicFacing=true} </span><span class='personDetailValue'>{$person->mortuaryName}</span></div>
		{/if}
	{/if}
	{if count($obituaries) > 0 || $userIsAdmin}
		<h2 class="full-record-obituaries blockhead">{translate text="Obituaries" isPublicFacing=true}</h2>
		{foreach from=$obituaries item=obituary}
			<div class="full-record-obituary-title obituaryTitle">
			{$obituary.source}{if !empty($obituary.sourcePage)} page {$obituary.sourcePage}{/if}{if !empty($obituary.formattedObitDate)} - {$obituary.formattedObitDate}{/if}
			{if !empty($userIsAdmin)}
				<div class="btn-toolbar">
					<a href='/Admin/Obituaries?objectAction=edit&amp;id={$obituary.obituaryId}' title='Edit this Obituary' class='btn btn-xs btn-default'>
						{translate text="Edit" isAdminFacing=true}
					</a>
					<a href='/Admin/Obituaries?objectAction=delete&amp;id={$obituary.obituaryId}' title='Delete this Obituary' onclick='return confirm("{translate text="Removing this obituary will permanently remove it from the system.	Are you sure?" isAdminFacing=true}")' class='btn btn-xs btn-danger'>
						{translate text="Delete" isAdminFacing=true}
					</a>
				</div>
			{/if}
			</div>
			{if !empty($obituary.contents) && $obituary.picture}
				<div class="full-record-obituary-text obituaryText">{if $obituary.picture|escape}<a href='/files/original/{$obituary.picture|escape}'><img class='obitPicture' src='/files/medium/{$obituary.picture|escape}'></a>{/if}{$obituary.contents|escape}</div>
				<div class="clearer"></div>
			{elseif $obituary.contents}
				<div class="full-record-obituary-text obituaryText">{$obituary.contents|escape|replace:"\r":"<br>"}</div>
				<div class="clearer"></div>
			{elseif $obituary.picture}
				<div class="full-record-obituary-picture obituaryPicture">{if $obituary.picture|escape}<a href='/files/original/{$obituary.picture|escape}'><img class='obitPicture' src='/files/medium/{$obituary.picture|escape}'></a>{/if}</div>
				<div class="clearer"></div>
			{/if}

		{/foreach}

	{/if}
	{if $person->ledgerVolume || $person->ledgerYear || $person->ledgerEntry}
		<h2 class="full-record-ledger-information blockhead">{translate text="Ledger Information" isPublicFacing=true}</h2>
		{if $person->ledgerVolume}
			<div class='full-record-ledger-volume personDetail'><span class='result-label'>{translate text="Volume" isPublicFacing=true}</span><span class='result-value-bold'>{$person->ledgerVolume}</span></div>
		{/if}
		{if $person->ledgerYear}
			<div class='full-record-ledger-year personDetail'><span class='result-label'>{translate text="Year" isPublicFacing=true}</span><span class='personDetailValue'>{$person->ledgerYear}</span></div>
		{/if}
		{if $person->ledgerYear}
			<div class='full-record-ledger-entry personDetail'><span class='result-label'>{translate text="Entry" isPublicFacing=true}</span><span class='personDetailValue'>{$person->ledgerEntry}</span></div>
		{/if}
	{/if}
	<h2 class="full-record-comments blockhead">{translate text="Comments" isPublicFacing=true}</h2>
	{if $person->comments}
	<div class='full-record-comments-content personComments'>{$person->comments|escape}</div>
	{else}
	<div class='full-record-comments-content personComments'>{translate text="No comments found." isPublicFacing=true}</div>
	{/if}
{/strip}