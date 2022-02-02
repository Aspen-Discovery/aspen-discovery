> You'll need to have developer accounts for Apple App Store and Google Play created and ready to use before completing these steps.

#Create a new branded instance
In command line, run `/app-configs/makeNew.sh` to make a new app.json file. This houses all the configuration for the new app.

---
#Launching a branded instance
```
cd /usr/local/aspen-discovery/code/aspen_app
APP_ENV={app} expo start
```
**{app}** = the same filename keyword used when making the new json file, i.e. If `app.lida.json`, then you would run `APP_ENV=lida expo start`

---
#Updating the App Stores
For updating open testing or TestFlight instances, use the **beta** release channel, otherwise **production** channel.
##Update version and build numbers
In command line, run `/app-configs/bumpVersions.php -r[major/minor/patch]` to increment the version and build number. **This will modify ALL the .json files in /app-configs/**

##Over-the-air updates
For hot fixes, you can use the over-the-air update module from Expo to bypass needing to re-build and wait for the app stores.
```
APP_ENV={app} expo publish --release-channel beta/production
```

##Starting the build
1. Launch each app you want to build updates for in an Expo instance
2. Run the Expo build commands and follow the prompts to start the builds. It'll usually take 15-30 minutes for Expo to build each app.
###Android
```
APP_ENV={app} expo build:android --release-channel beta/production
```
1. Choose the `app-bundle` build type
2. If it's the first build for the app, you'll be prompted to upload or create a keystore. Choose option 1 to allow Expo to handle it.
   - After the build has started, come back and run `expo fetch:android:keystore` to back-up and store the keystore in a safe location.

###iOS
```
APP_ENV={app} expo build:ios --release-channel beta/production
```
1. Choose the `Archive` build type