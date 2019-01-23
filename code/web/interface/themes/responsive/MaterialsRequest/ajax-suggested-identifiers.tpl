{strip}
<div class='suggestedIdentifier'>
{if count($suggestedIdentifiers) == 0}
	Sorry, we couldn't find an ISBN or OCLC Number for that title, please try changing the title and author and searching again.
{else}
	<table class="suggestedIdentifierTable">
		<thead>
			<tr><td>&nbsp;</td><td>Title</td><td>&nbsp;</td></tr>
		</thead>
		<tbody>
			{foreach from=$suggestedIdentifiers item=suggestion key=rownum}
			{if $suggestion.isbn}
			  {assign var=isn value=$suggestion.isbn}
			{elseif $suggestion.oclcNumber}
			  {assign var=isn value=$suggestion.oclcNumber}
			{/if}
			<tr>
		    <td>{if $isn}<img src="{$path}/bookcover.php?isn={$isn}&size=small" alt="book cover"/>{else}&nbsp;{/if}</td>
				<td>
					<div class="worldCatTitle"><a href="{$suggestion.link}">{$suggestion.title}</a></div>
					<div>by {$suggestion.author|truncate:60}</div>
					<div>ISBN: {$suggestion.isbn}</div>
					<div>OCLC Number: {$suggestion.oclcNumber}</div>
					{if strlen($suggestion.description) > 0}
						<div id="worldCatDescription{$rownum}">
						  <div class="short">
						  {$suggestion.description|truncate:150|escape}
						  <a href="#" onclick="{literal}${/literal}('.short', '#worldCatDescription{$rownum}').hide();{literal}${/literal}('.full', '#worldCatDescription{$rownum}').slideDown().show();return false;">More</a>
						  </div>
						  <div class="full" style="display:none;">
						  {$suggestion.description|escape}
						  <a href="#" onclick="{literal}${/literal}('.full', '#worldCatDescription{$rownum}').hide();{literal}${/literal}('.short', '#worldCatDescription{$rownum}').show();return false;">Less</a>
						  </div>
						</div>
					{/if}
				</td>

				<td><input type="button" value="Use This" onclick="VuFind.MaterialsRequest.setIsbnAndOclcNumber('{$suggestion.title|escape}', '{$suggestion.author|escape}', '{$suggestion.isbn}', '{$suggestion.oclcNumber}')" /></td>
			</tr>
			{/foreach}
		</tbody>
	</table>
{/if}
</div>
{/strip}