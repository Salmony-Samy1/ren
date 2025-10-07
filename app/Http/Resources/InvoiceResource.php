<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
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
            'invoice_number' => 'INV-' . str_pad($this->id, 6, '0', STR_PAD_LEFT),
            'user_id' => $this->user_id,
            'booking_id' => $this->booking_id,
            'total_amount' => $this->total_amount,
            'tax_amount' => $this->tax_amount,
            'discount_amount' => $this->discount_amount,
            'commission_amount' => $this->commission_amount,
            'provider_amount' => $this->provider_amount,
            'platform_amount' => $this->platform_amount,
            'invoice_type' => $this->invoice_type,
            'status' => $this->status,
            'payment_method' => $this->payment_method,
            'transaction_id' => $this->transaction_id,
            'due_date' => $this->due_date?->format('Y-m-d'),
            'paid_at' => $this->paid_at?->format('Y-m-d H:i:s'),
            'cancelled_at' => $this->cancelled_at?->format('Y-m-d H:i:s'),
            'notes' => $this->notes,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            
            // البيانات المنسقة
            'formatted_total' => $this->formatted_total,
            'formatted_tax' => $this->formatted_tax,
            'formatted_commission' => $this->formatted_commission,
            
            // العلاقات
            'user' => new UserResource($this->whenLoaded('user')),
            'booking' => $this->whenLoaded('booking', function () {
                return [
                    'id' => $this->booking->id,
                    'service' => [
                        'id' => $this->booking->service->id,
                        'name' => $this->booking->service->name,
                        'price' => $this->booking->service->price,
                    ],
                    'start_date' => $this->booking->start_date?->format('Y-m-d'),
                    'end_date' => $this->booking->end_date?->format('Y-m-d'),
                    'status' => $this->booking->status,
                ];
            }),
            'items' => InvoiceItemResource::collection($this->whenLoaded('items')),
        ];

        // إضافة تفاصيل العمولة للمزودين فقط
        if ($this->invoice_type === 'provider' && $this->commission_breakdown) {
            $data['commission_breakdown'] = $this->commission_breakdown;
            $data['commission_breakdown_display'] = $this->commission_breakdown_display;
        }

        // إخفاء تفاصيل العمولة عن العملاء
        if ($this->invoice_type === 'customer') {
            unset($data['commission_amount']);
            unset($data['provider_amount']);
            unset($data['platform_amount']);
            unset($data['commission_breakdown']);
        }

        return $data;
    }
}
