<?php

namespace App\Modules\Cart\Services;

use App\Models\User;
use App\Modules\Branches\Models\Branch;
use App\Modules\Cart\Exceptions\CartUnavailableException;
use App\Modules\Cart\Models\Cart;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CartManager
{
    private const SESSION_GUEST_TOKEN = 'restaurant.guest_cart_token';

    public function resolve(Branch $branch, Request $request): Cart
    {
        $user = $request->user();
        abort_unless($user || config('restaurant.allow_guest_checkout', true), 403, 'Guest checkout is disabled.');
        $guestToken = $user ? null : $this->guestToken($request);
        $ownerKey = hash('sha256', $user ? 'user:'.$user->id.':branch:'.$branch->id : 'guest:'.$guestToken.':branch:'.$branch->id);
        $guestHash = $guestToken ? hash('sha256', $guestToken) : null;

        try {
            return DB::transaction(function () use ($branch, $user, $ownerKey, $guestHash): Cart {
                // Serialize cart creation for the same branch and avoid duplicate active carts.
                Branch::query()->whereKey($branch->id)->lockForUpdate()->firstOrFail();

                Cart::query()->where('branch_id', $branch->id)
                    ->where('active_owner_key', $ownerKey)
                    ->where('status', 'active')
                    ->where('expires_at', '<=', now())
                    ->update(['status' => 'expired', 'active_owner_key' => null]);

                $cart = Cart::query()->where('branch_id', $branch->id)
                    ->where('active_owner_key', $ownerKey)
                    ->where('status', 'active')
                    ->first();

                if ($cart) {
                    return $cart;
                }

                return Cart::create([
                    'public_id' => (string) Str::uuid(),
                    'branch_id' => $branch->id,
                    'user_id' => $user?->id,
                    'guest_token_hash' => $guestHash,
                    'active_owner_key' => $ownerKey,
                    'currency' => $branch->restaurant->currency ?: config('restaurant.currency', 'EGP'),
                    'status' => 'active',
                    'expires_at' => now()->addDays(30),
                ]);
            }, 3);
        } catch (QueryException $exception) {
            // A concurrent request may win the unique active-owner insert.
            $cart = Cart::query()->where('branch_id', $branch->id)
                ->where('active_owner_key', $ownerKey)
                ->where('status', 'active')->first();

            if ($cart) {
                return $cart;
            }

            throw $exception;
        }
    }

    public function assertOwnedByRequest(Cart $cart, Request $request): void
    {
        $user = $request->user();
        if ($user) {
            abort_unless((int) $cart->user_id === (int) $user->id, 404);
            return;
        }

        $token = $request->session()->get(self::SESSION_GUEST_TOKEN);
        abort_unless(is_string($token) && $cart->user_id === null
            && hash_equals((string) $cart->guest_token_hash, hash('sha256', $token)), 404);
    }

    public function guestToken(Request $request): string
    {
        $token = $request->session()->get(self::SESSION_GUEST_TOKEN);
        if (! is_string($token) || strlen($token) < 40) {
            $token = Str::random(64);
            $request->session()->put(self::SESSION_GUEST_TOKEN, $token);
        }

        return $token;
    }
}
