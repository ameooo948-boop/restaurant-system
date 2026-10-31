<?php
namespace App\Modules\Inventory\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class UpdateInventoryCountItemRequest extends FormRequest
{
 public function authorize(): bool { return true; }
 public function rules(): array { return ['counted_qty'=>['required','numeric','min:0','max:999999999'],'note'=>['nullable','string','max:500']]; }
}
