<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use App\Models\Citizen;
use Str;
use DB;

use Illuminate\Support\Carbon;

use Illuminate\Validation\ValidationException;

use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\Directorate;
use App\Models\Neighborhood;
use App\Models\Type;
use App\Models\SpeelLevel;
use App\Models\Category;
use App\Models\ComplaintType;
use App\Models\Complaint;
use Illuminate\Support\Facades\Validator;
use App\Models\Slider;
use App\Models\Blog;
use Illuminate\Support\Facades\File;
use App\Models\ComplaintMessage;
use App\Models\ComplaintStatus;

class UserApiController extends Controller
{

   

    public function complaints(Request $request)
    {
        $user = Auth::guard('api_users')->user();
        $data = Complaint::with('attachments')->where('directorate_id',$user->directorate_id)->where('entity_id',$user->entity_id)
            ->when($request->neighborhood_id, fn ($q, $v) =>
                $q->where('neighborhood_id', $v))

            ->when($request->status_id, fn ($q, $v) =>
                $q->where('complaint_status_id', $v))

            ->when($request->directorate_id, fn ($q, $v) =>
                $q->where('directorate_id', $v))

            ->when($request->level_id, fn ($q, $v) =>
                $q->where('level_id', $v))

            ->when($request->from, fn ($q, $v) =>
                $q->whereDate('created_at', '>=', $v))

            ->when($request->to, fn ($q, $v) =>
                $q->whereDate('created_at', '<=', $v))

            // 🔍 بحث نصي (اختياري)
           ->when($request->search, function ($q, $v) {
                $q->where(function ($qq) use ($v) {
                    $qq->where('title', 'like', "%$v%")
                    ->orWhere('desc', 'like', "%$v%")
                    ->orWhereHas('citizen', function ($q) use ($v) {
                            $q->where('name', 'like', "%$v%");
                    });
                });
            })

            
            ->latest()

            ->paginate(20);

      
        $data->getCollection()->transform(function ($item) {
            return [
                'id' => $item->id,
                'complaint_type_name' => $item->complaint_type_name,
                // 'type_name' => $item->type_name,
                'level_name' => $item->level_name,
                'directorate_name' => $item->directorate_name,
                // 'neighborhood_name' => $item->neighborhood_name,
                'entity_name' => $item->entity_name,
                // 'lat' => $item->lat,
                // 'lang' => $item->lang,
                'address' => $item->address,
                // 'title' => $item->title,
                // 'desc' => $item->desc,
                'status_name' => $item->status_name,
                'status_id' => $item->complaint_status_id,
                'citizen_name' => $item->citizen_name,
                'created_at' => optional($item->created_at)->format('d-m-Y'),

                // 'attachments' => $item->attachments->map(function ($attachment) {
                //     return [
                //         'id' => $attachment->id,
                //         'file_url' => $attachment->file_url,
                //         'type' => $attachment->type,
                //     ];
                // })->values(),
            ];
        });

        return response()->json([
            'status' => true,
            'data' => $data,
        ]);
    }

    public function complaint_details(Request $request,$id)
    {
        $item = Complaint::with([
            'attachments',
            'complaint_type',
            'type',
            'level',
            'directorate',
            'neighborhood',
            'entity',
            'status',
            'citizen'
        ])->findOrFail($id);
        
        $complaintsCount = Complaint::where('citizen_id',$item->citizen_id)->count();
        $data = [
            'id' => $item->id,
            'complaint_type_name' => $item->complaint_type_name,
            'type_name' => $item->type_name,
            'level_name' => $item->level_name,
            'directorate_name' => $item->directorate_name,
            'neighborhood_name' => $item->neighborhood_name,
            'entity_name' => $item->entity_name,
            'lat' => $item->lat,
            'lang' => $item->lang,
            'address' => $item->address,
            'title' => $item->title,
            'desc' => $item->desc,
            'status_name' => $item->status_name,
            'status_id' => $item->complaint_status_id,
            'citizen_name' => $item->citizen_name,
            'citizen_image_url' => $item->citizen_image_url,
            'complaint_count' => $complaintsCount,
            'created_at' => optional($item->created_at)->format('d-m-Y'),

            'attachments' => $item->attachments->map(function ($attachment) {
                return [
                    'id' => $attachment->id,
                    'file_url' => $attachment->file_url,
                    'type' => $attachment->type,
                ];
            })->values(),
        ];

        return response()->json([
            'status' => true,
            'data' => $data,
        ]);
    }

    public function update_complaint_status(Request $request,$id)
    {
        $data = Complaint::findOrFail($id);
        $data->complaint_status_id = $request->status_id;
        $data->save();
          
       
        return response()->json([
            'status' => true,
            'message' => "تم تعديل الحالة",
        ]);
    }

  
    public function user_send_message(Request $request)
    {

     $messages = [
            'image' => 'حقل :attribute يجب أن يكون صورة.',
            'mimes' => 'حقل :attribute يجب أن يكون بصيغة jpg أو jpeg أو png.',
            'max.file' => 'حقل :attribute يجب ألا يتجاوز 2 ميجا.',
            'exists' => 'القسم غير موجود.',
        ];

        $attributes = [
            'message' => 'الرسالة',
            'attachment' => 'الملف',
            'complaint_id' => 'الشكوي',
        ];

        $request->validate([
            'complaint_id'=>'required|exists:complaints,id',
            'message'=>'nullable|string',
            'attachment'=>'nullable|file|max:20480',
        ], $messages, $attributes);

       

        $name = null;
        if ($file = $request->file('attachment')) {
             $name = time() . $file->getClientOriginalName();
            $file->move('messages', $name);
        }
        $user = auth('api_users')->user();
        ComplaintMessage::create([
            'complaint_id'=>$request->complaint_id,
            'sender_type'=>'user',
            'sender_id'    => $user->id,
            'sender_name'  => $user->name,
            'message'=>$request->message,
            'attachment'=>$name
        ]);

        return response()->json([
            'status'=>true,
            'message'=>'تم ارسال الرسالة'
        ]);
    }

    public function complaint_messages($id)
    {
        $messages = ComplaintMessage::where('complaint_id',$id)
            ->orderBy('id')
            ->get()->transform(function ($item) {
             return [
                'id'  => $item->id,
                'complaint_id'=> $item->complaint_id,
                'sender_type'=> $item->sender_type,
                'sender_name'=> $item->sender_name,
                'sender_id'=> $item->sender_id,
                'message'=> $item->message,
                'attachment_url'=> $item->attachment_url,

            ];
        });
          

        return response()->json([
            'status'=>true,
            'data'=>$messages
        ]);
    }



    public function get_citizen_details($id)
    {
        $citizen = Citizen::withCount('complaints')->findOrFail($id);

        $data = [
            'id'  => $citizen->id,
            'name'=> $citizen->name,
            'email'=> $citizen->email,
            'identity_number'=> $citizen->identity_number,
            'phone'=> $citizen->phone,
            'directorate_name'=> $citizen->directorate_name,
            'directorate_id'=> $citizen->directorate_id,
            'neighborhood_name'=> $citizen->neighborhood_name,
            'neighborhood_id'=> $citizen->neighborhood_id,
            'complaint_count' => $citizen->complaints_count,
        ];

        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }
}
