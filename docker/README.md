### 1) Installing Vagrant & Docker-Compose

The first thing will be to install Vagrant, which will be able to manage virtual environments to raise Aspen locally.

Check that it has been installed correctly

```
vagrant --version
```

Install the necessary dependencies

```
sudo apt install libarchive-dev libarchive-tools
```

It will be necessary to create a directory to place the Vagrant startup file

```
mkdir Vagrant
cd Vagrant
```

Then, you will have to use an image of the OS you want to use. For this we will run :

```
vagrant box add generic/ubuntu2204
```

To start a VM we will need a VagrantFile, but this will be created when running :

```
vagrant init generic/ubuntu2204
```

In the VagrantFile it will be necessary to configure ports and, probably, RAM memory to allocate to the new VM. Recommended configurations can be :

```
Vagrant.configure("2") do |config|  
 config.vm.network "forwarded_port", guest: 80, host: 8080
 config.vm.network "forwarded_port", guest: 22, host: 2222

 config.vm.provider "virtualbox" do |vb|
   vb.memory = 6144
 end
end
```

Then, from the folder where the VagrantFile is located and, in order to start Vagrant, we use :

```
vagrant up
```

Once Vagrant is up, we must connect to the virtual machine :

```
ssh vagrant@localhost -p2222
```

In order to have the necessary permissions we must be root user

```
sudo su
```

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
mkdir -p /srv/INSTANCE_NAME_WITHOUT_WHITESPACES/mariadb_data && chmod -R 777 /srv/INSTANCE_NAME_WITHOUT_WHITESPACES/
```

### 3) Build a new stack

Create a file docker-compose.yml with these settings, if you're working with traefik (this case) or any other reverse-proxy you should probably need to uncomment deploy label on backend service.

```
version: '3.7'
networks:
  net-aspen:
  traefik-public:
    external: true
services:

  backend:
    image: $COMPOSE_ImagePath:$COMPOSE_ImageVersion
    networks:
      - net-aspen
      - traefik-public
    deploy:
      labels:
        - "traefik.enable=true"
        - "traefik.constraint-label=traefik-public"
        - "traefik.http.routers.aladi-demo-aspen-http.rule=Host(`$SITE_sitename`)"
        - "traefik.http.routers.aladi-demo-aspen-http.entrypoints=http"
        - "traefik.http.routers.aladi-demo-aspen-http.middlewares=https-redirect"
        - "traefik.http.routers.aladi-demo-aspen.rule=Host(`$SITE_sitename`)"
        - "traefik.http.routers.aladi-demo-aspen.entrypoints=https"
        - "traefik.http.routers.aladi-demo-aspen.tls.certresolver=le"
        - "traefik.http.services.aladi-demo-aspen.loadbalancer.server.port=80"
        - "traefik.http.middlewares.aladi-demo-aspen.headers.accesscontrolalloworiginlist=*"
        - "traefik.http.middlewares.aladi-demo-aspen_compress.compress=true"
        - "traefik.http.routers.aladi-demo-aspen.middlewares=aladi-demo-aspen_compress"
    tty: true
    volumes:
      - $COMPOSE_ClientSrvPath:/mnt   
    environment:
      - SITE_sitename=$SITE_sitename
      - SITE_operatingSystem=$SITE_operatingSystem
      - SITE_library=$SITE_library
      - SITE_title=$SITE_title
      - SITE_url=$SITE_url
      - SITE_siteOnWindows=$SITE_siteOnWindows
      - SITE_solrHost=$SITE_solrHost
      - SITE_solrPort=$SITE_solrPort
      - SITE_ils=$SITE_ils
      - SITE_timezone=$SITE_timezone
      - ASPEN_DBHost=$ASPEN_DBHost
      - ASPEN_DBPort=$ASPEN_DBPort
      - ASPEN_DBName=$ASPEN_DBName
      - ASPEN_DBUser=$ASPEN_DBUser
      - ASPEN_DBPwd=$ASPEN_DBPwd
      - ASPEN_aspenAdminPwd=$ASPEN_aspenAdminPwd
      - ILS_ilsDriver=$ILS_ilsDriver
      - ILS_ilsUrl=$ILS_ilsUrl
      - ILS_staffUrl=$ILS_staffUrl
      - KOHA_DBHost=$KOHA_DBHost
      - KOHA_DBName=$KOHA_DBName
      - KOHA_DBUser=$KOHA_DBUser
      - KOHA_DBPwd=$KOHA_DBPwd
      - KOHA_DBPort=$KOHA_DBPort
      - KOHA_timezone=$KOHA_timezone
      - KOHA_ClientId=$KOHA_ClientId
      - KOHA_ClientSecret=$KOHA_ClientSecret
      - COMPOSE_ImagePath=$COMPOSE_ImagePath
      - COMPOSE_ImageVersion=$COMPOSE_ImageVersion
      - COMPOSE_ClientSrvPath=$COMPOSE_ClientSrvPath
      - COMPOSE_DBRoot=$COMPOSE_DBRoot
      - COMPOSE_Cron=$COMPOSE_Cron
      - COMPOSE_Apache=$COMPOSE_Apache
      - COMPOSE_Solr=off
      - COMPOSE_Dirs=$COMPOSE_Dirs
      - COMPOSE_RootPwd=$COMPOSE_RootPwd
    #entrypoint: ["/usr/bin/tail","-f","/dev/null"]
  db:
    image: mariadb:10.3
    environment:
      - MARIADB_ROOT_PASSWORD=$COMPOSE_RootPwd
    deploy:
      endpoint_mode: dnsrr
    volumes:
      - $COMPOSE_ClientSrvPath/mariadb_data:/var/lib/mysql
    networks:
      - net-aspen

  solr:
    image: $COMPOSE_ImagePath:$COMPOSE_ImageVersion
    environment:
      - SITE_sitename=$SITE_sitename
      - SITE_operatingSystem=$SITE_operatingSystem
      - SITE_library=$SITE_library
      - SITE_title=$SITE_title
      - SITE_url=$SITE_url
      - SITE_siteOnWindows=$SITE_siteOnWindows
      - SITE_solrHost=$SITE_solrHost
      - SITE_solrPort=$SITE_solrPort
      - SITE_ils=$SITE_ils
      - SITE_timezone=$SITE_timezone
      - ASPEN_DBHost=$ASPEN_DBHost
      - ASPEN_DBPort=$ASPEN_DBPort
      - ASPEN_DBName=$ASPEN_DBName
      - ASPEN_DBUser=$ASPEN_DBUser
      - ASPEN_DBPwd=$ASPEN_DBPwd
      - ASPEN_aspenAdminPwd=$ASPEN_aspenAdminPwd
      - ILS_ilsDriver=$ILS_ilsDriver
      - ILS_ilsUrl=$ILS_ilsUrl
      - ILS_staffUrl=$ILS_staffUrl
      - KOHA_DBHost=$KOHA_DBHost
      - KOHA_DBName=$KOHA_DBName
      - KOHA_DBUser=$KOHA_DBUser
      - KOHA_DBPwd=$KOHA_DBPwd
      - KOHA_DBPort=$KOHA_DBPort
      - KOHA_timezone=$KOHA_timezone
      - KOHA_ClientId=$KOHA_ClientId
      - KOHA_ClientSecret=$KOHA_ClientSecret
      - COMPOSE_ImagePath=$COMPOSE_ImagePath
      - COMPOSE_ImageVersion=$COMPOSE_ImageVersion
      - COMPOSE_ClientSrvPath=$COMPOSE_ClientSrvPath
      - COMPOSE_DBRoot=$COMPOSE_DBRoot
      - COMPOSE_Cron=off
      - COMPOSE_Apache=off
      - COMPOSE_Solr=on
      - COMPOSE_Dirs=$COMPOSE_Dirs
      - COMPOSE_RootPwd=$COMPOSE_RootPwd
    deploy:
      endpoint_mode: dnsrr
    volumes:
      - $COMPOSE_ClientSrvPath:/mnt
    networks:
      - net-aspen
    depends_on:
      - backend
      
  tunnel:
    image: $COMPOSE_ImagePath:$COMPOSE_ImageVersion 
    environment:
      - TUNNEL_LocalPort=$TUNNEL_LocalPort
      - TUNNEL_RemotePort=$TUNNEL_RemotePort
      - TUNNEL_RemoteHost=$TUNNEL_RemoteHost
      - TUNNEL_JumpServer=$TUNNEL_JumpServer
      - COMPOSE_Cron=off
      - COMPOSE_Apache=off
      - COMPOSE_Solr=off
    deploy:
      endpoint_mode: dnsrr
    volumes:
      - $COMPOSE_ClientSrvPath:/mnt
    networks:
      - net-aspen
    entrypoint: ["/root/.ssh/tunnel.sh"]
    #entrypoint: ["/usr/bin/tail","-f","/dev/null"]
   
  backup:
    image: registry.gitlab.com/thekesolutions/tools/aspen-backup:23.6.15.0
    networks:
      - net-aspen
    depends_on:
      - backend
    volumes: 
        - $COMPOSE_ClientSrvPath/start.sh:/start.sh
        - $COMPOSE_ClientSrvPath/sites-enabled:/etc/apache2/sites-enabled
        - $COMPOSE_ClientSrvPath/sites-available:/etc/apache2/sites-available
        - $COMPOSE_ClientSrvPath/cron.d_bk/backup:/etc/crontabs/root
        - $COMPOSE_ClientSrvPath/log:/var/log/aspen-discovery/$SITE_sitename
        - $COMPOSE_ClientSrvPath/aladi.aspen.theke.io:/usr/local/aspen-discovery/sites/$SITE_sitename
        - $COMPOSE_ClientSrvPath/data:/data/aspen-discovery
        - $COMPOSE_ClientSrvPath/home:/home
        - $COMPOSE_ClientSrvPath/files:/usr/local/aspen-discovery/code/web/files
    environment:
      - BACKUP_Folder=$BACKUP_Folder daily
      - BACKUP_AccountId=$BACKUP_AccountId 002afbbf0a5586f000000002e
      - BACKUP_ApplicationKey=$BACKUP_ApplicationKey K002OKpLTz0LeJJ4i4WH6vi7YKm4m1o
      - BACKUP_Bucket=$BACKUP_Bucket b2://theke-backup-2/ALADI.ASPEN.THEKE.IO
      - BACKUP_UserDB=$BACKUP_UserDB root
      - BACKUP_PassDB=$BACKUP_PassDB root
      - BACKUP_DB=$BACKUP_DB aspen_aladi
      - BACKUP_Sitename=$BACKUP_Sitename aladi.aspen.theke.io
      - BACKUP_HostDB=$BACKUP_HostDB testaspen
    entrypoint: ["crond", "-d", "8", "-f"]
```

An .env file must be created where the 'environment variables' that will be used later by the docker-compose.yml will be stored.

The variables to use are :

NOTE : These variables need to be setted with all data corresponded to your instance.

* Site :

```
SITE_sitename=aladi-demo.aspen.theke.io   <-- Example
SITE_operatingSystem=debian
SITE_library=Biblioteca de la ALADI
SITE_title=Biblioteca de la ALADI
SITE_url=https://aladi-demo.aspen.theke.io
SITE_siteOnWindows=n
SITE_solrHost=solr
SITE_solrPort=8080
SITE_ils=Koha
SITE_timezone=America/Argentina/Cordoba
```

* Aspen :

```
ASPEN_DBHost=db           <-- Example
ASPEN_DBPort=3306
ASPEN_DBName=aspen
ASPEN_DBUser=aspen
ASPEN_DBPwd=aspenpwd
ASPEN_aspenAdminPwd=supersecreta
```

* Ils :

```
ILS_ilsDriver=Koha              <-- Example
ILS_ilsUrl=aladi.koha.theke.io
ILS_staffUrl=aladi-admin.koha.theke.io
```

* Koha :

```
KOHA_DBHost=tunnel              <-- Example
KOHA_DBName=koha_aladi
KOHA_DBUser=koha_aladi
KOHA_DBPwd=pass
KOHA_DBPort=3306
KOHA_timezone=America/Argentina/Cordoba
KOHA_ClientId=
KOHA_ClientSecret=
```

* Compose :

```
COMPOSE_ImagePath=registry.gitlab.com/thekesolutions/aspen/base <-- Expl
COMPOSE_ImageVersion=23.09.00.03
COMPOSE_ClientSrvPath=/srv/aladidemoaspen
COMPOSE_ClientDockerPath=/mnt
COMPOSE_DBRoot=root
COMPOSE_RootPwd=root
COMPOSE_Apache=on
COMPOSE_Cron=on
COMPOSE_Solr=on
COMPOSE_Dirs=/etc/apache2/sites-enabled /etc/apache2/sites-available /etc/cron.d /etc/php/8.0/apache2 /usr/local/aspen-discovery/sites/aladi-demo.aspen.theke.io /usr/local/aspen-discovery/code/web/files /usr/local/aspen-discovery/code/web/images /data /home /var/log/aspen-discovery /var/lib/mysql /etc/mysql /etc/mysql/mariadb.conf.d
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
  TUNNEL_JumpServer=dev.koha.theke.io
  ```

### 4) Initialize services

We go to the directory where the docker-compose.yml is located and execute :

```
docker-compose up
```

### 5) Check Aspen instance is up

On the browser :

```
INSTANCE_NAME:8080
```