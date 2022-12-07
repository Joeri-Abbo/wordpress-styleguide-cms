FROM wordpress:apache
WORKDIR /usr/src/wordpress

RUN set -eux; \
	find /etc/apache2 -name '*.conf' -type f -exec sed -ri -e "s!/var/www/html!$PWD!g" -e "s!Directory /var/www/!Directory $PWD!g" '{}' +; \
	cp -s wp-config-docker.php wp-config.php
COPY themes/ ./wp-content/themes/
COPY plugins/ ./wp-content/plugins/

RUN rm -rf ./wp-config-docker.php
COPY wp-config-docker.php ./wp-config-docker.php
