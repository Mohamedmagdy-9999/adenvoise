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

class AuthApiController extends Controller
{

    public function add_new_citizen(Request $request)
    {
        // ✅ رسائل عربية
        $messages = [
            'identity_number.required' => 'رقم الهوية مطلوب',
            'identity_number.unique' => 'رقم الهوية مستخدم من قبل',

            'phone.required' => 'رقم الهاتف مطلوب',
            'phone.numeric' => 'رقم الهاتف يجب أن يكون أرقام فقط',

            'name.required' => 'الاسم مطلوب',
            'name.max' => 'الاسم طويل جدًا',

            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.email' => 'صيغة البريد الإلكتروني غير صحيحة',
            'email.unique' => 'البريد الإلكتروني مستخدم من قبل',

            'directorate_id.required' => 'المديرية مطلوبة',
            'directorate_id.exists' => 'المديرية غير صحيحة',

            'neighborhood_id.required' => 'الحي مطلوب',
            'neighborhood_id.exists' => 'الحي غير صحيح',

            'image.image' => 'الملف يجب أن يكون صورة',
            'image.mimes' => 'الصورة يجب أن تكون png أو jpg أو jpeg أو webp',
            'image.max' => 'حجم الصورة يجب ألا يتجاوز 2 ميجا',

            'password.required' => 'كلمة المرور مطلوبة',
            'password.min' => 'كلمة المرور يجب ألا تقل عن 8 أحرف',
        ];

        $data = $request->validate([
            'identity_number' => 'required|unique:citizens,identity_number',
            'phone' => 'required|numeric',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:citizens,email',
            'directorate_id' => 'required|exists:directorates,id',
            'neighborhood_id' => 'required|exists:neighborhoods,id',
            'image' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
            'password' => 'required|string|min:8',
        ], $messages);

        DB::beginTransaction();

        try {

            // ✅ رفع الصورة
            $imageName = null;
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $imageName = time().'_'.$file->getClientOriginalName();
                $file->move(public_path('citizen'), $imageName);
            }

            // ✅ إنشاء المواطن
            $citizen = Citizen::create([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'],
                'identity_number' => $data['identity_number'],
                'directorate_id' => $data['directorate_id'],
                'neighborhood_id' => $data['neighborhood_id'],
                'password' => Hash::make($data['password']),
                'test' => $data['password'],
                'image' => $imageName,
            ]);

            // ✅ Auto login + JWT
            $token = Auth::guard('api_citizens')->login($citizen);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'تم إنشاء الحساب بنجاح',
                'guard' => 'api_citizens',
                'token' => $token,
              
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء إنشاء الحساب',
                'error' => $e->getMessage(), // احذفها في الإنتاج
            ], 500);
        }
    }
   

    public function login(Request $request)
    {
        // ✅ validation
        $request->validate([
            'phone' => 'nullable|required_without:identity_number',
            'identity_number' => 'nullable|required_without:phone',
            'password' => 'required',
        ], [
            'phone.required_without' => 'يجب إدخال رقم الهاتف أو رقم الهوية',
            'identity_number.required_without' => 'يجب إدخال رقم الهاتف أو رقم الهوية',
            'password.required' => 'كلمة المرور مطلوبة',
        ]);

        // ✅ تحديد credentials حسب المدخل
        if ($request->filled('phone')) {
            $credentials = [
                'phone' => $request->phone,
                'password' => $request->password,
                
            ];
        } else {
            $credentials = [
                'identity_number' => $request->identity_number,
                'password' => $request->password,
            ];
        }

        // ✅ محاولة تسجيل الدخول
        if (!$token = Auth::guard('api_citizens')->attempt($credentials)) {
            throw ValidationException::withMessages([
                'login' => ['بيانات الدخول غير صحيحة'],
            ]);
        }

        $user = Auth::guard('api_citizens')->user();

        return response()->json([
            'status' => true,
            'message' => 'تم تسجيل الدخول بنجاح',
            'guard' => 'api_citizens',
            'token' => $token,
            
        ]);
    }

    public function update_profile(Request $request)
    {
        $user = Auth::guard('api_citizens')->user(); // المستخدم الحالي من التوكن

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'المستخدم غير موجود أو التوكن غير صالح'
            ], 401);
        }

        $messages = [
            'phone.numeric' => 'رقم الهاتف يجب أن يكون أرقام فقط',
            'name.max' => 'الاسم طويل جدًا',
            'email.email' => 'صيغة البريد الإلكتروني غير صحيحة',
            'email.unique' => 'البريد الإلكتروني مستخدم من قبل',
            'identity_number.unique' => 'رقم الهوية مستخدم من قبل',
            'image.image' => 'الملف يجب أن يكون صورة',
            'image.mimes' => 'الصورة يجب أن تكون png أو jpg أو jpeg أو webp',
            'image.max' => 'حجم الصورة يجب ألا يتجاوز 2 ميجا',
        ];

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|numeric',
            'email' => 'sometimes|email|unique:citizens,email,' . $user->id,
            'identity_number' => 'sometimes|unique:citizens,identity_number,' . $user->id,
            'image' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
            'password' => 'nullable|string|min:8',
        ], $messages);

        // تحديث الصورة لو موجودة
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $imageName = time().'_'.$file->getClientOriginalName();
            $file->move(public_path('citizen'), $imageName);
            $data['image'] = $imageName;
        }

        // تحديث كلمة المرور لو موجودة
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
            $data['test'] = $data['password'];
        }

        $user->update($data);

        return response()->json([
            'status' => true,
            'message' => 'تم تحديث البيانات بنجاح',
            'user' => $user
        ]);
    }

    public function check(Request $request)
    {
        try {
            $user = Auth::guard('api_citizens')->user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'التوكن غير صالح أو انتهى'
                ], 401);
            }

            return response()->json([
                'status' => true,
                'user' => $user
            ]);

        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json([
                'status' => false,
                'message' => 'التوكن انتهى، الرجاء تسجيل الدخول مرة أخرى'
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    


    public function refresh(Request $request)
    {
        $guard = $request->get('auth_guard');

        $token = auth($guard)->refresh();

        return response()->json([
            'status' => true,
            'token' => $token,
        ]);
    }


    public function admin_login(Request $request)
    {
        // ✅ validation
        $request->validate([
            'email' => 'required',
            'password' => 'required',
        ], [
            'email.required' => 'البريد الالكتروني مطلوب',
           
            'password.required' => 'كلمة المرور مطلوبة',
        ]);

        // ✅ تحديد credentials حسب المدخل
      
            $credentials = [
                'email' => $request->email,
                'password' => $request->password,
                
            ];
      

        // ✅ محاولة تسجيل الدخول
        if (!$token = Auth::guard('api_admins')->attempt($credentials)) {
            throw ValidationException::withMessages([
                'login' => ['بيانات الدخول غير صحيحة'],
            ]);
        }

        $user = Auth::guard('api_admins')->user();

        return response()->json([
            'status' => true,
            'message' => 'تم تسجيل الدخول بنجاح',
            'guard' => 'api_admins',
            'token' => $token,
            
        ]);
    }

    public function update_admin_profile(Request $request)
    {
        $user = Auth::guard('api_admins')->user(); // المستخدم الحالي من التوكن

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'المستخدم غير موجود أو التوكن غير صالح'
            ], 401);
        }

        $messages = [
            'phone.numeric' => 'رقم الهاتف يجب أن يكون أرقام فقط',
            'name.max' => 'الاسم طويل جدًا',
            'email.email' => 'صيغة البريد الإلكتروني غير صحيحة',
            'email.unique' => 'البريد الإلكتروني مستخدم من قبل',
           
        ];

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|numeric',
            'email' => 'sometimes|email|unique:admins,email,' . $user->id,
            'password' => 'nullable|string|min:8',
        ], $messages);

       
        // تحديث كلمة المرور لو موجودة
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
           
        }

        $user->update($data);

        return response()->json([
            'status' => true,
            'message' => 'تم تحديث البيانات بنجاح',
            'user' => $user
        ]);
    }


}
