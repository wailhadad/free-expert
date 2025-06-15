<?php

namespace App\Http\Controllers\FrontEnd;

use App\Http\Controllers\Controller;
use App\Models\BasicSettings\Basic;
use App\Models\HomePage\CtaSectionInfo;
use App\Models\HomePage\Partner;
use App\Models\HomePage\Section;
use Exception;
use Illuminate\Support\Facades\DB;

class AboutUsController extends Controller
{
    public function index()
    {
        try {
            $misc = new MiscellaneousController();
            $language = $misc->getLanguage();
            $queryResult['seoInfo'] = $language->seoInfo()->select('meta_keyword_aboutus', 'meta_description_aboutus')->first();
            $queryResult['secInfo'] = Section::query()->first();;
            $queryResult['pageHeading'] = $misc->getPageHeading($language);
            $queryResult['breadcrumb'] = $misc->getBreadcrumb();
            $queryResult['testimonialBgImg'] = Basic::query()->pluck('testimonial_bg_img')->first();
            $queryResult['aboutInfo'] = DB::table('basic_settings')->select('about_section_image', 'about_section_video_link')->first();
            $queryResult['aboutData'] = $language->aboutSection()->first();
            $queryResult['testimonials'] = $language->testimonial()->orderByDesc('id')->get();
            $queryResult['partners'] = Partner::query()->orderByDesc('id')->get();
            $queryResult['ctaSectionInfo'] = CtaSectionInfo::where('language_id', $language->id)->first();
            $queryResult['ctaBgImg'] = Basic::query()->pluck('cta_bg_img')->first();
            return view('frontend.aboutus', $queryResult);
        } catch (Exception $e) {

            abort(404);
        }
    }
}
