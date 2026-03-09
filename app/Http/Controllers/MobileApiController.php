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
class MobileApiController extends Controller
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
            'lang' => 'احداثيات الغرب',
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
                'lang' => 'required',
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
                'lang' => $request->lang,
                'complaint_status_id' => 1,
            ]);

           if ($request->hasFile('attachments')) {

                foreach ($request->file('attachments') as $file) {

                    if (!$file || !$file->isValid()) {
                        continue;
                    }

                    $filename = time().'_'.uniqid().'.'.$file->getClientOriginalExtension();

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


            ];
        });
        return response()->json([
                'status' => true,
                'data' => $data,
              
        ]);
    }



}
