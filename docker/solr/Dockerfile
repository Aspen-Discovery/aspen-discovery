FROM solr:7.6.0-slim
EXPOSE 8983
USER root
RUN apt-get update \
    && apt-get install -y sudo \
    && rm -rf /var/lib/apt/lists/*
COPY aspen-discovery/data_dir_setup/solr7/ /opt/solr/server/solr/configsets
RUN chown -R 8983:8983 /opt/solr/server/solr/configsets/ 
USER solr
