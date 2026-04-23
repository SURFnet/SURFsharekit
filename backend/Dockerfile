FROM gitlab.zooma.cloud:5050/zooma-int/true/docker-containers/silverstripe:8.3-stable-backend

RUN install-php-extensions xdebug sockets redis

RUN echo "xdebug.mode=debug" >> /usr/local/etc/php/conf.d/xdebug.ini && \
        echo "xdebug.default_enable=1" >> /usr/local/etc/php/conf.d/xdebug.ini && \
        echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/xdebug.ini && \
        echo "xdebug.mode=debug" >> /usr/local/etc/php/conf.d/xdebug.ini && \
        echo "xdebug.discover_client_host=0" >> /usr/local/etc/php/conf.d/xdebug.ini && \
        echo "xdebug.client_port=9003" >> /usr/local/etc/php/conf.d/xdebug.ini && \
        echo "xdebug.idekey=PHPSTORM" >> /usr/local/etc/php/conf.d/xdebug.ini && \
        echo "xdebug.start_with_request=trigger" >> /usr/local/etc/php/conf.d/xdebug.ini

RUN echo "upload_max_filesize=10000000" >> /usr/local/etc/php/conf.d/php-overrides.ini && \
        echo "xdebug.post_max_size=10000000" >> /usr/local/etc/php/conf.d/php-overrides.ini

#### Install RabbitMQ + dependencies

RUN apt-get install curl gpg apt-transport-https -y

RUN apt-get update && apt-get install -y \
    curl \
    gnupg \
    apt-transport-https

# Team RabbitMQ's main signing key
RUN curl -1sLf "https://keys.openpgp.org/vks/v1/by-fingerprint/0A9AF2115F4687BD29803A206B73A36E6026DFCA" | gpg --dearmor | tee /usr/share/keyrings/com.rabbitmq.team.gpg > /dev/null

# Community mirror of Cloudsmith: modern Erlang repository
RUN curl -1sLf https://github.com/rabbitmq/signing-keys/releases/download/3.0/cloudsmith.rabbitmq-erlang.E495BB49CC4BBE5B.key | gpg --dearmor | tee /usr/share/keyrings/rabbitmq.E495BB49CC4BBE5B.gpg > /dev/null

# Community mirror of Cloudsmith: RabbitMQ repository
RUN curl -1sLf https://github.com/rabbitmq/signing-keys/releases/download/3.0/cloudsmith.rabbitmq-server.9F4587F226208342.key | gpg --dearmor | tee /usr/share/keyrings/rabbitmq.9F4587F226208342.gpg > /dev/null

## Update package indices
RUN apt-get update -y

## Install Erlang packages
RUN apt-get install -y erlang-base \
                        erlang-asn1 erlang-crypto erlang-eldap erlang-ftp erlang-inets \
                        erlang-mnesia erlang-os-mon erlang-parsetools erlang-public-key \
                        erlang-runtime-tools erlang-snmp erlang-ssl \
                        erlang-syntax-tools erlang-tftp erlang-tools erlang-xmerl

## Install rabbitmq-server and its dependencies
RUN apt-get install rabbitmq-server -y --fix-missing

COPY ./rabbitmq.list /etc/apt/sources.list.d/rabbitmq.list

######################################

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/bin/install-php-extensions