{strip}
	{if !empty($lcSubjects)}
		<div class="row">
			<div class="result-label col-xs-3">{translate text='LC Subjects' isPublicFacing=true}</div>
			<div class="col-xs-9 result-value">
				{foreach from=$lcSubjects item=subject name=loop}
					{foreach from=$subject item=subjectPart name=subloop}
						{if empty($smarty.foreach.subloop.first)} -- {/if}
						<a href="/Search/Results?lookfor=%22{$subjectPart.search|escape:"url"}%22&amp;searchIndex=Subject">{$subjectPart.title|escape}</a>
					{/foreach}
					<br>
			{/foreach}
			</div>
		</div>
	{/if}

	{if !empty($bisacSubjects)}
		<div class="row">
			<div class="result-label col-xs-3">{translate text='Bisac Subjects' isPublicFacing=true}</div>
			<div class="col-xs-9 result-value">
				{foreach from=$bisacSubjects item=subject name=loop}
					{foreach from=$subject item=subjectPart name=subloop}
						{if empty($smarty.foreach.subloop.first)} -- {/if}
						<a href="/Search/Results?lookfor=%22{$subjectPart.search|escape:"url"}%22&amp;searchIndex=Subject">{$subjectPart.title|escape}</a>
					{/foreach}
					<br>
				{/foreach}
			</div>
		</div>
	{/if}

	{if !empty($oclcFastSubjects)}
		<div class="row">
			<div class="result-label col-xs-3">{translate text='OCLC Fast Subjects' isPublicFacing=true}</div>
			<div class="col-xs-9 result-value">
				{foreach from=$oclcFastSubjects item=subject name=loop}
					{foreach from=$subject item=subjectPart name=subloop}
						{if empty($smarty.foreach.subloop.first)} -- {/if}
						<a href="/Search/Results?lookfor=%22{$subjectPart.search|escape:"url"}%22&amp;searchIndex=Subject">{$subjectPart.title|escape}</a>
					{/foreach}
					<br>
				{/foreach}
			</div>
		</div>
	{/if}

	{if !empty($localSubjects)}
		<div class="row">
			<div class="result-label col-xs-3">{translate text='Local Subjects' isPublicFacing=true}</div>
			<div class="col-xs-9 result-value">
				{foreach from=$localSubjects item=subject name=loop}
					{foreach from=$subject item=subjectPart name=subloop}
						{if empty($smarty.foreach.subloop.first)} -- {/if}
						<a href="/Search/Results?lookfor=%22{$subjectPart.search|escape:"url"}%22&amp;searchIndex=Subject">{$subjectPart.title|escape}</a>
					{/foreach}
					<br>
				{/foreach}
			</div>
		</div>
	{/if}

	{if !empty($otherSubjects)}
		<div class="row">
			<div class="result-label col-xs-3">{translate text='Other Subjects' isPublicFacing=true}</div>
			<div class="col-xs-9 result-value">
				{foreach from=$otherSubjects item=subject name=loop}
					{foreach from=$subject item=subjectPart name=subloop}
						{if empty($smarty.foreach.subloop.first)} -- {/if}
						<a href="/Search/Results?lookfor=%22{$subjectPart.search|escape:"url"}%22&amp;searchIndex=Subject">{$subjectPart.title|escape}</a>
					{/foreach}
					<br>
				{/foreach}
			</div>
		</div>
	{/if}

{/strip}