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
               // 'type_name' => $item->type_name,
                'level_name' => $item->level_name,
                'directorate_name' => $item->directorate_name,
                'neighborhood_name' => $item->neighborhood_name,
               // 'lat' => $item->lat,
               // 'lang' => $item->lang,
                'address' => $item->address,
                //'title' => $item->title,
               // 'desc' => $item->desc,
                'status_name' => $item->status_name,
                'entity_name' => $item->entity_name,
                //'status_id' => $item->complaint_status_id,
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

    public function add_user(Request $request)
    {
        $messages = [
            'required' => 'حقل :attribute مطلوب.',
        ];

        $attributes = [
            'name' => 'الاسم',
            'email' => 'البريد الالكتروني',
            'password' => 'كلمة المرور',
            'directorate_id' => 'المديرية',
            'entity_id' => 'الجهة',
            'phone' => 'الجوال',
        ];

        $request->validate([
            'name' => 'required|string|max:255',
            'directorate_id' => 'required|exists:directorates,id',
            'entity_id' => 'required|exists:entities,id',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required',
            'password' => 'required|string|min:6|confirmed',
        ], $messages, $attributes);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'directorate_id' => $request->directorate_id,
            'entity_id' => $request->entity_id,
            'status' => 'active',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'تم الاضافة بنجاح',
        ]);
    }

    public function users(Request $request)
    {
        $data = User::query()

            ->when($request->entity_id, fn ($q, $v) =>
                $q->where('entity_id', $v))

            ->when($request->directorate_id, fn ($q, $v) =>
                $q->where('directorate_id', $v))

            // فلترة بالحالة
            ->when($request->status, fn ($q, $v) =>
                $q->where('status', $v))

            // البحث بالاسم
            ->when($request->name, function ($q, $v) {
                $q->where('name', 'like', "%$v%");
            })

            ->when($request->from, fn ($q, $v) =>
                $q->whereDate('created_at', '>=', $v))

            ->when($request->to, fn ($q, $v) =>
                $q->whereDate('created_at', '<=', $v))

            ->latest()
            ->paginate(20);

        $data->getCollection()->transform(function ($item) {
            return [
                'id' => $item->id,
                'name'=> $item->name,
                'email'=> $item->email,
                'phone'=> $item->phone,
                'directorate_id'=> $item->directorate_id,
                'directorate_name'=> $item->directorate_name,
                'entity_id'=> $item->entity_id,
                'entity_name'=> $item->entity_name,
                'status'=> $item->status,
                'created_at' => optional($item->created_at)->format('d-m-Y'),
            ];
        });

        return response()->json([
            'status' => true,
            'data' => $data,
        ]);
    }

    public function update_user(Request $request, $id)
    {
        $messages = [
            'required' => 'حقل :attribute مطلوب.',
        ];

        $attributes = [
            'name' => 'الاسم',
            'email' => 'البريد الالكتروني',
            'password' => 'كلمة المرور',
            'directorate_id' => 'المديرية',
            'entity_id' => 'الجهة',
            'phone' => 'الجوال',
        ];

        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'directorate_id' => 'required|exists:directorates,id',
            'entity_id' => 'required|exists:entities,id',
            'email' => 'required|email|unique:users,email,' . $id,
            'phone' => 'required',
            'password' => 'nullable|string|min:6|confirmed',
        ], $messages, $attributes);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'directorate_id' => $request->directorate_id,
            'entity_id' => $request->entity_id,
            'password' => $request->password 
                            ? Hash::make($request->password) 
                            : $user->password,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'تم التعديل بنجاح',
        ]);
    }

    public function toggle_user_status($id)
    {
        $user = User::findOrFail($id);

        $user->status = $user->status == 'active' ? 'notactive' : 'active';

        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'تم تغيير حالة المستخدم',
            'new_status' => $user->status
        ]);
    }
    
    public function cards()
    {
        $total = Complaint::count();
        $new = Complaint::where('complaint_status_id',1)->count();
        $inprogress = Complaint::where('complaint_status_id',2)->count();
        $done = Complaint::where('complaint_status_id',3)->count();
        return response()->json([
            'status' => true,
            'total' => $total,
            'new' => $new,
            'inprogress' => $inprogress,
            'done' => $done,
        ]);
    }

    private function filterByDate($query, $period)
    {
        switch ($period) {
            case '7days':
                return $query->where('created_at', '>=', now()->subDays(7));
            case '1month':
                return $query->where('created_at', '>=', now()->subMonth());
            case '3months':
                return $query->where('created_at', '>=', now()->subMonths(3));
            case '6months':
                return $query->where('created_at', '>=', now()->subMonths(6));
            case '1year':
                return $query->where('created_at', '>=', now()->subYear());
            default:
                return $query->where('created_at', '>=', now()->subDays(7));
        }
    }
    public function complaintsByStatus(Request $request)
    {
        $period = $request->period ?? '7days';

        $complaints = Complaint::query();
        $this->filterByDate($complaints, $period);

        $data = $complaints
            ->selectRaw('complaint_status_id, COUNT(*) as total')
            ->groupBy('complaint_status_id')
            ->with('status')
            ->get()
            ->map(function ($item) {

                $colors = [
                    'جديد' => '#3B82F6',
                    'قيد المعالجة' => '#F59E0B',
                    'متأخرة' => '#EF4444',
                    'تم حلها' => '#22C55E'
                ];

                return [
                    'name' => $item->status->name ?? '',
                    'value' => $item->total,
                    'color' => $colors[$item->status->name] ?? '#999'
                ];
            });

        return response()->json($data);
    }

    public function complaintsByDirectorate(Request $request)
    {
        $period = $request->period ?? '7days';

        $complaints = Complaint::query();
        $this->filterByDate($complaints, $period);

        $data = $complaints
            ->selectRaw('
                directorate_id,
                COUNT(*) as total,
                MIN(lat) as lat,
                MIN(lang) as lng
            ')
            ->groupBy('directorate_id')
            ->with('directorate')
            ->get()
            ->map(function ($item) {

                return [
                    'name' => $item->directorate->name ?? '',
                    'value' => $item->total,
                    'lat' => $item->lat,
                    'lng' => $item->lng,
                ];
            });

        return response()->json($data);
    }

    public function complaintsByClassification(Request $request)
    {
        $period = $request->period ?? '7days';

        $complaints = Complaint::query();
        $this->filterByDate($complaints, $period);

        $data = $complaints
            ->selectRaw('complaint_type_id, COUNT(*) as total')
            ->groupBy('complaint_type_id')
            ->with('complaint_type')
            ->get()
            ->map(function ($item) {

                return [
                    'name' => $item->complaint_type->name ?? '',
                    'value' => $item->total,
                    'color' => sprintf('#%06X', mt_rand(0, 0xFFFFFF))
                ];
            });

        return response()->json($data);
    }

    public function performance(Request $request)
    {
        $period = $request->period ?? '7days';

        $query = Complaint::query();
        $this->filterByDate($query, $period);

        $data = $query
            ->selectRaw('DATE(created_at) as day,
            COUNT(*) as incoming,
            SUM(CASE WHEN complaint_status_id = 4 THEN 1 ELSE 0 END) as resolved')
            ->groupBy('day')
            ->orderBy('day','desc')
            ->get()
            ->map(function ($item) {

                return [
                    'day' => $item->day,
                    'incoming' => $item->incoming,
                    'resolved' => $item->resolved,
                ];
            });

        return response()->json($data);
    }

    public function newcomplaints(Request $request)
    {
        $data = Complaint::latest()->paginate(20);

        $data->getCollection()->transform(function ($item) {
            return [
                'id' => $item->id,
                'complaint_type_name' => $item->complaint_type_name,
                'directorate_name' => $item->directorate_name,
                'entity_name' => $item->entity_name,
                'status_name' => $item->status_name,
                'status_id' => $item->complaint_status_id,
                'created_at' => optional($item->created_at)->format('d-m-Y'),

            ];
        });

        return response()->json([
            'status' => true,
            'data' => $data,
        ]);
    }


    public function communityPressureAndTrendingComplaints()
    {
        $data = Complaint::selectRaw('
                complaint_type_id,
                entity_id,
                directorate_id,
                speel_level_id,
                COUNT(*) as total
            ')
            ->with([
                'complaint_type:id,name_ar,name_en',
                'entity:id,name_ar,name_en',
                'directorate:id,name_ar,name_en',
                'level:id,name_ar,name_en'
            ])
            ->groupBy(
                'complaint_type_id',
                'entity_id',
                'directorate_id',
                'speel_level_id'
            )
            ->orderByDesc('total')
            ->get()
            ->map(function ($item) {

                return [
                    'complaint_type' => $item->complaint_type->name ?? null,
                    'entity' => $item->entity->name ?? null,
                    'directorate' => $item->directorate->name ?? null,
                    'level' => $item->level->name ?? null,
                    'count' => $item->total
                ];
            });

        return response()->json([
            'status' => true,
            'data' => $data
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
