<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Support\Facades\Config;

class Subscription
{
    public User $user;
    public array $package;

    public function __construct(#[CurrentUser] User $user)
    {
        $this->user = $user;
        $this->determineSubscriptionPackage();
    }

    public function active(): bool
    {
        return $this->user?->subscription()->active() ?? false;
    }

    public function package(): array
    {
        return $this->package;
    }

    public function aiProviderIsUser(): bool
    {
        if (!isset($this->package['usage']['ai_provider'])) {
            return config('ai.provider') === 'user';
        }
        return $this->package['usage']['ai_provider'] === 'user';
    }

    public function aiProviderIsApp(): bool
    {
        if (!isset($this->package['usage']['ai_provider'])) {
            return config('ai.provider') === 'app';
        }
        return $this->package['usage']['ai_provider'] === 'app';
    }

    /**
     * Check if the subscription supports bot billing
     */
    public function supportsBotBilling(): bool
    {
        return $this->package['usage']['bot_billing'] ?? false;
    }

    /**
     * Get max number of bots allowed for this subscription
     */
    public function maxBots(): int
    {
        return $this->package['usage']['bots'] ?? 0;
    }

    /**
     * Check if user has reached their bot limit
     */
    public function canCreateBot(int $currentBotCount): bool
    {
        return $currentBotCount < $this->maxBots();
    }

    /**
     * Get max number of custom tools allowed for this subscription
     */
    public function maxCustomTools(): int
    {
        return $this->package['usage']['custom_tools'] ?? 0;
    }

    /**
     * Check if user has reached their custom tools limit
     */
    public function canCreateCustomTool(int $currentToolCount): bool
    {
        return $currentToolCount < $this->maxCustomTools();
    }

    /**
     * Get max number of inbuilt tools allowed for this subscription
     */
    public function maxInbuiltTools(): int
    {
        return $this->package['usage']['inbuild_tools'] ?? 0;
    }

    /**
     * Check if user can use more inbuilt tools
     */
    public function canUseInbuiltTool(int $currentInbuiltToolCount): bool
    {
        return $currentInbuiltToolCount < $this->maxInbuiltTools();
    }

    /**
     * Check if subscription supports user forwarding
     */
    public function supportsUserForwarding(): bool
    {
        return $this->package['usage']['user_forwarding'] ?? false;
    }

    /**
     * Check if subscription supports vector storage
     */
    public function supportsVectorStorage(): bool
    {
        return $this->package['usage']['vector_storage'] ?? false;
    }

    /**
     * Check if subscription has a dedicated server
     */
    public function hasDedicatedServer(): bool
    {
        return $this->package['usage']['dedicated_server'] ?? false;
    }

    /**
     * Check if subscription supports affiliate program
     */
    public function supportsAffiliate(): bool
    {
        return $this->package['usage']['affiliate'] ?? false;
    }

    /**
     * Check if the user needs to upgrade to use a specific feature
     */
    public function needsUpgradeFor(string $feature): bool
    {
        switch ($feature) {
            case 'bot_billing':
                return !$this->supportsBotBilling();
            case 'user_forwarding':
                return !$this->supportsUserForwarding();
            case 'vector_storage':
                return !$this->supportsVectorStorage();
            case 'dedicated_server':
                return !$this->hasDedicatedServer();
            default:
                return false;
        }
    }

    /**
     * Get the subscription name
     */
    public function getName(): string
    {
        return $this->package['name'] ?? 'Free';
    }

    /**
     * Check if current subscription is free
     */
    public function isFree(): bool
    {
        return ($this->package['monthly_price'] ?? 0) === 0;
    }

    /**
     * Get upgrade recommendation based on current needs
     */
    public function getRecommendedUpgrade(string $neededFeature): ?string
    {
        $packages = Config::get('subscriptions.packages');

        foreach ($packages as $key => $package) {
            // Skip current package
            if ($package === $this->package) {
                continue;
            }

            // Check if this package has the needed feature
            switch ($neededFeature) {
                case 'bot_billing':
                    if ($package['usage']['bot_billing'] ?? false) {
                        return $key;
                    }
                    break;
                case 'user_forwarding':
                    if ($package['usage']['user_forwarding'] ?? false) {
                        return $key;
                    }
                    break;
                case 'vector_storage':
                    if ($package['usage']['vector_storage'] ?? false) {
                        return $key;
                    }
                    break;
                case 'dedicated_server':
                    if ($package['usage']['dedicated_server'] ?? false) {
                        return $key;
                    }
                    break;
                case 'custom_tools':
                    if (($package['usage']['custom_tools'] ?? 0) > ($this->package['usage']['custom_tools'] ?? 0)) {
                        return $key;
                    }
                    break;
                case 'bots':
                    if (($package['usage']['bots'] ?? 0) > ($this->package['usage']['bots'] ?? 0)) {
                        return $key;
                    }
                    break;
            }
        }

        return null;
    }

    protected function determineSubscriptionPackage(): void
    {
        $free = config('subscriptions.packages.free');
        $user = $this->user;
        if (!$user || !$user->stripe_id) { // Added null check for user
            $this->package = $free;
            return;
        }
        $subscription = $user->subscription('default');
        if (!$subscription || !$subscription->active()) {
            $this->package = $free;
            return;
        }
        $packages = Config::get('subscriptions.packages');
        foreach ($packages as $package) {
            if (
                $subscription->hasPrice($package['stripe_monthly_price_id']) ||
                $subscription->hasPrice($package['stripe_yearly_price_id'])
            ) {
                $this->package = $package;
                return;
            }
        }
        $this->package = $free;
    }
}
