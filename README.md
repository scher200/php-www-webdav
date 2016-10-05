php-www-webdav
==============

This is a contianer with Nginx, PHP7 and Sabre. Helping serve any share any file dir or volumes.
It is protected with by 5 tries of a password per ip adres. This will help against those brute force bots. 


```
docker run -p "80:80" \
           -v "$PWD/files:/any/shared/dir/files" \
           -e SHARED_DIR="/any/shared/dir/files" \
           -e AUTH_USER="yourusername" \
           -e AUTH_PASS="yourpassword" \
           scher200/php-www-webdav
```
