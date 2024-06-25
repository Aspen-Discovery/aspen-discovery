import React from 'react';
import { Box, ScrollView } from '@gluestack-ui/themed';
import { LanguageContext, LibrarySystemContext, ThemeContext } from '../../../context/initialContext';
import { useWindowDimensions } from 'react-native';
import RenderHtml from 'react-native-render-html';
import { decodeHTML } from '../../../util/apiAuth';

export const PalaceProjectInstructions = () => {
     const { library } = React.useContext(LibrarySystemContext);
     const { textColor } = React.useContext(ThemeContext);
     const { width } = useWindowDimensions();

     let instructions = decodeHTML(
          '&lt;p&gt;The Palace Project has a wide variety of eBooks and eAudioBooks for use on your mobile device. &lt;/p&gt;&lt;p&gt;Palace Project titles can be read or listened using the Palace Project app which is available for both iOS and Android devices. At this time, the Palace Project app is not available on Kindle, PC, Linux, or other devices.&lt;/p&gt;&lt;p&gt;To use the Palace Project, follow these simple steps: &lt;/p&gt;&lt;ol&gt;&lt;li&gt;Download the App on your device  &lt;/li&gt;&lt;/ol&gt;&lt;p&gt;&lt;a href=&quot;https://apps.apple.com/us/app/the-palace-project/id1574359693#?platform=iphone&quot;&gt;&lt;img src=&quot;/images/app_store_badge.png&quot; alt=&quot;Download on the App Store&quot; class=&quot;img-responsive&quot; /&gt;&lt;/a&gt;&lt;/p&gt;&lt;p&gt;&lt;a href=&quot;https://play.google.com/store/apps/details?id=org.thepalaceproject.palace&amp;amp;pli=1&quot;&gt;&lt;img src=&quot;/images/google-play-badge.png&quot; alt=&quot;Get it on Google Play&quot; class=&quot;img-responsive&quot; /&gt;&lt;/a&gt;&lt;/p&gt;&lt;ol start=&quot;2&quot;&gt;&lt;li&gt;&lt;p&gt;Select your library&lt;br /&gt;After downloading the app, select &ldquo;Find Your Library,&rdquo; and choose your library from the alphabetized selection.&lt;/p&gt;&lt;/li&gt;&lt;li&gt;&lt;p&gt;Sign in&lt;br /&gt;Select the settings icon in the lower-right hand corner of the screen, then enter your LIBRARY CARD NUMBER and LIBRARY PASSWORD and select LOG IN.&lt;/p&gt;&lt;/li&gt;&lt;/ol&gt;&lt;p&gt;For more information, visit &lt;a href=&quot;https://thepalaceproject.org/information-for-library-patrons/&quot;&gt;the Palace Project website&lt;/a&gt;. &lt;/p&gt;'
     );

     if (library.palaceProjectInstructions) {
          instructions = decodeHTML(library.palaceProjectInstructions);
     }

     const source = {
          baseUrl: library.baseUrl,
          html: instructions,
     };

     const tagsStyles = {
          body: {
               color: textColor,
          },
          a: {
               color: textColor,
               textDecorationColor: textColor,
          },
     };

     return (
          <ScrollView>
               <Box p="$5">
                    <RenderHtml contentWidth={width} source={source} tagsStyles={tagsStyles} />
               </Box>
          </ScrollView>
     );
};