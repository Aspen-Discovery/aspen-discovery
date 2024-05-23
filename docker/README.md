(README UNDER CONSTRUCTION)
### 0) Introduction

### 1) Install Docker & Docker-Compose

Only these components are necessary before proceeding with the installation of Aspen:

* Docker ([install instructions](https://docs.docker.com/engine/install/))
* Docker Compose ([install instructions](https://docs.docker.com/compose/install/#install-compose-on-linux-systems))

### 2) Prepare host

It will be necessary to create: 
* The directory where Aspen containers are going to run.
* Those directories which will be used for the persistence of data.

```
ASPEN_INSTANCE=nameOfYourInstance
echo "export ASPEN_INSTANCE" >> ~/.bashrc
echo "export ASPEN_REPO=~/aspen-repos/${ASPEN_INSTANCE}" >> ~/.bashrc
source ~/.bashrc

mkdir -p ${ASPEN_REPO}/database ${ASPEN_REPO}/solr ${ASPEN_REPO}/conf ${ASPEN_REPO}/data ${ASPEN_REPO}/logs
cd ${ASPEN_REPO}
git clone https://github.com/mdnoble73/aspen-discovery.git
cp aspen-discovery/docker/docker-compose.yml .
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
Observation : These variables response to a backup service called "BackBlaze". The user needs to search for appropriate setted variables if another backup service is being used.

  
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
