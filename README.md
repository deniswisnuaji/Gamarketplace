# GaMarketplace

RAAR's Project repository for COMMIT Web Design Competition 2026.

#### Created with the _**Power of Friendship✨**_ & _Barely_ ~~un~~healthy amount of caffeine and milk☕.

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
#### Overview

Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.

#### Why This?

Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident

## The Project

### Features

#### Lorem

Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.

#### Lorem Ipsum

Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.

### Demonstration

#### Lorem

Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo. Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit,

![Image Placeholder](instagram.com/denisadji._)

#### Lorem

Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo. Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet

![Image Placeholder](instagram.com/denisadji._)

## Technology Stacks

| Category        | Technology                   |
| --------------- | ---------------------------- |
| Frontend        | HTML, CSS, Javascript, Blade |
| Backend         | Laravel (PHP)                |
| Database        | MySQL                        |
| Build Tools     | Vite, npm                    |
| Version Control | Git, Github                  |

## Getting Started

### Prerequisites

- Sed ut perspiciatis unde omnis iste natus error sit
- Sed ut perspiciatis unde omnis iste
- Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium

### Installation

Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo. Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia.

### Links & Deployment

Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium

![Useful Links](https://www.youtube.com/watch?v=QDia3e12czc)

## About Us

### Team Members

| Name                        | Identifier | Role                    | MCP[^*]                   |
| --------------------------- | :--------: | ----------------------- | ------------------------- |
| Denis Wisnu Aji             |   576057   | Project Manager         | "Supossedly..."           |
| Kaindra Rakha Naufal Yuwono |   586383   | Backend Developer       | "Ya intinya itulah..."    |
| Radya Aieshya Rinaldi       |   585065   | Documentation & Testing | "Hah? Apa?..."            |
| Shafa Sekar Arum            |   580265   | UI/UX & Frontend        | "Tunggu.. Gajadi deng..." |

[^*]: Stands for _Most Common Phrase(s)_ i.e. quotes
