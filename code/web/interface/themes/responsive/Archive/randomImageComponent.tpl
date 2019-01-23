{strip}
	<div class="archiveComponentContainer nopadding col-sm-12 col-md-6">
		<div class="archiveComponent horizontalComponent">
			<div class="archiveComponentBody">
				<div class="archiveComponentBox">
					<div class="archiveComponentHeader">Random Image</div>
					<div class="archiveComponentRandomImage row">
						<figure class="" id="randomImagePlaceholder">
							{include file="Archive/randomImage.tpl"}
						</figure>
						<a href="#" onclick="return VuFind.Archive.nextRandomObject('{$randomObjectPids}');"><img id="refreshRandomImage" src="{$path}/interface/themes/responsive/images/refresh.png" alt="New Random Image"></a>
					</div>
				</div>
			</div>
		</div>
	</div>
{/strip}