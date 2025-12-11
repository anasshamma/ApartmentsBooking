<?php

namespace App\Http\Controllers;

use App\Models\Apartment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;



class ApartmentController extends Controller
{
    // إنشاء شقة جديدة (لأصحاب الشقق)
    public function store(Request $request)
    {
        $user = $request->user();

        if (!$user->isOwner()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بإضافة شقق'
            ], 403);
        }
        $validator = Validator::make($request->all(), [
            'province' => 'required|string',
            'city' => 'required|string',
            'address' => 'required|string',
            'price_per_night' => 'required|numeric|min:0',
            'bedrooms' => 'required|integer|min:1',
            'bathroom' => 'required|integer|min:1',
            'maxperson' => 'required|integer|min:1',
            'has_wifi' => 'boolean',
            'has_parking' => 'boolean',
            'first_photo' => 'required|image|mimes:jpg,png,gif',
            'second_photo' => 'nullable|image|mimes:jpg,png,gif',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        $firstphotopath = $request->file('first_photo')->store('Apartment_photos', 'public');
        $secondphotopath = null;
        if ($request->hasFile('second_photo')) {
            $secondphotopath = $request->file('second_photo')->store('Apartment_photos', 'public');
        }

        $apartment = Apartment::create([
            'province' => $request->province,
            'city' => $request->city,
            'address' => $request->address,
            'price_per_night' => $request->price_per_night,
            'first_photo' => $firstphotopath,
            'second_photo' => $secondphotopath,
            'bedrooms' => $request->bedrooms,
            'bathroom' => $request->bathroom,
            'maxperson' => $request->maxperson,
            'has_wifi' => $request->has_wifi,
            'has_parking' => $request->has_parking,
            'owner_id' => $user->id
        ]);
        return response()->json([
            'success' => true,
            'message' => 'تم إضافة الشقة بنجاح',
            'apartment' => $apartment
        ], 201);
    }
    //عرض جميع الشقق
    public function index()
    {
        $apartment = Apartment::all();

        return response()->json([

            'success' => true,
            'apartments' => $apartment

        ], 200);
    }
    public function indexfilter(Request $request)
    {
        $user = $request->user();

        if (!$user->istenant()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بفلترة شقق'
            ], 403);
        }

        $query = Apartment::with('owner')->where('is_available', true);

        // التصفية حسب المحافظة
        if ($request->has('province')) {
            $query->where('province', $request->province);
        }

        // التصفية حسب المدينة
        if ($request->has('city')) {
            $query->where('city', $request->city);
        }

        // التصفية حسب السعر
        if ($request->has('min_price')) {
            $query->where('price_per_night', '>=', $request->min_price);
        }

        if ($request->has('max_price')) {
            $query->where('price_per_night', '<=', $request->max_price);
        }

        // التصفية حسب عدد الغرف
        if ($request->has('bedrooms')) {
            $query->where('bedrooms', $request->bedrooms);
        }

        // التصفية حسب الميزات
        if ($request->has('has_wifi')) {
            $query->where('has_wifi', $request->has_wifi);
        }

        if ($request->has('has_parking')) {
            $query->where('has_parking', $request->has_parking);
        }

        $apartments = $query->paginate(12);

        return response()->json([
            'success' => true,
            'apartments' => $apartments
        ]);
    }
}
