<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class StudentAuthController extends Controller
{
    public function login(Request $request)
    {
        // check dulu yg dlm incoming json payload
        $request->validate([
           "student_id" => "required|string",
           "password" => "required|string",
           "device.name" => "",
            "device.push_token" => "required|string",
        ]);

        // astu cari student
        $student = User::where('student_id', $request->student_id)->first();

        // check password kalau xvalid
        if (! $student || Hash::check($request->password, $student->password))
        {
            return response()->json([
                "message" => "Invalid credentials"
            ], 401);
        }

        // save atau un update, dia automatic create record baru kalau nama device tu xde lagi, kalau dh ade update je token tu
        $student->fcmTokens()::updateOrCreate(['device_name' => $request->input('device.name'),
            'device_token' => $request->input('device.push_token')]);

        $deviceName = $request->input('device.name'); // store nama device dari json dlm variable
        $student->tokens()->where('name', $deviceName)->delete(); // astu kalau dh ade dlm sacntum token, delete token tu dulu
        $token = $student->createToken($deviceName)->plainTextToken; // astu baru buat token baru

        return response()->json([
            "token" => $token,
            "user" => $student,
        ]);
    }
}
