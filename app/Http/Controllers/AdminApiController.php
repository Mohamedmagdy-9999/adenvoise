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
class AdminApiController extends Controller
{

    public function directorates()
    {
        $data = Directorate::latest()->get();
        return response()->json([
                'status' => true,
                'data' => $data,
              
        ]);

    }

    public function neighborhood($id)
    {
        $data = Neighborhood::where('directorate_id',$id)->latest()->get();
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

    public function complaints(Request $request)
    {
        $data = Complaint::with('attachments')
            ->when($request->neighborhood_id, fn ($q, $v) =>
                $q->where('neighborhood_id', $v))

            ->when($request->status_id, fn ($q, $v) =>
                $q->where('status_id', $v))

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
                    ->orWhere('citizen_name', 'like', "%$v%");
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

    public function add_slider(Request $request)
    {
        $messages = [
                'required' => 'حقل :attribute مطلوب.',
                'image' => 'حقل :attribute يجب أن يكون صورة.',
                'mimes' => 'حقل :attribute يجب أن يكون بصيغة jpg أو jpeg أو png.',
               
                'after' => 'حقل :attribute يجب أن يكون بعد تاريخ البداية.',
                'numeric' => 'حقل :attribute يجب أن يكون رقم.',
                // 🔥 رسائل الـ between المخصصة
                'max.file' => 'حقل :attribute يجب ألا يتجاوز 2 ميجا.',
               
            ];
            
            $attributes = [
                'image' => 'الصورة',
              
            ];
            
            $request->validate([
            
                'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
              
            ], $messages, $attributes);

            
            $name = null;
            if ($file = $request->file('image')) {
                $name = time() . $file->getClientOriginalName();
                $file->move('slider', $name);
            }

            $slider = new Slider();
            $slider->image =$name;
            $slider->save();

            return response()->json([
                'message' => 'تم الاضافة',
                'status' => true,
              
            ], 200);
    }

    public function update_slider(Request $request, $id)
    {
        $messages = [
            'required' => 'حقل :attribute مطلوب.',
            'image' => 'حقل :attribute يجب أن يكون صورة.',
            'mimes' => 'حقل :attribute يجب أن يكون بصيغة jpg أو jpeg أو png.',
            'max.file' => 'حقل :attribute يجب ألا يتجاوز 2 ميجا.',
        ];

        $attributes = [
            'image' => 'الصورة',
        ];

        // ✅ الصورة اختيارية في التحديث
        $request->validate([
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ], $messages, $attributes);

        // 🔥 التأكد من وجود السجل
        $slider = Slider::find($id);

        if (!$slider) {
            return response()->json([
                'status' => false,
                'message' => 'السلايدر غير موجود'
            ], 404);
        }

        // ✅ لو في صورة جديدة
        if ($request->hasFile('image')) {

            // 🧹 حذف القديمة لو موجودة
            if ($slider->image && File::exists(public_path('slider/' . $slider->image))) {
                File::delete(public_path('slider/' . $slider->image));
            }

            $file = $request->file('image');
            $name = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('slider'), $name);

            $slider->image = $name;
        }

        $slider->save();

        return response()->json([
            'message' => 'تم التحديث بنجاح',
            'status' => true,
        ], 200);
    }

    public function delete_slider($id)
    {
        $slider = Slider::find($id);

        // ❌ لو مش موجود
        if (!$slider) {
            return response()->json([
                'status' => false,
                'message' => 'السلايدر غير موجود'
            ], 404);
        }

        // 🧹 حذف الصورة من السيرفر
        if ($slider->image && File::exists(public_path('slider/' . $slider->image))) {
            File::delete(public_path('slider/' . $slider->image));
        }

        // 🗑️ حذف من الداتابيز
        $slider->delete();

        return response()->json([
            'status' => true,
            'message' => 'تم الحذف بنجاح'
        ], 200);
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

    public function add_blog(Request $request)
    {
        $messages = [
            'required' => 'حقل :attribute مطلوب.',
            'image' => 'حقل :attribute يجب أن يكون صورة.',
            'mimes' => 'حقل :attribute يجب أن يكون بصيغة jpg أو jpeg أو png.',
            'max.file' => 'حقل :attribute يجب ألا يتجاوز 2 ميجا.',
            'exists' => 'القسم غير موجود.',
        ];

        $attributes = [
            'image' => 'الصورة',
            'title_ar' => 'عنوان المقال العربي',
            'title_en' => 'عنوان المقال الانجليزي',
            'desc_ar' => 'وصف المقال العربي',
            'desc_en' => 'وصف المقال الانجليزي',
            'category_id' => 'القسم',
        ];

        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
            'category_id' => 'required|exists:categories,id',
            'title_ar' => 'required|string|max:255',
            'title_en' => 'required|string|max:255',
            'desc_ar' => 'required',
            'desc_en' => 'required',
        ], $messages, $attributes);

        
        $name = null;
        if ($file = $request->file('image')) {
            $name = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('blog'), $name);
        }

        
        $blog = new Blog();
        $blog->image = $name;
        $blog->category_id = $request->category_id;
        $blog->title_ar = $request->title_ar;
        $blog->title_en = $request->title_en;
        $blog->desc_ar = $request->desc_ar;
        $blog->desc_en = $request->desc_en;
        $blog->save();

        return response()->json([
            'status' => true,
            'message' => 'تم الاضافة بنجاح',
        ], 200);
    }

    public function update_blog(Request $request, $id)
    {
        $messages = [
            'image' => 'حقل :attribute يجب أن يكون صورة.',
            'mimes' => 'حقل :attribute يجب أن يكون بصيغة jpg أو jpeg أو png.',
            'max.file' => 'حقل :attribute يجب ألا يتجاوز 2 ميجا.',
            'exists' => 'القسم غير موجود.',
        ];

        $attributes = [
            'image' => 'الصورة',
            'title_ar' => 'عنوان المقال العربي',
            'title_en' => 'عنوان المقال الانجليزي',
            'desc_ar' => 'وصف المقال العربي',
            'desc_en' => 'وصف المقال الانجليزي',
            'category_id' => 'القسم',
        ];

        $request->validate([
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'category_id' => 'required|exists:categories,id',
            'title_ar' => 'required|string|max:255',
            'title_en' => 'required|string|max:255',
            'desc_ar' => 'required',
            'desc_en' => 'required',
        ], $messages, $attributes);

        
        $blog = Blog::find($id);

        if (!$blog) {
            return response()->json([
                'status' => false,
                'message' => 'المقال غير موجود'
            ], 404);
        }

       
        if ($request->hasFile('image')) {

            
            if ($blog->image && File::exists(public_path('blog/' . $blog->image))) {
                File::delete(public_path('blog/' . $blog->image));
            }

            $file = $request->file('image');
            $name = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('blog'), $name);

            $blog->image = $name;
        }

        $blog->category_id = $request->category_id;
        $blog->title_ar = $request->title_ar;
        $blog->title_en = $request->title_en;
        $blog->desc_ar = $request->desc_ar;
        $blog->desc_en = $request->desc_en;
        $blog->save();

        return response()->json([
            'status' => true,
            'message' => 'تم التحديث بنجاح'
        ]);
    }

    public function delete_blog($id)
    {
        $blog = Blog::find($id);

        if (!$blog) {
            return response()->json([
                'status' => false,
                'message' => 'المقال غير موجود'
            ], 404);
        }

        // حذف الصورة
        if ($blog->image && File::exists(public_path('blog/' . $blog->image))) {
            File::delete(public_path('blog/' . $blog->image));
        }

        // حذف من الداتابيز
        $blog->delete();

        return response()->json([
            'status' => true,
            'message' => 'تم الحذف بنجاح'
        ]);
    }

    public function blogs(Request $request)
    {
        $data = Blog::query()
            

            ->when($request->category_id, fn ($q, $v) =>
                $q->where('category_id', $v))

            ->when($request->from, fn ($q, $v) =>
                $q->whereDate('created_at', '>=', $v))

            ->when($request->to, fn ($q, $v) =>
                $q->whereDate('created_at', '<=', $v))

           
            
            ->latest()

            ->paginate(20);

      
        $data->getCollection()->transform(function ($item) {
             return [
                'id'  => $item->id,
                'title_ar'=> $item->title_ar,
                'title_en'=> $item->title_en,
                'desc_ar'=> $item->desc_ar,
                'desc_en'=> $item->desc_en,
                'image_url'=> $item->image_url,
                'category_name'=> $item->category_name,
                'category_id'=> $item->category_id,
                'created_at' => optional($item->created_at)->format('d-m-Y'),

            ];
        });
        return response()->json([
                'status' => true,
                'data' => $data,
              
        ]);
    }


    public function citizens()
    {
        $data = Citizen::withCount('complaints')->latest()->paginate(8);
        $data->getCollection()->transform(function ($data) {
            return [
                'id'  => $data->id,
                'name'=> $data->name,
                'email'=> $data->email,
                'identity_number'=> $data->identity_number,
                'phone'=> $data->phone,
                'directorate_name'=> $data->directorate_name,
                'directorate_id'=> $data->directorate_id,
                'neighborhood_name'=> $data->neighborhood_name,
                'neighborhood_id'=> $data->neighborhood_id,
                'complaint_count' => $data->complaints_count,

            ];
        });
        return response()->json([
                'status' => true,
                'data' => $data,
              
        ]);
    }

    public function citizen_complaints(Request $request,$id)
    {
        $data = Complaint::where('citizen_id',$id)->with('attachments')->latest()->paginate(20);
           

        $data->getCollection()->transform(function ($item) {
            return [
                'id' => $item->id,
                'complaint_type_name' => $item->complaint_type_name,
                'type_name' => $item->type_name,
                'level_name' => $item->level_name,
                'directorate_name' => $item->directorate_name,
                'neighborhood_name' => $item->neighborhood_name,
                'lat' => $item->lat,
                'lang' => $item->lang,
                'address' => $item->address,
                'title' => $item->title,
                'desc' => $item->desc,
                'status_name' => $item->status_name,
                'status_id' => $item->complaint_status_id,
                'citizen_name' => $item->citizen_name,
                'created_at' => optional($item->created_at)->format('d-m-Y'),

                'attachments' => $item->attachments->map(function ($attachment) {
                    return [
                        'id' => $attachment->id,
                        'file_url' => $attachment->file_url,
                        'type' => $attachment->type,
                    ];
                })->values(),
            ];
        });

        return response()->json([
            'status' => true,
            'data' => $data,
        ]);
    }

    public function admin_send_message(Request $request)
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
        $admin = auth('api_admins')->user();
        ComplaintMessage::create([
            'complaint_id'=>$request->complaint_id,
            'sender_type'=>'admin',
            'sender_id'    => $admin->id,
            'sender_name'  => $admin->name,
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

    public function complaint_status()
    {
        $data = ComplaintStatus::latest()->get();
        return response()->json([
                'status' => true,
                'data' => $data,
              
        ]);

    }

}
