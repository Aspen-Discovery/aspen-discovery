{strip}
	<div class="row">
		<div class="col-xs-12 col-md-9">
			<h1 id="pageTitle">{$pageTitleShort}</h1>
		</div>
	</div>
{/strip}
	{if $mapsKey}
		<div class="row">
			<div class="col-xs-12">
				<div id="map" style="height: 600px;width: 100%"></div>
			</div>
		</div>
		<script>
			{literal}
			var map;
			function initMap() {
				map = new google.maps.Map(document.getElementById('map'), {
					center: {lat: {/literal}{$center.latitude}, lng: {$center.longitude}{literal} },
					zoom: 4
				});
				{/literal}
					{foreach from=$siteMarkers item=siteMarker}
						var marker = new google.maps.Marker({ldelim}
							position: {ldelim}lat:{$siteMarker->latitude}, lng: {$siteMarker->longitude} {rdelim},
							map: map,
							title: '{$siteMarker->name}',
						{rdelim});
					{/foreach}
				{literal}
			}
			{/literal}
		</script>
		<script src="https://maps.googleapis.com/maps/api/js?key={$mapsKey}&callback=initMap"
		        async defer></script>
	{else}
		<div class="row">
			<div class="col-xs-12">
				<div>{translate text="You must define a google maps key to see the map of all sites" isAdminFacing=true}</div>
			</div>
		</div>
	{/if}
