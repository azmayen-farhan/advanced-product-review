# Advanced Product Review

A custom product review system for WooCommerce, built as an Elementor widget for single product pages.

## Features
- Rating summary: average score, number of reviews, recommended percentage, and a star breakdown with progress bars
- Review form with star rating labels (Perfect, Good, Average, Not that bad, Very poor)
- Logged-in users use their account details, guests enter name and email
- Photo uploads (up to 3 images, 2MB each, JPG, PNG, GIF or WEBP)
- Honeypot and nonce protection on submissions
- Reviews are saved as Pending until approved
- Admin moderation queue with Pending, Approved and Rejected views, row actions and bulk actions
- Pending count shown in the admin menu
- Deleting a review also deletes its uploaded images
- Custom database table, no clutter in the WooCommerce comments
- Style controls in the Elementor editor

## Requirements
- WordPress 5.8+
- PHP 7.4+
- WooCommerce and Elementor (the review queue still works without Elementor)

## Installation
1. Download the plugin zip from the Releases page.
2. Go to Plugins > Add New > Upload Plugin and upload it.
3. Activate it. The database table is created on activation.
4. Edit your single product template with Elementor, search for "Product Reviews" under the DevThrives category, and drag it in.
5. Approve incoming reviews from the Product Reviews menu in wp-admin.

## Author
Azmayen Farhan - https://azmayenfarhan.com
