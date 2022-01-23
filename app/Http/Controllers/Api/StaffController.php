<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class StaffController extends Controller
{
    public function staff_signup(Request $request){
        $request->validate([
            's_name' => 'required' ,
            'S_ID' => 'required|string|unique:staff' ,
            'ph_number' => 'required|integer' ,
            'email' => 'required|email' ,
            'branch' => 'required|string' ,
            'password' => 'required|string|confirmed' ,
        ]);

        $staff = new Staff([
            's_name' => $request->s_name ,
            'S_ID' => $request->S_ID ,
            'ph_number' => $request->ph_number ,
            'email' => $request->email ,
            'branch' => $request->branch ,
            'password' => bcrypt($request->password) ,
        ]);

        $staff->save();

        return response()->json([
            'data' => $staff,
            'message' => 'Successfully created staff!'
        ], 201);
    }

    public function staff_login(Request $request){
        $request->validate([
            'S_ID' => 'required|string',
            'password' => 'required|string',
        ]);
        $credentials = request(['S_ID', 'password']);
        if(!Auth::guard('staff')->attempt($credentials))
            return response()->json([
                'message' => 'Invalid Staff ID or Password'
            ], 401);

        $staff = Auth::guard('staff')->user();

        $tokenResult = $staff->createToken('Personal Access Token');
        $token = $tokenResult->token;
        $token->expires_at = Carbon::now()->addWeeks(20);
        $token->save();
        return response()->json([
            'staff' => $staff,
            'tokend' => $token,
            'token' => $tokenResult->accessToken,
        ]);
    }


    public function staff(Request $request){
        return response()->json(Auth::guard('api-staff')->user());
    }

    public function staff_reset_password(Request $request){
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

    public function logout_staff(Request $request){
        Auth::guard('api-staff')->user()->token()->revoke();
        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }

}
