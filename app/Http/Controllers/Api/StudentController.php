<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class StudentController extends Controller
{

    public function student_signup(Request $request){
        $request->validate([
            'name' => 'required',
            'USN' => 'required|string|unique:student',
            'branch' => 'required|string',
            'academic_year' => 'required|integer',
            'password' => 'required|string|confirmed'
        ]);

        $student = new Student([
            'name' => $request->name,
            'USN' => $request->USN,
            'branch' => $request->branch,
            'academic_year' => $request->academic_year,
            'password' => bcrypt($request->password)
        ]);

        $student->save();

        return response()->json([
            'data' => $student,
            'message' => 'Successfully created student!'
        ], 201);
    }

    public function student_login(Request $request){
        $request->validate([
            'USN' => 'required|string',
            'password' => 'required|string',
        ]);
        $credentials = request(['USN', 'password']);
        if(!Auth::guard('student')->attempt($credentials))
            return response()->json([
                'message' => 'Invalid USN or Password'
            ], 401);

        $student = Auth::guard('student')->user();

        $tokenResult = $student->createToken('Personal Access Token');
        $token = $tokenResult->token;
        $token->expires_at = Carbon::now()->addWeeks(20);
        $token->save();
        return response()->json([
            'student' => $student,
            'tokend' => $token,
            'token' => $tokenResult->accessToken,
        ]);
    }

    public function student(Request $request){
        return response()->json(Auth::guard('api-student')->user());
    }

    public function student_reset_password(Request $request){
        $input = $request->all();
        $userid = Auth::guard('api-student')->user()->id;
        $rules = array(
            'old_password' => 'required',
            'new_password' => 'required|min:6',
            'confirm_password' => 'required|same:new_password',
        );
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            $arr = array("status" => 400, "message" => $validator->errors()->first(), "data" => array());
        } else {
            try {
                if ((Hash::check(request('old_password'), Auth::user()->password)) == false) {
                    $arr = array("status" => 400, "message" => "Check your old password.", "data" => array());
                } else if ((Hash::check(request('new_password'), Auth::user()->password)) == true) {
                    $arr = array("status" => 400, "message" => "Please enter a password which is not similar then current password.", "data" => array());
                } else {
                    User::where('id', $userid)->update(['password' => Hash::make($input['new_password'])]);
                    $arr = array("status" => 200, "message" => "Password updated successfully.", "data" => array());
                }
            } catch (\Exception $ex) {
                if (isset($ex->errorInfo[2])) {
                    $msg = $ex->errorInfo[2];
                } else {
                    $msg = $ex->getMessage();
                }
                $arr = array("status" => 400, "message" => $msg, "data" => array());
            }
        }
        return \Response::json($arr);
    }

    public function logout_student(Request $request){
        Auth::guard('api-student')->user()->token()->revoke();
        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }

    // Home Page
    public function student_details(Request $request){
        $USN = Auth::guard('api-student')->user();

        $data = [];

        return response()->json([
            'Details' => $data
        ]);
    }


}
