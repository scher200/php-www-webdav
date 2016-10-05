#!/bin/bash

while [ 1 ]
do
	# cleanup blocked ips 
	echo "cleanup both ip-basic-auth.log and blockips.log, so they can try again"
    rm /sabre/blockips.log
    rm /sabre/ip-basic-auth.log
    touch /sabre/blockips.log
    touch /sabre/ip-basic-auth.log
    chown xfs:xfs /sabre/blockips.log
    chown xfs:xfs /sabre/ip-basic-auth.log
    sleep 86400
    # in a daily loop
done