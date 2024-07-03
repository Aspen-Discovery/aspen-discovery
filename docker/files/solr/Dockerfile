FROM solr:8.11.2
USER root

# Install dependencies
RUN apt-get update \
    && apt-get install -y  \
       sudo \
       vim \
       sed \
    && rm -rf /var/cache/apt/archives/* \
    && rm -rf /var/lib/apt/lists/*

# Set where Solr will look for configsets
ENV SOLR_INSTALL_DIR=/opt/solr
ENV SOLR_HOME=$SOLR_INSTALL_DIR/server/solr

# Copy configset for each core
COPY data_dir_setup/solr7/ ${SOLR_HOME}/configsets
RUN chown -R ${SOLR_USER}:${SOLR_GROUP} ${SOLR_HOME}/configsets

# Change the owner for /var/solr to 'solr'
RUN chown -R solr:solr /var/solr

# Init as solr
USER ${SOLR_USER}

# Init in SolR home
WORKDIR ${SOLR_HOME}
