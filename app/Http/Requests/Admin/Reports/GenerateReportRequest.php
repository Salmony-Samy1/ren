<?php

namespace App\Http\Requests\Admin\Reports;

use Illuminate\Foundation\Http\FormRequest;

class GenerateReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $u = auth('api')->user();
        return $u && $u->type === 'admin' && $u->can('reports.view');
    }

    public function rules(): array
    {
        return [
            'type' => ['required','in:revenue,monthly_revenue,daily_revenue,service_type,provider,commission,tax,discount,performance,quick_stats,provider_profitability,trend'],
            'start_date' => ['sometimes','date'],
            'end_date' => ['sometimes','date','after_or_equal:start_date'],
            'year' => ['sometimes','integer','min:2020','max:'.(now()->year+1)],
            'provider_id' => ['sometimes','integer','exists:users,id'],
        ];
    }
}

