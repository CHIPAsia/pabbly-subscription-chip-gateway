# Pabbly Subscription Billing X CHIP

This to integrate Pabbly Subscription Billing with CHIP Payment Gateway.

Reference: https://apidocs.pabbly.com/

## Multiple Pabbly Subscription Billing Account

To integrate with multiple pabbly subscription billing accounts:
- Clone the whole project to new directory.
- Use different database or same database with different prefix

## System Requirements

- PHP version 7.4 or greater.
- MySQL version 5.7 or greater OR MariaDB version 10.3 or greater.
- HTTPS support

## Installation

1. Download and unzip this repository if you haven’t already.
1. Create a database for Pabbly on your web server, as well as a MySQL (or MariaDB) user who has all privileges for accessing and modifying it.
1. Find and rename **auth-sample.php** to **auth.php**, then edit the file and add:
    - Pabbly API Key and Secret
    - CHIP Secret Key and Brand ID
    - Database information
    - Installation URL. Example: `https://www.yoururl.com/payment`.
    - (Optional) Database Prefix
1. Upload all files to the desired location on your web server.

## Configuration

1. Go to Pabbly Subscriptions -> Settings -> Payment Gateway Integration -> Custom Connect Now
1. Set Gateway status: Activate
1. Gateway name: CHIP (or anything that suit to your needs)
1. Gateway URL: Installation URL with ending /hostedpage.php. Example: `https://www.yoururl.com/hostedpage.php`

## Other

Facebook: [Merchants & DEV Community](https://www.facebook.com/groups/3210496372558088)
