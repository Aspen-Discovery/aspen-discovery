import { createDrawerNavigator } from '@react-navigation/drawer';
import { useToken, useColorModeValue } from 'native-base';
import React from 'react';
import TabNavigator from '../tab/TabNavigator';
import { DrawerContent } from './DrawerContent';
import { LibrarySystemContext, UserContext } from '../../context/initialContext';

const Drawer = createDrawerNavigator();

const AccountDrawer = () => {
     const screenBackgroundColor = useToken('colors', useColorModeValue('warmGray.50', 'coolGray.800'));
     return (
          <Drawer.Navigator
               initialRouteName="Tabs"
               screenOptions={{
                    drawerType: 'front',
                    drawerHideStatusBarOnOpen: true,
                    drawerPosition: 'left',
                    headerShown: false,
                    backBehavior: 'none',
                    lazy: true,
                    drawerStyle: {
                         backgroundColor: screenBackgroundColor,
                    },
               }}
               drawerContent={(props) => <DrawerContent {...props} userContext={React.useContext(UserContext)} libraryContext={JSON.stringify(React.useContext(LibrarySystemContext))} />}>
               <Drawer.Screen
                    name="Tabs"
                    component={TabNavigator}
                    screenOptions={{
                         headerShown: false,
                         lazy: true,
                    }}
                    options={({ props }) => ({
                         params: { ...props },
                    })}
               />
          </Drawer.Navigator>
     );
};

export default AccountDrawer;