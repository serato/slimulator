# Install git an zip/unzip (required by composer)
apt update && apt install -y git zip unzip libgmp-dev

# Configure and install PHP GMP extension (this is not included in the base container images)
docker-php-ext-configure gmp
docker-php-ext-install gmp

# Download and install Composer
# I _could_ mount a volume and install it there. But it only takes
# a few seconds so do it everytime the container is run.
curl -o /home/composer-setup.php https://getcomposer.org/installer
EXPECTED_SIGNATURE="$(curl https://composer.github.io/installer.sig)"
ACTUAL_SIGNATURE="$(php -r "echo hash_file('sha384', '/home/composer-setup.php');")"
if [ "$EXPECTED_SIGNATURE" != "$ACTUAL_SIGNATURE" ]
then
    >&2 echo 'ERROR: Invalid installer signature'
    rm composer-setup.php
    exit 1
fi
cd /home
php /home/composer-setup.php --filename=composer
php -r "rename('/home/composer', '/usr/local/bin/composer');"
php -r "unlink('/home/composer-setup.php');"

# Create an AWS IAM credentials file (used for integration testing)
# Provide a AWS IAM access key ID to docker-compose via the AWS_ACCESS_KEY_ID environment variable.
# Provide a AWS IAM access key secret to docker-compose via the AWS_SECRET_ACCESS_KEY environment variable.
mkdir -p /home/.aws
touch /home/.aws/credentials
echo "[default]\naws_access_key_id = $1\naws_secret_access_key = $2\nregion = us-east-1" > /home/.aws/credentials

# Install project dependencies using Composer
cd /srv/php-lib
composer install
ls -alh

# Output a PHP version details + other info
echo "\n"
php -v
echo "\n"

# Start a bash shell
bash
