#!/bin/bash

echo Name for app.______.json file:
read fileName
fullFileName="app.$fileName.json"
cp app.template.json $fullFileName
sed -i'.bak' "s/bundleId/$fileName/g" $fullFileName

echo Name of new app, as it appears in the stores:
read appName
sed -i'.bak' "s/appName/$appName/g" $fullFileName

echo Organization or individual owner of Expo project:
read expoOwner
sed -i'.bak' "s/expoOwner/$expoOwner/g" $fullFileName

echo Reverse DNS of domain for bundle id:
read reverseDNS
sed -i'.bak' "s/reverseDNS/$reverseDNS/g" $fullFileName

echo Slug name of the app, must match value in Aspen App Settings:
read appSlug
sed -i'.bak' "s/appSlug/$appSlug/g" $fullFileName

echo URL of the library to connect to:
read libraryUrl
sed -i'.bak' "s,libraryUrl,$libraryUrl,g" $fullFileName

echo Id of library to inherit app settings and theme from:
read libraryId
sed -i'.bak' "s/libraryId/$libraryId/g" $fullFileName

echo Background color for splash screen:
read color
sed -i'.bak' "s/bgColor/$color/g" $fullFileName

## mac bash requires a bak file, so lets remove it when we're done creating the new config
rm -f "app.$fileName.json.bak"