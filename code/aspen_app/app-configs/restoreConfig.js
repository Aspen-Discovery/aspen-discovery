const fs = require('fs');
const data = require('./apps.json');
const owner = require('./projectOwner.json');

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
const app = data[instance];

fs.readFile('ORIGINAL_app.config.js', 'utf8', function (err, data) {
     if (err) {
          return console.log(err);
     } else {
          console.log('✅ Found original app.config.js');
          fs.rename('ORIGINAL_app.config.js', 'app.config.js', () => {
               if (err) {
                    return console.log(err);
               } else {
                    console.log('✅ Restored original app.config.js');
               }
          });
     }
});

fs.readFile('eas.json', 'utf8', function (err, data) {
     if (err) {
          return console.log(err);
     } else {
          console.log('✅ Found eas.json');
          let json = JSON.stringify(data);
          const ascAppId = app['ascAppId'];
          const appleTeamId = app['appleTeamId'];
          const devAppleId = owner['devAppleId'];
          const appleKeyPath = app['ascApiKeyPath'];
          const appleKeyIssuer = app['ascApiKeyIssuerId'];
          const appleKeyId = app['ascApiKeyId'];
          const googleKeyPath = app['googleServiceKeyPath'];
          json = json.replace(ascAppId, '{{DEV_APP_ID}}');
          json = json.replace(appleTeamId, '{{DEV_TEAM_ID}}');
          json = json.replace(devAppleId, '{{DEV_APPLE_ID}}');
          json = json.replace(appleKeyPath, '{{DEV_APPLE_API_KEY_PATH}}');
          json = json.replace(appleKeyIssuer, '{{DEV_APPLE_API_KEY_ISSUER_ID}}');
          json = json.replace(appleKeyId, '{{DEV_APPLE_API_KEY_ID}}');
          json = json.replace(googleKeyPath, '{{DEV_GOOGLE_SERVICE_KEY_PATH}}');
          const obj = JSON.parse(json);
          fs.writeFile('eas.json', obj, 'utf8', function (err) {
               if (err) {
                    return console.log(err);
               }
               console.log('✅ Restored original eas.json');
          });
     }
});