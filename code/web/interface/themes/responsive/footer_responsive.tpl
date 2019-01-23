{strip}
<div class="navbar navbar-static-bottom">
	<div class="navbar-inner">
		<div class="row">
			<div class="col-tn-12 col-sm-5 text-right pull-right" id="connect-with-us-info">
				{if $twitterLink || $facebookLink || $generalContactLink || $youtubeLink || $instagramLink || $goodreadsLink}
					<span id="connect-with-us-label" class="large">CONNECT WITH US</span>
					{if $twitterLink}
						<a href="{$twitterLink}" class="connect-icon"><img src="{img filename='twitter.png'}" class="img-rounded"></a>
					{/if}
					{if $facebookLink}
						<a href="{$facebookLink}" class="connect-icon"><img src="{img filename='facebook.png'}" class="img-rounded"></a>
					{/if}
					{if $youtubeLink}
						<a href="{$youtubeLink}" class="connect-icon"><img src="{img filename='youtube.png'}" class="img-rounded"></a>
					{/if}
					{if $instagramLink}
						<a href="{$instagramLink}" class="connect-icon"><img src="{img filename='instagram.png'}" class="img-rounded"></a>
					{/if}
					{if $goodreadsLink}
						<a href="{$goodreadsLink}" class="connect-icon"><img src="{img filename='goodreads.png'}" class="img-rounded"></a>
					{/if}
					{if $generalContactLink}
						<a href="{$generalContactLink}" class="connect-icon"><img src="{img filename='email-contact.png'}" class="img-rounded"></a>
					{/if}
				{/if}
			</div>
			<div class="col-tn-12 {if $showPikaLogo}col-sm-4{else}col-sm-7{/if} text-left pull-left" id="install-info">
				{if !$productionServer}
					<small class='location_info'>{$physicalLocation}{if $debug} ({$activeIp}){/if} - {$deviceName}</small>
				{/if}
				<small class='version_info'>{if !$productionServer} / {/if}v. {$gitBranch}</small>
				{if $debug}
					<small class='session_info'> / session. {$session}</small>
				{/if}
				{if $debug}
					<small class='scope_info'> / scope {$solrScope}</small>
				{/if}
			</div>
			{if $showPikaLogo}
			<div class="col-tn-12 col-sm-3 text-center pull-left">
				<a href="http://marmot.org/pika-discovery/about-pika" title="Proud Pika Partner">
					<img id="footer-pika-logo" src="{img filename='pika-logo.png'}" alt="Proud Pika Partner" style="max-width: 100%; max-height: 80px;">
				</a>
			</div>
			{/if}
		</div>
		{if $loggedIn && (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles) || array_key_exists('cataloging', $userRoles) || array_key_exists('libraryManager', $userRoles) || array_key_exists('locationManager', $userRoles))}
			<div class="row">
				<div class="col-sm-7 text-left" id="indexing-info">
					<small>Last Full Index {$lastFullReindexFinish}, Last Partial Index {$lastPartialReindexFinish}</small>
				</div>
			</div>
		{/if}
	</div>
</div>
{/strip}
