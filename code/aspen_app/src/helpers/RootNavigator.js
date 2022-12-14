import { CommonActions, createNavigationContainerRef } from '@react-navigation/native';

import React from 'react';

export const navigationRef = createNavigationContainerRef();

export const navigate = (name, params) => {
     if (navigationRef.current) {
          navigationRef.current.navigate(name, params);
     }
};

export const navigateStack = (stack, screen, params) => {
     if (navigationRef.current) {
          navigationRef.current.navigate(stack, {
               screen: screen,
               params: params,
          });
     }
};

export const navigateAndSimpleReset = (name, index = 0) => {
     if (navigationRef.isReady()) {
          navigationRef.dispatch(
               CommonActions.reset({
                    index,
                    routes: [{ name }],
               })
          );
     }
};