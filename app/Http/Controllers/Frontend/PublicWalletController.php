<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repositories\UserRepo\IUserRepo;
use App\Services\WalletService;

class PublicWalletController extends Controller
{
    public function __construct(private readonly WalletService $walletService, private readonly IUserRepo $userRepo)
    {
    }

    private function ensureTestMode()
    {
        if (!env('WALLET_TEST_MODE', false)) {
            abort(403, 'Wallet public endpoints are disabled');
        }
    }

    // GET /api/v1/public/wallet/balance?public_id=...
    public function balance(Request $request)
    {
        $this->ensureTestMode();
        $request->validate(['public_id' => 'required|string']);
        $user = $this->userRepo->getAll(columns: ['*'], filter: ['public_id' => $request->query('public_id')], query_builder: true)->first();
        if (!$user) {
            return format_response(false, __('User not found'), code: 404);
        }
        $data = [
            'balance' => $this->walletService->getBalance($user),
            'transactions' => $this->walletService->transactions($user)
        ];
        return format_response(true, __('Fetched successfully'), $data);
    }

    // POST /api/v1/public/wallet/deposit { public_id, amount }
    public function deposit(Request $request)
    {
        $this->ensureTestMode();
        $validated = $request->validate([
            'public_id' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
        ]);
        $user = $this->userRepo->getAll(columns: ['*'], filter: ['public_id' => $validated['public_id']], query_builder: true)->first();
        if (!$user) {
            return format_response(false, __('User not found'), code: 404);
        }
        $balance = $this->walletService->deposit($user, $validated['amount']);
        return format_response(true, __('Deposited successfully'), ['balance' => $balance]);
    }
}

