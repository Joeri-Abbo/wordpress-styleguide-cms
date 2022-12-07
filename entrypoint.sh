#!/bin/sh

if [ "$SECRET_ENV_FILE" ]; then
    ln -s "/run/secrets/$SECRET_ENV_FILE" /var/www/.env
fi

# This will exec the CMD from your Dockerfile
exec "$@"
