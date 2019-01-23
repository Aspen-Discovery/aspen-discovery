{strip}
	{if $lcSubjects}
		<div class="row">
			<div class="result-label col-xs-3">{translate text='LC Subjects'}</div>
			<div class="col-xs-9 result-value">
				{foreach from=$lcSubjects item=subject name=loop}
					{foreach from=$subject item=subjectPart name=subloop}
						{if !$smarty.foreach.subloop.first} -- {/if}
						<a href="{$path}/Search/Results?lookfor=%22{$subjectPart.search|escape:"url"}%22&amp;basicType=Subject">{$subjectPart.title|escape}</a>
					{/foreach}
					<br>
			{/foreach}
			</div>
		</div>
	{/if}

	{if $bisacSubjects}
		<div class="row">
			<div class="result-label col-xs-3">{translate text='Bisac Subjects'}</div>
			<div class="col-xs-9 result-value">
				{foreach from=$bisacSubjects item=subject name=loop}
					{foreach from=$subject item=subjectPart name=subloop}
						{if !$smarty.foreach.subloop.first} -- {/if}
						<a href="{$path}/Search/Results?lookfor=%22{$subjectPart.search|escape:"url"}%22&amp;basicType=Subject">{$subjectPart.title|escape}</a>
					{/foreach}
					<br>
				{/foreach}
			</div>
		</div>
	{/if}

	{if $oclcFastSubjects}
		<div class="row">
			<div class="result-label col-xs-3">{translate text='OCLC Fast Subjects'}</div>
			<div class="col-xs-9 result-value">
				{foreach from=$oclcFastSubjects item=subject name=loop}
					{foreach from=$subject item=subjectPart name=subloop}
						{if !$smarty.foreach.subloop.first} -- {/if}
						<a href="{$path}/Search/Results?lookfor=%22{$subjectPart.search|escape:"url"}%22&amp;basicType=Subject">{$subjectPart.title|escape}</a>
					{/foreach}
					<br>
				{/foreach}
			</div>
		</div>
	{/if}

	{if $localSubjects}
		<div class="row">
			<div class="result-label col-xs-3">{translate text='Local Subjects'}</div>
			<div class="col-xs-9 result-value">
				{foreach from=$localSubjects item=subject name=loop}
					{foreach from=$subject item=subjectPart name=subloop}
						{if !$smarty.foreach.subloop.first} -- {/if}
						<a href="{$path}/Search/Results?lookfor=%22{$subjectPart.search|escape:"url"}%22&amp;basicType=Subject">{$subjectPart.title|escape}</a>
					{/foreach}
					<br>
				{/foreach}
			</div>
		</div>
	{/if}

	{if $otherSubjects}
		<div class="row">
			<div class="result-label col-xs-3">{translate text='Other Subjects'}</div>
			<div class="col-xs-9 result-value">
				{foreach from=$otherSubjects item=subject name=loop}
					{foreach from=$subject item=subjectPart name=subloop}
						{if !$smarty.foreach.subloop.first} -- {/if}
						<a href="{$path}/Search/Results?lookfor=%22{$subjectPart.search|escape:"url"}%22&amp;basicType=Subject">{$subjectPart.title|escape}</a>
					{/foreach}
					<br>
				{/foreach}
			</div>
		</div>
	{/if}

{/strip}