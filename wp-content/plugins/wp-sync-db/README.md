# WP Sync DB

WP Sync DB eliminates the manual work of migrating a WP database. Copy your db from one WP install to another with a single-click in your dashboard. Especially handy for syncing a local development database with a live site.

<p align="center"><img src="https://raw.github.com/slang800/psychic-ninja/master/wp-migrate-db.png"/></p>

## Description

WP Sync DB exports your database as a MySQL data dump (much like phpMyAdmin), does a find and replace on URLs and file paths, then allows you to save it to your computer, or send it directly to another WordPress instance. It is perfect for developers who develop locally and need to move their WordPress site to a staging or production server.

### Selective Sync

WP Sync DB lets you choose which DB tables are migrated. Have a huge analytics table you'd rather not send? Simply deselect it and it won't be synced.

### Bi-directional Sync

#### Pull: Replace a Local DB with a Remote DB

If you have a test site setup locally but you need the latest data from the production server, just install WP Sync DB on both sites and you can pull the live database down, replacing your local database in just a few clicks.

#### Push: Replace a Remote DB with a Local DB

If you're developing a new feature for a site that's already live, you likely need to tweak your settings locally before deploying. Once you've perfected your configuration on your development machine, it's easy to send the settings to your production server. Just push to the server, replacing your remote database with your local one.

### Database Export & Backup

Not only can WP Sync DB pull and push your DB: it can export your DB to an SQL file that you can save and backup wherever you want. No need to ssh into your machine or open up phpMyAdmin.

### Encrypted Transfers

All data is sent over SSL to prevent your database from being read during the transfer. WP Sync DB also uses HMAC encryption to sign and verify every request. This ensures that all requests are coming from an authorized server and haven't been tampered with en route.

### Automatic Find & Replace

When migrating a WordPress site, URLs in the content, widgets, menus, etc need to be updated to the new site's URL. Doing this manually is annoying, time consuming, and very error-prone. WP Sync DB does all of this for you.

### Stress Tested on Massive Sites

Huge database? No prob. WP Sync DB has been tested with tables several GBs in size.

### Detect Limitations Automatically

WP Sync DB checks both the remote and local servers to determine limitations and optimize for performance. For example, we detect the MySQL `max_allowed_packet_size` and adjust how much SQL we execute at a time.

### Sync Media Libraries Between Installations

Using the optional [WP Sync DB Media Files](https://github.com/wp-sync-db/wp-sync-db-media-files) addon, you can have media files synced between installs too.

## Installation

1. Install [github-updater](https://github.com/afragen/github-updater) by downloading the latest zip [here](https://github.com/afragen/github-updater/releases). We rely on this plugin for updating WP Sync DB directly from this git repo.
2. Install WP Sync DB by downloading the latest zip [here](https://github.com/wp-sync-db/wp-sync-db/releases). Both github-updater and WP Sync DB will now download their own updates automatically, so you will never need to go through that tedious zip downloading again.
3. Access the WP Sync DB menu option under Tools.
4. Install the optional [WP Sync DB Media Files](https://github.com/wp-sync-db/wp-sync-db-media-files) addon.

## Help Videos

### Feature Walkthrough

<https://www.youtube.com/watch?v=u7jFkwwfeJc>

A brief walkthrough of the WP Sync DB plugin showing all of the different options and explaining them.

### Pulling Live Data Into Your Local Development Environment

<http://www.youtube.com/watch?v=IFdHIpf6jjc>

This screencast demonstrates how you can pull data from a remote, live WordPress install and update the data in your local development environment.

### Pushing Local Development Data to a Staging Environment

<http://www.youtube.com/watch?v=FjTzNqAlQE0>

This screencast demonstrates how you can push a local WordPress database you've been using for development to a staging environment.

### Media Files Addon Demo

<http://www.youtube.com/watch?v=0aR8-jC2XXM>

A short demo of how the [Media Files addon](https://github.com/wp-sync-db/wp-sync-db-media-files) allows you to sync up your WordPress Media Libraries.

## Similar Tools

- [Interconnect IT's Search & Replace](https://github.com/interconnectit/Search-Replace-DB)

## Isn't this the same as WP Migrate DB Pro?

No, of course not, don't be silly. I took out the license verification code, a really shady looking PressTrends reporter, and the tab for installing the Media Files addon before I published 1.4. Release 1.3 was the same as [WP Migrate DB Pro](https://deliciousbrains.com/wp-migrate-db-pro), but I've made several improvements since then.

## Is this Illegal?

**No.** Just because this is based on the paid-for WP Migrate DB Pro, it doesn't mean I can't release it. WP Migrate DB Pro is released under GPLv2, a copyleft license that guarantees my freedom (and the freedom of all users) to copy, distribute, and/or modify this software.

I _was_ forced to rename it from "WP Migrate DB" to "WP Sync DB" after Delicious Brains decided to trademark the name "WP Migrate DB", [filed a DMCA takedown](http://wptavern.com/dmca-takedown-notice-issued-against-fork-of-wp-migrate-db-pro) against the repo, and threatened to take me to court. But they should be OK with it now.
