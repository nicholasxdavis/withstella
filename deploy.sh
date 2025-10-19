#!/bin/bash
# Deployment script for Stella on Nixpacks/Coolify/Hostinger

echo "🚀 Starting Stella deployment..."

# Step 1: Install Composer dependencies
echo "📦 Installing Composer dependencies..."
if command -v composer &> /dev/null; then
    composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
    composer dump-autoload --optimize
    echo "✅ Composer dependencies installed"
else
    echo "❌ Composer not found. Please install Composer first."
    exit 1
fi

# Step 2: Verify Stripe installation
echo "💳 Verifying Stripe PHP SDK..."
if [ -d "vendor/stripe/stripe-php" ]; then
    echo "✅ Stripe PHP SDK is installed"
else
    echo "⚠️  Stripe PHP SDK not found. Installing manually..."
    composer require stripe/stripe-php:^10.0 --no-interaction
fi

# Step 3: Check environment configuration
echo "🔧 Checking environment configuration..."
if [ ! -f ".env" ]; then
    echo "⚠️  .env file not found. Please create one with the following variables:"
    echo "   - STRIPE_SK (Stripe Secret Key)"
    echo "   - STRIPE_PK (Stripe Publishable Key)"
    echo "   - STRIPE_PRICE_ID (Stripe Price ID)"
    echo "   - Database credentials (DB_HOST, DB_NAME, DB_USER, DB_PASS)"
else
    echo "✅ .env file exists"
fi

# Step 4: Check file permissions
echo "📁 Setting proper permissions..."
chmod -R 755 api/
chmod -R 755 app/
chmod -R 755 config/
chmod -R 755 vendor/ 2>/dev/null || true
echo "✅ Permissions set"

# Step 5: Test PHP
echo "🔍 Testing PHP configuration..."
php -v
echo "✅ PHP is working"

# Step 6: Test autoload
echo "🧪 Testing Composer autoload..."
php -r "require 'vendor/autoload.php'; echo 'Autoload OK\n';"

echo ""
echo "✨ Deployment complete!"
echo ""
echo "📋 Next steps:"
echo "   1. Configure your .env file with Stripe and database credentials"
echo "   2. Run database migrations if needed"
echo "   3. Test Stripe integration at /dashboard/?page=billing"
echo ""

