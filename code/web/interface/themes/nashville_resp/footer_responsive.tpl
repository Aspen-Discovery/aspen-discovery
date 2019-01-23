{strip}


    <div id="footer">
      <div class="l--constrained clearfix">

        <div id="block-menu-footer-menu" class="block block-menu">
            <ul class="nav">
              <li class="first leaf nav-item"><a href="http://www.library.nashville.org/about/abt_about.asp" title="About" class="nav-link">About Us</a></li>
              <li class="leaf nav-item"><a href="http://www.library.nashville.org/footer/contact_us.asp" title="Contact Us" class="nav-link">Contact Us</a></li>
              <!--<li class="leaf nav-item"><a href="http://www.library.nashville.org/Info/gen_privacy.asp" title="Privacy Notice" class="nav-link">Privacy Notice</a></li>-->
              <li class="last collapsed nav-item"><a href="http://www.library.nashville.org/Info/gen_privacy.asp" title="Privacy Notice" class="nav-link">Privacy Notice</a></li>
            </ul>
        </div>

        <div id="block-infodesk-base-social" class="block block-infodesk-base">
            <div class="social">
              <ul>
                <li class="twitter"><a class="icons_twitter" href="http://www.twitter.com/nowatnpl">Twitter</a></li>
                <li class="youtube"><a class="icons_youtube" href="http://www.youtube.com/user/NashvilleLibrary">YouTube</a></li>
                <li class="facebook"><a class="icons_facebook" href="http://www.facebook.com/nashvillepubliclibrary">Facebook</a></li>
                <li class="flickr"><a class="icons_flickr" href="http://www.flickr.com/photos/nashvillepubliclibrary">Flickr</a></li>
                <li class="pinterest"><a class="icons_pinterest" href="http://www.pinterest.com/nowatnpl/">Pinterest</a></li>
              </ul>
              <a class="newsletter" href="http://www.library.nashville.org/Info/gen_email.asp">Newsletter Signup</a>
            </div>
        </div>

      </div>
    </div>

<div class="navbar navbar-static-bottom">
	<div class="navbar-inner">
		<div class="l--constrained clearfix">

		<div class="row">
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
	<!--		<div class="col-sm-5 text-right" id="connect-with-us-info">

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

			</div>-->
		</div>
		{if $lastFullReindexFinish && $lastPartialReindexFinish}
			<div class="row">
				<div class="col-sm-12 text-left" id="indexing-info">
					<small>Last Full Index {$lastFullReindexFinish}, Last Partial Index {$lastPartialReindexFinish}</small>
				</div>
			</div>
		{/if}

		{if $inLibrary}
			<div class="text-right">
				<a href="https://www.volgistics.com/ex/portal.dll/?FROM=15495">VOLUNTEER LOGIN</a>
			</div>
		{/if}

</div>
    </div>
</div>



{/strip}
