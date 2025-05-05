<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PaymentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // Users can view their payments
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Payment $payment): bool
    {
        // User can view if they made the payment or they own the bot
        return $user->id === $payment->user_id || 
               $user->id === $payment->bot->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Any authenticated user can create a payment
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Payment $payment): bool
    {
        // Only bot owners can update payments
        return $user->id === $payment->bot->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Payment $payment): bool
    {
        // Only bot owners can delete payments
        return $user->id === $payment->bot->user_id;
    }

    /**
     * Determine whether the user can process the payment.
     */
    public function processPayment(User $user, Payment $payment): bool
    {
        // Only bot owners can process payments
        return $user->id === $payment->bot->user_id;
    }

    /**
     * Determine whether the user can cancel the payment.
     */
    public function cancelPayment(User $user, Payment $payment): bool
    {
        // Users can cancel their own payments, or bot owners can cancel payments for their bots
        return $user->id === $payment->user_id || 
               $user->id === $payment->bot->user_id;
    }

    /**
     * Determine whether the user can view payment statistics.
     */
    public function viewStatistics(User $user): bool
    {
        // Only administrators can view payment statistics
        return $user->is_admin;
    }
}