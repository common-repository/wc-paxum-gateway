=== Gateway for PAXUM on WooCommerce ===
Contributors: electricblueindustriesltd
Donate link: admin@electric-blue-industries.com
Tags: WooCommerce,PAXUM,Instant Payment
Requires PHP: 7.0.29
Requires at least: 5.4.1
Tested up to: 5.4.1
Stable tag: 5.4.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

This WooCommerce extention allows PAXUM payments by adding 'PAXUM' payment option in checkout. PAXUM is a Canadian origined leading company in online payment and online banking, and suitable for receiving international payment at low transaction cost in USD securely.

= Acceptable payment type =

* Instant payment (yes)
* subscription (no)

== Conditions and notes ==

* httpS required.
* You need to set WooCommerce currency to 'USD', otherwise PAXUM will return an error.
* This plugin provides 'Instant Payment' only, though PAXUM offers 'Subscription' and 'Donation' for payment type.
* Total order amount is sent to PAXUM even when an order consists of multiple items.
* You need to register a PAXUM Business Account that passed KYC (Know Your Customer) process by PAXUM.
* PAXUM Personal Account may NOT work -> not confirmed yet.

== Language availability==

* English
* Japanese 日本語

== Screenshots ==

1. screenshot-1.png
1. screenshot-2.png
1. screenshot-3.png

== Misc features ==

* IPN listener equiped (turn IPN ON in PAXUM setting)
* Order status is automatically updated from 'Pending Payment' to 'Payment Completed' when IPN received
* IPN is recorded in plugin log folder for 30 days, by 'log rotation' logic
* Refund button in order view is enabled when Shared Secret is registered (PAXUM API for Refund)

== Changelog ==

= 1.0.1 =
* PAXUM's internal transaction id is associated with WC order
* Added '1-click refund' function by introducing API call function
= 1.0.0 =
* First release version (alpha release)

== Upgrade Notice ==

= 1.0.0 =
* Minor update (Transaction id association, Refund button using PAXUM API)
= 1.0.0 =
* First release version (alpha release)
