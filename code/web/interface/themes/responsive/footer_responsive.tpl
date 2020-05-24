{strip}
<div class="navbar navbar-static-bottom">
	<div class="navbar-inner">
		<div class="row">
			<div class="col-tn-12 col-sm-5 text-left" id="install-info">
				<small>{translate text='powered_by_aspen_bywater' defaultText='Powered By Aspen Discovery supported by ByWater Solutions'}</small><br>
				{if !$productionServer}
					<small class='location_info'>{$physicalLocation}{if $debug} ({$activeIp}){/if} - {$deviceName}</small>
				{/if}
				<small class='version_info'>{if !$productionServer} / {/if}v. {$gitBranch}</small>
				{if $debug}
					<small class='session_info'> / session. {$session}</small>
					<small class='scope_info'> / scope {$solrScope}</small>
				{/if}
			</div>
			<div class="col-tn-12 col-sm-2 text-center" id="footer-branding">
				{if !empty($footerLogo)}
					<div>
					{if $footerLogoLink}
						<a href="{$footerLogoLink}">
					{/if}
					<img src="{$footerLogo}" aria-hidden="true" alt="Branding Image"/>
					{if $footerLogoLink}
						</a>
					{/if}
					</div>
				{/if}
			</div>
			<div class="col-tn-12 col-sm-5 text-right" id="connect-with-us-info">
				{if $twitterLink || $facebookLink || !empty($generalContactLink) || $youtubeLink || $instagramLink || $goodreadsLink}
					<span id="connect-with-us-label" class="large">CONNECT WITH US</span>
					{if $twitterLink}
						<a href="{$twitterLink}" class="connect-icon" target="_blank"><img alt='{translate text="Follow us on Twitter" inAttribute=true}' src="{img filename='twitter.png'}" class="img-rounded"></a>
					{/if}
					{if $facebookLink}
						<a href="{$facebookLink}" class="connect-icon" target="_blank"><img alt='{translate text="Like us on Facebook" inAttribute=true}' src="{img filename='facebook.png'}" class="img-rounded"></a>
					{/if}
					{if $youtubeLink}
						<a href="{$youtubeLink}" class="connect-icon" target="_blank"><img alt='{translate text="Subscribe to our YouTube Account" inAttribute=true}' src="{img filename='youtube.png'}" class="img-rounded"></a>
					{/if}
					{if $instagramLink}
						<a href="{$instagramLink}" class="connect-icon" target="_blank"><img alt='{translate text="Follow us on Instagram" inAttribute=true}' src="{img filename='instagram.png'}" class="img-rounded"></a>
					{/if}
					{if $goodreadsLink}
						<a href="{$goodreadsLink}" class="connect-icon" target="_blank"><img alt='{translate text="Follow us on GoodReads" inAttribute=true}' src="{img filename='goodreads.png'}" class="img-rounded"></a>
					{/if}
					{if !empty($generalContactLink)}
						<a href="{$generalContactLink}" class="connect-icon" target="_blank"><img alt='{translate text="Contact Us" inAttribute=true}' src="{img filename='email-contact.png'}" class="img-rounded"></a>
					{/if}
				{/if}
			</div>

		</div>
	</div>
</div>
{/strip}
