<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\ClientService\Form;
use App\Models\ClientService\ServiceContent;
use App\Models\Language;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;

class FormController extends Controller
{
    public function index(Request $request)
    {
        $language = Language::query()->where('code', '=', $request->language)->firstOrFail();
        $information['language'] = $language;
        
        // Check for flash message from URL parameter
        if ($request->has('error')) {
            session()->flash('error', urldecode($request->error));
        }
        
        // Get current package limits
        $currentPackage = \App\Http\Helpers\SellerPermissionHelper::currentPackagePermission(Auth::guard('seller')->user()->id);
        $formLimit = $currentPackage ? $currentPackage->number_of_form_add : 0;
        
        // Fetch all forms for the seller with service contents relationship
        $allForms = Form::where('seller_id', Auth::guard('seller')->user()->id)
            ->with(['serviceContents' => function($query) {
                $query->with(['service' => function($serviceQuery) {
                    $serviceQuery->select('id', 'service_status');
                }]);
            }])
            ->orderByDesc('id')
            ->get();
        $totalForms = $allForms->count();
        
        // Determine which forms are within the limit using the helper
        $formsWithinLimit = \App\Http\Helpers\UserPermissionHelper::getSellerFormsWithinLimit(Auth::guard('seller')->user()->id, $formLimit);
        $isPrioritized = $totalForms > $formLimit;
        
        $information['forms'] = $allForms;
        $information['langs'] = Language::where('code', '!=', 'ar')->get();
        $information['formLimit'] = $formLimit;
        $information['totalForms'] = $totalForms;
        $information['formsWithinLimit'] = $formsWithinLimit;
        $information['isPrioritized'] = $isPrioritized;

        return view('seller.form.index', $information);
    }

    public function store(Request $request)
    {
        // Check form limit before creating
        $currentPackage = \App\Http\Helpers\SellerPermissionHelper::currentPackagePermission(Auth::guard('seller')->user()->id);
        $formLimit = $currentPackage ? $currentPackage->number_of_form_add : 0;
        $currentFormCount = Form::where('seller_id', Auth::guard('seller')->user()->id)->count();
        
        if ($formLimit == 0) {
            return Response::json([
                'error' => 'Your current package does not allow form creation.'
            ], 400);
        }
        
        if ($currentFormCount >= $formLimit) {
            return Response::json([
                'error' => 'You have reached the maximum number of forms allowed by your package.'
            ], 400);
        }

        $rules = [
            'language_id' => 'required',
            'name' => 'required'
        ];

        $message = [
            'language_id.required' => 'The language field is required.'
        ];

        $validator = Validator::make($request->all(), $rules, $message);

        if ($validator->fails()) {
            return Response::json([
                'errors' => $validator->getMessageBag()
            ], 400);
        }
        $in = $request->all();
        $in['seller_id'] = Auth::guard('seller')->user()->id;

        Form::query()->create($in);

        $request->session()->flash('success', 'Form added successfully!');

        return Response::json(['status' => 'success'], 200);
    }

    public function update(Request $request)
    {
        $rule = [
            'name' => 'required'
        ];

        $validator = Validator::make($request->all(), $rule);

        if ($validator->fails()) {
            return Response::json([
                'errors' => $validator->getMessageBag()
            ], 400);
        }
        $form = Form::where([['id', $request['id']], ['seller_id', Auth::guard('seller')->user()->id]])->firstOrFail();

        $form->update($request->all());

        $request->session()->flash('success', 'Form updated successfully!');

        return Response::json(['status' => 'success'], 200);
    }

    public function destroy($id, Request $request)
    {
        $form = Form::query()->where([['id', '=', $id], ['seller_id', Auth::guard('seller')->user()->id]])->first();

        $serviceContent = ServiceContent::query()->where('form_id', '=', $form->id)->first();

        if (empty($serviceContent)) {
            $inputFields = $form->input()->get();

            if (count($inputFields) > 0) {
                foreach ($inputFields as $inputField) {
                    $inputField->delete();
                }
            }

            $form->delete();

            $request->session()->flash('success', 'Form deleted successfully!');

            return redirect()->back();
        } else {
            $request->session()->flash('error', 'Sorry, this form cannot be deleted right now. This form is attached with the services. Either you have to delete those services or change the form of those services.');

            return redirect()->back();
        }
    }
}
