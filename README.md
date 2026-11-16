# GaMarketplace

RAAR's Project repository for COMMIT Web Design Competition 2026.

#### Created with the _**Power of Friendship✨**_ & _Barely_ ~~un~~healthy amount of caffeine and milk☕.

## Overview

GaMarketplace is a secondhand marketplace for UGM students. Students can list items they no longer need, find cheaper alternatives, and arrange exchanges around campus. We also added auctions, wanted-item requests, and basic reporting tools to make the experience more useful than a simple listing board.

## Why we made it
Buying and selling used goods through group chats can get messy. Listings disappear under new messages, people keep asking whether an item is still available, and buyers have little context about who they're dealing with.

Our idea was to put these interactions in one place, with a focus on affordability, reuse, and meet-ups around campus. It's our entry for COMMIT 2026's theme, Optimasi Keuangan dan Gaya Hidup.

## Getting Started

### Links & Deployment

![Gamarketplace Link](https://gamarketplace-production.up.railway.app/gm/main.html)

or copy and paste this: "https://gamarketplace-production.up.railway.app/gm/main.html"

**Accessing through deployed website is recommended, alternatively you could run it from your local machine**

### Prerequisites

You'll need PHP 8.4.1+, Composer, Node.js/npm, and PHP's SQLite extension. We used Node.js 22 for development.

### Installation

Clone the repository and install the dependencies:

```bash
git clone https://github.com/deniswisnuaji/Gamarketplace.git
cd Gamarketplace
composer install
npm ci
```
Create a local environment file (`copy .env.example .env` on Windows Command Prompt), then initialize the application:

```bash
cp .env.example .env
php artisan key:generate
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate --seed
php artisan storage:link
npm run build
php artisan serve
```

Open **http://127.0.0.1:8000/gm/main.html**.

The default local setup uses SQLite. To use MySQL instead, create a database, update the `DB_*` values in your `.env`, and run `php artisan config:clear` followed by `php artisan migrate --seed`.

## Features

 **Listings:** Create and manage secondhand listings with images, prices, categories, and suggested meet-up points. New listings go through admin review before appearing publicly.
- **Marketplace:** Browse and filter available items, then view their details.
- **Auctions:** Place bids or use Buy Now on eligible items. Auctions can close automatically through Laravel's scheduler.
- **Looking for:** Post wanted-item requests and respond with offers.
- **Messages and meet-ups:** Chat with other users, share images, and propose a campus meet-up.
- **Wishlist and notifications:** Save interesting listings and keep track of updates.
- **Reports and moderation:** Report suspicious activity or technical issues; admins can review listings, reports, and accounts.

## Tech stack

| Part                 | Technology                                                      |
| -------------------- | --------------------------------------------------------------- |
| UI                   | HTML, CSS, JavaScript, Laravel Blade                            |
| Backend              | PHP 8.4.1+, Laravel 13                                          |
| Database             | SQLite (default local setup); MySQL supported                   |
| Frontend tooling     | Node.js, npm, Vite                                              |
| Starter dependencies | Laravel Vue starter kit (Vue and Inertia packages are included) |

## Limitations

This is a competition prototype, not a production marketplace. Email domain restrictions are in place, but UGM SSO and email ownership verification are not implemented. We haven't integrated payment processing, delivery, or live GPS tracking. Email delivery and a production scheduler require deployment configuration.

## Tools and credits

- **AI assistance:** ChatGPT (OpenAI) brainstorming, code suggestions/debugging, and documentation ; Claude was used to help with Laravel integration ; Gemini was used to suggest UI/UX & Documentation. The team reviewed and adapted the output.
- **Starter code:** Laravel Vue starter kit — https://laravel.com/starter-kits
- **Libraries and packages:** See [`composer.json`](composer.json) and [`package.json`](package.json). Licenses remain with their respective maintainers.
  
## About Us

### Team Members

| Name                        | Identifier | Role                    | MCP[^*]                   |
| --------------------------- | :--------: | ----------------------- | ------------------------- |
| Denis Wisnu Aji             |   576057   | Project Manager         | "Supossedly..."           |
| Kaindra Rakha Naufal Yuwono |   586383   | Backend Developer       | "Ya intinya itulah..."    |
| Radya Aieshya Rinaldi       |   585065   | Documentation & Testing | "Hah? Apa?..."            |
| Shafa Sekar Arum            |   580265   | UI/UX & Frontend        | "Tunggu.. Gajadi deng..." |

[^*]: Stands for _Most Common Phrase(s)_ i.e. quotes
