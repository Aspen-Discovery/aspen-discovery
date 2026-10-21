<!DOCTYPE html>
<html lang="{$userLang->code}" class="hero-slider-website">
{strip}
<head>
	<title>{$location->name}</title>
	<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />

	{include file="cssAndJsIncludes.tpl" includeAutoLogoutCode=false}
	<link rel="stylesheet" href="/interface/themes/responsive/css/lib/swiper-bundle.css" />
</head>

<body class="hero-slider-website">
	<div class="hero-slider-container">
		<div class="swiper hero-slider">
			<div class="swiper-wrapper">
				{foreach from=$slides item=slide}
					<div class="swiper-slide hero-slide" data-duration="{$slide.duration}">
						{if $slide.image->pageLink}
							<a href="{$slide.image->pageLink}" target="_blank">
								<img src="{$slide.image->getDisplayUrl('full')}"
									 alt="{$slide.image->altText|escape}" />
							</a>
						{else}
							<img src="{$slide.image->getDisplayUrl('full')}"
								 alt="{$slide.image->altText|escape}" />
						{/if}
					</div>
				{/foreach}
			</div>

			{if count($slides) > 1}
				<div class="swiper-pagination"></div>
			{/if}
			{if $location->autoRotate && count($slides) > 1}
				<button class="swiper-button-pause" aria-label="Pause auto-rotation" title="Pause">
					<i class="fas fa-pause"></i>
				</button>
			{/if}
			{if count($slides) > 1}
				<div class="swiper-button-prev"></div>
				<div class="swiper-button-next"></div>
			{/if}
		</div>
	</div>

	<script>
		$(() => {ldelim}
			AspenDiscovery.HeroSlider.initWebsiteSlider({ldelim}
				autoRotate: {if $location->autoRotate}true{else}false{/if},
				defaultInterval: {$location->rotationInterval * 1000}
			{rdelim})
		{rdelim})
	</script>
</body>
{/strip}
</html>
