#!/bin/bash

# Optimizing configuration loading, route loading and view loading
# https://laravel.com/docs/8.x/deployment#optimization

php artisan config:clear

php artisan config:cache

# php artisan route:cache

php artisan view:cache

php artisan vendor:publish --tag="cors"

sudo php artisan storage:link

# php artisan db:seed --class=SettingTrialConversations