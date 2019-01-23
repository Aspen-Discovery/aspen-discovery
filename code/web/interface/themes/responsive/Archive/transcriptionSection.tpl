{strip}
	{foreach from=$transcription item=transcript}
		<div class="transcript">
			{if $transcript.location}
				<div class="transcriptLocation">From the {$transcript.location}</div>
			{/if}
			{$transcript.text}
		</div>
	{/foreach}
{/strip}