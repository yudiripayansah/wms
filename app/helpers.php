<?php

use App\Models\User;

if (! function_exists('current_user')) {
    /**
     * Return the currently authenticated user typed as App\Models\User.
     * Replaces auth()->user() to give IDE tools the concrete return type.
     */
    function current_user(): ?User
    {
        return auth()->user();
    }
}
