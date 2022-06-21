{strip}
	<div data-role="content">
		{if $loggedIn}
			{if !empty($profile->_web_note)}
				<div id="web_note" class="text-info text-center alert alert-warning"><strong>{$profile->_web_note}</strong></div>
			{/if}
			{if !empty($accountMessages)}
				{include file='systemMessages.tpl' messages=$accountMessages}
			{/if}
			{if !empty($ilsMessages)}
				{include file='ilsMessages.tpl' messages=$ilsMessages}
			{/if}

			<h1>{translate text='My Account' isPublicFacing=true}</h1>
			{if $userHasCatalogConnection}
				<h2>{translate text='Account Summary' isPublicFacing=true}</h2>
				{if $offline}
					<div>
						<div class="alert alert-warning"><strong>{translate text="The library system is currently offline." isPublicFacing=true}</strong> {translate text="We are unable to retrieve information about your account at this time." isPublicFacing=true}</div>
					</div>
				{else}
					<div class="row">
						<div class="col-tn-6">
							<div class="btn btn-block btn-default">
								<a href="/MyAccount/CheckedOut">
									<div class="dashboardLabel">{translate text="Checked Out" isPublicFacing=true}</div>
									<div class="dashboardValue"><span class="checkouts-placeholder"><img src="/images/loading.gif" alt="loading"></span></div>
								</a>
							</div>
						</div>
						<div class="col-tn-6">
							<div class="btn btn-block btn-default">
								<a href="/MyAccount/CheckedOut">
									<div class="dashboardLabel">{translate text="Overdue" isPublicFacing=true}</div>
									<div class="dashboardValue"><span class="ils-overdue-placeholder"><img src="/images/loading.gif" alt="loading"></span></div>
								</a>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-sm-12">&nbsp;</div>
					</div>
					<div class="row">
						<div class="col-tn-6">
							<div class="btn btn-block btn-default">
								<a href="/MyAccount/Holds">
									<div class="dashboardLabel">{translate text="Holds" isPublicFacing=true}</div>
									<div class="dashboardValue"><span class="holds-placeholder"><img src="/images/loading.gif" alt="loading"></span></div>
								</a>
							</div>
						</div>
						<div class="col-tn-6">
							<div class="btn btn-block btn-default">
								<a href="/MyAccount/Holds">
									<div class="dashboardLabel">{translate text="Ready For Pickup" isPublicFacing=true}</div>
									<div class="dashboardValue"><span class="ils-available-holds-placeholder"><img src="/images/loading.gif" alt="loading"></span></div>
								</a>
							</div>
						</div>
					</div>
				{/if}
			{/if}
			{if $showRatings}
				<h2>{translate text='Recommended for you' isPublicFacing=true}</h2>
				{if !$hasRatings}
					<p>
						{translate text='You have not rated any titles.' isPublicFacing=true}
					</p>
					<p>
						{translate text ='If you rate titles, we can provide you with suggestions for titles you might like to read. Suggestions are based on titles you like and information within the catalog. Library staff does not have access to your suggestions.' isPublicFacing=true}
					</p>
				{else}
					<div id="recommendedForYouInfo" class="row">
						<div class="col-sm-12">
							<div class="jcarousel-wrapper recommendationsWrapper">
								<div class="jcarousel horizontalCarouselSpotlight" id="recommendationsCarousel">
									<div class="loading">{translate text="Loading recommendations..." isPublicFacing=true}</div>
								</div>

								<a href="#" class="jcarousel-control-prev" aria-label="{translate text="Previous Item" inAttribute=true isPublicFacing=true}"><i class="fas fa-caret-left"></i></a>
								<a href="#" class="jcarousel-control-next" aria-label="{translate text="Next Item" inAttribute=true isPublicFacing=true}"><i class="fas fa-caret-right"></i></a>
							</div>
						</div>
					</div>
					<script type="text/javascript">
						{literal}
							$(document).ready(function (){
								AspenDiscovery.Account.loadRecommendations();
							});
						{/literal}
					</script>
				{/if}
			{/if}
		{else}
			{translate text="You must sign in to view this information." isPublicFacing=true}<a href='/MyAccount/Login' class="btn btn-primary">{translate text="Sign In" isPublicFacing=true}</a>
		{/if}
	</div>
{/strip}