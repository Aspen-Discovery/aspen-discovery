<strip>
<style type="text/css">
#header-container{ldelim}
    {if $headerBackgroundColor}
    background-color: {$headerBackgroundColor};
    background-image: none;
    {/if}
    {if $headerForegroundColor}
        color: {$headerForegroundColor};
    {/if}
    {if $headerBottomBorderColor}
        border-bottom-color: {$headerBottomBorderColor};
    {/if}
    {if $headerBottomBorderWidth}
        border-bottom-width: {$headerBottomBorderWidth};
    {/if}
{rdelim}

.header-button{ldelim}
    {if $headerButtonBackgroundColor}
        background-color: {$headerButtonBackgroundColor};
    {/if}
    {if $headerButtonColor}
        color: {$headerButtonColor};
    {/if}
    {if $headerButtonRadius}
        border-radius: {$headerButtonRadius};
    {/if}
{rdelim}
</style>
</strip>