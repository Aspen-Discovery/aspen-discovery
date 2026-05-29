{strip}
    <h1>{translate text="Share Your Reward" isPublicFacing=true}</h1>
    <h2 class="campaignName">{$rewardName}</h2>
    
    <div class="campaignImage">
        <img src="{$og_image}" alt="{$rewardName}" style="max-width:300px; max-height:300px;" />
    </div>

	<div class="share-tools">
		<span class="share-tools-label hidden-inline-xs">{translate text="SHARE" isPublicFacing=true}</span>
        <div class="a2a_kit a2a_kit_size_32 a2a_default_style">
            {if !empty($showShareOnX)}
                <a class="a2a_button_x"></a>
            {/if}
            {if !empty($showShareOnFacebook)}
                <a class="a2a_button_facebook"></a>
            {/if}
            {if !empty($showShareOnPinterest)}
                <a class="a2a_button_pinterest"></a>
            {/if}
            {if !empty($showShareOnLink)}
                <a class="a2a_button_linkedin"></a>
            {/if}
        </div>
        <script defer src="https://static.addtoany.com/menu/page.js"></script>
    </div>
{/strip}



