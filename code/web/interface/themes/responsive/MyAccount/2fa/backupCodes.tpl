<div id="main-content">
    {if $loggedIn}
		<table class="table table-bordered table-striped" style="width: auto; margin: 0 auto">
            {foreach from=$backupCodes item=code name="backupCodes"}
				<tr>
					<td><samp>{$code}</samp></td>
				</tr>
            {/foreach}
		</table>
    {/if}
</div>