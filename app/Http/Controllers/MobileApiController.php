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
use App\Models\ComplaintMessage;
use App\Models\ComplaintRate;
class MobileApiController extends Controller
{

    public function directorates()
    {
        $data = Directorate::where('status', 'active')->latest()->get();
        return response()->json([
                'status' => true,
                'data' => $data,
              
        ]);

    }

    public function neighborhood($id)
    {
        $data = Neighborhood::where('directorate_id',$id)->where('status','active')->latest()->get();
        return response()->json([
                'status' => true,
                'data' => $data,
              
        ]);
    }

    public function types()
    {
        $data = Type::latest()->get();
        return response()->json([
                'status' => true,
                'data' => $data,
              
        ]);

    }


    public function levels()
    {
        $data = SpeelLevel::latest()->get();
        return response()->json([
                'status' => true,
                'data' => $data,
              
        ]);

    }

    public function categories()
    {
        $data = Category::latest()->get();
        return response()->json([
                'status' => true,
                'data' => $data,
              
        ]);

    }

    public function complaint_types()
    {
        $data = ComplaintType::latest()->get();
        return response()->json([
                'status' => true,
                'data' => $data,
              
        ]);

    }

    
    public function add_complaint(Request $request)
    {
        $messages = [
            'required' => 'حقل :attribute مطلوب.',
            'image' => 'حقل :attribute يجب أن يكون صورة.',
            'mimes' => 'صيغة :attribute غير مدعومة.',
            'numeric' => 'حقل :attribute يجب أن يكون رقم.',
            'max.file' => 'حجم :attribute كبير جداً.',
        ];

        $attributes = [
            'type_id' => 'تصنيف الشكوي',
            'complaint_type_id' => 'نوع الشكوي',
            'entity_id' => 'الجهة',
            'title' => 'اسم الشكوي',
            'desc' => 'وصف الشكوي',
            'directorate_id' => 'المديرية',
            'neighborhood_id' => 'الحي',
            'address' => 'العنوان',
            'lat' => 'احداثيات الشمال',
            'lng' => 'احداثيات الغرب',
            'speel_level_id' => 'مستوي السرعة',
            'attachments' => 'المرفقات',
        ];

        try {
            $validator = Validator::make($request->all(), [
                'type_id' => 'required|exists:types,id',
                'complaint_type_id' => 'required|exists:complaint_types,id',
                'entity_id' => 'required|exists:entities,id',
                'directorate_id' => 'required|exists:directorates,id',
                'neighborhood_id' => 'required|exists:neighborhoods,id',
                'speel_level_id' => 'required|exists:speel_levels,id',
                'address' => 'required',
                'lat' => 'required',
                'lng' => 'required',
                'title' => 'required|string|max:255',
                'desc' => 'required|string',
                'attachments' => 'nullable|array',
                'attachments.*' => 'file|mimes:jpg,jpeg,png,mp4,mov,avi|max:20480',
            ], $messages, $attributes);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'يوجد أخطاء في البيانات',
                    'errors' => $validator->errors()
                ], 422);
            }

            // إنشاء الشكوى
            $complaint = Complaint::create([
                'citizen_id' => auth('api_citizens')->id(),
                'title' => $request->title,
                'desc' => $request->desc,
                'type_id' => $request->type_id,
                'complaint_type_id' => $request->complaint_type_id,
                'entity_id' => $request->entity_id,
                'directorate_id' => $request->directorate_id,
                'neighborhood_id' => $request->neighborhood_id,
                'speel_level_id' => $request->speel_level_id,
                'address' => $request->address,
                'lat' => $request->lat,
                'lang' => $request->lng,
                'complaint_status_id' => 1,
            ]);

           if ($request->hasFile('attachments')) {

                foreach ($request->file('attachments') as $file) {

                    if (!$file || !$file->isValid()) {
                        continue;
                    }

                    $filename = time() . '_' . $file->getClientOriginalName();

                    $file->move(public_path('complaints'), $filename);

                    $type = str_starts_with($file->getMimeType(), 'video/') ? 'video' : 'image';

                    ComplaintAttachment::create([
                        'complaint_id' => $complaint->id,
                        'file' => $filename,
                        'type' => $type,
                    ]);
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'تم اضافة الشكوى بنجاح',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ غير متوقع',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function my_complaints()
    {
        $data = Complaint::with('attachments')->where('citizen_id',auth('api_citizens')->id())->latest()->paginate(8);
        $data->getCollection()->transform(function ($data) {
             return [
                'id'  => $data->id,
                'complaint_type_name'=> $data->complaint_type_name,
                'complaint_type_id'=> $data->complaint_type_id,
                'type_name'=> $data->type_name,
                'type_id'=> $data->type_id,
                'level_name'=> $data->level_name,
                'level_id'=> $data->speel_level_id,
               'directorate_name'=> $data->directorate_name,
               'directorate_id'=> $data->directorate_id,
               'neighborhood_name'=> $data->neighborhood_name,
               'neighborhood_id'=> $data->neighborhood_id,
               'lat'=> $data->lat,
               'lang'=> $data->lang,
               'address'=> $data->address,
               'title'=> $data->title,
               'desc'=> $data->desc,
               'status_name'=> $data->status_name,
               'status_id'=> $data->complaint_status_id,
               'attachments'        => $data->attachments->map(function($attachment){
                                        return [
                                            'id' => $attachment->id,
                                            'file_url' => $attachment->file_url,
                                            'type' => $attachment->type,
                                        ];
                                    }),
           

            ];
        });
        return response()->json([
                'status' => true,
                'data' => $data,
              
        ]);
    }

   
    
    public function sliders()
    {
        $data = Slider::latest()->get()->map(function ($slider) {
            return [
                'id'    => $slider->id,
                'image' => $slider->image_url,
            ];
        });

        return response()->json([
            'status' => true,
            'data'   => $data,
        ]);
    }


    public function blogs()
    {
        $data = Blog::latest()->paginate(8);
        $data->getCollection()->transform(function ($data) {
             return [
                'id'  => $data->id,
                'title'=> $data->title,
                'desc'=> $data->desc,
                'image_url'=> $data->image_url,
                'category_name'=> $data->category_name,
                'created_at' => optional($item->created_at)->format('d-m-Y'),


            ];
        });
        return response()->json([
                'status' => true,
                'data' => $data,
              
        ]);
    }

    public function blog_details($id)
    {
        $blog = Blog::findOrFail($id);

        $data = [
            'id' => $blog->id,
            'title' => $blog->title,
            'desc' => $blog->desc,
            'image_url' => $blog->image_url,
            'category_name' => $blog->category_name,
            'created_at' => optional($blog->created_at)->format('d-m-Y'),
        ];

        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }


    public function send_message(Request $request)
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
        $citizen = auth('api_citizens')->user();
        ComplaintMessage::create([
            'complaint_id'=>$request->complaint_id,
            'sender_type'=>'citizen',
            'sender_id'    => $citizen->id,
            'sender_name'  => $citizen->name,
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

    public function add_rate_complaint(Request $request)
    {
         $citizen = auth('api_citizens')->user();

        $validator = Validator::make($request->all(), [
            'complaint_id' => 'required|exists:complaints,id',
            'rate' => 'required|integer|min:1|max:5',
           // 'comment' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ]);
        }

        $exists = ComplaintRate::where('complaint_id',$request->complaint_id)
            ->where('citizen_id',$citizen->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'status' => false,
                'message' => 'لقد قمت بتقييم هذه الشكوى مسبقاً'
            ]);
        }

        $rating = ComplaintRate::create([
            'complaint_id' => $request->complaint_id,
            'citizen_id' => $citizen->id,
            'rate' => $request->rate,
          //  'comment' => $request->comment
        ]);

        return response()->json([
            'status' => true,
            'message' => 'تم إضافة التقييم بنجاح',
            'data' => $rating
        ]);
    }

}
