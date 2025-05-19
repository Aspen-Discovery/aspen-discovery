#!/bin/bash

chmod -R 600 /home/"${TUNNEL_USER}"/.ssh/id_rsa*

ssh -o StrictHostKeyChecking=no -i /home/"${TUNNEL_USER}"/.ssh/id_rsa -N -g -L 0.0.0.0:"${TUNNEL_LOCAL_PORT}":"${TUNNEL_REMOTE_HOST}":"${TUNNEL_REMOTE_PORT}" "${TUNNEL_JUMP_USER}@${TUNNEL_JUMP_SERVER}" &
#Espera infinita
/bin/bash -c "trap : TERM INT; sleep infinity & wait"

