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
class AdminApiController extends Controller
{

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


}
