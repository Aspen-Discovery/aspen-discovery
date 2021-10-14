## Grouped Works

Grouped works are grouped together based on a normalized author value and title value. Below you can find sections on how that normalization happens. Once the normalization occurs, Aspen groups based on the title it has defined and the author it has defined. Keep in mind the following criteria are only for grouping purposes and not for defining how to display the title and author in the catalog. 

**Aspen normalizes Authors by doing the following:**

- Remove brackets (`[ ]` or `< >`) from author names
- Strip apostrophes, spaces, and white space from author names
- Remove anything after "presents," "presentation," or "distributed"
- If the author name ends in "md," strip that from the author name	
- Normalize diacritics in author names
- Remove the following common patterns: pty ltd|llc|co|company|inc|ltd|lp
- Swap First Name and Last Name for titles that are entered as last name, first name
- Remove any dates from author names (ie. Gordon, Maxwell, 1910-1983; Bernice (Bernice S.), 1879 or 1880-1964; Alder, Jamie, 1951-2010; Jacobs, David, 1978 December 11-)
- Remove trailing spaces or punctuation at the end of author names
- Remove information contained within parentheses from the author name
- Remove common author prefixes: consultant|publisher & editor-in-chief|edited by|by the editors of|editor in chief|editor-in-chief|general editor|editors|editor|by|chosen by|translated by|prepared by|translated and edited by|completely rev by|pictures by|selected and adapted by|with a foreword by|with a new foreword by|introd by|introduction by|intro by|retold by|concept|presents|general editor|editor in chief|editor-in-chief|editors|editor|inc\\setc|etc|inc|co|corporation|llc|partners|company|home entertainment|musical group|et al|concept|consultant|set al
- Strip common suffixes from author name


**Aspen normalizes Titles by doing the following:**

- Normalize diacritics using NFKC
- Add trimming of "with illustrations" to title normalization
- Add trimming dates and parenthetical information to authors
- Group title and sub title at the same time
- Strip apostrophe from "'s"
- Remove special characters 
- Remove extra spaces
- Remove parts of the title in brackets. If the whole title is in brackets, just remove the brackets. 
- Remove edition information (first|second|third|fourth|fifth|sixth|seventh|eighth|ninth|tenth|revised|edition|ed|ed|update)
- Replace "&" with "and" for better matching
- Normalize numeric values in the title (ie. 1st = first)
- Remove subtitles. Some common subtitles that are removed follow: (by, a novel of, stories, an autobiography, a biography, a memoir in books|poems|the movie|large print|graphic novel|magazine|audio cd|book club kit|with illustrations|book|the original classic edition|classic edition|a novel|large type edition|novel, novela, mystery, thriller, chronicles book, trilogy book, series)

### Format Category Consideration
Aspen also takes into account the format when grouping. Aspen will create separate grouped works for movie format categories, book format categories, and music format categories. 

### Overdrive Record Consideration
When looking at OverDrive records in order to determine what grouped work to tack onto, we look at the title, subtitle, series, and primaryCreatorName from the OverDrive data. We strip series and volume from subtitle and use the end Title and Author to group to ILS or other eContent records.
