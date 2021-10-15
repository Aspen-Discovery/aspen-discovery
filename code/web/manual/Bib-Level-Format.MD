##Assigning Format Based on Biblio Record Data 

Within Aspen you have the capability to assign format based on bibliographic record data, instead of item specific codes (ie item types). In order to do this, within ILS Indexing Information under Format Information you assign 'Load Format From' to 'Bib Record'. Aspen Discovery thoroughly examines the biblio record and checks in various places before deciding what the format of the item is. The final format decision is based on the following criteria:


  * If the 008 is six characters or longer and the 6th character is a J, the format is 'MusicRecording'.
  * If position 6 of the leader contains a,t,A or T and the 008 position 23 is 'f' then the format is "Braille"
  * If position 6 of the leader contains a,t,A or T and the 008 position 23 is 'd' then the format is "LargePrint"

The following rules reference the 347$b. 

  * If the 347b contains "Blu-Ray" then the format will be "Blu-ray"
  * If the 347b contains "DVD video" then the format will be "DVD"
  * If the 347b contains "Archival Materials then the format will be "Archival Materials"
  * If the 347b contains "SoundCassette" and "MusicRecording" then format will be "MusicCassette"
  * If the 347b contains Thesis" then the format will be "Thesis"
  * If the 347b contains "Phonograph" then the format will be "Phonograph"
  * If the 347b contains "MusicRecording" and "CD" then the format will be "MusicCD"
  * If the 347b contains "SoundDisc" then the format will be "MusicCD"
  * If the 347b contains "PlayawayView" then the format will be "PlayawayView"
  * If the 347b contains "Playaway" then the format will be "Playaway"
  * If the 347b contains "GoReader" then the format will be "GoReader"
  * If the 347b contains "Video" and "DVD" then the format will be "Video"
  * If the 347b contains "VideoDisc" and "DVD" then the format will be "DVD"
  * If the 347b contains "Video" and "VideoDisc" then the format will be "VideoDisc"
  * If the 347b contains "Video" and "VideoCassette" then the format will be "VideoCassette"
  * If the 347b contains "DVD" then the format will be "DVD"
  * If the 347b contains "Blu-ray" then the format will be "Blu-ray"
  * If the 347b contains "SoundDisc" then the format will be "SoundDisc"
  * If the 347b contains "SoundCassette" then the format will be "SoundCassette"
  * If the 347b contains "SoundRecording" and "CDROM" then the format will be "SoundDisc"
  * If the 347b contains "Book" and "LargePrint" then the format will be "LargePrint"
  * If the 347b contains "Book" and "Manuscript" then the format will be "Manuscript"
  * If the 347b contains "Book" and "GraphicNovel" then the format will be "GraphicNovel"
  * If the 347b contains "Book" and "MusicalScore" then the format will be "MusicalScore"
  * If the 347b contains "Book" and "BookClubKit" then the format will be "BookClubKit"
  * If the 347b contains "Book" and "Kit" then the format will be "Kit"
  * If the 347b contains "AudioCD" and "CD" then the format will be "CD"
  * If the 347b contains "CD" and "SoundDisc" then the format will be "SoundDisc"
  * If the 347b contains "CompactDisc" and "SoundDisc" then the format will be "SoundDisc"
  * If the 347b contains "CompactDisc" then the format will be "CompactDisc"
  * If the 347b contains "GraphicNovel" then the format will be "GraphicNovel"
  * If the 347b contains "Atlas" and "Map" then the format will be "Map"
  * If the 347b contains "LargePrint" then the format will be "LargePrint"
  * If the 347b contains "Kinect" then the format will be "Kinect"
  * If the 347b contains "XBox360" then the format will be "XBox360"
  * If the 347b contains "XBoxOne" then the format will be "XBoxOne"
  * If the 347b contains "PlayStation" then the format will be "PlayStation"
  * If the 347b contains "PlayStation3" then the format will be "PlayStation3"
  * If the 347b contains "PlayStation4" then the format will be "PlayStation4"
  * If the 347b contains "Wii" then the format will be "Wii"
  * If the 347b contains "WiiU" then the format will be "WiiU"
  * If the 347b contains "3DS" then the format will be "3DS"
  * If the 347b contains "WindowsGame" then the format will be "WindowsGame"
  * If the 347b contains "LibraryOfThings" then the format will be "LibraryOfThings"
  * If the 347b contains "CD+DVD" then the format will be "CD+DVD"
  * If the 347b contains "VoxBooks" then the format will be "VoxBooks"
  * If the 347b contains "BookClubKit" & "LargePrint" then the format will be "BookClubKitLarge"


The following rules reference the 245$h. 

  * If the 245h contains "sound recording-cass" then the format will be "SoundCassette"
  * If the 245h contains "large print" then the format will be "LargePrint"
  * If the 245h contains "book club kit" then the format will be "BookClubKit"
  * If the 245h contains "ebook" then the format will be "eBook"
  * If the 245h contains "eaudio" then the format will be "eAudio"
  * If the 245h contains "emusic" then the format will be "eMusic"
  * If the 245h contains "evideo" then the format will be "eVideo"
  * If the 245h contains "ejournal" then the format will be "eJournal"
  * If the 245h contains "playaway" then the format will be "Playaway"
  * If the 245h contains "periodical" then the format will be "Serial"
  * If the 245h contains "vhs" then the format will be "VideoCassette"
  * If the 245h contains "blu-ray" then the format will be "Blu-ray"
  * If the 245h contains "dvd" then the format will be "DVD"


The following rules reference the 245$k. 

  * If the 245k contains "sound recording-cass" then the format will be "SoundCassette"
  * If the 245k contains "large print" then the format will be "LargePrint"
  * If the 245k contains "book club kit" then the format will be "BookClubKit"


The following rules reference the 245$p. 

  * If the 245p contains "sound recording-cass" then the format will be "SoundCassette"
  * If the 245p contains "large print" then the format will be "LargePrint"


The following rules reference the 245$a.

  * If the 245a contains "book club kit" then the format will be "BookClubKit"


The following rules reference the 260$b.

  * If the 260b contains "playaway" then the format will be "Playaway"
  * If the 260b contains "go reader" then the format will be "GoReader"


The following rules reference the 250$a.

  * If the 250a contains "large type" or "large print" then the format will be "LargePrint"
  * If the 250a contains "go reader" then the format will be "GoReader"
  * If the 250a contains "kinect sensor" then the format will be "Kinect"
  * If the 250a contains "xbox one" and does not contain "compatible" then the format will be "XboxOne"
  * If the 250a contains "xbox" and does not contain "compatible" then the format will be "Xbox360"
  * If the 250a contains "playstation 4" and does not contain "compatible" then the format will be "PlayStation4"
  * If the 250a contains "playstation 3" and does not contain "compatible" then the format will be "PlayStation3"
  * If the 250a contains "playstation" and does not contain "compatible" then the format will be "PlayStation"
  * If the 250a contains "wii u" then the format will be "WiiU"
  * If the 250a contains "nintendo wii" then the format will be "Wii"
  * If the 250a contains "nintendo 3ds" then the format will be "3DS"
  * If the 250a contains "directx" then the format will be "WindowsGame"
  * If the 250a contains any variation of "blu-ray" AND "dvd" then the format will be "Blu-ray/DVD"


The following rules reference the 300 field and any subfield with the exception of subfield e. 

  * If the subfields in the 300, except for subfield 'e', contain "large type" or "large print" then the format will be "LargePrint"
  * If the subfields in the 300, except for subfield 'e', contain "bluray" or "blu-ray" then the format will be "Blu-ray"
  * If the subfields in the 300, except for subfield 'e', contain "computer optical disc" and a note about the number of pages then the format will be "Software"
  * If the subfields in the 300, except for subfield 'e', contain "sound cassettes" or "SoundCassette" or "sound discs" or "audio discs" or "compact disc" then the format will be "SoundDisc"
  * If the subfields in the 300, except for subfield 'e', contain "mp3" then the format will be "MP3Disc"

The following rules reference the 300 field subfield e. 

  * If the 300e contains "Kit" then the format will be "Kit"
  * If the 300e contains "book" or "cd" then the format will be "CD+Book"
  * If the 300e contains "dvd" then the format will be "Book+DVD"

If this point no format has been assigned from the 300 and the 300$f or 300$a contain a note about the number of pages, then the format will be "Book"


The following rules reference the 538$a.

  * If the 538a contains "playaway" then the format will be "Playaway"
  * If the 538a contains "bluray" OR "blu-ray" then the format will be "Blu-ray"
  * If the 538a contains "dvd" then the format will be "DVD"
  * If the 538a contains "vertical file" then the format will be "VerticalFile"
  * If the 538a contains any variation of "blu-ray" AND "dvd" then the format will be "Blu-ray/DVD"


The following rules reference the 500$a.

  * If the 500a contains "vertical file" then the format will be "VerticalFile"
  * If the 500a contains "vox books" then the format will be "VoxBooks"
  * If the 500a contains any variation of "blu-ray" AND "dvd" then the format will be "Blu-ray/DVD"


The following rules reference the 502$a.

  * If the 502a contains "thesis (m.a.)" then the format will be "Thesis"


The following rules reference the 590$a.

  * If the 590a contains "archival materials" then the format will be "Archival Materials"


The following rules reference the 650$a.

  * If the 650a contains "large type" or "large print" then the format will be "LargePrint"
  * If the 650a contains "playaway" then the format will be "Playaway"
  * If the 650a contains "graphic novel" and the 650v does not contain "television adaptation" then the format will be "GraphicNovel"


The following rules reference the 655$a.

  * If the 655a contains "large type" then the format will be "LargePrint"
  * If the 655a contains "playaway" then the format will be "Playaway"
  * If the 655a contains "graphic novel" and the 655v does not contain "television adaptation" then the format will be "GraphicNovel"



  * If the 655 contains "library of things" then the format will be "LibraryOfThings"


The following rules reference the 690$a.

  * If the 690a contains "seed library" then the format will be "SeedPacket"


The following rules reference the 710$a.

  * If the 710a contains "playaway view" then the format will be "PlayawayView"
  * If the 710a contains "playaway digital audio" or "findaway world" then the format will be "Playaway"


The following rules reference the 007.

  * If position 0 = "A" and position 1 = "D" then the format will be "Atlas"
  * If position 0 = "A" and position 1 does not equal "D" then the format will be "Map"
  * If position 0 = "C" and position 1 = "A" then the format will be "TapeCartridge"
  * If position 0 = "C" and position 1 = "B" then the format will be "ChipCartridge"
  * If position 0 = "C" and position 1 = "C" then the format will be "DiscCartridge"
  * If position 0 = "C" and position 1 = "F" then the format will be "TapeCassette"
  * If position 0 = "C" and position 1 = "H" then the format will be "TapeReel"
  * If position 0 = "C" and position 1 = "J" then the format will be "FloppyDisk"
  * If position 0 = "C" and position 1 = "M" or "O" then the format will be "CDROM"
  * If position 0 = "C" and position 1 does not equal "A", "B", "C", "F", "H", "J", "M", "O" then the format will be "Software"
  * If position 0 = "D" then the format will be "Globe"
  * If position 0 = "F" then the format will be "Braille"
  * If position 0 = "G" and position 1 = "C" or "D" then the format will be "Filmstrip"
  * If position 0 = "G" and position 1 = "T" then the format will be "Transparency"
  * If position 0 = "G" and position 1 does not equal "T", "C", or "D" then the format will be "Slide"
  * If position 0 = "H" then the format will be "Microfilm"
  * If position 0 = "K" and position 1 = "C" then the format will be "Collage"
  * If position 0 = "K" and position 1 = "D" or "L" then the format will be "Drawing"
  * If position 0 = "K" and position 1 = "E" then the format will be "Painting"
  * If position 0 = "K" and position 1 = "F" or "J" then the format will be "Print"
  * If position 0 = "K" and position 1 = "G" then the format will be "Photonegative"
  * If position 0 = "K" and position 1 = "O" then the format will be "FlashCard"
  * If position 0 = "K" and position 1 = "N" then the format will be "Chart"
  * If position 0 = "K" and position 1 does not equal "C", "D", "E", "F", "G", "O", or "N" then the format will be "Photo"
  * If position 0 = "M" and position 1 = "F" then the format will be "VideoCassette"
  * If position 0 = "M" and position 1 = "R" then the format will be "Filmstrip"
  * If position 0 = "M" and position 1 does not equal "F" or "R" then the format will be "MotionPicture"
  * If position 0 = "O" then the format will be "Kit"
  * If position 0 = "Q" then the format will be "MusicalScore"
  * If position 0 = "R" then the format will be "SensorImage"
  * If position 0 = "S" and position 1 = "D" AND position 3 is between "A" and "E" then the format will be "Phonograph"
  * If position 0 = "S" and position 1 = "D" AND position 3 = "F" then the format will be "CompactDisc"
  * If position 0 = "S" and position 1 = "D" AND position 3 is between "K" and "R" then the format will be "TapeRecording"
  * If position 0 = "S" and position 1 = "D" AND position 3 is not between "A" and "F" and position 3 is not between "K" and "R" then the format will be "SoundDisc"
  * If position 0 = "S" and position 1 = "S" then the format will be "SoundCassette"
  * If position 0 = "S" and position 1 does not equal "D" or "S" then the format will be "SoundRecording"
  * If position 0 = "T" and position 1 = "A" then the format will be "Book"
  * If position 0 = "T" and position 1 = "B" then the format will be "LargePrint"
  * If position 0 = "V" and position 1 = "C" then the format will be "VideoCartridge"
  * If position 0 = "V" and position 1 = "D" then the format will be "VideoDisc"
  * If position 0 = "V" and position 1 = "F" then the format will be "VideoCassette"
  * If position 0 = "V" and position 1 = "R" then the format will be "VideoReel"
  * If position 0 = "V" and position 1 does not equal "C", "D", "F" or "R" then the format will be "Video"

If Aspen reads from the 007 that the format should be "CompactDisc" and "VideoDisc" then the format will be "CD+DVD"

The following rules reference the leader.

  * If position 6 is "C" or "D" then the format will be "MusicalScore"
  * If position 6 is "E" or "F" then the format will be "Map"
  * If position 6 = "G" then the format will be "Video"
  * If position 6 = "I" then the format will be "SoundRecording"
  * If position 6 = "J" then the format will be "MusicRecording"
  * If position 6 = "K" then the format will be "Photo"
  * If position 6 = "M" then the format will be "Electronic"
  * If position 6 = "O" or "P" then the format will be "Kit"
  * If position 6 = "R" then the format will be "PhysicalObject"
  * If position 6 = "T" then the format will be "Manuscript"
  * If position 7 = "M" then the format will be "Book"
  * If position 7 = "S" and the 008 position 21="N" then the format will be "Newspaper"
  * If position 7 = "S" and the 008 position 21="P" then the format will be "Journal"
  * If position 7 = "S" and the 008 position 21 does not equal "N" or "P" then the format will be "Serial"

If there are no formats found, Aspen defaults to the format 'Book'

Aspen will weight the different formats coming from all of these places and the format with the highest occurence, will bt the format Aspen chooses for the record. 

You can translate the formats and the way they are displayed to the patron in the ILS Integration Indexing Profile. 
