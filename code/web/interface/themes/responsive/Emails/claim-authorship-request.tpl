New claim for authorship of materials in the Local Digital Archive.

Name: {$requestResult->name}

Phone: {$requestResult->phone}
{if $requestResult->alternatePhone}Alternate Phone: {$requestResult->alternatePhone}{/if}

Email: {$requestResult->email}

Message:
{$requestResult->message}

Object Claimed:
{if $requestedObject}
{$requestedObject->getTitle()}
{else}
Could not load requested object.  Pid is {$requestResult->getUniqueID()}
{/if}