# Vibe SaaS Starter Kit

![Vibe SaaS](https://cdn.scriptoshi.com/logos/hero.png)

> Vibe code your SaaS in minutes. A minimal, elegant Laravel Livewire starter kit with a detailed system prompt, built-in authentication, subscription management, and admin controls.

## Overview

Vibe SaaS Starter Kit is a lightweight, production-ready foundation for building Software-as-a-Service applications. Built with Laravel's official packages including Livewire, Volt, Socialite, and Cashier, this kit provides everything you need to launch your SaaS project quickly while maintaining clean, maintainable code.

## Key Features

-   **Lean Setup**: Exclusively uses Laravel official packages (Livewire, Volt, Socialite, Cashier)
-   **Authentication**: Secure login with Google, GitHub, or traditional email/password
-   **Subscription Management**: Flexible billing options with Stripe and Paddle integration
-   **Admin Dashboard**: Simple, extensible admin panel with built-in user management

## Installation

```bash
# Clone the repository
git clone https://github.com/your-username/vibe-saas-starter.git

# Navigate to the project directory
cd vibe-saas-starter

# Install dependencies
composer install
npm install

# Copy the environment file and update it with your configurations
cp .env.example .env

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate

# Compile assets
npm run dev
```

## Configuration

### Environment Setup

Update your `.env` file with the necessary credentials:

```
APP_NAME="Vibe SaaS"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Social Authentication
GITHUB_CLIENT_ID=
GITHUB_CLIENT_SECRET=
GITHUB_REDIRECT_URI=

GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=

# Stripe Integration

#https://dashboard.stripe.com/test/apikeys
STRIPE_KEY=
STRIPE_SECRET=
#https://dashboard.stripe.com/test/workbench/overview
STRIPE_WEBHOOK_SECRET=
#stripe products
STRIPE_PRO_MONTHLY_PRICE_ID=
STRIPE_PRO_YEARLY_PRICE_ID=
STRIPE_MAX_MONTHLY_PRICE_ID=
STRIPE_MAX_YEARLY_PRICE_ID=

# Paddle Integration (Professional and Max plans)
PADDLE_VENDOR_ID=
PADDLE_VENDOR_AUTH_CODE=
PADDLE_PUBLIC_KEY=

```

## Usage

After installation, you can:

1. Register a new user account
2. Set up subscription plans in your Stripe/Paddle dashboard
3. Configure webhooks for payment processing
4. Customize the admin dashboard for your specific needs

### Free Plan ($0/year)

-   For use in a single project
-   Only Stripe integration
-   Support documentation
-   No priority updates

## Support

If you have any questions or need assistance, please contact our support team.

---

Built with ❤️ by [scriptoshi]
