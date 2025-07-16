<?php

namespace App\Http\Controllers\FrontEnd;

use App\Models\CustomerBrief;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;

class CustomerBriefController extends Controller
{
    // List all briefs for the authenticated user
    public function index()
    {
        $user = Auth::user();
        $briefs = \App\Models\CustomerBrief::where('user_id', $user->id)->orderByDesc('created_at')->get();
        $breadcrumb = app(\App\Http\Controllers\FrontEnd\MiscellaneousController::class)::getBreadcrumb();
        return view('user.customer-briefs.index', compact('briefs', 'breadcrumb'));
    }

    // Show the form for creating a new brief
    public function create()
    {
        $breadcrumb = app(\App\Http\Controllers\FrontEnd\MiscellaneousController::class)::getBreadcrumb();
        return view('user.customer-briefs.create', compact('breadcrumb'));
    }

    // Store a new brief
    public function store(Request $request)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'delivery_time' => 'required|integer|min:1',
            'tags' => 'required|string',
            'price' => 'nullable|numeric|min:0',
            'request_quote' => 'nullable|boolean',
            'subuser_id' => 'nullable|exists:subusers,id',
            'attachments.*' => 'nullable|file|mimes:pdf,doc,docx,txt,jpg,jpeg,png,gif,zip,rar|max:10240', // 10MB max per file
        ]);
        
        // Handle file uploads
        $attachments = [];
        $attachmentNames = [];
        
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if ($file->isValid()) {
                    $fileName = \App\Http\Helpers\UploadFile::store('./assets/file/customer-briefs/', $file);
                    $attachments[] = $fileName;
                    $attachmentNames[] = $file->getClientOriginalName();
                }
            }
        }
        
        $brief = new \App\Models\CustomerBrief();
        $brief->user_id = $user->id;
        $brief->subuser_id = $validated['subuser_id'] ?? null;
        $brief->title = $validated['title'];
        $brief->description = $validated['description'];
        $brief->delivery_time = $validated['delivery_time'];
        $brief->tags = $validated['tags'];
        $brief->price = $request->input('request_quote') ? null : $validated['price'];
        $brief->request_quote = $request->has('request_quote');
        $brief->attachments = $attachments;
        $brief->attachment_names = $attachmentNames;
        $brief->status = 'active';
        $brief->save();
        
        // Send real-time notifications to matching sellers
        try {
            $notificationService = new \App\Services\NotificationService();
            $notificationService->notifySellersAboutNewBrief($brief);
        } catch (\Exception $e) {
            \Log::error('Failed to send customer brief notifications: ' . $e->getMessage());
        }
        
        return redirect()->route('user.customer-briefs.index')->with('success', 'Customer brief created successfully.');
    }

    // Show the form for editing a brief
    public function edit(CustomerBrief $customerBrief)
    {
        $user = Auth::user();
        if ($customerBrief->user_id !== $user->id) {
            abort(403, 'You can only edit your own briefs.');
        }
        $brief = $customerBrief;
        $breadcrumb = app(\App\Http\Controllers\FrontEnd\MiscellaneousController::class)::getBreadcrumb();
        return view('user.customer-briefs.edit', compact('brief', 'breadcrumb'));
    }

    // Update a brief
    public function update(Request $request, CustomerBrief $customerBrief)
    {
        $user = Auth::user();
        if ($customerBrief->user_id !== $user->id) {
            abort(403, 'You can only update your own briefs.');
        }
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'delivery_time' => 'required|integer|min:1',
            'tags' => 'required|string',
            'price' => 'nullable|numeric|min:0',
            'request_quote' => 'nullable|boolean',
            'subuser_id' => 'nullable|exists:subusers,id',
            'attachments.*' => 'nullable|file|mimes:pdf,doc,docx,txt,jpg,jpeg,png,gif,zip,rar|max:10240', // 10MB max per file
        ]);
        
        // Handle file uploads
        $attachments = $customerBrief->getAttachmentsArray();
        $attachmentNames = $customerBrief->getAttachmentNamesArray();
        
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if ($file->isValid()) {
                    $fileName = \App\Http\Helpers\UploadFile::store('./assets/file/customer-briefs/', $file);
                    $attachments[] = $fileName;
                    $attachmentNames[] = $file->getClientOriginalName();
                }
            }
        }
        
        $customerBrief->subuser_id = $validated['subuser_id'] ?? null;
        $customerBrief->title = $validated['title'];
        $customerBrief->description = $validated['description'];
        $customerBrief->delivery_time = $validated['delivery_time'];
        $customerBrief->tags = $validated['tags'];
        $customerBrief->price = $request->input('request_quote') ? null : $validated['price'];
        $customerBrief->request_quote = $request->has('request_quote');
        $customerBrief->attachments = $attachments;
        $customerBrief->attachment_names = $attachmentNames;
        $customerBrief->save();
        
        return redirect()->route('user.customer-briefs.index')->with('success', 'Customer brief updated successfully.');
    }

    // Delete a brief
    public function destroy(CustomerBrief $customerBrief)
    {
        $user = Auth::user();
        if ($customerBrief->user_id !== $user->id) {
            abort(403, 'You can only delete your own briefs.');
        }
        $customerBrief->delete();
        return redirect()->route('user.customer-briefs.index')->with('success', 'Customer brief deleted successfully.');
    }

    // Show details of a brief
    public function show(CustomerBrief $customerBrief)
    {
        $user = Auth::user();
        if ($customerBrief->user_id !== $user->id) {
            abort(403, 'You can only view your own briefs.');
        }
        $brief = $customerBrief;
        $breadcrumb = app(\App\Http\Controllers\FrontEnd\MiscellaneousController::class)::getBreadcrumb();
        return view('user.customer-briefs.show', compact('brief', 'breadcrumb'));
    }

    // Toggle brief status (activate/close)
    public function toggleStatus(CustomerBrief $customerBrief)
    {
        $user = Auth::user();
        if ($customerBrief->user_id !== $user->id) {
            abort(403, 'You can only modify your own briefs.');
        }
        
        $newStatus = $customerBrief->status === 'active' ? 'closed' : 'active';
        $customerBrief->update(['status' => $newStatus]);
        
        $action = $newStatus === 'active' ? 'activated' : 'closed';
        return redirect()->route('user.customer-briefs.index')->with('success', "Customer brief {$action} successfully.");
    }
} 