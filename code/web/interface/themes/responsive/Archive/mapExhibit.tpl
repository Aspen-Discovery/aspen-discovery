{strip}
<div class="col-xs-12">
	{if $parentExhibitUrl}
		{* Search/Archive Navigation for Exhibits within an exhibit *}
		{include file="Archive/search-results-navigation.tpl"}
	{/if}

	{if $main_image}
		<div class="main-project-image">
			<img src="{$main_image}" class="img-responsive" usemap="#map">
		</div>
	{/if}

	<h2>
		{$title}
		{*{$title|escape} // plb 3/8/2017 not escaping because some titles use &amp; *}
	</h2>

	<div class="lead row">
		<div class="col-xs-12">
			{if $thumbnail && !$main_image}
			{if $exhibitThumbnailURL}<a href="{$exhibitThumbnailURL}">{/if}
				<img src="{$thumbnail}" class="img-responsive thumbnail exhibit-thumbnail">
			{if $exhibitThumbnailURL}</a>{/if}
			{/if}
			{$description}
		</div>
	</div>


	<div class="row">
		<div id="exhibit-map" class="col-xs-12">
		</div>
	</div>

	<div id="exhibit-map-legend" class="row">
		<div class="col-xs-12">
			{/strip}
			{if $mapsBrowserKey}
				<script type="text/javascript">
					var infowindow;
					function initMap() {ldelim}
						VuFind.Archive.archive_map = new google.maps.Map(document.getElementById('exhibit-map'), {ldelim}
								center: {ldelim}lat: {$mapCenterLat}, lng: {$mapCenterLong}{rdelim},
								zoom: {$mapZoom}
						{rdelim});

						VuFind.Archive.archive_info_window = new google.maps.InfoWindow({ldelim}{rdelim});

						{foreach from=$mappedPlaces item=place name=place}
							{if $place.latitude && $place.longitude}
								var marker{$smarty.foreach.place.index} = new google.maps.Marker({ldelim}
									position: {ldelim}lat: {$place.latitude}, lng: {$place.longitude}{rdelim},
									map: VuFind.Archive.archive_map,
									title: '{$place.label|escapeCSS} ({$place.count})',
									icon: {ldelim}
										path: google.maps.SymbolPath.CIRCLE,
										title: '{$place.count}',
										scale: {if $place.count > 999}35{elseif $place.count > 500}30{elseif $place.count > 250}25{elseif $place.count > 99}20{elseif $place.count > 49}17{elseif $place.count > 9}12{else}8{/if},
										strokeWeight: 2,
										strokeColor: 'white',
										strokeOpacity: 0.9,
										fillOpacity: 0.85,
										fillColor: 'DodgerBlue'
										{rdelim}
								{rdelim});

								VuFind.Archive.markers[{$smarty.foreach.place.index}] = marker{$smarty.foreach.place.index};
								marker{$smarty.foreach.place.index}.addListener('click', function(){ldelim}
									VuFind.Archive.handleMapClick({$smarty.foreach.place.index}, '{$pid|urlencode}', '{$place.pid|urlencode}', '{$place.label|escape:javascript}', false, {$showTimeline});
								{rdelim});

								{if $selectedPlace == $place.pid}
									{* Click the first marker so we show images by default *}
									VuFind.Archive.handleMapClick({$smarty.foreach.place.index}, '{$pid|urlencode}', '{$place.pid|urlencode}', '{$place.label|escape:javascript}', false, {$showTimeline});
								{/if}
							{/if}
						{/foreach}
						{foreach from=$geolocatedObjects item=geolocatedObject name=geolocatedObjects}
							var geomarker{$smarty.foreach.geolocatedObjects.index} = new google.maps.Marker({ldelim}
								position: {ldelim}lat: {$geolocatedObject.latitude}, lng: {$geolocatedObject.longitude}{rdelim},
								map: VuFind.Archive.archive_map,
								title: '{$geolocatedObject.label|escape:javascript}',
								{rdelim});

							VuFind.Archive.geomarkers[{$smarty.foreach.geolocatedObjects.index}] = geomarker{$smarty.foreach.geolocatedObjects.index};
							geomarker{$smarty.foreach.geolocatedObjects.index}.addListener('click', function(){ldelim}
								VuFind.Archive.showObjectInPopup('{$geolocatedObject.pid|urlencode}', {$smarty.foreach.geolocatedObjects.index}, 1);
								{rdelim});

						{/foreach}
						{foreach from=$unmappedPlaces item=place}
							{if $selectedPlace == $place.pid}
								{* Click the first marker so we show images by default *}
								VuFind.Archive.handleMapClick(-1, '{$pid|urlencode}', '{$place.pid|urlencode}', '{$place.label|escape:javascript}', false, {$showTimeline});
							{/if}
						{/foreach}
					{rdelim}
				</script>
			{/if}
			{strip}
		</div>
	</div>

	<div id="related-objects-header" class="row">
		<div class="col-sm-8">
			{if $totalMappedLocations}
				Showing {$totalMappedLocations} locations.  Click any location to view more information about that location.
			{/if}

		</div>
		{if count($unmappedPlaces) > 0}
			<div class="col-sm-4">
				<button class="btn btn-info btn-xs" onclick="VuFind.showElementInPopup('Unmapped Locations', '#unmappedLocations');">Show Unmapped Locations</button>
			</div>
			<div id="unmappedLocations" style="display: none">
				Click any location to view more information about that location.
				<ol>
					{foreach from=$unmappedPlaces item=place}
						<li>
							<a href="{$place.url}" onclick="VuFind.closeLightbox();return VuFind.Archive.handleMapClick(-1, '{$pid|urlencode}', '{$place.pid|urlencode}', '{$place.label|escape:javascript}', false, {$showTimeline});">
								{$place.label} has {$place.count} objects
							</a>
						</li>
					{/foreach}
				</ol>
			</div>
		{/if}
	</div>

	<div id="related-objects-for-exhibit">
		<div id="exhibit-results-loading" class="row" style="display:none">
			<div class="alert alert-info">
				Updating results, please wait.
			</div>
		</div>
	</div>

	{if $repositoryLink && $loggedIn && (array_key_exists('archives', $userRoles) || array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles))}
		<div id="more-details-accordion" class="panel-group">
			<div class="panel {*active*}{*toggle on for open*}" id="staffViewPanel">
				<a href="#staffViewPanelBody" data-toggle="collapse">
					<div class="panel-heading">
						<div class="panel-title">
							Staff View
						</div>
					</div>
				</a>
				<div id="staffViewPanelBody" class="panel-collapse collapse {*in*}{*toggle on for open*}">
					<div class="panel-body">
						<a class="btn btn-small btn-default" href="{$repositoryLink}" target="_blank">
							View in Islandora
						</a>
						<a class="btn btn-small btn-default" href="{$repositoryLink}/datastream/MODS/view" target="_blank">
							View MODS Record
						</a>
						<a class="btn btn-small btn-default" href="{$repositoryLink}/datastream/MODS/edit" target="_blank">
							Edit MODS Record
						</a>
						<a class="btn btn-small btn-default" href="#" onclick="return VuFind.Archive.clearCache('{$pid}');" target="_blank">
							Clear Cache
						</a>
					</div>
				</div>
			</div>
		</div>
	{/if}
</div>
	{if $mapsBrowserKey}
		<script src="https://maps.googleapis.com/maps/api/js?key={$mapsBrowserKey}&callback=initMap" async defer></script>
	{/if}
{/strip}
<script type="text/javascript">
	$().ready(function(){ldelim}
		VuFind.Archive.loadExploreMore('{$pid|urlencode}');
	{rdelim});
</script>