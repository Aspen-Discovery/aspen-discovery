const fs = require('fs');
const data = require('./apps.json');
const owner = require('./projectOwner.json');
const version = require('./version.json');

function getArgs() {
     const args = {};
     process.argv.slice(2, process.argv.length).forEach((arg) => {
          // long arg
          if (arg.slice(0, 2) === '--') {
               const longArg = arg.split('=');
               const longArgFlag = longArg[0].slice(2, longArg[0].length);
               args[longArgFlag] = longArg.length > 1 ? longArg[1] : true;
          }
          // flags
          else if (arg[0] === '-') {
               const flags = arg.slice(1, arg.length).split('');
               flags.forEach((flag) => {
                    args[flag] = true;
               });
          }
     });
     return args;
}

const args = getArgs();
const instance = args.instance;
const env = args.env;

const app = data[instance];

/* if (env === 'production' || env === 'beta') {
 fs.readFile('app-configs/projectOwner.json', 'utf8', function (err, data) {
 if (err) {
 return console.log(err);
 } else {
 console.log('✅ Found projectOwner.json');
 let json = JSON.stringify(data);
 let curBuildCode = owner['buildCode'];
 curBuildCode = parseInt(curBuildCode);
 curBuildCode = curBuildCode + 1;
 json = json.replace(owner['buildCode'], curBuildCode);
 const obj = JSON.parse(json);
 fs.writeFile('app-configs/projectOwner.json', obj, 'utf8', function (err) {
 if (err) {
 return console.log(err);
 }
 console.log('✅ Updated build number in projectOwner.json');
 });
 }
 });
 } */

fs.readFile('eas.json', 'utf8', function (err, data) {
     if (err) {
          return console.log(err);
     } else {
          console.log('✅ Found eas.json');
          let json = JSON.stringify(data);
          json = json.replace('{{DEV_APP_ID}}', app['ascAppId']);
          json = json.replace('{{DEV_TEAM_ID}}', app['appleTeamId']);
          json = json.replace('{{DEV_APPLE_ID}}', owner['devAppleId']);
          json = json.replace('{{DEV_APPLE_API_KEY_PATH}}', app['ascApiKeyPath']);
          json = json.replace('{{DEV_APPLE_API_KEY_ISSUER_ID}}', app['ascApiKeyIssuerId']);
          json = json.replace('{{DEV_APPLE_API_KEY_ID}}', app['ascApiKeyId']);
          json = json.replace('{{DEV_GOOGLE_SERVICE_KEY_PATH}}', app['googleServiceKeyPath']);
          const obj = JSON.parse(json);
          fs.writeFile('eas.json', obj, 'utf8', function (err) {
               if (err) {
                    return console.log(err);
               }
               console.log('✅ Updated eas.json');
          });
     }
});

let versionAsInt = version['build'];
versionAsInt = parseInt(versionAsInt, 10);

const app_config = {
     name: app['name'],
     slug: app['slug'],
     scheme: app['scheme'],
     owner: owner['expoProjectOwner'],
     privacy: 'public',
     platforms: ['ios', 'android'],
     version: version['version'],
     sdkVersion: '48.0.0',
     orientation: 'default',
     icon: app['discoveryUrl'] + 'API/SystemAPI?method=getLogoFile&themeId=' + app['themeId'] + '&type=appIcon&slug=' + app['slug'],
     updates: {
          enabled: true,
          checkAutomatically: 'ON_LOAD',
          fallbackToCacheTimeout: 250000,
          url: 'https://u.expo.dev/' + app['easId'],
     },
     runtimeVersion: {
          policy: 'sdkVersion',
     },
     splash: {
          image: app['discoveryUrl'] + 'API/SystemAPI?method=getLogoFile&themeId=' + app['themeId'] + '&type=appSplash&slug=' + app['slug'],
          resizeMode: 'contain',
          backgroundColor: app['background'],
     },
     jsEngine: 'hermes',
     assetBundlePatterns: ['**/*'],
     ios: {
          buildNumber: version['build'],
          bundleIdentifier: app['reverseDns'],
          supportsTablet: true,
          icon: app['discoveryUrl'] + 'API/SystemAPI?method=getLogoFile&themeId=' + app['themeId'] + '&type=appIcon&slug=' + app['slug'],
          infoPlist: {
               NSLocationWhenInUseUsageDescription: 'This app uses your location to find nearby libraries to make logging in easier',
               LSApplicationQueriesSchemes: ['comgooglemaps', 'citymapper', 'uber', 'lyft', 'waze', 'aspen-lida', 'aspen-lida-beta'],
               CFBundleAllowMixedLocalizations: true,
          },
          jsEngine: 'jsc'
     },
     android: {
          allowBackup: false,
          package: app['reverseDns'],
          versionCode: versionAsInt,
          permissions: ['ACCESS_COARSE_LOCATION', 'ACCESS_FINE_LOCATION', 'RECEIVE_BOOT_COMPLETED', 'SCHEDULE_EXACT_ALARM'],
          adaptiveIcon: {
               foregroundImage: app['discoveryUrl'] + 'API/SystemAPI?method=getLogoFile&themeId=' + app['themeId'] + '&type=appIcon&slug=' + app['slug'],
               backgroundColor: app['background'],
          },
          icon: app['discoveryUrl'] + 'API/SystemAPI?method=getLogoFile&themeId=' + app['themeId'] + '&type=appIcon&slug=' + app['slug'],
          googleServicesFile: './google-services.json',
     },
     notification: {
          icon: app['discoveryUrl'] + 'API/SystemAPI?method=getLogoFile&themeId=' + app['themeId'] + '&type=appNotification&slug=' + app['slug'],
     },
     extra: {
          apiUrl: app['discoveryUrl'],
          greenhouseUrl: owner['greenhouseUrl'],
          loginLogo: app['discoveryUrl'] + 'API/SystemAPI?method=getLogoFile&themeId=' + app['themeId'] + '&type=appLogin&slug=' + app['slug'],
          libraryCardLogo: app['discoveryUrl'] + 'API/SystemAPI?method=getLogoFile&themeId=' + app['themeId'] + '&type=logoApp&slug=' + app['slug'],
          backgroundColor: app['background'],
          libraryId: app['libraryId'],
          themeId: app['themeId'],
          sentryDSN: app['sentryDsn'],
          eas: {
               projectId: app['easId'],
          },
          iosStoreUrl: 'https://apps.apple.com/us/app/id' + app['ascAppId'],
          androidStoreUrl: 'https://play.google.com/store/apps/details?id=' + app['reverseDns'],
          patch: version['patch']
     },
     hooks: {
          postPublish: [
               {
                    file: 'sentry-expo/upload-sourcemaps',
                    config: {
                         organization: owner['expoProjectOwner'],
                         project: app['sentryProject'],
                         authToken: app['sentryAuth'],
                    },
               },
          ],
     },
     plugins: ['sentry-expo', 'expo-localization']
};

fs.readFile('app.config.js', 'utf8', function (err, data) {
     if (err) {
          return console.log(err);
     } else {
          console.log('✅ Found app.config.js');
          const result = data.replace('{{LOAD_APP_CONFIG}}', JSON.stringify(app_config));
          fs.writeFile('app.config.js', result, 'utf8', function (err) {
               if (err) {
                    return console.log(err);
               }
               console.log('✅ Updated app.config.js');
          });
     }
});