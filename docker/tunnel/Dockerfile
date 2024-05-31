FROM alpine:3.19

ARG TUNNEL_USER=tunnel
ENV TUNNEL_USER=${TUNNEL_USER}


# Install dependencies
RUN apk add --no-cache \
  openssh-client \
  ca-certificates \
  bash \
  sudo

# Add both user and group
RUN addgroup -g 1000 "${TUNNEL_USER}" && \
    adduser -D -u 1000 -G "${TUNNEL_USER}" "${TUNNEL_USER}"

# Add tunnel user to sudoers
RUN echo "${TUNNEL_USER} ALL=(ALL) ALL" > /etc/sudoers.d/${TUNNEL_USER} && \
    chmod 0440 /etc/sudoers.d/${TUNNEL_USER}

# Change to root user
USER root

# Update packages
RUN apk update

# Create ssh directory
RUN mkdir -p /home/${TUNNEL_USER}/.ssh

# Copy tunnel content
COPY docker/tunnel/tunnel.sh docker/tunnel/config /home/${TUNNEL_USER}/.ssh/

# Add entrypoint
CMD ["/bin/ash", "-c", "/home/${TUNNEL_USER}/.ssh/tunnel.sh"]
