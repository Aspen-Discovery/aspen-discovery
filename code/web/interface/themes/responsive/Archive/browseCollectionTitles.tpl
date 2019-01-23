{strip}
	<div class="archiveComponentContainer nopadding col-sm-12 col-md-6">
		<div class="archiveComponent horizontalComponent">
			<div class="archiveComponentBody">
				<div class="archiveComponentBox">
					<div class="archiveComponentHeader">{$browseCollectionTitlesData.title}</div>
					<div class="archiveComponentLinks row">
						{foreach from=$browseCollectionTitlesData.collectionTitles item=titleInfo key=indexNumber}
							<div class="col-tn-6"><a href="{$titleInfo.link}"{if $isCollectionOnExhibitPage && $browseCollectionTitlesData.collectionPid} onclick="VuFind.Archive.setForExhibitNavigation({$indexNumber}, 1, '{$browseCollectionTitlesData.collectionPid}')"{/if}>{$titleInfo.title}</a></div>
						{/foreach}
					</div>
				</div>
			</div>
		</div>
	</div>
{/strip}