{strip}
<div id="main-content" class="col-md-12">
		<h1>Collection Spotlight</h1>
		<div class="btn-group">
			<a class="btn btn-sm btn-default" href="/Admin/CollectionSpotlights">All Collection Spotlights</a>
			<a class="btn btn-sm btn-default" href="/Admin/CollectionSpotlights?objectAction=edit&amp;id={$object->id}">Edit</a>
			<a class="btn btn-sm btn-default" href="/API/SearchAPI?method=getCollectionSpotlight&amp;id={$object->id}">Preview</a>
			{if $canDelete}
				<a class="btn btn-sm btn-danger" href="/Admin/CollectionSpotlights?objectAction=delete&amp;id={$object->id}" onclick="return confirm('Are you sure you want to delete {$object->name}?');">Delete</a>
			{/if}
		</div>
		{* Show details for the selected spotlight *}
		<h2>{$object->name}</h2>
		<hr>
		<h4>Available to</h4>
		<div id="selectedSpotlightLibrary" class="well well-sm">{$object->getLibraryName()}</div>
		<h4>Description</h4>
		<div id="selectedSpotlightDescription" class="well well-sm">{$object->description}</div>
		<h4>Style Sheet</h4>
		<div id="selectedSpotlightCss" class="well well-sm">{if $object->customCss}{$object->customCss}{else}No custom css defined{/if}</div>
		<h4>Spotlight Style</h4>
		{assign var=selectedStyle value=$object->style}
		<div id="selectedSpotlightDisplayType" class="well well-sm">{$object->getStyle($selectedStyle)}</div>
		<h4>Display Type</h4>
		{assign var="selectedDisplayType" value=$object->listDisplayType}
		<div id="selectedSpotlightDisplayType" class="well well-sm">{$object->getDisplayType($selectedDisplayType)}</div>

		<h4>Maximum Titles to show</h4>
		<div id="maxTitlesToShow" class="well well-sm">{$object->numTitlesToShow}</div>

		{if count($object->lists) > 0}
			<h4 id="selectedSpotlightListsHeader">Lists</h4>
			<table id="selectedSpotlightLists" class="table table-bordered">
			<thead>
			<tr><th>Name</th><th>Display For</th><th>Source</th></tr>
			</thead>
			<tbody>
			{foreach from=$object->lists item=list}
				<tr class="sortable" id="{$list->id}">
				<td>{$list->name}</td>
				<td>{$list->displayFor}</td>
				<td>{$list->source}</td>
				</tr>
			{/foreach}
			</tbody>
			</table>
		{else}
			<p>This spotlight has no lists defined for it.</p>
		{/if}
		<div id="collectionSpotlightHelp">
			<h4>Integration notes</h4>
			<div class="well">
				<p>To integrate this spotlight into another site, insert an iFrame into your site with a source of:</p>
				<blockquote class="alert-info" style="font-weight: bold;">{$url}/API/SearchAPI?method=getCollectionSpotlight&amp;id={$object->id}</blockquote>
				<p>
					<code style="white-space: normal">&lt;iframe src=&quot;{$url}/API/SearchAPI?method=getCollectionSpotlight&amp;id={$object->id}&quot;
						width=&quot;{$width}&quot; height=&quot;{$height}&quot;
						scrolling=&quot;{if $selectedStyle == "text-list"}yes{else}no{/if}&quot;&gt;&lt;/iframe&gt;
					</code>
				</p>
				<p>Width and height can be adjusted as needed to fit within your site.</p>
				<blockquote class="alert-warning"> Note: Please avoid using percentages for the iframe width &amp; height as these values are not respected on iPads and other iOS devices & browsers.</blockquote>
				<blockquote class="alert-warning"> Note: Text Only Spotlights use the iframe's scrollbar.</blockquote>
				<blockquote class="alert-warning"> Recommend: set iframe attribute frameborder="0" and put border any desired styling in your Style Sheet.</blockquote>
			</div>
		</div>

		<h4>Live Preview</h4>

		<iframe src="{$url}/API/SearchAPI?method=getCollectionSpotlight&id={$object->id}&reload=true" width="{$width}" height="{$height}" scrolling="{if $selectedStyle == "text-list"}yes{else}no{/if}" >
			<p>Your browser does not support iframes. :( </p>
		</iframe>
	<hr>
		<h3>Collection Spotlight with Resizing</h3>
		<h4>Integration notes</h4>
		<div class="well">
			<p>
				To have a collection spotlight which adjusts it's height based on the html content within the spotlight use the source url:
			</p>
			<blockquote class="alert-info">
			{$url}/API/SearchAPI?method=getCollectionSpotlight&amp;id={$object->id}<span style="font-weight: bold;">&resizeIframe=on</span>
			</blockquote>
			<p>
				Include the iframe tag and javascript tags in the site :
			</p>
			<p>
				{/strip}
<code style="white-space: normal">
	&lt;iframe id=&quot;collectionSpotlight{$object->id}&quot;  onload=&quot;setSpotlightSizing(this, 30)&quot;  src=&quot;{$url}/API/SearchAPI?method=getCollectionSpotlight&amp;id={$object->id}&amp;resizeIframe=on&quot;
	width=&quot;{$width}&quot; scrolling=&quot;{if $selectedStyle == "text-list"}yes{else}no{/if}&quot;&gt;&lt;/iframe&gt;
</code>
{literal}
<code style="white-space: pre">

&lt;!-- Horizontal Resizing : Based on Iframe Content --&gt;

&lt;script type=&quot;text/javascript&quot; src=&quot;{/literal}{$url}{literal}/js/iframeResizer/iframeResizer.min.js&quot;&gt;&lt;/script&gt;
&lt;script type=&quot;text/javascript&quot;&gt;
	jQuery(&quot;#collectionSpotlight{/literal}{$object->id}{literal}&quot;).iFrameResize();
&lt;/script&gt;

&lt;!-- Vertical Resizing : When Iframe is larger than viewport width,
	resize to 100% of browser width - 2 * padding (in px) --&gt;

&lt;script type=&quot;text/javascript&quot;&gt;
	setSpotlightSizing = function(iframe, OutsidePadding){
		originalWidth = jQuery(iframe).width();
		wasResized = false;
		jQuery(window).resize(function(){
			resizeSpotlightWidth(iframe, OutsidePadding);
		}).resize();
	};

	resizeSpotlightWidth = function(iframe, padding){
		if (padding == undefined) padding = 4;
		var viewPortWidth = jQuery(window).width(),
			newWidth = viewPortWidth - 2*padding,
			width = jQuery(iframe).width();
		if (width > newWidth) {
			wasResized = true;
			return jQuery(iframe).width(newWidth);
		}
		if (wasResized && originalWidth + 2*padding < viewPortWidth){
			wasResized = false;
			return jQuery(iframe).width(originalWidth);
		}
	};
{/literal}
&lt;/script&gt;
</code>
				{strip}
			</p>
			<blockquote class="alert-warning">
				This requires that the site displaying the collection spotlight have the jQuery library.
			</blockquote>

		</div>
	<h4>Live Preview</h4>
	<iframe id="collectionSpotlight{$object->id}" onload="setSpotlightSizing(this, 30)" src="{$url}/API/SearchAPI?method=getCollectionSpotlight&id={$object->id}&resizeIframe=on&reload=true" width="{$width}" {*height="{$height}"*} scrolling="{if $selectedStyle == "text-list"}yes{else}no{/if}">
		<p>Your browser does not support iframes. :( </p>
	</iframe>

	{* Iframe dynamic Height Re-sizing script *}
	<script type="text/javascript" src="/js/iframeResizer/iframeResizer.min.js"></script>
	{/strip}

	{* Width Resizing Code *}
<script type="text/javascript">
	jQuery('#collectionSpotlight{$object->id}').iFrameResize();
</script>

{literal}
	<script type="text/javascript">
		setSpotlightSizing = function(iframe, OutsidePadding){
			originalWidth = jQuery(iframe).width();
			wasResized = false;
			jQuery(window).resize(function(){
				resizeSpotlightWidth(iframe, OutsidePadding);
			}).resize();
		};

	resizeSpotlightWidth = function(iframe, padding){
		if (padding === undefined) padding = 4;
		var viewPortWidth = jQuery(window).width(),
				newWidth = viewPortWidth - 2*padding,
				width = jQuery(iframe).width();
		if (width > newWidth) {
			wasResized = true;
			return jQuery(iframe).width(newWidth);
		}
		if (wasResized && originalWidth + 2*padding < viewPortWidth){
			wasResized = false;
			return jQuery(iframe).width(originalWidth);
		}
	};
</script>
{/literal}

</div>
