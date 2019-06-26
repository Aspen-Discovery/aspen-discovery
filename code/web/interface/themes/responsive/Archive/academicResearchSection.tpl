{strip}
	{if $researchType}
		<div class="row">
			<div class="result-label col-sm-4">{translate text="Research Type"} </div>
			<div class="result-value col-sm-8">
				{implode subject=$researchType}
			</div>
		</div>
	{/if}
	{if $researchLevel}
		<div class="row">
			<div class="result-label col-sm-4">{translate text="Research Level"} </div>
			<div class="result-value col-sm-8">
				{$researchLevel}
			</div>
		</div>
	{/if}
	{if $peerReview}
		<div class="row">
			<div class="result-label col-sm-4">{translate text="Peer Reviewed?"} </div>
			<div class="result-value col-sm-8">
				{$peerReview}
			</div>
		</div>
	{/if}
	{if $supportingDepartments}

		<div class="row">
			<div class="result-label col-sm-4">
				{translate text="Supporting Departments"}
			</div>
			<div class="result-value col-sm-8">
				{foreach from=$supportingDepartments item="academicPerson"}
					{if $academicPerson.link}
						<a href='{$academicPerson.link}'>
							{$academicPerson.label}
						</a>
					{else}
						{$academicPerson.label}
					{/if}
					<br/>
				{/foreach}
			</div>
		</div>
	{/if}

	{if $degreeName}
		<div class="row">
			<div class="result-label col-sm-4">{translate text="Degree Name"} </div>
			<div class="result-value col-sm-8">
				{$degreeName}
			</div>
		</div>
	{/if}
	{if $degreeDiscipline}
		<div class="row">
			<div class="result-label col-sm-4">{translate text="Degree Discipline"} </div>
			<div class="result-value col-sm-8">
				{$degreeDiscipline}
			</div>
		</div>
	{/if}
	{if $defenceDate}
		{translate text="Defence Date "} {$defenceDate}<br/>
	{/if}
	{if $acceptedDate}
		{$acceptedDate}
	{/if}

	{foreach from=$publicationPresentations item="publicationPresentation"}

		{if $publicationPresentation.journalTitle}
			<div class="row">
				<div class="result-label col-sm-4">{translate text="Published in"}</div>
				<div class="result-value col-sm-8">
					{if $publicationPresentation.journalTitle}
						{$publicationPresentation.journalTitle}
					{/if}
					{if $publicationPresentation.journalVolumeNumber}
						, {$publicationPresentation.journalVolumeNumber}
					{/if}
					{if $publicationPresentation.journalIssueNumber}
						, {$publicationPresentation.journalIssueNumber}
					{/if}
					{if $publicationPresentation.journalArticleNumber}
						, {$publicationPresentation.journalArticleNumber}
					{/if}
					{if $publicationPresentation.articleFirstPage}
						, p. {$publicationPresentation.articleFirstPage}
					{/if}
					{if $publicationPresentation.articleLastPage}
						{if $publicationPresentation.articleFirstPage}-{else}&nbsp;{/if}{$publicationPresentation.articleLastPage}
					{/if}
				</div>
			</div>
		{/if}
		{if $publicationPresentation.conferenceName}
			<div class="row">
				<div class="result-label col-sm-4">{translate text="Presented At"}</div>
				<div class="result-value col-sm-8">
					{if $publicationPresentation.conferenceName}
						{$publicationPresentation.conferenceName}
					{/if}
					{if $publicationPresentation.conferencePresentationDate}
						&nbsp; ({$publicationPresentation.conferencePresentationDate})
					{/if}
				</div>
			</div>
		{/if}
		<br/>
	{/foreach}
{/strip}