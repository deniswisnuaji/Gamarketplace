# Fullstack setup and walkthrough

Prepared 15 November 2026. `fullstack` is based on `Gm-Frontend` commit `5804c2dcfd360d932d613cccda8af4a28c378a8a`. `Gm_Backend` at `12b16c3b2650feb98226c3d41c8e5a65320ea0b9` was compared: its changes were a `users.role` migration and `User::isAdmin()`. This branch retains the admin helper and implements a single role migration with `student/admin`, matching the approved frontend. Do not also cherry-pick the backend role migration; that would add the same column twice.

## What is already prepared in this workspace

The `Gamarketplace` subfolder is a fresh checkout on local branch `fullstack`. PHP and Node dependencies are installed; a local `.env`, application key, SQLite database and public storage link exist. Changes have not been committed, pushed, or deployed. `Gm-Frontend`, `Gm_Backend`, and `main` were not changed.

To continue here:

```bash
cd '/home/denisaji/Documents/ChatGPT/Commit Web Design Competition/Gamarketplace'
git status
php artisan serve
```

Open http://127.0.0.1:8000/gm/main.html. Run the auction scheduler in another terminal:

```bash
php artisan schedule:work
```

The development server is not a production web server. Auction finalization also supports a manual `php artisan marketplace:finalize-auctions` command.

## Fresh installation on another computer

The locked development dependencies were tested with PHP 8.5.12, Composer 2.11.1 and Node 22.23.1. Install PHP with PDO SQLite/MySQL, GD, fileinfo, mbstring, XML, zip and normal Laravel extensions. Use the lockfiles; do not use `composer update` or ignore platform requirements to work around an old PHP installation.

After the integration branch is pushed, clone/switch to `fullstack`. To create a fresh integration branch yourself from the original frontend, before copying these changes:

```bash
git fetch origin
git switch -c fullstack origin/Gm-Frontend
```

Do not repeat branch creation in this workspace: it already exists. Do not switch away from uncommitted work.

Then, on a new checkout only:

```bash
composer install
npm ci
cp .env.example .env
php artisan key:generate
```

Create the SQLite file (or choose MySQL below):

```bash
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate --seed
php artisan storage:link
npm run build
```

`key:generate` is for a new installation, not an existing application's key. Do not replace an existing `.env`. `migrate --seed` creates/updates schema and reference rows without wiping records. Seeders create categories, faculties and meet-up points only—no default passwords or production users.

## Create your administrator

```bash
php artisan marketplace:admin your-admin@ugm.ac.id --name="Your Admin Name"
```

Enter and confirm your password at the hidden prompts. Use a new email; the command does not silently promote an existing student or reset an account. Sign in at `/gm/admin-login.html`. Ordinary accounts are created through `/gm/login.html#register`, with a UGM email, confirmed password and safety agreement. Public registration cannot set an admin role.

The local browser QA used synthetic accounts and a clearly labelled sample request/report. They are not part of the seeders. Create your own administrator using the command above. Do not copy the QA database into a deployment.

## Use MySQL

MySQL 8.4.10 migrations and the marketplace feature tests were verified in a separate disposable database. SQLite remains the convenient local default. MariaDB and older MySQL releases have not been verified; checks require a modern engine.

Create an empty database such as `gamarketplace_dev` with `utf8mb4` and a dedicated database user. Set these values in `.env` (use your own password):

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gamarketplace_dev
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password
```

Remove an obsolete `DB_SOCKET` or SQLite file path if you previously configured one. Then run `php artisan config:clear` and `php artisan migrate --seed`. This creates a new database installation; it does not transfer records from SQLite. XAMPP is optional; its database may be used independently of Apache, but verify PHP compatibility separately.

Use a separate disposable test database for MySQL tests. Laravel's test suite resets database schema. Never point the test suite or `migrate:fresh` at a database with records you want to retain.

## Demonstrate the complete flow

1. Register a student, sign out, and sign back in. Use a separate browser profile/incognito window for a second user: Laravel sessions are shared between tabs in the same browser profile.
2. Submit a listing with an image, category, condition, price and meet-up point. It appears in **My Listings / In Review** and is invisible to other students.
3. Sign in as an administrator. Reject with a reason, have the owner edit/resubmit, then approve. Confirm it appears in Marketplace and direct item links work for another student.
4. Post a product request. A different student can send one offer, withdraw it and send again. The owner can mark it found. Admins can inspect/remove requests under **Product Requests**.
5. Save a listing, open a contextual chat, send a message or photo, propose a future campus meet-up, and respond as the other participant.
6. Submit a **Website bug**, **Listing complaint**, **User complaint**, **General support** or **Scam** report. Admins review these in **Reports & Support**. Resolve bugs privately. Only scam reports may become public, with a reviewed public title/summary. Evidence stays private.
7. Create and approve an auction. Bid from another account; verify minimum increments and owner/current-leader rejection. Buy Now closes immediately; expired auctions close through the scheduler. Re-running finalization does not create another winner or duplicate winning notices.
8. Suspend a student. Existing sessions and writes are denied, and their public listings disappear. Restoring the student does not restore manually hidden listings.

## Where to work next

| Work | Source |
| --- | --- |
| Relational schema and constraints | `database/migrations/2026_11_15_*` |
| Lookup data | `database/seeders/MarketplaceReferenceSeeder.php` |
| Session login, registration, profile, password | `app/Http/Controllers/Marketplace/AuthController.php`, `app/Actions/Fortify/CreateNewUser.php` |
| Listing/request/report/admin actions | `app/Http/Controllers/Marketplace/CoreController.php` |
| Bids, chat, meet-ups, wishlist, notifications | `app/Http/Controllers/Marketplace/InteractionController.php`, `app/Services/AuctionService.php` |
| Safe browser projections | `app/Services/MarketplaceData.php` |
| Validated image storage | `app/Services/MarketplaceFiles.php` |
| Web routes and access gates | `routes/marketplace.php`, `app/Http/Middleware/Ensure*` |
| JavaScript API adapter | `public/marketplace/js/store.js` |
| Feature tests | `tests/Feature/Marketplace*Test.php` |

JavaScript still renders the approved design. `Store` now posts JSON to Laravel with a CSRF token and session cookie, awaits the result, and replaces its in-memory read data with an authorized server projection. Browser storage is used only for harmless UI preferences/drafts/checklists/view-count hints; it is no longer an account or marketplace database. Old `gm-db`/`gm-session` records are ignored, not imported.

## Checks and sharing

```bash
php artisan test
vendor/bin/phpstan analyse --memory-limit=512M
vendor/bin/pint --test
npm run types:check
npm run build
git diff --check
```

The existing `npm run check` also checks formatting of the preserved original frontend; see the verification record for its result. Build can require network access to download the starter's configured fonts.

Review the changes before committing and pushing:

```bash
git status
git diff
# Stage only the changes you have reviewed (including new files), then:
git commit -m "Connect marketplace UI to Laravel backend and relational database"
git push -u origin fullstack
```

Open a pull request into the team's agreed integration target. No force-push or direct `main` push is needed. `.env`, dependency folders, database files and uploaded files remain ignored.

## Remaining configuration and boundaries

- UGM SSO and email ownership verification are not configured. Domain validation alone is not proof of student identity. The SSO and verified-only controls are explicitly unavailable.
- Password reset uses the existing Fortify flow. With `MAIL_MAILER=log`, development reset messages go to the Laravel log; configure a real mail service before promising delivered email. Weekly email summaries are unavailable.
- Public identifier searches were removed because identifiers and evidence are private. Reviewed public scam summaries remain searchable. No automated suspension is based on text matching.
- Account deletion requires an administrator retention review; records with chat/moderation relationships are not silently erased.
- This integration uses a filtered full-state projection and 15-second polling to preserve the existing synchronous rendering helpers. It is appropriate for the competition prototype; pagination, narrower read endpoints and load testing are required before a large deployment. It is not a WebSocket service.
- No payment processing, orders, delivery, live GPS, barter, or actual UGM SSO is implemented.
- Before a public deployment: configure HTTPS/session cookies, production database credentials, persistent upload storage, scheduled auction finalization, mail and backups. No public deployment was performed here.
