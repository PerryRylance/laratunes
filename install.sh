#!/bin/bash

cat << 'EOF'
 _                    _                         
| |    __ _ _ __ __ _| |_ _   _ _ __   ___  ___ 
| |   / _` | '__/ _` | __| | | | '_ \ / _ \/ __|
| |__| (_| | | | (_| | |_| |_| | | | |  __/\__ \
|_____\__,_|_|  \__,_|\__|\__,_|_| |_|\___||___/

EOF

# Requirements
echo "📋 Checking requirements..."

if ! command -v openssl &> /dev/null; then
    echo "❌ Error: 'openssl' is not installed."
    echo "Please install it (e.g., 'sudo apt install openssl' or 'brew install openssl') and try again."
    exit 1
fi

if ! command -v docker &> /dev/null; then
    echo "❌ Error: Docker is not installed or not in your PATH."
    echo -e "Please see follow the documentation \033[34mhttps://docs.docker.com/engine/install/\033[0m and try again."
    exit 1
fi

if [ -f .env ]; then
    echo "⚠️  Warning: A .env file already exists. Installation aborted to prevent overwriting."
    echo "It appears you already have an installation of Laratunes here. To re-install, please delete .env and try again."
    exit 1
fi

# App URL
echo "⌨️  Gathering user input..."

while true; do
    read -p "Enter your domain name (Required): " domain
    if [ -z "$domain" ]; then
        echo "❌ Error: Domain cannot be blank. Please enter a domain (e.g., example.com)."
    else
        break
    fi
done

if [[ "$domain" == "localhost" ]]; then
    domain="http://localhost"
elif [[ "$domain" == "http://localhost" ]]; then
    domain="http://localhost"
elif [[ ! "$domain" =~ ^https?:// ]]; then
    domain="https://$domain"
fi

# Media path
while true; do
    read -e -p "Enter the full path to your media folder: " mediapath
    
    # Check if the path is empty
    if [ -z "$mediapath" ]; then
        echo "❌ Error: Media path is required."
    # Check if the directory actually exists on the host machine
    elif [ ! -d "$mediapath" ]; then
        echo "❌ Error: The directory '$mediapath' does not exist. Please check the path."
    else
        break
    fi
done

# Dotenv
echo "🌍 Configuring environment..."

# App key
appkey="base64:$(openssl rand -base64 32)"

# Set up .env
cp .env.example .env
{
    echo "APP_KEY=$appkey"
    echo "APP_URL=$domain"
    echo "MEDIA_PATH=$mediapath"
} >> .env

# Copy over default background
cp "./resources/media/default-background.png" "$mediapath/default-background.png"

# Containers build and up
echo "🚢 Starting containers..."

docker compose -f compose.production.yaml build
docker compose -f compose.production.yaml up -d --force-recreate

echo -n "⏳ Waiting for the web service to start..."

while [ "$(docker inspect -f '{{.State.Health.Status}}' $(docker compose ps -q laravel))" != "healthy" ]; do
    printf "."
    sleep 1
    
    # Optional: Add a check to see if it becomes 'unhealthy' to avoid infinite loops
    if [ "$(docker inspect -f '{{.State.Health.Status}}' $(docker compose ps -q laravel))" == "unhealthy" ]; then
        echo -e "\n❌ Error: Container became unhealthy. Check logs with 'docker compose logs'."
        exit 1
    fi
done

echo ""

echo "🦅 Running migrations..."

docker compose exec laravel php artisan migrate --force

# Prompt the user to set up their admin account
echo "🔑 Creating admin account..."

docker compose exec -it laravel php artisan app:make-admin-user

# Open the login link in the browser
URL="${domain}/admin/login"

if command -v open >/dev/null; then
    OPENER="open"
elif command -v xdg-open >/dev/null; then
    OPENER="xdg-open"
else
    OPENER=""
fi

# Use it
if [ -n "$OPENER" ]; then
    $OPENER "$URL"
fi

echo -e "Please navigate to \e]8;;$URL\e\\$URL\e]8;;\e\\ to complete setup."
