<?php

namespace App\Http\Controllers\BackEnd\ClientService;

use App\Http\Controllers\Controller;
use App\Http\Helpers\UploadFile;
use App\Http\Requests\ClientService\ServiceStoreRequest;
use App\Http\Requests\ClientService\ServiceUpdateRequest;
use App\Models\Admin;
use App\Models\BasicSettings\Basic;
use App\Models\BasicSettings\BasicExtends;
use App\Models\ClientService\Form;
use App\Models\ClientService\Service;
use App\Models\ClientService\ServiceCategory;
use App\Models\ClientService\ServiceContent;
use App\Models\Language;
use App\Models\Membership;
use App\Models\Package;
use App\Models\Seller;
use App\Rules\ImageMimeTypeRule;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Mews\Purifier\Facades\Purifier;

class ServiceController extends Controller
{
  public function settings(Request $request)
  {
    $language = Language::query()->where('code', '=', $request->language)->firstOrFail();
    $service_settings = Basic::select('is_service', 'tax', 'profit_percentage', 'chat_max_file')->first();
    return view('backend.client-service.service.settings', compact('language', 'service_settings'));
  }
  public function settingsUpdate(Request $request)
  {
    $rules = [
      'tax' => 'required',
      'profit_percentage' => 'required',
      'chat_max_file' => 'required',
    ];
    $messages = [
      'chat_max_file.required' => 'Max file upload in chat box feild is required',
      'profit_percentage.required' => 'Profit percentage field is required'
    ];
    $validator = Validator::make($request->all(), $rules, $messages);
    if ($validator->fails()) {
      return response()->json([
        'errors' => $validator->getMessageBag()
      ], 400);
    }
    $service_settings = Basic::first();
    $service_settings->update([
      'tax' => $request->tax,
      'profit_percentage' => $request->profit_percentage,
      'chat_max_file' => $request->chat_max_file
    ]);
    Session::flash('success', 'Service settings update successfully!');

    return Response::json(['status' => 'success'], 200);
  }
  /**
   * Display a listing of the resource.
   *
   * @return \Illuminate\Http\Response
   */
  public function index(Request $request)
  {
    $seller = $title = null;
    if ($request->filled('seller')) {
      $seller = $request->seller;
    }
    if ($request->filled('title')) {
      $title = $request->title;
    }
    $language = Language::query()->where('code', '=', $request->language)->firstOrFail();

    $information['services'] = Service::query()->join('service_contents', 'services.id', '=', 'service_contents.service_id')
      ->join('service_categories', 'service_categories.id', '=', 'service_contents.service_category_id')
      ->when($seller, function ($query) use ($seller) {
        if ($seller == 'admin') {
          $seller_id = 0;
        } else {
          $seller_id = $seller;
        }
        return $query->where('services.seller_id', '=', $seller_id);
      })
      ->when($title, function ($query) use ($title) {
        return $query->where('service_contents.title', 'like', '%' . $title . '%');
      })
      ->where('service_contents.language_id', '=', $language->id)
      ->select('services.id', 'services.seller_id', 'service_contents.title', 'service_contents.slug', 'service_categories.name as categoryName', 'services.is_featured', 'services.quote_btn_status')
      ->orderByDesc('services.id')
      ->paginate(10);
    $information['sellers'] = Seller::select('id', 'username')->where('id', '!=', 0)->get();
    $information['langs'] = Language::all();

    return view('backend.client-service.service.index', $information);
  }

  /**
   * Show the form for creating a new resource.
   *
   * @return \Illuminate\Http\Response
   */
  public function create()
  {
    $languages = Language::where('code', '!=', 'ar')->get();

    $languages->map(function ($language) {
      $language['forms'] = $language->form()->where('seller_id', null)->orderByDesc('id')->get();

      $language['categories'] = $language->serviceCategory()->where('status', 1)->orderByDesc('id')->get();
    });

    $information['languages'] = $languages;
    $information['sellers'] = Seller::where('id', '!=', 0)->get();

    $information['currencyInfo'] = $this->getCurrencyInfo();

    return view('backend.client-service.service.create', $information);
  }

  public function get_form(Request $request)
  {
    if ($request->id != null && $request->id != 0) {
      $data = Form::where([['seller_id', $request->id], ['language_id', $request->lang_id]])->get();
      return $data;
    } elseif ($request->id == 0) {
      $data = Form::where([['seller_id', null], ['language_id', $request->lang_id]])->get();
      return $data;
    } else {
      $data = Form::where([['seller_id', null], ['language_id', $request->lang_id]])->get();
      return $data;
    }
  }

  /**
   * Store a new slider image in storage.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return \Illuminate\Http\Response
   */
  public function uploadImage(Request $request)
  {
    $rule = [
      'slider_image' => new ImageMimeTypeRule()
    ];

    $validator = Validator::make($request->all(), $rule);

    if ($validator->fails()) {
      return Response::json([
        'error' => $validator->getMessageBag()
      ], 400);
    }

    $imageName = UploadFile::store('./assets/img/services/slider-images/', $request->file('slider_image'));

    return Response::json(['uniqueName' => $imageName], 200);
  }

  /**
   * Remove a slider image from storage.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return \Illuminate\Http\Response
   */
  public function removeImage(Request $request)
  {
    $img = $request['imageName'];

    try {
      unlink('assets/img/services/slider-images/' . $img);

      return Response::json(['success' => 'The image has been deleted.'], 200);
    } catch (Exception $e) {
      return Response::json(['error' => 'Something went wrong!'], 400);
    }
  }

  /**
   * Get subcategory of selected category.
   *
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function getSubcategory($id)
  {
    try {
      $category = ServiceCategory::query()->findOrFail($id);

      $subcategories = $category->subcategory()->where('status', 1)->orderByDesc('id')->get();

      return response()->json(['successData' => $subcategories], 200);
    } catch (Exception $e) {
      return response()->json(['errorData' => 'Something went wrong!'], 400);
    }
  }

  /**
   * Store a newly created resource in storage.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return \Illuminate\Http\Response
   */
  public function store(ServiceStoreRequest $request)
  {
    //check admin's membership available or not ?
    $admin_package_check = Membership::where('seller_id', 0)->first();
    if (!$admin_package_check) {

      // $admin_pacakge = Package::first();
      $admin_pacakge = new Package();
      $admin_pacakge->id = 999999;
      $admin_pacakge->price = 0;
      $admin_pacakge->term = 'lifetime';
      $admin_pacakge->save();

      Membership::create([
        'price' => $admin_pacakge->price,
        'status' => 1,
        'package_id' => $admin_pacakge->id,
        'seller_id' => 0,
        'start_date' => Carbon::now(),
        'expire_date' => Carbon::now()->addDays(99999),
      ]);
    }
    //check dummy admin's exist or not in sellers table
    $admin_seller_check = Seller::where('id', 0)->first();
    $admin = Admin::first();
    if (empty($admin_seller_check)) {
      $admin_seller = new Seller();
      $admin_seller->id = 0;
      $admin_seller->email = $admin->email;
      $admin_seller->recipient_mail = $admin->email;
      $admin_seller->username = $admin->username;
      $admin_seller->status = 1;
      $admin_seller->save();
      $admin_seller->id = 0;
      $admin_seller->save();
    }

    // store thumbnail image in storage
    $thumbnailImage = UploadFile::store('./assets/img/services/thumbnail-images/', $request->file('thumbnail_image'));

    //Video Link format 
    $link = NULL;
    if ($request->filled('video_preview_link')) {
      $link = $request->video_preview_link;
      if (strpos($link, '&') != 0) {
        $link = substr($link, 0, strpos($link, '&'));
      }
    }
    $sliderArr = [];
    foreach ($request['slider_images'] as $image) {
      if (file_exists(public_path('assets/img/services/slider-images/' . $image))) {
        array_push($sliderArr, $image);
      }
    }
    //End video Link store
    $service = Service::query()->create($request->except('thumbnail_image', 'slider_images', 'video_preview_link') + [
      'thumbnail_image' => $thumbnailImage,
      'slider_images' => json_encode($sliderArr),
      'video_preview_link' => $link
    ]);

    $languages = Language::where('code', '!=', 'ar')->get();

    foreach ($languages as $language) {
      $serviceContent = new ServiceContent();
      $serviceContent->language_id = $language->id;
      $serviceContent->service_category_id = $request[$language->code . '_category_id'];
      $serviceContent->service_subcategory_id = !empty($request[$language->code . '_subcategory_id']) ? $request[$language->code . '_subcategory_id'] : NULL;
      $serviceContent->service_id = $service->id;
      $serviceContent->form_id = $request[$language->code . '_form_id'];
      $serviceContent->title = $request[$language->code . '_title'];
      $serviceContent->slug = createSlug($request[$language->code . '_title']);
      $serviceContent->description = Purifier::clean($request[$language->code . '_description'], 'youtube');
      $serviceContent->tags = $request[$language->code . '_tags'];
      $serviceContent->skills = $request[$language->code . '_skills'] != null ? json_encode($request[$language->code . '_skills']) : null;
      $serviceContent->meta_keywords = $request[$language->code . '_meta_keywords'];
      $serviceContent->meta_description = $request[$language->code . '_meta_description'];
      $serviceContent->save();
    }

    // Get service details for notifications
    $serviceName = $request['en_title'] ?? 'New Service';
    $seller = Seller::find($service->seller_id);
    $sellerName = $seller ? $seller->username : 'Admin';

    // Prepare notification data
    $notificationData = [
      'service_id' => $service->id,
      'service_name' => $serviceName,
      'seller_id' => $service->seller_id,
      'seller_name' => $sellerName,
      'is_featured' => $service->is_featured,
      'status' => $service->status,
      'created_at' => $service->created_at,
    ];

    // Notify all admins about new service
    $admins = Admin::all();
    foreach ($admins as $admin) {
      $admin->notify(new \App\Notifications\ServiceNotification([
        'title' => 'New Service Created',
        'message' => "New service '{$serviceName}' has been created by {$sellerName}",
        'url' => route('admin.service_management.services'),
        'icon' => 'fas fa-plus-circle',
        'extra' => $notificationData,
      ]));
    }

    // Notify seller about service creation (if not admin)
    if ($service->seller_id && $service->seller_id != 0) {
      $seller->notify(new \App\Notifications\ServiceNotification([
        'title' => 'Service Created Successfully',
        'message' => "Your service '{$serviceName}' has been created successfully",
        'url' => route('seller.service_management.services'),
        'icon' => 'fas fa-check-circle',
        'extra' => $notificationData,
      ]));
    }

    $request->session()->flash('success', 'New service added successfully!');

    return Response::json(['status' => 'success'], 200);
  }

  /**
   * Update the 'featured' status of a specified resource.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function updateFeaturedStatus(Request $request, $id)
  {
    $service = Service::query()->find($id);

    if ($request['is_featured'] == 'yes') {
      $service->update([
        'is_featured' => 'yes'
      ]);

      $request->session()->flash('success', 'Service featured successfully!');
    } else {
      $service->update([
        'is_featured' => 'no'
      ]);

      $request->session()->flash('success', 'Service unfeatured successfully!');
    }

    return redirect()->back();
  }

  /**
   * Show the form for editing the specified resource.
   *
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function edit($id)
  {
    $service = Service::query()->findOrFail($id);
    $information['service'] = $service;

    // get all the languages from db
    $languages = Language::where('code', '!=', 'ar')->get();

    $languages->map(function ($language) use ($service) {
      // get service content information of each language from db
      $language['serviceData'] = $language->serviceContent()->where('service_id', $service->id)->first();

      // get all the forms of each language from db
      $language['forms'] = $language->form()->orderByDesc('id')->get();

      // get all the categories of each language from db
      $language['categories'] = $language->serviceCategory()->where('status', 1)->orderByDesc('id')->get();
    });

    $information['sellers'] = Seller::where('id', '!=', 0)->get();

    $information['languages'] = $languages;

    return view('backend.client-service.service.edit', $information);
  }

  /**
   * Remove 'stored' slider image form storage.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return \Illuminate\Http\Response
   */
  public function detachImage(Request $request)
  {
    $id = $request['id'];
    $key = $request['key'];

    $service = Service::query()->find($id);

    $sliderImages = json_decode($service->slider_images);

    if (count($sliderImages) == 1) {
      return Response::json(['message' => 'Sorry, the last image cannot be delete.'], 400);
    } else {
      $image = $sliderImages[$key];

      @unlink(public_path('assets/img/services/slider-images/' . $image));

      array_splice($sliderImages, $key, 1);

      $service->update([
        'slider_images' => json_encode($sliderImages)
      ]);

      return Response::json(['message' => 'Slider image removed successfully!'], 200);
    }
  }

  /**
   * Update the specified resource in storage.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function update(ServiceUpdateRequest $request, $id)
  {
    $service = Service::query()->find($id);

    // merge slider images with existing images if request has new slider image
    if ($request->filled('slider_images')) {
      $prevImages = json_decode($service->slider_images);
      $newImages = $request['slider_images'];
      $imgArr = array_merge($prevImages, $newImages);
    }

    // store thumbnail image in storage
    if ($request->hasFile('thumbnail_image')) {
      $newImage = $request->file('thumbnail_image');
      $oldImage = $service->thumbnail_image;
      $thumbnailImage = UploadFile::update('./assets/img/services/thumbnail-images/', $newImage, $oldImage);
    }
    //Video Link format 
    $link = NULL;
    if ($request->filled('video_preview_link')) {
      $link = $request->video_preview_link;
      if (strpos($link, '&') != 0) {
        $link = substr($link, 0, strpos($link, '&'));
      }
    }
    //End video Link store


    // update data in db
    $service->update($request->except('thumbnail_image', 'slider_images', 'video_preview_link') + [
      'thumbnail_image' => $request->hasFile('thumbnail_image') ? $thumbnailImage : $service->thumbnail_image,
      'slider_images' => isset($imgArr) ? json_encode($imgArr) : $service->slider_images,
      'video_preview_link' => $link
    ]);

    $languages = Language::where('code', '!=', 'ar')->get();

    foreach ($languages as $language) {
      $serviceContent = ServiceContent::query()->where('service_id', '=', $id)
        ->where('language_id', '=', $language->id)
        ->first();
      if (empty($serviceContent)) {
        $serviceContent = new ServiceContent();
      }

      $serviceContent->service_category_id = $request[$language->code . '_category_id'];
      $serviceContent->service_subcategory_id = !empty($request[$language->code . '_subcategory_id']) ? $request[$language->code . '_subcategory_id'] : NULL;
      $serviceContent->form_id = $request[$language->code . '_form_id'];
      $serviceContent->title = $request[$language->code . '_title'];
      $serviceContent->slug = createSlug($request[$language->code . '_title']);
      $serviceContent->description = Purifier::clean($request[$language->code . '_description'], 'youtube');
      $serviceContent->tags = $request[$language->code . '_tags'];
      $serviceContent->skills = $request[$language->code . '_skills'] != null ? json_encode($request[$language->code . '_skills']) : null;
      $serviceContent->meta_keywords = $request[$language->code . '_meta_keywords'];
      $serviceContent->meta_description = $request[$language->code . '_meta_description'];
      $serviceContent->save();
    }

    // Get service details for notifications
    $serviceName = $request['en_title'] ?? 'Service';
    $seller = Seller::find($service->seller_id);
    $sellerName = $seller ? $seller->username : 'Admin';

    // Prepare notification data
    $notificationData = [
      'service_id' => $service->id,
      'service_name' => $serviceName,
      'seller_id' => $service->seller_id,
      'seller_name' => $sellerName,
      'is_featured' => $service->is_featured,
      'status' => $service->status,
      'updated_at' => $service->updated_at,
    ];

    // Notify all admins about service update
    $admins = Admin::all();
    foreach ($admins as $admin) {
      $admin->notify(new \App\Notifications\ServiceNotification([
        'title' => 'Service Updated',
        'message' => "Service '{$serviceName}' has been updated by {$sellerName}",
        'url' => route('admin.service_management.services'),
        'icon' => 'fas fa-edit',
        'extra' => $notificationData,
      ]));
    }

    // Notify seller about service update (if not admin)
    if ($service->seller_id && $service->seller_id != 0) {
      $seller->notify(new \App\Notifications\ServiceNotification([
        'title' => 'Service Updated Successfully',
        'message' => "Your service '{$serviceName}' has been updated successfully",
        'url' => route('seller.service_management.services'),
        'icon' => 'fas fa-edit',
        'extra' => $notificationData,
      ]));
    }

    $request->session()->flash('success', 'Service updated successfully!');

    return Response::json(['status' => 'success'], 200);
  }

  /**
   * Remove the specified resource from storage.
   *
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function destroy($id)
  {
    // Get service details before deletion for notifications
    $service = Service::find($id);
    if ($service) {
      $serviceName = $service->content()->where('language_id', 1)->pluck('title')->first() ?? 'Service';
      $seller = Seller::find($service->seller_id);
      $sellerName = $seller ? $seller->username : 'Admin';

      // Prepare notification data
      $notificationData = [
        'service_id' => $service->id,
        'service_name' => $serviceName,
        'seller_id' => $service->seller_id,
        'seller_name' => $sellerName,
        'deleted_at' => now(),
      ];

      // Notify all admins about service deletion
      $admins = Admin::all();
      foreach ($admins as $admin) {
        $admin->notify(new \App\Notifications\ServiceNotification([
          'title' => 'Service Deleted',
          'message' => "Service '{$serviceName}' has been deleted by {$sellerName}",
          'url' => route('admin.service_management.services'),
          'icon' => 'fas fa-trash',
          'extra' => $notificationData,
        ]));
      }

      // Notify seller about service deletion (if not admin)
      if ($service->seller_id && $service->seller_id != 0 && $seller) {
        $seller->notify(new \App\Notifications\ServiceNotification([
          'title' => 'Service Deleted',
          'message' => "Your service '{$serviceName}' has been deleted",
          'url' => route('seller.service_management.services'),
          'icon' => 'fas fa-trash',
          'extra' => $notificationData,
        ]));
      }
    }

    $this->deleteService($id);

    return redirect()->back()->with('success', 'Service deleted successfully!');
  }

  /**
   * Remove the selected or all resources from storage.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return \Illuminate\Http\Response
   */
  public function bulkDestroy(Request $request)
  {
    $ids = $request->ids;

    foreach ($ids as $id) {
      $this->deleteService($id);
    }

    $request->session()->flash('success', 'Services deleted successfully!');

    return Response::json(['status' => 'success'], 200);
  }

  // service deletion code
  public function deleteService($id)
  {
    $service = Service::query()->find($id);

    // delete the thumbnail image
    @unlink(public_path('assets/img/services/thumbnail-images/' . $service->thumbnail_image));

    // delete the slider images
    $sliderImages = json_decode($service->slider_images);

    foreach ($sliderImages as $sliderImage) {
      @unlink(public_path('assets/img/services/slider-images/' . $sliderImage));
    }

    // delete all the service-contents
    $serviceContents = $service->content()->get();

    foreach ($serviceContents as $serviceContent) {
      $serviceContent->delete();
    }

    // delete all the packages of this service
    $packages = $service->package()->get();

    if (count($packages) > 0) {
      foreach ($packages as $package) {
        $package->delete();
      }
    }

    // delete all the addons of this service
    $addons = $service->addon()->get();

    if (count($addons) > 0) {
      foreach ($addons as $addon) {
        $addon->delete();
      }
    }

    // delete all the faqs of this service
    $faqs = $service->faq()->get();

    if (count($faqs) > 0) {
      foreach ($faqs as $faq) {
        $faq->delete();
      }
    }

    // delete all the reviews of this service
    $reviews = $service->review()->get();

    if (count($reviews) > 0) {
      foreach ($reviews as $review) {
        $review->delete();
      }
    }

    // delete all the orders of this service
    $orders = $service->order()->get();

    if (count($orders) > 0) {
      foreach ($orders as $order) {
        // Check if this is a customer offer order and handle the relationship
        if ($order->conversation_id && strpos($order->conversation_id, 'customer_offer_') === 0) {
          $offerId = str_replace('customer_offer_', '', $order->conversation_id);
          $customerOffer = \App\Models\CustomerOffer::find($offerId);
          
          if ($customerOffer) {
            // Update the customer offer to remove the order reference
            $customerOffer->update([
              'accepted_order_id' => null,
              'status' => 'expired' // or 'declined' depending on your business logic
            ]);
          }
        }

        // delete zip file which has uploaded by the user
        $informations = json_decode($order->informations);

        if (!is_null($informations)) {
          foreach ($informations as $key => $information) {
            if ($information->type == 8) {
              @unlink(public_path('assets/file/zip-files/' . $information->value));
            }
          }
        }

        // delete order receipt
        @unlink(public_path('assets/img/attachments/service/' . $order->receipt));

        // delete order invoice
        @unlink(public_path('assets/file/invoices/service/' . $order->invoice));

        // delete messages of this service-order
        $messages = $order->message()->get();

        foreach ($messages as $msgInfo) {
          if (!empty($msgInfo->file_name)) {
            @unlink(public_path('assets/file/message-files/' . $msgInfo->file_name));
          }

          $msgInfo->delete();
        }

        $order->delete();
      }
    }

    // delete wishlist records of this service
    $records = $service->wishlist()->get();

    if (count($records) > 0) {
      foreach ($records as $record) {
        $record->delete();
      }
    }

    $service->delete();
  }

  public function popularTags(Request $request)
  {
    $lang = Language::where('code', $request->language)->firstOrFail();
    $lang_id = $lang->id;

    $information['language'] = $lang;
    $information['langs'] = Language::where('code', '!=', 'ar')->get();
    $information['data'] =  BasicExtends::where('language_id', $lang_id)->first();
    if (is_null($information['data'])) {
      $information['data'] = BasicExtends::create([
        'language_id' => $lang_id
      ]);
    }
    return view('backend.client-service.service.popular-tags', $information);
  }
  public function populerTagupdate(Request $request)
  {
    $rules = [
      'language_id' => 'required',
      'popular_tags' => 'required',
    ];

    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
      return Response::json([
        'errors' => $validator->getMessageBag()
      ], 400);
    }

    $lang = Language::where('code', $request->language_id)->first();
    $be = BasicExtends::where('language_id', $lang->id)->first();
    $be->popular_tags = $request->popular_tags;
    $be->save();
    Session::flash('success', 'Populer tags update successfully!');
    return Response::json(['status' => 'success'], 200);
  }
}
