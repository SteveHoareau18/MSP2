FROM nginx:alpine

RUN apk update && \
    apk upgrade && \
    apk add --no-cache nano openssl && \
    mkdir -p /etc/nginx/ssl && \
    openssl req -x509 -nodes -days 365 -newkey rsa:2048 -keyout /etc/nginx/ssl/server.key -out /etc/nginx/ssl/server.crt -subj "/CN=localhost" && \
    mkdir -p /etc/nginx/conf.d && \
    echo 'server { \
            listen 80; \
            server_name localhost; \
            return 301 https://$host$request_uri; \
        } \
        server { \
            listen 443 ssl; \
            server_name localhost; \
            ssl_certificate /etc/nginx/ssl/server.crt; \
            ssl_certificate_key /etc/nginx/ssl/server.key; \
            root /var/www/html/public; \
            index index.php index.html index.htm; \
            location / { \
                try_files $uri /index.php$is_args$args; \
            } \
            location ~ \.php$ { \
                fastcgi_pass web:9000; \
                fastcgi_index index.php; \
                include fastcgi_params; \
                fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; \
                fastcgi_param PHP_VALUE "upload_max_filesize=40M \n post_max_size=40M"; \
            } \
            location ~ /\.ht { \
                deny all; \
            } \
        }' > /etc/nginx/conf.d/default.conf
