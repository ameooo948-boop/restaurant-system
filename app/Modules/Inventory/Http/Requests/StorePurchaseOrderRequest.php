<?php
namespace App\Modules\Inventory\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StorePurchaseOrderRequest extends FormRequest
{
 public function authorize(): bool { return true; }
 public function rules(): array { return [
  'supplier_id'=>['required','integer','exists:suppliers,id'],'notes'=>['nullable','string','max:2000'],
  'items'=>['required','array','min:1','max:100'],'items.*.ingredient_id'=>['required','integer','distinct','exists:ingredients,id'],
  'items.*.quantity_ordered'=>['required','numeric','gt:0','max:999999999'],'items.*.unit_cost'=>['required','numeric','min:0','max:999999999'],
 ]; }
}
