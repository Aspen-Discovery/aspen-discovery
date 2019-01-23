{assign var=bracket value='{'}
{assign var=marcPhdField value=$marc->getField('502')}
{assign var=marcProceedingsField value=$marc->getField('711')}
{if $marcProceedingsField}
@proceedings{$bracket}
{else}
{if $marcPhdField}
@phdthesis{$bracket}
{else}
{if is_array($recordFormat)}
{if in_array('Article', $recordFormat)}
@article{$bracket}
{else}
{if in_array('Journal', $recordFormat)}
@misc{$bracket}
{else}
@book{$bracket}
{/if}
{/if}
{else}
{if $recordFormat == 'Article' || $recordFormat == 'Book'}
@{$recordFormat}{$bracket}
{else}
@misc{$bracket}
{/if}
{/if}
{/if}
{/if}
{assign var=marcIdField value=$marc->getField('001')}
{if $marcIdField}
GBV-{$marcIdField->getData()},
{/if}
{assign var=marcField value=$marc->getField('245')}
title = {$bracket}{$marcField|getvalue:'a'|replace:'/':''}{if $marcField|getvalue:'b'} {$marcField|getvalue:'b'|replace:'/':''}{/if}},
{assign var=marcField440 value=$marc->getFields('440')}
{* Display subject section if at least one subject exists. *}
{if $marcField440}
{foreach from=$marcField440 item=field name=loop}
series = {$bracket}{$field|getvalue:'a'}},
{/foreach}
{/if}
{assign var=marcField value=$marc->getField('100')}
{if $marcField}
author = {$bracket}{$marcField|getvalue:'a'|replace:'.':''}},
{/if}
{assign var=marcField value=$marc->getField('110')}
{if $marcField}
author = {$bracket}{$marcField|getvalue:'a'}},
{/if}
{assign var=marcField value=$marc->getFields('700')}
{if $marcField}
{foreach from=$marcField item=field name=loop}
editor = {$bracket}{$field|getvalue:'a'}},
{/foreach}
{/if}
{assign var=marcField value=$marc->getFields('260')}
{if $marcField}
{foreach from=$marcField item=field name=loop}
{if $field|getvalue:'a'}
address = {$bracket}{$field|getvalue:'a'|replace:':':''}},
{/if}
{if $field|getvalue:'b'}
publisher = {$bracket}{$field|getvalue:'b'|replace:',':''}},
{/if}
{if $field|getvalue:'c'}
year = {$bracket}{$field|getvalue:'c'|replace:'.':''}},
{/if}
{/foreach}
{/if}
{assign var=marcField value=$marc->getFields('250')}
{if $marcField}
{foreach from=$marcField item=field name=loop}
edition = {$bracket}{$field|getvalue:'a'}},
{/foreach}
{/if}
{if $marcPhdField}
school = {$bracket}{$marcPhdField|getvalue:'a'}},
{/if}
{assign var=marcField value=$marc->getField('300')}
{if $marcField}
pages = {$bracket}{$marcField|getvalue:'a'}},
{/if}
{assign var=marcField value=$marc->getField('500')}
{if $marcField}
note = {$bracket}{$marcField|getvalue:'a'}},
{/if}
{assign var=marcField value=$marc->getField('856')}
{if $marcField}
url = {$marcField|getvalue:'u'},
{/if}
crossref = {$path}/{$activeRecordProfileModule}/{$id|escape:"url"}
}
