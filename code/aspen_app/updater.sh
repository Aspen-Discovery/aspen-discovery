#!/usr/bin/env bash
printf "\n******************************\n"
printf "Starting Aspen LiDA Updater...\n"
printf "******************************\n"
printf "Select release channel:\n"
PS3="> "
channels=("production" "beta" "alpha" "development")
select item in "${channels[@]}"
do
    case $REPLY in
        *) channel=$item; break;;
    esac
done

readarray -t instances < <(jq -c 'keys' 'app-configs/apps.json' | jq -r '.[]')
declare -a instances
printf "Select instance:\n"
PS3="> "
select item in "${instances[@]}" all
do
  eval item=$item
    case $REPLY in
        *) slug=$item; break;;
    esac
done


printf "Over-the-air update?\n"
PS3="> "
otaOptions=("yes" "no")
select item in "${otaOptions[@]}"
do
    case $REPLY in
        *) otaUpdate=$item; break;;
    esac
done

if [[ $otaUpdate == 'yes' ]]
then
  printf "\nBranch to send over-the-air update to: "
  read -r branchName
  printf "\nComment about the update: "
  read -r comment
fi

printf "Select platform(s):\n"
PS3="> "
platforms=("ios" "android" "all")
select item in "${platforms[@]}"
do
    case $REPLY in
        *) osPlatform=$item; break;;
    esac
done

printf "******************************\n"
if [[ $slug == 'all' ]]
then
  readarray -t sites < <(jq -c 'keys' 'app-configs/apps.json' | jq @sh | jq -r)
  declare -a sites
  for site in ${sites[@]}
      do
        eval site=$site
         printf "\nUpdating %s in channel %s for %s platform(s)... \n" "$site" "$channel" "$osPlatform"
          node /usr/local/aspen-discovery/code/aspen_app/app-configs/copyConfig.js
          node /usr/local/aspen-discovery/code/aspen_app/app-configs/updateConfig.js --instance=$site --env=$channel
          sed -i'.bak' "s/{{APP_ENV}}/$site/g" eas.json
          if [[ $otaUpdate == 'yes' ]]
          then
            APP_ENV=$site eas update --branch $branchName --message "$comment" --platform $osPlatform
          else
            APP_ENV=$site eas build --platform $osPlatform --profile $channel --no-wait
          fi
          node /usr/local/aspen-discovery/code/aspen_app/app-configs/restoreConfig.js --instance=$site
          sed -i'.bak' "s/$site/{{APP_ENV}}/g" eas.json
      done
else
  printf "\nUpdating %s in channel %s for %s platform(s)... \n" "$slug" "$channel" "$osPlatform"
  node /usr/local/aspen-discovery/code/aspen_app/app-configs/copyConfig.js
  node /usr/local/aspen-discovery/code/aspen_app/app-configs/updateConfig.js --instance=$slug --env=$channel
  sed -i'.bak' "s/{{APP_ENV}}/$slug/g" eas.json
  if [[ $otaUpdate == 'yes' ]]
  then
    APP_ENV=$site eas update --branch $branchName --message "$comment" --platform $osPlatform
  else
    #APP_ENV=$slug eas build --profile development --platform ios
    APP_ENV=$slug npx expo prebuild
    APP_ENV=$slug eas build --platform $osPlatform --profile $channel --no-wait
  fi
  node /usr/local/aspen-discovery/code/aspen_app/app-configs/restoreConfig.js --instance=$slug
  sed -i'.bak' "s/$slug/{{APP_ENV}}/g" eas.json
fi

rm -f "eas.json.bak"
printf "******************************\n"
printf " 👌 Finished. Bye! \n"
exit