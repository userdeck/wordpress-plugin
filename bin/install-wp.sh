#!/usr/bin/env sh

# Install WordPress.
wp core install \
  --title="UserDeck" \
  --admin_user="admin" \
  --admin_password="wordpress" \
  --admin_email="admin@example.com" \
  --url="http://udplugin.test:8026" \
  --skip-email

# Update permalink structure.
wp option update permalink_structure "/%year%/%monthnum%/%postname%/" --skip-themes --skip-plugins

# Activate plugin.
wp plugin activate userdeck
