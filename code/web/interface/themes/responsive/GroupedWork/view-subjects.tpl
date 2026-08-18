{strip}
	{* Loads & assigned the template variables *}
	{if (($showLCSubjects || $showBisacSubjects) && !($showFastAddSubjects || $showOtherSubjects))}
		{*If only lc subjects or bisac subjects are chosen for display (but not the others), display those specific subjects *}

		{if !empty($lcSubjects)}
			<div class="full-record-lc-subjects row">
				<div class="result-label col-xs-3">{translate text='LC Subjects' isPublicFacing=true}</div>
				<div class="col-xs-9 result-value">
					{foreach from=$lcSubjects item=subject name=loop}
						<a href="/Search/Results?lookfor=%22{$subject|escape:"url"}%22&amp;searchIndex=Subject">{$subject|escape}</a>
						<br>
					{/foreach}
				</div>
			</div>
		{/if}

		{if !empty($bisacSubjects)}
			<div class="full-record-bisac-subjects row">
				<div class="result-label col-xs-3">{translate text='Bisac Subjects' isPublicFacing=true}</div>
				<div class="col-xs-9 result-value">
					{foreach from=$bisacSubjects item=subject name=loop}
						<a href="/Search/Results?lookfor=%22{$subject|escape:"url"}%22&amp;searchIndex=Subject">{$subject|escape}</a>
						<br>
					{/foreach}
				</div>
			</div>
		{/if}

		{if !empty($oclcFastSubjects)}
			<div class="full-record-oclc-fast-subjects row">
				<div class="result-label col-xs-3">{translate text='OCLC Fast Subjects' isPublicFacing=true}</div>
				<div class="col-xs-9 result-value">
					{foreach from=$oclcFastSubjects item=subject name=loop}
						<a href="/Search/Results?lookfor=%22{$subject|escape:"url"}%22&amp;searchIndex=Subject">{$subject|escape}</a>
						<br>
					{/foreach}
				</div>
			</div>
		{/if}

		{if !empty($localSubjects)}
			<div class="full-record-local-subjects row">
				<div class="result-label col-xs-3">{translate text='Local Subjects' isPublicFacing=true}</div>
				<div class="col-xs-9 result-value">
					{foreach from=$localSubjects item=subject name=loop}
						<a href="/Search/Results?lookfor=%22{$subject|escape:"url"}%22&amp;searchIndex=Subject">{$subject|escape}</a>
						<br>
					{/foreach}
				</div>
			</div>
		{/if}

		{if !empty($otherSubjects)}
			<div class="full-record-other-subjects row">
				<div class="result-label col-xs-3">{translate text='Other Subjects' isPublicFacing=true}</div>
				<div class="col-xs-9 result-value">
					{foreach from=$otherSubjects item=subject name=loop}
						<a href="/Search/Results?lookfor=%22{$subject|escape:"url"}%22&amp;searchIndex=Subject">{$subject|escape}</a>
						<br>
					{/foreach}
				</div>
			</div>
		{/if}

	{else}
		{* Display All the subjects *}
		{if !empty($subjects)}
			<div class="full-record-subjects row">
				<div class="result-label col-xs-3">{translate text='Subjects' isPublicFacing=true}</div>
				<div class="col-xs-9 result-value">
					{foreach from=$subjects item=subject name=loop}
						<a href="/Search/Results?lookfor=%22{$subject|escape:"url"}%22&amp;searchIndex=Subject">{$subject|escape}</a>
						<br>
					{/foreach}
				</div>
			</div>
		{/if}

	{/if}

{/strip}
