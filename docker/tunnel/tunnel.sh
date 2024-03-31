#!/bin/bash

chmod -R 400 /home/${TUNNEL_User}/.ssh/id_rsa*

ssh -o StrictHostKeyChecking=no -i /home/${TUNNEL_User}/.ssh/id_rsa -N -g -L 0.0.0.0:${TUNNEL_LocalPort}:${TUNNEL_RemoteHost}:${TUNNEL_RemotePort} ${TUNNEL_JumpServer} &
#Espera infinita
/bin/bash -c "trap : TERM INT; sleep infinity & wait"

