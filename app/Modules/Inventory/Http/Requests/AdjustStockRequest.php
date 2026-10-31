<?php
namespace App\Modules\Inventory\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class AdjustStockRequest extends FormRequest
{
 public function authorize(): bool { return true; }
 public function rules(): array { return ['quantity_delta' => ['required','numeric','not_in:0','between:-999999999,999999999'], 'reason' => ['required','string','min:3','max:500']]; }
}
