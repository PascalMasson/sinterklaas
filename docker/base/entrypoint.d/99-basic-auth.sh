#!/bin/sh

set -e

# Check if Basic Auth is enabled
if [ "$BASIC_AUTH_ENABLE" = "1" ]; then
    echo "Enabling Basic Auth..."
    # Create .htpasswd file with provided username and password
    echo "$BASIC_AUTH_USERNAME:$(openssl passwd -apr1 $BASIC_AUTH_PASSWORD)" > /var/www/html/.htpasswd

    # Add Basic Auth block to nginx config if not already added
    if ! grep -q "auth_basic" /etc/nginx/site-opts.d/http.conf; then
        sed -i '/location \/ {/a \    auth_basic \"Restricted Area\";\n    auth_basic_user_file /var/www/html/.htpasswd;' /etc/nginx/site-opts.d/http.conf
    fi
else
    echo "Basic Auth is disabled."
fi
