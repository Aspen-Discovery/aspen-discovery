#!/bin/bash

chmod -R 400 /root/.ssh/id_rsa*

ssh -N -L ${TUNNEL_LocalPort}:${TUNNEL_RemoteHost}:${TUNNEL_RemotePort} ${TUNNEL_JumpServer} & 
#Espera infinita
/bin/bash -c "trap : TERM INT; sleep infinity & wait"

