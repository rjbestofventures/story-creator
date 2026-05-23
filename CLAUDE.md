# StoryCreator — Claude Code Guide

## Stack
- **Backend:** Laravel 13 (PHP 8.3), Inertia.js, Laravel Sanctum, Ziggy
- **Frontend:** Vue 3 (Composition API), Tailwind CSS v3, Vite
- **Auth scaffold:** Laravel Breeze
- **Testing:** PHPUnit 12

## Dev Commands
```bash
composer run dev       # starts all services: PHP server, queue, log tail, Vite
composer run test      # clears config cache then runs PHPUnit
composer run setup     # fresh install from scratch
npm run build          # production Vite build
php artisan pint       # format PHP code (always run before committing)
```

## Project Structure
- `app/Http/Controllers/` — Laravel controllers
- `app/Models/` — Eloquent models
- `resources/js/Pages/` — Inertia page components (Vue)
- `resources/js/Components/` — shared Vue components
- `resources/js/Layouts/` — Vue layout wrappers
- `routes/web.php` — primary web routes
- `routes/auth.php` — Breeze auth routes

## Branding & UI Rules
See [BRANDING_STYLE.md](BRANDING_STYLE.md) for the full style guide. Always follow it for any UI work.

**Quick reference:**
- Background: `#F5F5F5` / `#FAFAF8` (warm off-white)
- Accent / CTA: `#F5A623` (gold/amber) — use for primary buttons, highlights, active states
- Headlines / dark text: `#1A1A1A`
- Body copy: `#555555`
- Borders / dividers: `#DDDDDD`
- Card / input backgrounds: `#FFFFFF`
- Buttons: rounded (`rounded-lg`), bold. Primary = gold bg + dark text. Secondary = white bg + dark border.

## Coding Conventions
- Use Vue Composition API (`<script setup>`) for all components
- Tailwind utility classes only — no custom CSS unless unavoidable
- Inertia `useForm` for form state and submission
- Follow Laravel conventions: fat models, thin controllers, form request classes for validation
- Run `php artisan pint` before committing PHP changes
- No comments unless the reason is non-obvious

## What to Avoid
- Do not use the Options API in Vue components
- Do not write inline styles; use Tailwind classes
- Do not add features, abstractions, or error handling beyond what the task requires
