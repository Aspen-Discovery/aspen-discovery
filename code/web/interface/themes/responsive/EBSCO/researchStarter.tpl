{strip}
	<div class="researchStarter" id="researchStarter-{$id}">
		<div class="label-top">
			<div class="col-tn-10 col-md-11">
				{translate text='RESEARCH STARTER' isPublicFacing=true}
			</div>
			<div class="col-tn-2 col-md-1 text-right">
				<div class="btn btn-xs btn-warning researchStarter-dismiss" onclick="return AspenDiscovery.EBSCO.dismissResearchStarter('{$id}')">{translate text="X"}</div>
			</div>
		</div>
		<div class="row researchStarterBody">
			<div class="col-tn-12 col-xs-4 col-md-3 text-center">
				{if !empty($image)}
					<a href="{$link}" target="_blank" class="researchStarter-link">
					<img src="{$image}" class="researchStarter-image img-thumbnail {$coverStyle}" alt="{$title}">
					</a>
				{/if}
			</div>
			<div class="col-tn-12 col-xs-8 col-md-9">
				<div class="result-title notranslate">
					<a href="{$link}" target="_blank" class="researchStarter-link">{$title}</a>
				</div>
				{if !empty($description)}
					<div class="researchStarter-body">
						{$description}
					</div>
				{/if}
			</div>
		</div>
	</div>
{/strip}