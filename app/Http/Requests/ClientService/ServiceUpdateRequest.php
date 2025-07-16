<?php

namespace App\Http\Requests\ClientService;

use App\Models\ClientService\ServiceCategory;
use App\Models\Language;
use App\Rules\ImageMimeTypeRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ServiceUpdateRequest extends FormRequest
{
  /**
   * Determine if the user is authorized to make this request.
   *
   * @return bool
   */
  public function authorize()
  {
    return true;
  }

  /**
   * Get the validation rules that apply to the request.
   *
   * @return array
   */
  public function rules()
  {
      $ruleArray = [
          // thumbnail only if a new file was uploaded
          'thumbnail_image' => $this->hasFile('thumbnail_image')
              ? [
                  'image',
                  new ImageMimeTypeRule(),
                  'mimes:jpeg,png,gif',
                  'max:10248',                          // 10 MB
                  'dimensions:min_width=10,max_width=2048,
                                 min_height=40,max_height=2048',
              ]
              : [],

          'service_status'  => 'required|numeric',
      ];

    $languages = Language::all();

    foreach ($languages as $language) {
      if ($language->is_default) {
        $ruleArray[$language->code . '_title'] = [
          'required',
          'max:255'
        ];
        $ruleArray[$language->code . '_category_id'] = 'required';

        $categoryId = $this[$language->code . '_category_id'];
        $category = ServiceCategory::query()->find($categoryId);

        if (!is_null($category)) {
          $subcategories = $category->subcategory()->where('status', 1)->get();
          $ruleArray[$language->code . '_subcategory_id'] = count($subcategories) > 0 ? 'required' : '';
        }

        $ruleArray[$language->code . '_description'] = 'min:30';
        $ruleArray[$language->code . '_form_id'] = 'nullable';
      } else {
        $ruleArray[$language->code . '_title'] = ['nullable', 'max:255'];
        $ruleArray[$language->code . '_category_id'] = 'nullable';
        $ruleArray[$language->code . '_subcategory_id'] = 'nullable';
        $ruleArray[$language->code . '_description'] = 'nullable';
        $ruleArray[$language->code . '_form_id'] = 'nullable';
      }
    }

    return $ruleArray;
  }

  /**
   * Get the validation messages that apply to the request.
   *
   * @return array
   */
  public function messages()
  {
    $languages = Language::all();

    foreach ($languages as $language) {
      $messageArray[$language->code . '_title.required'] = 'The title field is required for ' . $language->name . ' language.';

      $messageArray[$language->code . '_title.max'] = 'The title field cannot contain more than 255 characters for ' . $language->name . ' language.';

      $messageArray[$language->code . '_title.unique'] = 'The title field must be unique for ' . $language->name . ' language.';

      $messageArray[$language->code . '_category_id.required'] = 'The category field is required for ' . $language->name . ' language.';

      $messageArray[$language->code . '_subcategory_id.required'] = 'The subcategory field is required for ' . $language->name . ' language.';

      $messageArray[$language->code . '_description.min'] = 'The description must be at least 30 characters for ' . $language->name . ' language.';

      $messageArray[$language->code . '_form_id.required'] = 'The form field is required for ' . $language->name . ' language.';
    }

    return $messageArray;
  }
}
