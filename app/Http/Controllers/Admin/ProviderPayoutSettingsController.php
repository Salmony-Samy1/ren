<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProviderPayoutSettingsController extends Controller
{
    public function update(Request $request)
    {
        $data = $request->validate([
            'provider_payout_trigger' => 'required|in:automatic_on_completion,manual_admin_approval',
            'escrow_system_user_id' => 'required|exists:users,id',
        ]);

        foreach ($data as $key => $value) {
            set_setting($key, $value);
        }

        return format_response(true, 'Provider payout settings updated');
    }
}

