<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'invoice_id' => $this->invoice_id,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'total' => $this->total,
            'tax_rate' => $this->tax_rate,
            'tax_amount' => $this->tax_amount,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];

        // إضافة تفاصيل العمولة إذا وجدت
        if ($this->commission_breakdown) {
            $data['commission_breakdown'] = $this->commission_breakdown;
        }

        // تنسيق الأسعار
        $data['formatted_unit_price'] = number_format($this->unit_price, 2) . ' ريال';
        $data['formatted_total'] = number_format($this->total, 2) . ' ريال';
        $data['formatted_tax_amount'] = number_format($this->tax_amount, 2) . ' ريال';

        return $data;
    }
}
