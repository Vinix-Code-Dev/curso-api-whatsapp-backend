#!/bin/sh

# Laravel requires some directories to be writable.

sudo chmod -R 777 storage/
sudo mkdir -p storage/app
sudo chmod -R 777 storage/app
sudo chmod -R 777 bootstrap/cache/
