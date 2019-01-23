New request for a copy of materials in the Local Digital Archive.

Name: {$requestResult->name}

{if $requestResult->address}
Address:
{$requestResult->address}
{if $requestResult->address2}
{$requestResult->address2}
{/if}{$requestResult->city} {$requestResult->state}, {$requestResult->zip}
{$requestResult->country}
{/if}

Phone: {$requestResult->phone}
{if $requestResult->alternatePhone}Alternate Phone: {$requestResult->alternatePhone}{/if}

E-mail: {$requestResult->email}

{if $requestResults->format}
Format Requested: {$requestResult->format}
{/if}
Purpose:
{$requestResult->purpose}

Object Requested:
{if $requestedObject}
{$requestedObject->getTitle()}
{else}
Could not load requested object.  Pid is {$requestResult->getUniqueID()}
{/if}