{strip}
	{if empty($debuggingInfo->processed)}
		<div>{translate text="Diagnostics will be available after the record is indexed next." isAdminFacing=true}</div>

	{else}
		<div>
			<i>Diagnostics performed at {$debuggingInfo->debugTime|date_format:"%D %T"}. Results will be reset after 24 hours.</i>
		</div>
		<br>
		<div>
			{$debuggingInfo->debugInfo}
		</div>
	{/if}
{/strip}