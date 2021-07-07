Aspen Mobile Application

Uses expo.io to build https://expo.io/

To setup for development

1) Install Node.js - see: https://nodejs.org/en/download/
2) Navigate to this directory in a command line
3) Setup install Expo CLI - see: https://docs.expo.io/get-started/installation/
4) Register and login to Expo - expo login 
5) Install dependencies with the following commands
expo install @react-native-async-storage/async-storage
expo install @react-navigation/native
expo install react-navigation
expo install react-native-gesture-handler react-native-reanimated react-native-screens react-native-safe-area-context @react-native-community/masked-view
expo install react-navigation-stack
expo install react-navigation-tabs
expo install react-native-vector-icons
expo install react-native-picker-select
expo install react-native-modal-selector-searchable
expo install @react-native-picker/picker
expo install react-native-shapes
expo install react-native-barcode-expo
expo install react-native-elements
expo install react-native-view-more-text
expo install react-native-screens
npm i https://github.com/peacechen/react-native-modal-selector --save
npm audit fix

6) Run expo start (or npm start), running expo start -c will clear cache
7) To run a specific device, can use npm run android, npm run ios, or npm run web

Optional
- Install Android Studio - https://developer.android.com/studio
