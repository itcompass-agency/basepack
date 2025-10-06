FROM nginx:alpine

# set main params
ARG BUILD_ARGUMENT_ENV=prod
ENV ENV=$BUILD_ARGUMENT_ENV

RUN ln -sf /dev/stdout /var/log/nginx/access.log && \
    ln -sf /dev/stderr /var/log/nginx/error.log && \
    rm -rf /etc/nginx/conf.d/*

# install openssl
RUN apk add --update openssl && \
    rm -rf /var/cache/apk/*

# create folder for certificates
RUN mkdir -p /etc/nginx/certificates

# Copy SSL certificates if they exist
# These will be copied from the host machine during docker build
# The build context should include the certificates
COPY ./general/ssl/ /etc/nginx/certificates/

# Put nginx config
COPY ./$BUILD_ARGUMENT_ENV/nginx.conf /etc/nginx/conf.d/default.conf

# Create a startup script to check for certificates
RUN echo '#!/bin/sh' > /docker-entrypoint.sh && \
    echo 'if [ ! -f /etc/nginx/certificates/cert.pem ] || [ ! -f /etc/nginx/certificates/key.pem ]; then' >> /docker-entrypoint.sh && \
    echo '    echo "ERROR: SSL certificates not found!"' >> /docker-entrypoint.sh && \
    echo '    echo "Please ensure cert.pem and key.pem are in .docker/general/ssl/"' >> /docker-entrypoint.sh && \
    echo '    exit 1' >> /docker-entrypoint.sh && \
    echo 'fi' >> /docker-entrypoint.sh && \
    echo 'exec nginx -g "daemon off;"' >> /docker-entrypoint.sh && \
    chmod +x /docker-entrypoint.sh

ENTRYPOINT ["/docker-entrypoint.sh"]