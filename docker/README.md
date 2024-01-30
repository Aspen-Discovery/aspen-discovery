(README UNDER CONSTRUCTION)
### 1) Installing Docker-Compose

Install docker :

```
   sudo apt update
   sudo apt install apt-transport-https ca-certificates curl software-properties-common
   curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg
   echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
   sudo apt update
   sudo apt install docker-ce
   systemctl enable docker
   systemctl status docker
```

### 2) Preparing host

It will be necessary to create the directory where Aspen will be installed and those directories which will be used for all services, otherwise Docker will rise an error

Also, the necessary permissions must be given in a recursive way.

```
export ASPEN_Instance=
mkdir -p ~/${ASPEN_Instance}/mariadb_data
mkdir -p ~/${ASPEN_Instance}/solr_data
```

### 3) Build a new stack

Create a file docker-compose.yml with these settings, if you're working with traefik (this case) or any other reverse-proxy you should probably need to uncomment deploy label on backend service.


An .env file must be created where the 'environment variables' that will be used later by the docker-compose.yml will be stored.

The variables to use are :

NOTE : These variables need to be setted with all data corresponded to your instance.

* Site :

```
SITE_sitename=test.localhost   <-- Example
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
ASPEN_Instance=testaspen
ASPEN_DBHost=db           <-- Example
ASPEN_DBPort=3306
ASPEN_DBName=aspen
ASPEN_DBUser=aspen
ASPEN_DBPwd=password
ASPEN_aspenAdminPwd=password
```

* Ils :

```
ILS_ilsDriver=Koha              <-- Example
ILS_ilsUrl=test.koha.theke.io
ILS_staffUrl=test-admin.koha.theke.io
```

* Koha :

```
KOHA_DBHost=tunnel              <-- Example
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

* Backup :

  ```
  BACKUP_Folder          <-- Example
  BACKUP_AccountId
  BACKUP_ApplicationKey
  BACKUP_Bucket
  BACKUP_UserDB
  BACKUP_PassDB
  BACKUP_DB
  BACKUP_Sitename
  BACKUP_HostDB
  
  
  ```

Observation : These variables response to a backup service called "BackBlaze". The user needs to search for appropriate setted variables if another backup service is being used.

* Tunnel :

  ```
  TUNNEL_LocalPort=3306      <-- Example
  TUNNEL_RemotePort=3306
  TUNNEL_RemoteHost=127.0.0.1
  TUNNEL_JumpServer=test.koha.theke.io
  ```

### 4) Initialize services

We go to the directory where the docker-compose.yml is located and execute :

```
docker-compose up
```

### 5) Check Aspen instance is up

On the browser :

```
SITE_sitename:80
```
