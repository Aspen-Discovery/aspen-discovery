import { ScrollView } from '@gluestack-ui/themed';
import React from 'react';

import { LanguageContext, LibrarySystemContext, ThemeContext } from '../../../context/initialContext';
import { CalendarPermissionStatus } from './Permission/Calendar';
import { CameraPermissionStatus } from './Permission/Camera';
import { GeolocationPermissionStatus } from './Permission/Geolocation';
import { NotificationPermissionStatus } from './Permission/Notifications';
import { ScreenBrightnessPermissionStatus } from './Permission/ScreenBrightness';
export const PermissionsDashboard = () => {
     const { language } = React.useContext(LanguageContext);
     const { catalogStatus, catalogStatusMessage } = React.useContext(LibrarySystemContext);
     const { theme, textColor, colorMode } = React.useContext(ThemeContext);

     return (
          <ScrollView p="$5">
               <CameraPermissionStatus />
               <CalendarPermissionStatus />
               <GeolocationPermissionStatus />
               <NotificationPermissionStatus />
               <ScreenBrightnessPermissionStatus />
          </ScrollView>
     );
};