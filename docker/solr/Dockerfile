FROM solr:8.11.2
USER root
#Install dependencies
RUN apt-get update \
    && apt-get install -y  \
    sudo \
    vim \
    sed \
    && rm -rf /var/lib/apt/lists/*

COPY data_dir_setup/solr7/ /opt/solr/server/solr/configsets

RUN chown -R solr:solr /opt/solr/server/solr/configsets/
USER solr
