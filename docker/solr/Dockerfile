FROM solr:8.11.2
USER root

#Install dependencies
RUN apt-get update \
    && apt-get install -y  \
    sudo \
    vim \
    sed \
    && rm -rf /var/lib/apt/lists/*

#Set environment variables for Solr
ENV SOLR_INSTALL_DIR=/opt/solr
ENV SOLR_LOGS_DIR=/var/solr/logs
ENV SOLR_DATA_DIR=/var/solr/data
ENV SOLR_USER=solr
#Set where Solr will look for configsets
ENV SOLR_HOME=$SOLR_INSTALL_DIR/server/solr
RUN usermod -d $SOLR_HOME ${SOLR_USER}

#Copy configset for each core
COPY data_dir_setup/solr7/ ${SOLR_HOME}/configsets
RUN chown -R ${SOLR_USER}:${SOLR_GROUP} ${SOLR_HOME}/configsets
 
#Do to solr owner of /var/solr to let this user write on it
RUN chown -R solr:solr /var/solr

#Init as solr
USER ${SOLR_USER}

#Init in Solr home
WORKDIR ${SOLR_HOME}


