{strip}
	<script type="text/javascript" src="https://imageserver.ebscohost.com/novelistselect/ns2init.js"></script>
	<div data-novelist-novelistselect="{$primaryISBN}"></div>
	<script type="text/javascript">
		novSelect.loadContentForQuery({
				ClientIdentifier: '{$primaryISBN}',
				ISBN: '{$primaryISBN}',
				version: '2.3'
			},
			'{$novelistProfile}',
			'{$novelistKey}'
		);
	</script>
{/strip}