<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Beacon;
use Illuminate\Http\Request;
use App\Models\ClassSession;
use App\Services\AttendanceServices;
use App\Models\AttendanceRecord;
use Carbon\Carbon;
use App\Models\QrToken;

class AttendanceController extends Controller
{
    public function checkInBle(Request $request, AttendanceServices $attendanceServices)
    {
        // TODO: check if the class is cancelled

        $request->validate([
            'timestamp' => 'required|string', // timestamp untuk compare dgn timeframe kelas
            'class_session_id' => 'required|integer',
            'uuid' => 'required|string', // beacon punye uuid untuk make sure checkin kelas yg betul gitu
            'rssi' => 'required|integer', // double check rssi dia beacon nk elak fraud
        ]);

        // search class session with room and beacons
        $class_session = ClassSession::with('room.beacons')->findOrFail($request->class_session_id);

        // 1. Try to grab the beacon from the room in one step
        $scannedBeacon = $class_session->room->beacons->firstWhere('uuid', $request->uuid);

        // check beacon if it suppose to be in the room
        if (! $scannedBeacon) {  // check beacon
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid beacon scanned for this classroom',
            ], 403);
        }

        // check the RSSI distance
        if ($request->rssi < $scannedBeacon->rssi) { // check rssi
            return response()->json([
                'status' => 'error',
                'message' => 'Signal too weak',
            ], 400);
        }

        // check if user already checked in
        if ($class_session->attendanceRecords()->where('user_id', $request->user()->id)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'You have already checked in.',
            ], 409);
        }

        if ($class_session->is_cancelled)
        {
            return response()->json([
                'status' => 'error',
                'message' => 'Class is cancelled',
            ], 409);
        }

        // classify arrival status using the timestamp
        $arrivalStatus = $attendanceServices->classifyArrival($class_session, $request->timestamp);
        if ($arrivalStatus === 'invalid') // if timestamp is invalid because API called after the class ended
        {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid timestamp',
            ], 400);
        }

        // calculate xp
        $xp = $attendanceServices->calculateXp($arrivalStatus);

        // check in time from request
        $check_in_time = Carbon::createFromTimestamp($request->timestamp)->toDateTimeString();

        // create record for attendance record table
        $attendanceRecord = new AttendanceRecord();
        $attendanceRecord->user_id = $request->user()->id;
        $attendanceRecord->session_id = $class_session->id;
        $attendanceRecord->check_in_time = $check_in_time;
        $attendanceRecord->status = $arrivalStatus;
        $attendanceRecord->checkin_method = 'ble';
        $attendanceRecord->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Successfully checked in.',
            'data' => [
                'attendance_status' => $arrivalStatus,
                'xp_earned' => $xp,
                'check_in_time' => $check_in_time,
            ]
        ]);
    }

    public function checkInQr(Request $request, AttendanceServices $attendanceServices)
    {
        $request->validate([
            'timestamp' => 'required|string', // timestamp untuk compare dgn timeframe kelas
            'class_session_id' => 'required|integer',
            'token' => 'required|string', // qr punye token untuk make sure checkin kelas yg betul gitu
        ]);

        // check if qr token is valid
        $class_session = ClassSession::findOrFail($request->class_session_id);
        $validationResult = $attendanceServices->checkInValidationQr($class_session, $request->timestamp, $request->token, $request->user());
        if (! $validationResult['status']) // if not valid then return error
        {
            return response()->json([
                'status' => 'error',
                'message' => $validationResult['message'],
            ], $validationResult['code']);
        }

        $arrivalStatus = $attendanceServices->classifyArrival($class_session, $request->timestamp);
        if ($arrivalStatus === 'invalid') // if timestamp is invalid because API called after the class ended
        {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid timestamp',
            ], 400);
        }

        // calculate xp
        $xp = $attendanceServices->calculateXp($arrivalStatus);

        // check in time from request
        $check_in_time = Carbon::createFromTimestamp($request->timestamp)->toDateTimeString();

        // create record for attendance record table
        $attendanceRecord = new AttendanceRecord();
        $attendanceRecord->user_id = $request->user()->id;
        $attendanceRecord->session_id = $class_session->id;
        $attendanceRecord->check_in_time = $check_in_time;
        $attendanceRecord->status = $arrivalStatus;
        $attendanceRecord->checkin_method = 'qr';
        $attendanceRecord->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Successfully checked in.',
            'data' => [
                'attendance_status' => $arrivalStatus,
                'xp_earned' => $xp,
                'check_in_time' => $check_in_time,
            ]
        ]);
    }
}
