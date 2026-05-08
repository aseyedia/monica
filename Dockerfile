FROM monica:4-apache

# Copy customized application files over the official image
COPY --chown=www-data:www-data app/            /var/www/html/app/
COPY --chown=www-data:www-data config/         /var/www/html/config/
COPY --chown=www-data:www-data resources/      /var/www/html/resources/
COPY --chown=www-data:www-data routes/         /var/www/html/routes/
COPY --chown=www-data:www-data database/migrations/ /var/www/html/database/migrations/

# Pre-built JS assets (run `yarn prod` locally or in CI before building this image)
# We rebuild inside the container so the official PHP/Node env is used.
COPY package.json yarn.lock webpack.mix.js /var/www/html/
COPY resources/js/  /var/www/html/resources/js/
COPY resources/sass/ /var/www/html/resources/sass/

RUN apt-get update -qq && \
    apt-get install -y --no-install-recommends nodejs npm && \
    npm install -g yarn && \
    apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
RUN yarn install --frozen-lockfile && \
    yarn prod && \
    rm -rf node_modules
