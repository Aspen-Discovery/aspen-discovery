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
ASPEN_Instance=nameOfYourInstance
export ASPEN_Repo=~/aspen-repos/${ASPEN_Instance}
mkdir -p ${ASPEN_Repo}/mariadb_data ${ASPEN_Repo}/solr_data
cd ${ASPEN_Repo}
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
* Site :

```
SITE_sitename=test.localhost   
SITE_operatingSystem=debian
SITE_library=Test Library
SITE_title=Test Library
SITE_url=http://test.localhost
SITE_siteOnWindows=n
SITE_solrHost=solr
SITE_solrPort=8080
SITE_ils=Koha
SITE_timezone=America/Argentina/Cordoba
```

* Aspen :

```
ASPEN_DBHost=db           
ASPEN_DBPort=3306
ASPEN_DBName=aspen
ASPEN_DBUser=aspen
ASPEN_DBPwd=password
ASPEN_aspenAdminPwd=password
```

* Ils :

```
ILS_ilsDriver=Koha              
ILS_ilsUrl=test.koha.theke.io
ILS_staffUrl=test-admin.koha.theke.io
```

* Koha :

```
KOHA_DBHost=tunnel              
KOHA_DBName=koha_dbname
KOHA_DBUser=koha_dbuser
KOHA_DBPwd=password
KOHA_DBPort=3306
KOHA_timezone=America/Argentina/Cordoba
KOHA_ClientId=
KOHA_ClientSecret=
```

* Compose :

```
COMPOSE_ImageVersion=24.01.00
COMPOSE_DBRoot=root
COMPOSE_RootPwd=root
COMPOSE_Apache=on
COMPOSE_Cron=on
COMPOSE_Dirs=/etc/apache2/sites-enabled /etc/apache2/sites-available /etc/cron.d /etc/php/8.0/apache2 /usr/local/aspen-discovery/sites/dev.aspen.theke.io /usr/local/aspen-discovery/code/web/files /usr/local/aspen-discovery/code/web/images /data /home /var/log/aspen-discovery
```
Observation: COMPOSE_Dirs variable saves all DIRECTORIES ( it doesn't support path files) inside Aspen that users want to be persistant on host server, like images, footers, covers, php settings and all data that shouldn't lost if you want to reset your containers.

* Tunnel :

```
TUNNEL_LocalPort=3306      
TUNNEL_RemotePort=3306
TUNNEL_RemoteHost=127.0.0.1
TUNNEL_JumpServer=test.koha.theke.io
```

### 3.1)
* Backup :

```
BACKUP_Folder=          
BACKUP_AccountId=
BACKUP_ApplicationKey=
BACKUP_Bucket=
BACKUP_UserDB=
BACKUP_PassDB=
BACKUP_DB=
BACKUP_Sitename=
BACKUP_HostDB=
  ```
Observation : These variables response to a backup service called "BackBlaze". The user needs to search for appropriate setted variables if another backup service is being used.

  
### 4) Create and start containers

We go to the directory where the docker-compose.yml is located and execute :

```
docker-compose up -d
```

### 5) Check Aspen instance is up

On the browser :

```
SITE_sitename:80
```
