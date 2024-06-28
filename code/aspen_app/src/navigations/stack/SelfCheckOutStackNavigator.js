import { createNativeStackNavigator } from '@react-navigation/native-stack';
import React from 'react';

import { LanguageContext, UserContext } from '../../context/initialContext';
import { getTermFromDictionary } from '../../translations/TranslationService';
import { StartCheckOutSession } from '../../screens/SCO/StartCheckOutSession';
import { SelfCheckOut } from '../../screens/SCO/SelfCheckOut';
import { FinishCheckOutSession } from '../../screens/SCO/FinishSelfCheckoutSession';
import _ from 'lodash';
import SelfCheckScanner from '../../screens/SCO/SelfCheckScanner';

const SelfCheckOutStackNavigator = () => {
     const { language } = React.useContext(LanguageContext);
     const { accounts } = React.useContext(UserContext);

     let defaultRoute = 'SelfCheckOut';
     if (_.size(accounts) >= 1) {
          defaultRoute = 'StartCheckoutSession';
     }

     const Stack = createNativeStackNavigator();
     return (
          <Stack.Navigator
               initialRouteName={defaultRoute}
               screenOptions={{
                    headerShown: true,
                    headerBackTitleVisible: false,
                    gestureEnabled: false,
               }}>
               <Stack.Screen name="StartCheckOutSession" component={StartCheckOutSession} options={{ title: getTermFromDictionary(language, 'self_checkout') }} initialParams={{ startNew: true }} />
               <Stack.Screen name="SelfCheckOut" component={SelfCheckOut} options={{ title: getTermFromDictionary(language, 'self_checkout') }} initialParams={{ startNew: true }} />
               <Stack.Screen
                    name="SelfCheckOutScanner"
                    component={SelfCheckScanner}
                    options={{
                         presentation: 'modal',
                         title: 'Scanner',
                    }}
               />
               <Stack.Screen name="FinishCheckOutSession" component={FinishCheckOutSession} options={{ title: getTermFromDictionary(language, 'finish_checkout_session') }} />
          </Stack.Navigator>
     );
};

export default SelfCheckOutStackNavigator;