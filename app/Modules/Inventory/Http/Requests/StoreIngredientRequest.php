<?php
namespace App\Modules\Inventory\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class StoreIngredientRequest extends FormRequest
{
 public function authorize(): bool { return true; }
 public function rules(): array { $restaurantId = (int) $this->route('branch')->restaurant_id; $skuRule = Rule::unique('ingredients','sku')->where('restaurant_id',$restaurantId)->whereNull('deleted_at'); if ($this->route('ingredient')) { $skuRule->ignore($this->route('ingredient')); } return [
  'name' => ['required','string','max:150'], 'base_unit_id' => ['required','integer',Rule::exists('measurement_units','id')->where('restaurant_id',$restaurantId)],
  'sku' => ['nullable','string','max:80',$skuRule],
  'description' => ['nullable','string','max:5000'], 'reorder_level' => ['nullable','numeric','min:0','max:999999999'], 'is_active' => ['sometimes','boolean'],
 ]; }
}
