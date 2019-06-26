{strip}
	{if $includesStamp}
		<div class="row">
			<div class="result-label col-sm-4">{translate text="Includes Stamp"} </div>
			<div class="result-value col-sm-8">
				Yes
			</div>
		</div>
	{/if}
	{if $postmarks}
		<div class="row">
			<div class="result-label col-sm-4">{translate text="Postmark"} </div>
			<div class="result-value col-sm-8">
				{foreach from=$postmarks item=postmark}
					{if $postmark.datePostmarked}
						{$postmark.datePostmarked}
					{/if}
					{if $postmark.postmarkLocation}
						{if $postmark.datePostmarked} ({/if}
						{if $postmark.postmarkLocation.link}
							<a href='{$postmark.postmarkLocation.link}'>
								{$postmark.postmarkLocation.label}
							</a>
						{else}
							{$postmark.postmarkLocation.label}
						{/if}
						{if $postmark.datePostmarked}){/if}
					{/if}
					<br/>
				{/foreach}
			</div>
		</div>
	{/if}

	{if $correspondenceRecipient}
		<div class="relatedPlace row">
			<div class="result-label col-sm-4">
				{translate text="Correspondence Recipient"}
			</div>
			<div class="result-value col-sm-8">
				{if $correspondenceRecipient.link}
					<a href='{$correspondenceRecipient.link}'>
						{$correspondenceRecipient.label}
					</a>
				{else}
					{$correspondenceRecipient.label}
				{/if}
			</div>
		</div>
	{/if}

	{if $postcardPublisherNumber}
		<div class="row">
			<div class="result-label col-sm-4">{translate text="Postcard Publisher Number"} </div>
			<div class="result-value col-sm-8">
				{$postcardPublisherNumber}
			</div>
		</div>
	{/if}
{/strip}