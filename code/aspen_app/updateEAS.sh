#!/usr/bin/env bash
printf "\n******************************\n"
printf "Starting EAS Updater...\n"
printf "******************************\n"
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

printf "Select release channel to modify:\n"
PS3="> "
channels=("production" "beta" "alpha" "development")
select item in "${channels[@]}"
do
    case $REPLY in
        *) channel=$item; break;;
    esac
done

printf "Branch to assign: "
read -r branch

if [[ $slug == 'all' ]]
then
  readarray -t sites < <(jq -c 'keys' 'app-configs/apps.json' | jq @sh | jq -r)
    declare -a sites
    for site in ${sites[@]}
        do
          eval site=$site
            node /usr/local/aspen-discovery/code/aspen_app/app-configs/copyConfig.js
            node /usr/local/aspen-discovery/code/aspen_app/app-configs/updateConfig.js --instance=$site --env=$channel
            sed -i'.bak' "s/{{APP_ENV}}/$site/g" eas.json
            printf "\nCreating new branch %s... \n" "$branch"
              APP_ENV=$site eas branch:create "$branch"
            printf "\nUpdating %s to point to %s... \n" "$channel" "$branch"
              APP_ENV=$site eas channel:edit "$channel" --branch "$branch"
            node /usr/local/aspen-discovery/code/aspen_app/app-configs/restoreConfig.js --instance=$site
            sed -i'.bak' "s/$site/{{APP_ENV}}/g" eas.json
        done
  else
    node /usr/local/aspen-discovery/code/aspen_app/app-configs/copyConfig.js
    node /usr/local/aspen-discovery/code/aspen_app/app-configs/updateConfig.js --instance=$slug --env=$channel
    sed -i'.bak' "s/{{APP_ENV}}/$slug/g" eas.json

    printf "\nCreating new branch %s... \n" "$branch"
    APP_ENV=$slug eas branch:create "$branch"
    printf "\nUpdating %s to point to %s... \n" "$channel" "$branch"
    APP_ENV=$slug eas channel:edit "$channel" --branch "$branch"

    node /usr/local/aspen-discovery/code/aspen_app/app-configs/restoreConfig.js --instance=$slug
    sed -i'.bak' "s/$slug/{{APP_ENV}}/g" eas.json
  fi



rm -f "eas.json.bak"
printf "******************************\n"
printf " 👌 Finished. Bye! \n"
exit