{strip}
	<div id="main-content" class="col-xs-12">
        <h1>{translate text="Aspen API Documentation" isAdminFacing=true}</h1>
        <hr>
        <rapi-doc spec-url="{$apiFile}" render-style="view" 
{/strip}
         allow-spec-file-load="false" 
         default-schema-tab="schema" 
         show-curl-before-try="true"
         style="width:100%;" 
         theme="light" 
         bg-color="{$bodyBackgroundColor}" 
         header-color="{$bodyBackgroundColor}" 
         regular-font="{$bodyFont}" 
         mono-font="'Consolas', monospace" 
         text-color="{$bodyTextColor}" 
         primary-color="{$primaryButtonBackgroundColor}" 
         nav-bg-color="{$secondaryBackgroundColor}">
{strip}
            <img slot="logo" src="" alt="" />
        </rapi-doc>
	</div>

<script type="module" src="/interface/themes/responsive/js/lib/rapidoc.min.js?v={$aspenVersion|urlencode}.{$cssJsCacheCounter}"></script>
{/strip}