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

use App\Models\Batch;
use App\Models\Project;
use App\Models\Staff;

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
            'USN' => strtoupper($request->USN),
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
            'token' => $tokenResult->accessToken,
        ]);
    }

    public function student(Request $request){
        $student = Auth::guard('api-student')->user();
        $batch = Batch::select('batch_id', 'branch')->Where('USN', '=', $student->USN)->orderBy('batch_id')->get();
        return response()->json([
            'student' => $student,
            'batches' => $batch,
        ]);
    }

    public function student_reset_password(Request $request){
        $input = $request->all();
        $USN = Auth::guard('api-student')->user()->USN;
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
                if ((Hash::check(request('old_password'), Auth::guard('api-student')->user()->password)) == false) {
                    $arr = array("status" => 400, "message" => "Check your old password.", "data" => array());
                } else if ((Hash::check(request('new_password'), Auth::guard('api-student')->user()->password)) == true) {
                    $arr = array("status" => 400, "message" => "Please enter a password which is not similar then current password.", "data" => array());
                } else {
                    Student::where('USN', $USN)->update(['password' => Hash::make($input['new_password'])]);
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

    // batch details
    public function student_get_batches_assigned(Request $request){
        $USN = Auth::guard('api-student')->user()->USN;
        $batch = Batch::where([['USN', '=', $USN]])->first();
        $student_list = Batch::where([['batch_id', '=', $batch->batch_id], ['branch','=',$batch->branch]])->get();
        $data = [];
        foreach($student_list as $item){
            $data [] = [
                'batch_data' => $item,
                'student_detail' => Student::where([['USN', '=', $item->USN]])->first()
            ];
        }
        return response()->json([
            'data' => $data,
        ], 200);
    }

    public function student_get_project_details(Request $request){
        $USN = Auth::guard('api-student')->user()->USN;
        $batch = Batch::where([['USN', '=', $USN]])->first();
        $project = Project::where([['batch_id', '=', $batch->batch_id]])->first();

        $staff = Staff::where([['S_ID','=',$project->S_ID]])->first();

        return response()->json([
            'data' => $project,
            'staff' => $staff,
        ], 200);
    }

    public function student_post_project_file(Request $request){

        $request->validate([
            'file' => 'required|mimes:pdf,ppt,pot,pps,pptx,potx,ppsx,thmx,doc,docx,xls,xlsx',
        ]);

        $USN = Auth::guard('api-student')->user()->USN;
        $batch = Batch::where([['USN', '=', $USN]])->first();
        $project = Project::where([['batch_id', '=', $batch->batch_id]])->first();

        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('public/file');
            $string = str_ireplace("public", "", $path);
            $project->file_link = $string;
        }else{
            return response()->json([
                'data' => $request->hasFile('file'),
                'message' => 'Error. File not recieved.'
            ], 401);
        }
        $project->save();

        return response()->json([
            'data' => $request->hasFile('file'),
            'message' => 'Successfully saved project.'
        ], 201);
    }


}
