### 1) Install Docker & Docker-Compose

Only these components are necessary before proceeding with the installation of Aspen:

* Docker ([install instructions](https://docs.docker.com/engine/install/))
* Docker Compose ([install instructions](https://docs.docker.com/compose/install/#install-compose-on-linux-systems))

### 2) Prepare host

This example deployment will persist the data on a directory structure inside
the directory pointed by `$ASPEN_DATA_DIR`. You will need to adjust it for a production
deployment.

```
echo "export ASPEN_INSTANCE=aspen" >> ~/.bashrc
echo "export ASPEN_DATA_DIR=~/aspen-repos/${ASPEN_INSTANCE}" >> ~/.bashrc
source ~/.bashrc
```
#### 2.1) Create the directories

```
mkdir -p ${ASPEN_DATA_DIR}/database \
         ${ASPEN_DATA_DIR}/conf \
         ${ASPEN_DATA_DIR}/data \
         ${ASPEN_DATA_DIR}/logs
```

### 3.0) Copy docker-compose.yml and .env

```
curl -O ${ASPEN_DATA_DIR}/docker-compose.yml https://raw.githubusercontent.com/Aspen-Discovery/aspen-discovery/24.08.00/docker/docker-compose.yml
curl -O ${ASPEN_DATA_DIR}/.env https://raw.githubusercontent.com/Aspen-Discovery/aspen-discovery/24.08.00/docker/env/default.env
```
 
### 4) Create and start containers

We go to the directory where the docker-compose.yml is located and execute :

```
docker compose -p aspen up -d
```

You need to wait until "Aspen is ready to use!" message is displayed
(Check 'docker logs -f aspen_backend' )
### 5) Check Aspen instance is up

On the browser :

```
$URL:80
```
