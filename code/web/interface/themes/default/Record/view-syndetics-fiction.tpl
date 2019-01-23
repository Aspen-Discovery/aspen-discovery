<div class='fictionProfile'>
<div class='fictionProfileTitle'>Characters</div>
{foreach from=$fictionData.characters item=character}
<div class='fictionCharacter'>
<span class='fictionCharacterName'>{$character.name}</span>
<span class='fictionCharacterGender'>{$character.gender}</span>
<span class='fictionCharacterAge'>{$character.age}</span>
<div class='fictionCharacterOccupation'>{$character.occupation}</div>
<div class='fictionCharacterDescription'>{$character.description}</div>
</div>
{/foreach}

{if isset($fictionData.topics)}
<div class='fictionProfileTitle'>Topics</div>
<div class='fictionTopics'>
{foreach from=$fictionData.topics item=topic}
<span class='fictionTopic'>{$topic}, </span>
{/foreach}
</div>
{/if}

{if isset($fictionData.settings)}
<div class='fictionProfileTitle'>Settings</div>
<div class='fictionSettings'>
{foreach from=$fictionData.settings item=setting}
<span class='fictionSetting'>{$setting}, </span>
{/foreach}
</div>
{/if}

{if isset($fictionData.settings)}
<div class='fictionProfileTitle'>Genres</div>
<div class='fictionGenres'>
{foreach from=$fictionData.genres item=genre}
<div class='fictionGenre'>{$genre.name}
	{foreach from=$genre.subGenres item=subGenre}
	<div class='fictionSubgenre'>--{$subGenre}</div>
	{/foreach}
</div>
{/foreach}
</div>
{/if}

<div class='fictionProfileTitle'>Awards</div>
{foreach from=$fictionData.awards item=award}
<div class='fictionAward'>
<span class='fictionAwardYear'>{$award.year}</span>
<span class='fictionAwardName'>{$award.name}</span>
</div>
{/foreach}

</div>