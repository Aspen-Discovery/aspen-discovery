{*Title Div*}
<div class="col-xs-12">
	<div class="row">
		<div class="col-sm-12">
			<h1>{$recordDriver->getTitle()}</h1>
		</div>
	</div>
</div>
{*Content Div*}
<div class="row">
	{*Left Panel Content*}
	<div class="col-tn-12 col-xs-12 col-sm-4 col-md-3 col-lg-3">
		<div class="panel active">
			<div class="panel-body" style="display:flex; justify-content:center">
				<a href="{$patronUrl}"><img class="img-responsive img-thumbnail {$coverStyle}" src="{$image}" alt="{$recordDriver->getTitle()|escape}" style="max-height: 280px; width: auto"></a>
			</div>
		</div>
		{if !empty($record->format)}
			<div class="panel active">
				<div class="panel-heading">{translate text="Format" isPublicFacing=true}</div>
				<div class="panel-body">
					<div class="col-xs-12">{$record->format->label}</div>
				</div>
			</div>
		{/if}
		{if !empty($record->subject)}
			<div class="panel active">
				{if count($record->subject) > 1}
					<div class="panel-heading">{translate text="Subjects" isPublicFacing=true}</div>
					<div class="panel-body">
						{foreach from=$record->subject item=$subject}
							<div class="col-xs-12">{$subject}</div>
						{/foreach}
					</div>
				{else}
					<div class="panel-heading">{translate text="Subject" isPublicFacing=true}</div>
					<div class="panel-body">
						{foreach from=$record->subject item=$subject}
							<div class="col-xs-12">{$subject}</div>
						{/foreach}
					</div>
				{/if}
			</div>
		{/if}

		{if !empty($record->language)}
			<div class="panel active">
				{if count($record->language->values) > 1}
					<div class="panel-heading">{translate text="Languages" isPublicFacing=true}</div>
					<div class="panel-body">
						{foreach from=$record->language->values item=$language}
							<div class="col-xs-12">{$language->label}</div>
						{/foreach}
					</div>
				{else}
					<div class="panel-heading">{translate text="Subject" isPublicFacing=true}</div>
					<div class="panel-body">
						{foreach from=$record->language->values item=$language}
							<div class="col-xs-12">{$language->label}</div>
						{/foreach}
					</div>
				{/if}
			</div>
		{/if}
	</div>

	{*Content Right of Panel*}
	<div class="col-tn-12 col-xs-12 col-sm-8 col-md-9 col-lg-9">
		<div class="row">
			<div class="col-xs-8">
			<ul>
				{if !empty($record->author) && is_array ($record->author)}
					{if count($record->author) > 1}
						<li>{translate text="Authors: " isPublicFacing=true}
							{foreach from=$record->author item=$author}
								<ul>
									<li>{$author->name}</li>
								</ul>
							{/foreach}
						</li>
					{else}
						<li>{translate text="Author: " isPublicFacing=true}{$record->author[0]->name}</li>
					{/if}
				{/if}
				{if !empty($record->peerReviewed)}
					<li>{translate text="Peer Reviewed: " isPublicFacing=true}{$record->peerReviewed->label}</li>
				{/if}
				{if !empty($record->openAccess)}
					<li>{translate text="Open Access: " isPublicFacing=true}{$record->openAccess->label}</li>
				{/if}
				{if !empty($record->publishDate)}
					<li>{translate text="Publish Date: " isPublicFacing=true}{$record->publishDate}</li>
				{/if}
				{if !empty($record->publisher)}
					<li>{translate text="Publisher: " isPublicFacing=true}{$record->publisher}</li>
				{/if}
				{if !empty($record->publicationCountry)}
					{if count($record->publicationCountry) > 1}
						<li>{translate text="Country of Publication: " isPublicFacing=true}
							{foreach from=$record->publicationCountry item=$publicationCountry}
								<ul>
									<li>{$publicationCountry}</li>
								</ul>
							{/foreach}
						</li>
					{else}
						<li>{translate text="Country of Publication: " isPublicFacing=true}{$record->publicationCountry[0]}</li>
					{/if}
				{/if}
				{if !empty($record->license)}
					<li>{translate text="License: " isPublicFacing=true}{$record->license->label}</li>
				{/if}
				{if !empty($record->publication)}
					{if !empty($record->publication->issn)}
						{if count($record->publication->issn) > 1}
							<li>{translate text="ISSNs: " isPublicFacing=true}
								{foreach from=$record->publication->issn item=$issn}
									<ul>
										<li>{$issn}</li>
									</ul>
								{/foreach}
							</li>
						{else}
							<li>{translate text="ISSN: " isPublicFacing=true}{$record->publication->issn[0]}</li>
						{/if}
					{/if}
					{if !empty($record->publication->alternateName)}
						{if count($record->publication->alternateName) > 1}
							<li>{translate text="Alternate Names: " isPublicFacing=true}
								{foreach from=$record->publication->alternateName item=$alternateName}
									<ul>
										<li>{$alternateName}</li>
									</ul>
								{/foreach}
							</li>
						{else}
							<li>{translate text="Alternate Name: " isPublicFacing=true}{$record->publication->alternateName[0]}</li>
						{/if}
					{/if}
					{if !empty($record->publication->volume)}
						<li>{translate text="Volume: " isPublicFacing=true}{$record->publication->volume}</li>
					{/if}
					{if !empty($record->publication->issue)}
						<li>{translate text="Issue: " isPublicFacing=true}{$record->publication->issue}</li>
					{/if}
					{if !empty($record->publication->type)}
						<li>{translate text="Publication Type: " isPublicFacing=true}{$record->publication->type}</li>
					{/if}
				{/if}
			</ul>
		</div>
		<div class="col-tn-4" style="display:flex; justify-content:center;">

		</div>
		</div>
		{*column for tool buttons & event description*}
		<div class="col-sm-9">
			<div class="btn-group btn-group-sm">
				<a href="{$patronUrl}" class="btn btn-sm btn-tools" target="_blank" aria-label="{translate text="More Info" isPublicFacing=true inAttribute=true} ({translate text="opens in a new window" isPublicFacing=true inAttribute=true})"><i class="fas fa-external-link-alt" role="presentation"></i> {translate text="More Info" isPublicFacing=true}</a>
				<button onclick="return AspenDiscovery.Account.showSaveToListForm(this, 'CloudSource', '{$recordDriver->getUniqueID()|escape}');" class="btn btn-sm btn-tools addToListBtn">{translate text="Add to List" isPublicFacing=true}</button>
			</div>
			<div class="btn-group btn-group-sm">
				{*{include file="Events/share-tools.tpl" eventUrl=$recordDriver->getRecordUrl()}*}
			</div>
			<br>
			<br>
			{$recordDriver->getDescription()}
		</div>
	</div>
</div>

