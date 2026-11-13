<div id="main-content" class="col-md-12">
	<h1>{translate text="View Hero Slider" isAdminFacing=true}</h1>
	<div class="btn-group">
		<a class="btn btn-default" href="{$returnToListUrl|default:'/Admin/HeroSliderLocations'}"><i class="fas fa-arrow-alt-circle-left" role="presentation"></i> {translate text="Return to List" isAdminFacing=true}</a>
	</div>
	<div class="btn-group">
		<a class="btn btn-default" href="/Admin/HeroSliderLocations?objectAction=edit&id={$object->id}"><i class="fas fa-edit" role="presentation"></i> {translate text="Edit" isAdminFacing=true}</a>
		<a class="btn btn-default" href="{$embedUrl}" target="_blank"><i class="fas fa-external-link-alt" role="presentation"></i> {translate text="Preview" isAdminFacing=true}</a>
	</div>
	{if !empty($canDelete)}
	<div class="btn-group">
		<a class="btn btn-danger" href="/Admin/HeroSliderLocations?objectAction=delete&id={$object->id}" onclick="return confirm('{translate text="Are you sure you want to delete %1%?" 1=$object->name inAttribute=true isAdminFacing=true}');"><i class="fas fa-trash" role="presentation"></i> {translate text="Delete" isAdminFacing=true}</a>
	</div>
	{/if}
	{* Show details for the selected hero slider location *}
	<h2>{$object->name}</h2>
	<hr>
	<h4>{translate text="Description" isAdminFacing=true}</h4>
	<div class="well well-sm">{if $object->description}{$object->description}{else}{translate text="No description was defined." isAdminFacing=true}{/if}</div>

	<h4>{translate text="Display Style" isAdminFacing=true}</h4>
	<div class="well well-sm">{if $object->displayStyle == 'digital_signage'}{translate text="Digital Signage" isAdminFacing=true}{else}{translate text="Website" isAdminFacing=true}{/if}</div>

	<h4>{translate text="Aspect Ratio" isAdminFacing=true}</h4>
	<div class="well well-sm">{$object->aspectRatioWidth}:{$object->aspectRatioHeight}</div>

	<h4>{translate text="Auto-Rotate" isAdminFacing=true}</h4>
	<div class="well well-sm">{if $object->autoRotate}{translate text="Yes" isAdminFacing=true} - {$object->rotationInterval} {translate text="seconds" isAdminFacing=true}{else}{translate text="No" isAdminFacing=true}{/if}</div>

	<div id="heroSliderHelp">
		<h4>{translate text="Integration Notes" isAdminFacing=true}</h4>
		<div class="well">
			<p>{translate text="To integrate this hero slider into another site, insert an iframe into your site with the following source." isAdminFacing=true}</p>
			<blockquote class="alert-info">{$embedUrl}</blockquote>
			<p>
				<code style="white-space: normal">&lt;iframe src=&quot;{$embedUrl}&quot;
					style=&quot;display: block; width: 100%; aspect-ratio: {$object->getEmbedAspectRatio()}; max-height: 100vh; border: 0;&quot;
					title=&quot;{$object->name|escape}&quot;&gt;&lt;/iframe&gt;
				</code>
			</p>
			<p>{translate text="The aspect ratio matches this hero slider's images, so the slider scales to fit the width of the page on any screen size, including phones." isAdminFacing=true}</p>
		</div>

		<h4>{translate text="For Digital Signage (XOGO)" isAdminFacing=true}</h4>
		<div class="well">
			<p>{translate text="Use this URL with auto-refresh enabled:" isAdminFacing=true}</p>
			<blockquote class="alert-info">{$embedUrl}&reload=1</blockquote>
			<p>{translate text="This URL will automatically refresh after each complete rotation cycle, allowing the playlist to update dynamically." isAdminFacing=true}</p>
		</div>

		<h4>{translate text="Web Builder Portal Cell" isAdminFacing=true}</h4>
		<div class="well">
			<ol>
				<li>{translate text="Create or edit a Portal Cell in Web Builder." isAdminFacing=true}</li>
				<li>{translate text="Set <strong>Source Type</strong> to \"Hero Slider\"." isAdminFacing=true inAttribute=true}</li>
				<li>{translate text="Select <strong>\"%1%\"</strong> from the Source dropdown." 1=$object->name isAdminFacing=true inAttribute=true}</li>
			</ol>
		</div>
	</div>

	<h4>{translate text="Live Preview" isAdminFacing=true}</h4>
	<iframe src="{$embedUrl}&reload=true" class="hero-slider-embed" style="aspect-ratio: {$object->getEmbedAspectRatio()};" title="{$object->name|escape}">
		<p>{translate text="Your browser does not support iframes." isAdminFacing=true}</p>
	</iframe>
</div>
