import { createNativeStackNavigator } from '@react-navigation/native-stack';
import React from 'react';

import { LanguageContext, UserContext } from '../../context/initialContext';
import { getTermFromDictionary } from '../../translations/TranslationService';
import { StartCheckOutSession } from '../../screens/SCO/StartCheckOutSession';
import { SelfCheckOut } from '../../screens/SCO/SelfCheckOut';
import Scanner from '../../components/Scanner';
import { FinishCheckOutSession } from '../../screens/SCO/FinishSelfCheckoutSession';
import _ from 'lodash';

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
               }}>
               <Stack.Screen name="StartCheckOutSession" component={StartCheckOutSession} options={{ title: getTermFromDictionary(language, 'self_checkout') }} />
               <Stack.Screen name="SelfCheckOut" component={SelfCheckOut} options={{ title: getTermFromDictionary(language, 'self_checkout') }} />
               <Stack.Screen
                    name="SelfCheckOutScanner"
                    component={Scanner}
                    options={{
                         gestureEnabled: false,
                         presentation: 'modal',
                    }}
               />
               <Stack.Screen name="FinishCheckOutSession" component={FinishCheckOutSession} options={{ title: getTermFromDictionary(language, 'finish_checkout_session') }} />
          </Stack.Navigator>
     );
};

export default SelfCheckOutStackNavigator;