<?php

namespace App\Services;

class StripeService
{
    private $stripe;
    
    public function __construct()
    {
        // Check if Stripe library is loaded
        if (!class_exists('\Stripe\Stripe')) {
            throw new \Exception('Stripe PHP SDK not loaded. Please ensure composer autoload is included.');
        }
        
        // Initialize Stripe with environment variable
        $secretKey = $_ENV['STRIPE_SK'] ?? '';
        if (empty($secretKey)) {
            throw new \Exception('Stripe secret key not found in environment variables');
        }
        
        \Stripe\Stripe::setApiKey($secretKey);
        $this->stripe = new \Stripe\StripeClient($secretKey);
    }
    
    public function createCheckoutSession($params)
    {
        $sessionParams = [
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price' => $params['price_id'],
                'quantity' => 1,
            ]],
            'mode' => 'subscription',
            'success_url' => $params['success_url'],
            'cancel_url' => $params['cancel_url'],
            'metadata' => $params['metadata'] ?? []
        ];
        
        if (isset($params['customer_email'])) {
            $sessionParams['customer_email'] = $params['customer_email'];
        }
        
        return $this->stripe->checkout->sessions->create($sessionParams);
    }
    
    public function createPortalSession($params)
    {
        return $this->stripe->billingPortal->sessions->create([
            'customer' => $params['customer'],
            'return_url' => $params['return_url']
        ]);
    }
    
    public function getCustomer($customerId)
    {
        return $this->stripe->customers->retrieve($customerId);
    }
    
    public function getSubscription($subscriptionId)
    {
        return $this->stripe->subscriptions->retrieve($subscriptionId);
    }
    
    public function cancelSubscription($subscriptionId)
    {
        return $this->stripe->subscriptions->cancel($subscriptionId);
    }
}
?>
