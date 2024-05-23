(README UNDER CONSTRUCTION)
### 0) Introduction

### 1) Install Docker & Docker-Compose

Only these components are necessary before proceeding with the installation of Aspen:

* Docker ([install instructions](https://docs.docker.com/engine/install/))
* Docker Compose ([install instructions](https://docs.docker.com/compose/install/#install-compose-on-linux-systems))

### 2) Prepare host

This example deployment will persist the data on a directory structure inside
the directory pointed by `$ASPEN_DATA_DIR`. You will need to adjust it for a production
deployment.

```
ASPEN_INSTANCE=nameOfYourInstance
echo "export ASPEN_INSTANCE" >> ~/.bashrc
echo "export ASPEN_DATA_DIR=~/aspen-repos/${ASPEN_INSTANCE}" >> ~/.bashrc
source ~/.bashrc
# now create the dirs
mkdir -p ${ASPEN_DATA_DIR}/database \
         ${ASPEN_DATA_DIR}/solr \
         ${ASPEN_DATA_DIR}/conf \
         ${ASPEN_DATA_DIR}/data \
         ${ASPEN_DATA_DIR}/logs
curl -O ${ASPEN_DATA_DIR}/docker-compose.yml https://raw.githubusercontent.com/Aspen-Discovery/aspen-discovery/24.06.00/docker/docker-compose.yml
```

### 3.0) Copy env file and set environment variables (mandatory)

With any text editor, the user must set the values for each variable in the **env** file.

```
cp aspen-discovery/docker/.env .
vim .env
```

Example :

```
#About Aspen itself
SITE_NAME=test.localhost
LIBRARY=TEST LIBRARY
TITLE=TEST LIBRARY
URL=http://test.localhost
SOLR_HOST=solr
SOLR_PORT=8985
ASPEN_ADMIN_PASSWORD=secretPass123
ENABLE_APACHE=yes
ENABLE_CRON=yes
CONFIG_DIRECTORY=/aspen

#About Aspen database
DATABASE_HOST=db
DATABASE_PORT=3306
DATABASE_NAME=aspen
DATABASE_USER=aspenusr
DATABASE_PASSWORD=aspenpwd
DATABASE_ROOT_USER=root
DATABASE_ROOT_PASSWORD=root
TIMEZONE=America/Argentina/Cordoba

#About Koha integration ("yes" to enable)
ENABLE_KOHA=yes

KOHA_OPAC_URL=
KOHA_STAFF_URL=
KOHA_DATABASE_HOST=
KOHA_DATABASE_NAME=
KOHA_DATABASE_USER=
KOHA_DATABASE_PASSWORD=
KOHA_DATABASE_PORT=
KOHA_DATABASE_TIMEZONE=
KOHA_CLIENT_ID=
KOHA_CLIENT_SECRET=

#About other ils (it would be set just if ENABLE_KOHA is not)
ILS_DRIVER=

#About compose images
BACKEND_IMAGE_TAG=backendimage
SOLR_IMAGE_TAG=solrimage
TUNNEL_IMAGE_TAG=tunnelimage

#About tunnel service
TUNNEL_LOCAL_PORT=3306
TUNNEL_REMOTE_HOST=127.0.0.1
TUNNEL_REMOTE_PORT=3306
TUNNEL_JUMP_SERVER=test.koha.theke.io
```

### 3.1)

* Backup :

```
#About backup service (backblaze)
BACKUP_FOLDER=
BACKUP_ACCOUNT_ID=
BACKUP_APPLICATION_KEY=
BACKUP_BUCKET=
BACKUP_DATABASE_USER=
BACKUP_DATABASE_PASSWORD=
BACKUP_DATABASE_NAME=
BACKUP_SITENAME=
BACKUP_DATABASE_HOST=
```

NOTE : These variables response to a backup service called "BackBlaze". The user needs to search for appropriate setted variables if another backup service is being used.
  
### 4) Create and start containers

We go to the directory where the docker-compose.yml is located and execute :

```
docker-compose -p aspen up -d
```

You need to wait until "Aspen is ready to use!" message is displayed
(Check docker logs -f aspen_backend )
### 5) Check Aspen instance is up

On the browser :

```
$URL:80
```
