import { Badge, BadgeText, Box, HStack, Pressable, Text, VStack } from '@gluestack-ui/themed';
import { Image } from 'expo-image';
import * as WebBrowser from 'expo-web-browser';
import _ from 'lodash';
import moment from 'moment';
import React from 'react';
import { popToast } from '../../components/loadError';

// custom components and helper files
import { LanguageContext, LibrarySystemContext, ThemeContext } from '../../context/initialContext';
import { getCleanTitle } from '../../helpers/item';
import { navigate } from '../../helpers/RootNavigator';
import { getTermFromDictionary } from '../../translations/TranslationService';
import AddToList from './AddToList';

const blurhash = 'MHPZ}tt7*0WC5S-;ayWBofj[K5RjM{ofM_';

export const DisplayEventResult = (props) => {
     const item = props.data;
     const { library } = React.useContext(LibrarySystemContext);
     const { language } = React.useContext(LanguageContext);
     const { theme, textColor, colorMode } = React.useContext(ThemeContext);

     const backgroundColor = colorMode === 'light' ? theme['colors']['warmGray']['200'] : theme['colors']['coolGray']['900'];

     const currentSource = item.type ?? 'unknown';
     const id = item.key ?? item.id;
     const key = 'medium_' + id;
     const url = item.image;

     let registrationRequired = false;
     if (!_.isUndefined(item.registration_required)) {
          registrationRequired = item.registration_required;
     }

     const startTime = item.start_date.date;
     const endTime = item.end_date.date;

     let time1 = startTime.split(' ');
     let day = time1[0];
     let time2 = endTime.split(' ');

     let time1arr = time1[1].split(':');
     let time2arr = time2[1].split(':');

     let displayDay = moment(day);
     let displayStartTime = moment().set({ hour: time1arr[0], minute: time1arr[1] });
     let displayEndTime = moment().set({ hour: time2arr[0], minute: time2arr[1] });

     displayDay = moment(displayDay).format('dddd, MMMM D, YYYY');
     displayStartTime = moment(displayStartTime).format('h:mm A');
     displayEndTime = moment(displayEndTime).format('h:mm A');

     let locationData = item?.location ?? [];
     let roomData = item?.room ?? null;

     const handlePressItem = () => {
          let eventSource = item.source;
          if (item.source === 'lc') {
               eventSource = 'library_calendar';
          }
          if (item.source === 'libcal' || item.source === 'springshare_libcal') {
               eventSource = 'springshare';
          }

          if (item.source === 'assabet') {
               eventSource = 'assabet';
          }

          if (item.bypass) {
               openURL(item.url);
          } else {
               navigate('EventScreen', {
                    id: id,
                    title: getCleanTitle(item.title),
                    url: library.baseUrl,
                    source: eventSource,
               });
          }
     };

     const openURL = async (url) => {
          const browserParams = {
               enableDefaultShareMenuItem: false,
               presentationStyle: 'automatic',
               showTitle: false,
               toolbarColor: backgroundColor,
               controlsColor: textColor,
               secondaryToolbarColor: backgroundColor,
          };
          await WebBrowser.openBrowserAsync(url, browserParams)
               .then((res) => {
                    console.log(res);
                    if (res.type === 'cancel' || res.type === 'dismiss') {
                         console.log('User closed or dismissed window.');
                         WebBrowser.dismissBrowser();
                         WebBrowser.coolDownAsync();
                    }
               })
               .catch(async (err) => {
                    if (err.message === 'Another WebBrowser is already being presented.') {
                         try {
                              WebBrowser.dismissBrowser();
                              WebBrowser.coolDownAsync();
                              await WebBrowser.openBrowserAsync(url, browserParams)
                                   .then((response) => {
                                        console.log(response);
                                        if (response.type === 'cancel') {
                                             console.log('User closed window.');
                                        }
                                   })
                                   .catch(async (error) => {
                                        console.log('Unable to close previous browser session.');
                                   });
                         } catch (error) {
                              console.log('Really borked.');
                         }
                    } else {
                         popToast(getTermFromDictionary('en', 'error_no_open_resource'), getTermFromDictionary('en', 'error_device_block_browser'), 'error');
                         console.log(err);
                    }
               });
     };

     return (
          <Pressable borderBottomWidth={1} borderColor={colorMode === 'light' ? theme['colors']['warmGray']['400'] : theme['colors']['gray']['600']} pl="$4" pr="$5" py="$2" onPress={handlePressItem}>
               <HStack space="md">
                    <VStack sx={{ '@base': { width: 100 }, '@lg': { width: 180 } }}>
                         <Box sx={{ '@base': { height: 150 }, '@lg': { height: 250 } }}>
                              <Image
                                   alt={item.title}
                                   source={url}
                                   style={{
                                        width: '100%',
                                        height: '100%',
                                        borderRadius: 4,
                                   }}
                                   placeholder={blurhash}
                                   transition={1000}
                                   contentFit="cover"
                              />
                         </Box>
                         {item.canAddToList ? <AddToList source="Events" itemId={item.key} btnStyle="sm" /> : null}
                    </VStack>
                    <VStack w="65%" pt="$1">
                         <Text color={textColor} bold sx={{ '@base': { fontSize: 14, lineHeight: 17, paddingBottom: 4 }, '@lg': { fontSize: 22, lineHeight: 25, paddingBottom: 4 } }}>
                              {item.title}
                         </Text>
                         {item.start_date && item.end_date ? (
                              <>
                                   <Text color={textColor} sx={{ '@base': { fontSize: 12, lineHeight: 15 }, '@lg': { fontSize: 18, lineHeight: 21 } }}>
                                        {displayDay}
                                   </Text>
                                   <Text color={textColor} sx={{ '@base': { fontSize: 12, lineHeight: 15 }, '@lg': { fontSize: 18, lineHeight: 21 } }}>
                                        {displayStartTime} - {displayEndTime}
                                   </Text>
                              </>
                         ) : null}
                         {locationData.name ? (
                              <Text color={textColor} sx={{ '@base': { fontSize: 12, lineHeight: 15 }, '@lg': { fontSize: 18, lineHeight: 21 } }}>
                                   {locationData.name}
                              </Text>
                         ) : null}
                         {registrationRequired ? (
                              <HStack mt="$4" direction="row" space="xs" flexWrap="wrap">
                                   <Badge key={0} borderRadius="$sm" borderColor={theme['colors']['secondary']['400']} variant="outline" bg="transparent">
                                        <BadgeText textTransform="none" color={theme['colors']['secondary']['400']} sx={{ '@base': { fontSize: 10, lineHeight: 14 }, '@lg': { fontSize: 16, lineHeight: 20 } }}>
                                             {getTermFromDictionary(language, 'registration_required')}
                                        </BadgeText>
                                   </Badge>
                              </HStack>
                         ) : null}
                    </VStack>
               </HStack>
          </Pressable>
     );
};