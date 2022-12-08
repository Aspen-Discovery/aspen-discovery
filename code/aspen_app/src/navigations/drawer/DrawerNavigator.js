import { createDrawerNavigator } from '@react-navigation/drawer';
import { useToken, useColorModeValue } from 'native-base';
import React from 'react';
import TabNavigator from '../tab/TabNavigator';
import { DrawerContent } from './DrawerContent';

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
                    lazy: false,
                    drawerStyle: {
                         backgroundColor: screenBackgroundColor,
                    },
               }}
               drawerContent={(props) => <DrawerContent {...props} />}>
               <Drawer.Screen
                    name="Tabs"
                    component={TabNavigator}
                    screenOptions={{
                         headerShown: false,
                         lazy: false,
                    }}
                    options={({ props }) => ({
                         params: { ...props },
                    })}
               />
          </Drawer.Navigator>
     );
};

export default AccountDrawer;