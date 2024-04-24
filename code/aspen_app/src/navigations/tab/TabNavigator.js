import { Ionicons } from '@expo/vector-icons';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { DrawerActions } from '@react-navigation/native';
import { HStack, Pressable, Text, useColorModeValue, useToken, VStack } from 'native-base';
import React from 'react';
import { Platform } from 'react-native';
import { LanguageContext, LibraryBranchContext } from '../../context/initialContext';
import { getTermFromDictionary } from '../../translations/TranslationService';

import DrawerNavigator from '../drawer/DrawerNavigator';
import AccountStackNavigator from '../stack/AccountStackNavigator';
import BrowseStackNavigator from '../stack/BrowseStackNavigator';
import LibraryCardStackNavigator from '../stack/LibraryCardStackNavigator';
import MoreStackNavigator from '../stack/MoreStackNavigator';
import SelfCheckOutStackNavigator from '../stack/SelfCheckOutStackNavigator';

export default function TabNavigator() {
     const Tab = createBottomTabNavigator();

     const { enableSelfCheck } = React.useContext(LibraryBranchContext);
     const [activeIcon, inactiveIcon] = useToken('colors', [useColorModeValue('gray.800', 'coolGray.200'), useColorModeValue('gray.500', 'coolGray.400')]);
     const tabBarBackgroundColor = useColorModeValue('light', 'dark');

     return (
          <Tab.Navigator
               tabBar={(props) => <TabItem {...props} />}
               initialRouteName="BrowseTab"
               screenOptions={({ route }) => ({
                    headerShown: false,
                    backBehavior: 'none',
                    tabBarHideOnKeyboard: true,
                    tabBarActiveTintColor: activeIcon,
                    tabBarInactiveTintColor: inactiveIcon,
                    tabBarLabelStyle: {
                         fontWeight: '400',
                    },
                    tabBarStyle: {
                         backgroundColor: tabBarBackgroundColor,
                         elevation: 0,
                    },
               })}>
               <Tab.Screen
                    name="BrowseTab"
                    component={BrowseStackNavigator}
                    options={
                         {
                              //tabBarLabel: browseTabLabel,
                         }
                    }
                    screenOptions={{
                         headerShown: false,
                    }}
               />
               <Tab.Screen
                    name="LibraryCardTab"
                    component={LibraryCardStackNavigator}
                    options={
                         {
                              //tabBarLabel: cardTabLabel,
                         }
                    }
               />
               {enableSelfCheck ? (
                    <Tab.Screen
                         name="SelfCheckTab"
                         component={SelfCheckOutStackNavigator}
                         options={
                              {
                                   //tabBarLabel: selfCheckTabLabel,
                              }
                         }
                    />
               ) : null}
               <Tab.Screen
                    name="AccountTab"
                    component={DrawerNavigator}
                    options={
                         {
                              //tabBarLabel: accountTabLabel,
                              //tabBarBadge: 3,
                         }
                    }
                    listeners={({ navigation }) => ({
                         tabPress: (e) => {
                              navigation.dispatch(DrawerActions.toggleDrawer());
                              e.preventDefault();
                         },
                    })}
               />
               <Tab.Screen
                    name="AccountScreenTab"
                    component={AccountStackNavigator}
                    options={{
                         tabBarButton: () => null,
                    }}
               />
               <Tab.Screen
                    name="MoreTab"
                    component={MoreStackNavigator}
                    options={
                         {
                              //tabBarLabel: moreTabLabel,
                         }
                    }
               />
          </Tab.Navigator>
     );
}

export const TabItem = ({ state, descriptors, navigation }) => {
     const { language } = React.useContext(LanguageContext);
     const [activeIcon, inactiveIcon] = useToken('colors', [useColorModeValue('gray.800', 'coolGray.200'), useColorModeValue('gray.500', 'coolGray.400')]);
     const tabBarBackgroundColor = useColorModeValue('light', 'dark');

     const [browseTabLabel, setBrowseTabLabel] = React.useState(getTermFromDictionary(language, 'nav_discover'));
     const [cardTabLabel, setCardTabLabel] = React.useState(getTermFromDictionary(language, 'nav_card'));
     const [accountTabLabel, setAccountTabLabel] = React.useState(getTermFromDictionary(language, 'nav_account'));
     const [scoTabLabel, setScoTabLabel] = React.useState(getTermFromDictionary(language, 'nav_sco'));
     const [moreTabLabel, setMoreTabLabel] = React.useState(getTermFromDictionary(language, 'nav_more'));

     React.useEffect(() => {
          setTimeout(() => {
               setBrowseTabLabel(getTermFromDictionary(language, 'nav_discover'));
               setCardTabLabel(getTermFromDictionary(language, 'nav_card'));
               setAccountTabLabel(getTermFromDictionary(language, 'nav_account'));
               setScoTabLabel(getTermFromDictionary(language, 'nav_sco'));
               setMoreTabLabel(getTermFromDictionary(language, 'nav_more'));
          }, 1500);
     }, [language]);

     let bottomPadding = 7;
     if (Platform.OS === 'android') {
          bottomPadding = 3;
     }

     return (
          <HStack safeAreaLeft={7} safeAreaRight={7} safeAreaTop={2} safeAreaBottom={bottomPadding} space={4} alignItems="center" justifyContent="space-between" bgColor={tabBarBackgroundColor} borderTopWidth="1" _dark={{ borderColor: 'coolGray.200' }} borderColor="gray.200">
               {state.routes.map((route, index) => {
                    const { options } = descriptors[route.key];
                    //let label = options.tabBarLabel !== undefined ? options.tabBarLabel : options.title !== undefined ? options.title : route.name;
                    const isFocused = state.index === index;

                    let iconName;
                    let dictionaryKey;
                    if (route.name === 'BrowseTab') {
                         iconName = isFocused ? 'library' : 'library-outline';
                         dictionaryKey = browseTabLabel;
                    } else if (route.name === 'LibraryCardTab') {
                         iconName = isFocused ? 'card' : 'card-outline';
                         dictionaryKey = cardTabLabel;
                    } else if (route.name === 'AccountTab') {
                         iconName = isFocused ? 'person' : 'person-outline';
                         dictionaryKey = accountTabLabel;
                    } else if (route.name === 'MoreTab') {
                         iconName = isFocused ? 'ellipsis-horizontal' : 'ellipsis-horizontal-outline';
                         dictionaryKey = moreTabLabel;
                    } else if (route.name === 'SelfCheckTab') {
                         iconName = isFocused ? 'barcode' : 'barcode-outline';
                         dictionaryKey = scoTabLabel;
                    }

                    let color = inactiveIcon;
                    if (isFocused) {
                         color = activeIcon;
                    }
                    const onPress = () => {
                         const event = navigation.emit({
                              type: 'tabPress',
                              target: route.key,
                              canPreventDefault: true,
                         });

                         if (!isFocused && !event.defaultPrevented) {
                              navigation.navigate(route.name, route.params);
                         }
                    };

                    const onLongPress = () => {
                         navigation.emit({
                              type: 'tabLongPress',
                              target: route.key,
                         });
                    };

                    if (route.name === 'AccountScreenTab') {
                         return null;
                    }

                    return (
                         <Pressable key={index} accessibilityRole="button" accessibilityState={isFocused ? { selected: true } : {}} accessibilityLabel={options.tabBarAccessibilityLabel} testID={options.tabBarTestID} onPress={onPress} onLongPress={onLongPress}>
                              <VStack space={1} alignItems="center">
                                   <Ionicons name={iconName} size={22} color={color} />
                                   <Text fontSize={9} color={color} fontWeight="400">
                                        {dictionaryKey}
                                   </Text>
                              </VStack>
                         </Pressable>
                    );
               })}
          </HStack>
     );
};