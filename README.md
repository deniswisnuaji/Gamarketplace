# GaMarketplace

UGM campus marketplace for secondhand items, auctions, wanted-item requests, chat and safer campus meet-ups. Built for the COMMIT Web Design Competition 2026.

The `fullstack` branch connects the approved Blade/JavaScript frontend to Laravel session authentication and a relational database. It includes protected student/admin flows, listing moderation, product requests and offers, auctions, private chat/meet-up proposals, wishlists, notifications, and an admin queue for scam/bug/listing/user/support reports.

Start with [the fullstack setup guide](docs/FULLSTACK_SETUP.md). See [schema decisions and ERD](docs/schema-decisions.md) and [verification evidence](docs/verification.md).

```bash
composer install
npm ci
# Configure .env and the database using the setup guide.
php artisan migrate --seed
php artisan storage:link
npm run build
php artisan marketplace:admin your-admin@ugm.ac.id
php artisan serve
# In another terminal:
php artisan schedule:work
```

The marketplace begins at `/gm/main.html`; administrators sign in at `/gm/admin-login.html`. No default administrator password is seeded. This is a competition prototype; SSO, mail delivery, email ownership verification and production hosting require configuration. The frontend-original directory is retained as design history and is not the served application.

## About Us

### Team Members

| Name                        | Identifier   | Role                    | MCP[^*]                   |
| --------------------------- | :----------: | ----------------------- | ------------------------- |
| Denis Wisnu Aji             | 576057       | Project Manager         | "Supossedly..."           |
| Kaindra Rakha Naufal Yuwono | 586383       | Backend Developer       | "Ya intinya itulah..."    |
| Radya Aieshya Rinaldi       | 585065       | Documentation & Testing | "Hah? Apa?..."            |
| Shafa Sekar Arum            | 580265       | UI/UX & Frontend        | "Tunggu.. Gajadi deng..." |

[^*]: Stands for _Most Common Phrase(s)_ i.e. quotes
