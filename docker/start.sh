#!/bin/bash

if [ ! -d /mnt/_usr_local_aspen-discovery_sites_${SITE_sitename} ] && [ $COMPOSE_Apache == "on" ] ; then
	#Esta es la primera ejecucion
	if [ $ASPEN_DBHost == "localhost" ]; then
		#Start MySQL
		service mysql start
	fi

	#Espero que mysql esté funcionando
	while ! nc -z $ASPEN_DBHost $ASPEN_DBPort; do sleep 3; done

	if [ ! -z "$COMPOSE_RootPwd" ] || [ $ASPEN_DBHost == "localhost" ]; then
		#Doy permisos a $DBUSER sobre $ASPEN_DBName
		mysql -u$COMPOSE_DBRoot -p$COMPOSE_RootPwd -h$ASPEN_DBHost -P$ASPEN_DBPort -e "create user '$ASPEN_DBUser'@'%' identified by '$ASPEN_DBPwd'; grant all on $ASPEN_DBName.* to '$ASPEN_DBUser'@'%'; flush privileges;"
	fi

	#fix hardcode strings
	sed -i "s/host=localhost/host={\$variables['aspenDBHost']}/g" /usr/local/aspen-discovery/install/createSite.php

  #Preparar el create site template
  cd /usr/local/aspen-discovery/install

  crudini --set createSiteTemplateVars.ini  Site sitename \$SITE_sitename
  crudini --set createSiteTemplateVars.ini  Site operatingSystem \$SITE_operatingSystem
  crudini --set createSiteTemplateVars.ini  Site library \$SITE_library
  crudini --set createSiteTemplateVars.ini  Site title \$SITE_title
  crudini --set createSiteTemplateVars.ini  Site url \$SITE_url
  crudini --set createSiteTemplateVars.ini  Site siteOnWindows \$SITE_siteOnWindows
  crudini --set createSiteTemplateVars.ini  Site solrHost \$SITE_solrHost
  crudini --set createSiteTemplateVars.ini  Site solrPort \$SITE_solrPort
  crudini --set createSiteTemplateVars.ini  Site ils \$SITE_ils
  crudini --set createSiteTemplateVars.ini  Site timezone \$SITE_timezone

  crudini --set createSiteTemplateVars.ini  Aspen DBHost \$ASPEN_DBHost
  crudini --set createSiteTemplateVars.ini  Aspen DBPort \$ASPEN_DBPort
  crudini --set createSiteTemplateVars.ini  Aspen DBName \$ASPEN_DBName
  crudini --set createSiteTemplateVars.ini  Aspen DBUser \$ASPEN_DBUser
  crudini --set createSiteTemplateVars.ini  Aspen DBPwd \$ASPEN_DBPwd
  crudini --set createSiteTemplateVars.ini  Aspen aspenAdminPwd \$ASPEN_aspenAdminPwd

  crudini --set createSiteTemplateVars.ini  ILS ilsDriver \$ILS_ilsDriver
  crudini --set createSiteTemplateVars.ini  ILS ilsUrl \$ILS_ilsUrl
  crudini --set createSiteTemplateVars.ini  ILS staffUrl \$ILS_staffUrl

  crudini --set createSiteTemplateVars.ini  Koha DBHost \$KOHA_DBHost
  crudini --set createSiteTemplateVars.ini  Koha DBName \$KOHA_DBName
  crudini --set createSiteTemplateVars.ini  Koha DBUser \$KOHA_DBUser
  crudini --set createSiteTemplateVars.ini  Koha DBPwd \$KOHA_DBPwd
  crudini --set createSiteTemplateVars.ini  Koha DBPort \$KOHA_DBPort
  crudini --set createSiteTemplateVars.ini  Koha DBTimezone \$KOHA_Timezone
  crudini --set createSiteTemplateVars.ini  Koha ClientId \$KOHA_ClientId
  crudini --set createSiteTemplateVars.ini  Koha ClientSecret \$KOHA_ClientSecret
  envsubst < createSiteTemplateVars.ini > createSiteTemplate.ini

	#Genero el sitio nuevo
	php createSite.php createSiteTemplate.ini

	#Elimino sitio por defecto de apache
	unlink /etc/apache2/sites-enabled/000-default.conf
	unlink /etc/apache2/sites-enabled/httpd-$SITE_sitename.conf
	cp /etc/apache2/sites-available/httpd-$SITE_sitename.conf  /etc/apache2/sites-enabled/httpd-$SITE_sitename.conf

	#Cambio prioridad para ingreso de aspen
	mysql -u$ASPEN_DBUser -p$ASPEN_DBPwd -h$ASPEN_DBHost -P$ASPEN_DBPort $ASPEN_DBName -e "update account_profiles set weight=0 where name='admin'; update account_profiles set weight=1 where name='ils';"

	if [ $ASPEN_DBHost == "localhost" ]; then
		service mysql stop
	fi
	
 	rsync -avl /usr/local/aspen-discovery/data_dir_setup/solr7/ /data/aspen-discovery/$SITE_sitename/solr7

	#Copio los datos a un volumen persistente
	for i in ${COMPOSE_Dirs[@]}; do
		dir=$(echo $i | sed 's/\//_/g'); 
		rsync -al $i/ /mnt/$dir; 
	done


fi

#Hago link simbolicos de los volumenes persistentes
for i in ${COMPOSE_Dirs[@]}; do
	dir=$(echo $i | sed 's/\//_/g'); 
	mv $i $i-back; 
	ln -s /mnt/$dir $i; 
done

#Si la base de datos es local, arranco el mysql
if [ $ASPEN_DBHost == "localhost" ]; then
	service mysql start
fi

#Espero que mysql esté funcionando ya sea local o remoto
while ! nc -z $ASPEN_DBHost $ASPEN_DBPort; do sleep 3; done

#Arranque de Apache
if [ $COMPOSE_Apache == "on" ]; then
	mkdir -p /var/log/aspen-discovery/$SITE_sitename
	service apache2 start 
fi

#Arranque de Cron
if [ $COMPOSE_Cron == "on" ]; then
	service cron start 
	php /usr/local/aspen-discovery/code/web/cron/checkBackgroundProcesses.php $SITE_sitename &
fi

#Asigno 'owner' correcto sobre distintos directorios dentro de Aspen
chown -R www-data:aspen_apache /usr/local/aspen-discovery/code/web
chown -R www-data:aspen /data/aspen-discovery/$SITE_sitename/

#Doy permisos de lectura y escritura a Aspen para subir imagenes
chmod -R 777 /usr/local/aspen-discovery/code/web
chmod -R 777 /data/aspen-discovery/$SITE_sitename/

#Espera infinita
/bin/bash -c "trap : TERM INT; sleep infinity & wait"

