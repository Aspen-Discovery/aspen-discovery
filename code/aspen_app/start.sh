#!/usr/bin/env bash
printf "\n******************************\n"
printf "Starting Aspen LiDA Launcher...\n"
printf "******************************\n"
printf "Select an instance to start:\n"
readarray -t instances < <(jq -c 'keys' 'app-configs/apps.json' | jq -r '.[]')
declare -a instances
PS3="> "
select item in "${instances[@]}"
do
  eval item=$item
    case $REPLY in
        *) site=$item; break;;
    esac
done

printf "Expo server mode:\n"
PS3="> "
serverOptions=("standard" "development" "production")
select item in "${serverOptions[@]}"
do
    case $REPLY in
        *) serverOption=$item; break;;
    esac
done
node /usr/local/aspen-discovery/code/aspen_app/app-configs/copyConfig.js
node /usr/local/aspen-discovery/code/aspen_app/app-configs/updateConfig.js --instance=$site --env=none
sed -i'.bak' "s/{{APP_ENV}}/$site/g" eas.json
if [[ $serverOption == 'development' ]]
then
  APP_ENV=$site npx expo start --dev-client
elif [[ $serverOption == 'production' ]]
then
  APP_ENV=$site npx expo start --no-dev --minify
else
    APP_ENV=$site npx expo start --clear
  fi
node /usr/local/aspen-discovery/code/aspen_app/app-configs/restoreConfig.js --instance=$site --env=none
sed -i'.bak' "s/$site/{{APP_ENV}}/g" eas.json
rm -f "eas.json.bak"