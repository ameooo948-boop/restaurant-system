<?php
namespace App\Modules\Inventory\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class SaveRecipeRequest extends FormRequest
{
 public function authorize(): bool { return true; }
 public function rules(): array { return [
  'yield_quantity' => ['required','numeric','gt:0','max:999999999'], 'instructions' => ['nullable','string','max:5000'],
  'items' => ['required','array','min:1','max:100'], 'items.*.ingredient_id' => ['required','integer','distinct','exists:ingredients,id'],
  'items.*.quantity' => ['required','numeric','gt:0','max:999999999'], 'items.*.waste_percent' => ['nullable','numeric','min:0','max:100'],
 ]; }
}
