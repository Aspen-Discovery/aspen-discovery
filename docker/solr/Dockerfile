FROM solr:7.6.0-slim
USER root
#Install dependencies
RUN apt-get update \
    && apt-get install -y  \
    sudo \
    vim \
    sed \
    && rm -rf /var/lib/apt/lists/*

#Copy necessary cores
COPY aspen-discovery/data_dir_setup/solr7/ /opt/solr/server/solr/configsets

RUN chown -R solr:solr /opt/solr/server/solr/configsets/
USER solr
