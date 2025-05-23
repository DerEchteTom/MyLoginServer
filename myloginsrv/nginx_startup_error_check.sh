#!/bin/bash
echo "Nginx Container Log:"
docker logs myloginsrv-nginx 2>&1 | tail -n 50
