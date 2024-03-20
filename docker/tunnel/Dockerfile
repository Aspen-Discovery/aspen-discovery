FROM alpine:3.19

ARG TUNNEL_User=tunnel
ENV TUNNEL_User=${TUNNEL_User}


# Install dependencies
RUN apk add --no-cache \
  openssh-client \
  ca-certificates \
  bash \
  sudo

# Agregar el nuevo usuario y grupo
RUN addgroup -g 1000 "${TUNNEL_User}" && \
    adduser -D -u 1000 -G "${TUNNEL_User}" "${TUNNEL_User}"

# Agregar el usuario al grupo sudoers
RUN echo "${TUNNEL_User} ALL=(ALL) ALL" > /etc/sudoers.d/${TUNNEL_User} && \
    chmod 0440 /etc/sudoers.d/${TUNNEL_User}

# Change to root user
USER root

# Update packages
RUN apk update

# Create ssh directory
RUN mkdir -p /home/${TUNNEL_User}/.ssh

# Adding tunnel content
COPY docker/tunnel/tunnel.sh docker/tunnel/config /home/${TUNNEL_User}/.ssh/

# Add entrypoint
ENTRYPOINT ["/bin/ash", "-c", "/home/${TUNNEL_User}/.ssh/tunnel.sh"]
