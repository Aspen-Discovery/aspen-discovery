{strip}
<div id="main-content" class="col-md-12">
	<h1>{translate text="Collection Spotlight" isAdminFacing=true}</h1>
	<div class="btn-group">
		<a class="btn btn-sm btn-default" href="/Admin/CollectionSpotlights">{translate text="All Collection Spotlights" isAdminFacing=true}</a>
		<a class="btn btn-sm btn-default" href="/Admin/CollectionSpotlights?objectAction=edit&amp;id={$object->id}">{translate text="Edit" isAdminFacing=true}</a>
		<a class="btn btn-sm btn-default" href="/API/SearchAPI?method=getCollectionSpotlight&amp;id={$object->id}">{translate text="Preview" isAdminFacing=true}</a>
		{if $canDelete}
			<a class="btn btn-sm btn-danger" href="/Admin/CollectionSpotlights?objectAction=delete&amp;id={$object->id}" onclick="return confirm('{translate text="Are you sure you want to delete %1%?" 1=$object->name inAttribute=true isAdminFacing=true}');"><i class="fas fa-trash"></i> {translate text="Delete" isAdminFacing=true}</a>
		{/if}
	</div>
	{* Show details for the selected spotlight *}
	<h2>{$object->name}</h2>
	<hr>
	<h4>{translate text="Available to" isAdminFacing=true}</h4>
	<div id="selectedSpotlightLibrary" class="well well-sm">{$object->getLibraryName()}</div>
	<h4>{translate text="Description" isAdminFacing=true}</h4>
	<div id="selectedSpotlightDescription" class="well well-sm">{$object->description}</div>
	<h4>{translate text="Style Sheet" isAdminFacing=true}</h4>
	<div id="selectedSpotlightCss" class="well well-sm">{if $object->customCss}{$object->customCss}{else}{translate text="No custom css defined" isAdminFacing=true}{/if}</div>
	<h4>{translate text="Spotlight Style" isAdminFacing=true}</h4>
	{assign var=selectedStyle value=$object->style}
	<div id="selectedSpotlightDisplayType" class="well well-sm">{translate text=$object->getStyle($selectedStyle) isAdminFacing=true}</div>
	<h4>{translate text="Display Type" isAdminFacing=true}</h4>
	{assign var="selectedDisplayType" value=$object->listDisplayType}
	<div id="selectedSpotlightDisplayType" class="well well-sm">{translate text=$object->getDisplayType($selectedDisplayType) isAdminFacing=true}</div>

	<h4>{translate text="Maximum Titles to show<" isAdminFacing=true}/h4>
	<div id="maxTitlesToShow" class="well well-sm">{$object->numTitlesToShow}</div>

	{if count($object->lists) > 0}
		<h4 id="selectedSpotlightListsHeader">{translate text="Lists" isAdminFacing=true}</h4>
		<table id="selectedSpotlightLists" class="table table-bordered">
		<thead>
		<tr><th>{translate text="Name" isAdminFacing=true}</th><th>{translate text="Display For" isAdminFacing=true}</th><th>{translate text="Created From" isAdminFacing=true}</th></tr>
		</thead>
		<tbody>
		{foreach from=$object->lists item=list}
			<tr class="sortable" id="{$list->id}">
			<td>{$list->name}</td>
			<td>{$list->displayFor}</td>
			<td>{if $list->sourceListId == -1}{$list->searchTerm}<br/>{$list->defaultFilter}{else}{$list->getSourceListName()}{/if}</td>
			</tr>
		{/foreach}
		</tbody>
		</table>
	{else}
		<p>{translate text="This spotlight has no lists defined for it." isAdminFacing=true}</p>
	{/if}
	<div id="collectionSpotlightHelp">
		<h4>{translate text="Integration notes" isAdminFacing=true}</h4>
		<div class="well">
			<p>{translate text="To integrate this spotlight into another site, insert an iFrame into your site with the following source." isAdminFacing=true}</p>
			<blockquote class="alert-info" style="font-weight: bold;">{$url}/API/SearchAPI?method=getCollectionSpotlight&amp;id={$object->id}</blockquote>
			<p>
				<code style="white-space: normal">&lt;iframe src=&quot;{$url}/API/SearchAPI?method=getCollectionSpotlight&amp;id={$object->id}&quot;
					width=&quot;{$width}&quot; height=&quot;{$height}&quot;
					scrolling=&quot;{if $selectedStyle == "text-list"}yes{else}no{/if}&quot;&gt;&lt;/iframe&gt;
				</code>
			</p>
			<p>{translate text="Width and height can be adjusted as needed to fit within your site." isAdminFacing=true}</p>
			<blockquote class="alert-warning">{translate text="Note: Please avoid using percentages for the iframe width and height as these values are not respected on iPads and other iOS devices and browsers." isAdminFacing=true}</blockquote>
			<blockquote class="alert-warning">{translate text="Note: Text Only Spotlights use the iframe's scrollbar." isAdminFacing=true}</blockquote>
			<blockquote class="alert-warning">{translate text='Recommend: set iframe attribute frameborder="0" and put border any desired styling in your Style Sheet.' isAdminFacing=true}</blockquote>
		</div>
	</div>

	<h4>{translate text="Live Preview" isAdminFacing=true}</h4>

	<iframe src="{$url}/API/SearchAPI?method=getCollectionSpotlight&id={$object->id}&reload=true" width="{$width}" height="{$height}" scrolling="{if $selectedStyle == "text-list"}yes{else}no{/if}" >
		<p>{translate text="Your browser does not support iframes." isAdminFacing=true}</p>
	</iframe>

	<hr>

	<h3>{translate text="Collection Spotlight with Resizing" isAdminFacing=true}</h3>
	<h4>{translate text="Integration notes" isAdminFacing=true}</h4>
	<div class="well">
		<p>
            {translate text="To have a collection spotlight which adjusts it's height based on the html content within the spotlight use the following source url." isAdminFacing=true}
		</p>
		<blockquote class="alert-info">
		{$url}/API/SearchAPI?method=getCollectionSpotlight&amp;id={$object->id}<span style="font-weight: bold;">&resizeIframe=on</span>
		</blockquote>
		<p>
			{translate text="Include the iframe tag and javascript tags in the site as shown below." isAdminFacing=true}
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
			{translate text="This requires that the site displaying the collection spotlight have the jQuery library." isAdminFacing=true}
		</blockquote>

	</div>
	<h4>{translate text="Live Preview" isAdminFacing=true}</h4>
	<iframe id="collectionSpotlight{$object->id}" onload="setSpotlightSizing(this, 30)" src="{$url}/API/SearchAPI?method=getCollectionSpotlight&id={$object->id}&resizeIframe=on&reload=true" width="{$width}" {*height="{$height}"*} scrolling="{if $selectedStyle == "text-list"}yes{else}no{/if}">
		<p>{translate text="Your browser does not support iframes." isAdminFacing=true}</p>
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
