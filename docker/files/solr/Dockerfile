FROM solr:9.10.1

# Build arguments with defaults
ARG SOLR_HOST=localhost
ARG SOLR_PORT=8983

USER root

# Install dependencies with error handling and cleanup
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
       sudo \
       vim \
       sed; \
    apt-get clean; \
    rm -rf /var/lib/apt/lists/* /var/cache/apt/archives/*

# Set environment variables
ENV SOLR_INSTALL_DIR=/opt/solr
ENV SOLR_HOME=/var/solr/data
ENV SOLR_HOST=$SOLR_HOST
ENV SOLR_PORT=$SOLR_PORT
ENV SOLR_OPTS="-Dsolr.config.lib.enabled=true" \
    SERIES_CONFIGSET_PATH=/opt/solr/server/solr/configsets/series/conf \
    GENEALOGY_CONFIGSET_PATH=/opt/solr/server/solr/configsets/genealogy/conf \
    GROUPED_WORK_V2_CONFIGSET_PATH=/opt/solr/server/solr/configsets/grouped_works_v2/conf \
    GROUPED_WORK_V3_CONFIGSET_PATH=/opt/solr/server/solr/configsets/grouped_works_v3/conf \
    LISTS_CONFIGSET_PATH=/opt/solr/server/solr/configsets/lists/conf \
    COURSE_RESERVES_CONFIGSET_PATH=/opt/solr/server/solr/configsets/course_reserves/conf \
    OPEN_ARCHIVES_CONFIGSET_PATH=/opt/solr/server/solr/configsets/open_archives/conf \
    WEBSITE_PAGES_CONFIGSET_PATH=/opt/solr/server/solr/configsets/website_pages/conf \
    EVENTS_CONFIGSET_PATH=/opt/solr/server/solr/configsets/events/conf

# Create configset directories
RUN mkdir -p $SERIES_CONFIGSET_PATH && \
    mkdir -p $GENEALOGY_CONFIGSET_PATH && \
    mkdir -p $GROUPED_WORK_V2_CONFIGSET_PATH && \
    mkdir -p $GROUPED_WORK_V3_CONFIGSET_PATH && \
    mkdir -p $LISTS_CONFIGSET_PATH && \
    mkdir -p $COURSE_RESERVES_CONFIGSET_PATH && \
    mkdir -p $OPEN_ARCHIVES_CONFIGSET_PATH && \
    mkdir -p $WEBSITE_PAGES_CONFIGSET_PATH && \
    mkdir -p $EVENTS_CONFIGSET_PATH

# Copy core configuration to configsets
COPY data_dir_setup/solr7/series/conf ${SERIES_CONFIGSET_PATH}
COPY data_dir_setup/solr7/genealogy/conf ${GENEALOGY_CONFIGSET_PATH}
COPY data_dir_setup/solr7/grouped_works_v2/conf ${GROUPED_WORK_V2_CONFIGSET_PATH}
COPY data_dir_setup/solr7/grouped_works_v3/conf ${GROUPED_WORK_V3_CONFIGSET_PATH}
COPY data_dir_setup/solr7/lists/conf ${LISTS_CONFIGSET_PATH}
COPY data_dir_setup/solr7/course_reserves/conf ${COURSE_RESERVES_CONFIGSET_PATH}
COPY data_dir_setup/solr7/open_archives/conf ${OPEN_ARCHIVES_CONFIGSET_PATH}
COPY data_dir_setup/solr7/website_pages/conf ${WEBSITE_PAGES_CONFIGSET_PATH}
COPY data_dir_setup/solr7/events/conf ${EVENTS_CONFIGSET_PATH}

# Copy solr.xml to SOLR_HOME
COPY data_dir_setup/solr7/solr.xml ${SOLR_HOME}/solr.xml

# Solr 9 moved ICU/analysis-extras (used by the text_general field type,
# e.g. ICUFoldingFilterFactory) out of core into an optional module. Copy it
# into SOLR_HOME/lib, which Solr auto-loads for every core, so schemas that
# reference it keep working without per-core solrconfig.xml <lib> entries.
RUN mkdir -p ${SOLR_HOME}/lib && \
    cp /opt/solr/modules/analysis-extras/lib/*.jar ${SOLR_HOME}/lib/

RUN set -eux; \
    chown -R ${SOLR_USER}:${SOLR_GROUP} ${SOLR_HOME}