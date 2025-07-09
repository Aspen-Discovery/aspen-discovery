{strip}
<div class="navbar navbar-static-bottom">
	<div class="navbar-inner">
		<div class="row {if !empty($fullWidthTheme)}row-no-gutters{/if}">
			<div class="col-tn-12 col-sm-5 col-md-4 text-left" id="install-info">
				{if !empty($supportingCompany)}
					<small>{translate text="Powered By Aspen Discovery supported by %1%" 1=$supportingCompany isPublicFacing=true}</small><br>
				{else}
					<small>{translate text="Powered By Aspen Discovery" isPublicFacing=true}</small><br>
				{/if}
				{if empty($productionServer)}
					<small class='location_info'>{$physicalLocation}{if !empty($debug)} ({$activeIp}){/if} - {$deviceName}</small>
				{/if}
				<small class='version_info'>{if empty($productionServer)} / {/if}{translate text="v. %1%" 1=$aspenVersion isPublicFacing=true}</small>
				{if !empty($debug)}
					<small class='session_info'> / {translate text="session %1%" 1=$session isAdminFacing=true}</small>
					<small class='scope_info'> / {translate text="scope %1%" 1=$solrScope isAdminFacing=true}</small>
				{/if}
				{if empty($loggedIn) && $ssoIsEnabled}{* Not Logged In *}
					{if $ssoStaffOnly && !(empty($ssoService))}
						{if $canLoginSSO}
							{if $bypassAspenLogin == '1' && $ssoService != 'ldap'}
								{if $ssoService == 'oauth'}
									<br><small id="ssoStaffLogin"><a href="/init_oauth.php" id="ssoStaffLoginLink">{translate text='Staff Login' isPublicFacing=true}</a></small>
								{/if}
								{if $ssoService == 'saml'}
									<br><small id="ssoStaffLogin"><a href="/Authentication/SAML2?init" id="ssoStaffLoginLink">{translate text='Staff Login' isPublicFacing=true}</a></small>
								{/if}
							{else}
								<br><small id="ssoStaffLogin"><a href="/MyAccount/StaffLogin" id="ssoStaffLoginLink">{translate text='Staff Login' isPublicFacing=true}</a></small>
							{/if}
						{/if}
					{/if}
				{/if}
			</div>
			<div class="col-tn-12 col-sm-3 col-md-4 text-center" id="footer-branding">
				{if !empty($footerText)}
					<div id="footer-custom-text">
						{$footerText}
					</div>
				{/if}
				{if !empty($footerLogo)}
					<div id='footer-branding-image'>
					{if !empty($footerLogoLink)}
						<a href="{$footerLogoLink}">
					{/if}
					<img src="{$footerLogo}" alt="{if !empty($footerLogoAlt)}{$footerLogoAlt}{else}{$librarySystemName}{/if}"/>
					{if !empty($footerLogoLink)}
						</a>
					{/if}
					</div>
				{/if}
			</div>
			<div class="col-tn-12 col-sm-4 col-md-4 text-right" id="connect-with-us-info">
				{if $twitterLink || $facebookLink || !empty($generalContactLink) || $youtubeLink || $instagramLink || $pinterestLink || $goodreadsLink || $tiktokLink}
					<span id="connect-with-us-label" class="large">{translate text='CONNECT WITH US' isPublicFacing=true}</span>
					{if !empty($twitterLink)}
						<a href="{$twitterLink}" class="connect-icon" target="_blank" title="{translate text="Follow us on X" inAttribute=true isPublicFacing=true}" aria-label="{translate text="Follow us on X" inAttribute=true isPublicFacing=true}  ({translate text="opens in a new window" isPublicFacing=true inAttribute=true})"><i class='fa-brands fa-x-twitter fa-lg' role="presentation"></i></a>
					{/if}
					{if !empty($facebookLink)}
						<a href="{$facebookLink}" class="connect-icon" target="_blank" title="{translate text="Like us on Facebook" inAttribute=true isPublicFacing=true}" aria-label="{translate text="Like us on Facebook" inAttribute=true isPublicFacing=true} ({translate text="opens in a new window" isPublicFacing=true inAttribute=true})"><i class='fa-brands fa-facebook fa-lg' role="presentation"></i></a>
					{/if}
					{if !empty($youtubeLink)}
						<a href="{$youtubeLink}" class="connect-icon" target="_blank" title="{translate text="Subscribe to our YouTube Channel" inAttribute=true isPublicFacing=true}" aria-label="{translate text="Subscribe to our YouTube Channel" inAttribute=true isPublicFacing=true} ({translate text="opens in a new window" isPublicFacing=true inAttribute=true})"><i class='fa-brands fa-youtube fa-lg' role="presentation"></i></a>
					{/if}
					{if !empty($instagramLink)}
						<a href="{$instagramLink}" class="connect-icon" target="_blank" title="{translate text="Follow us on Instagram" inAttribute=true isPublicFacing=true}" aria-label="{translate text="Follow us on Instagram" inAttribute=true isPublicFacing=true} ({translate text="opens in a new window" isPublicFacing=true inAttribute=true})"><i class='fa-brands fa-instagram fa-lg' role="presentation"></i></a>
					{/if}
					{if !empty($pinterestLink)}
						<a href="{$pinterestLink}" class="connect-icon" target="_blank" title="{translate text="Follow us on Pinterest" inAttribute=true isPublicFacing=true}" aria-label="{translate text="Follow us on Pinterest" inAttribute=true isPublicFacing=true} ({translate text="opens in a new window" isPublicFacing=true inAttribute=true})"><i class='fa-brands fa-pinterest fa-lg' role="presentation"></i></a>
					{/if}
					{if !empty($goodreadsLink)}
						<a href="{$goodreadsLink}" class="connect-icon" target="_blank" title="{translate text="Follow us on Goodreads" inAttribute=true isPublicFacing=true}" aria-label="{translate text="Follow us on Goodreads" inAttribute=true isPublicFacing=true} ({translate text="opens in a new window" isPublicFacing=true inAttribute=true})"><i class='fa-brands fa-goodreads fa-lg' role="presentation"></i></a>
					{/if}
					{if !empty($tiktokLink)}
						<a href="{$tiktokLink}" class="connect-icon" target="_blank" title="{translate text="Follow us on TikTok" inAttribute=true isPublicFacing=true}" aria-label="{translate text="Follow us on TikTok" inAttribute=true isPublicFacing=true} ({translate text="opens in a new window" isPublicFacing=true inAttribute=true})"><i class='fa-brands fa-tiktok fa-lg' role="presentation"></i></a>
					{/if}
					{if !empty($blueskyLink)}
							<a href="{$blueskyLink}" class="connect-icon" target="_blank" title="{translate text="Follow us on BlueSky" inAttribute=true isPublicFacing=true}" aria-label="{translate text="Follow us on BlueSky" inAttribute=true isPublicFacing=true} ({translate text="opens in a new window" isPublicFacing=true inAttribute=true})"><i class='fa-brands fa-bluesky fa-lg' role="presentation"></i></a>
					{/if}
					{if !empty($threadsLink)}
							<a href="{$threadsLink}" class="connect-icon" target="_blank" title="{translate text="Follow us on Threads" inAttribute=true isPublicFacing=true}" aria-label="{translate text="Follow us on Threads" inAttribute=true isPublicFacing=true} ({translate text="opens in a new window" isPublicFacing=true inAttribute=true})"><i class='fa-brands fa-threads fa-lg' role="presentation"></i></a>
					{/if}
					{if !empty($generalContactLink)}
						<a href="{$generalContactLink}" class="connect-icon" target="_blank" title="{translate text="Contact Us" inAttribute=true isPublicFacing=true}" aria-label="{translate text="Contact Us" inAttribute=true isPublicFacing=true} ({translate text="opens in a new window" isPublicFacing=true inAttribute=true})"><i class='fas fa-envelope-open fa-lg' role="presentation"></i></a>
					{/if}
				{/if}
			</div>

		</div>
	</div>
</div>
{/strip}
