<?php
namespace App\Modules\Inventory\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class ReceivePurchaseOrderRequest extends FormRequest
{
 public function authorize(): bool { return true; }
 public function rules(): array { return [
  'idempotency_key'=>['required','uuid'],'notes'=>['nullable','string','max:2000'],
  'items'=>['required','array','min:1','max:100'],'items.*.purchase_order_item_id'=>['required','integer','distinct','exists:purchase_order_items,id'],
  'items.*.received_qty'=>['required','numeric','gt:0','max:999999999'],
 ]; }
}
