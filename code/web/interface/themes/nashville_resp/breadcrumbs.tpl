{strip}
	{* Added Breadcrumbs to appear above the format filter icons - JE 6/26/15 *}
	<div class="row breadcrumbs">
		<div class="hidden-xs col-xs-12 col-sm-9">
			{if $showBreadcrumbs}
				<ul class="breadcrumb small">
					<li><a href="{$homeBreadcrumbLink}" id="home-breadcrumb"><i class="icon-home"></i> {translate text=$homeLinkText}</a> <span class="divider">&raquo;</span></li>
					{include file="$module/breadcrumbs.tpl"}
				</ul>
			{/if}
		</div>
		<a name="top"></a>
		<div class="col-xs-12 col-sm-3 text-right">
			{if $google_translate_key}
			{literal}
				<div id="google_translate_element">
				<script type="text/javascript">
							function googleTranslateElementInit() {
							new google.translate.TranslateElement({
							pageLanguage: 'en',
							layout: google.translate.TranslateElement.InlineLayout.SIMPLE
			{/literal}
				{if $google_included_languages}
				, includedLanguages: '{$google_included_languages}'
				{/if}
			{literal}
							}, 'google_translate_element');
							}
				</script>
				<script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
				</div>
			{/literal}
			{/if}
		</div>
	</div>
{/strip}