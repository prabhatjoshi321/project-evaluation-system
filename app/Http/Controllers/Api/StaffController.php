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

use App\Models\BatchList;
use App\Models\Batch;
use App\Models\Student;
use App\Models\WeeklyReport;
use App\Models\Project;
Use Exception;
use Illuminate\Queue\Console\BatchesTableCommand;

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
        ], 200);
    }

    // Home Page

    // Batch List creation


    public function staff_batch_list_creation(Request $request){
        $request->validate([
            'batch_id' => 'required|regex:/(^([A-Z])(\d+)?$)/u|unique:batch_list',
            'branch' => 'required|string',
        ]);
        $SID = Auth::guard('api-staff')->user()->S_ID;

        $batch_list = new BatchList([
            'batch_id' => $request->batch_id ,
            'branch' => $request->branch ,
            'S_ID' => $SID ,
        ]);
        $batch_list->save();

        return response()->json([
            'message' => 'Successfully created batch'
        ], 201);
    }



    // Batch Creation
    public function staff_check_student_exists(Request $request){
        $request->validate([
            'USN' => 'required',
        ]);
        $data = Student::Where('USN', 'like', '%' . $request->USN . '%')->orderBy('USN')->get();
        $json_decoded = json_decode($data);

        $data_arranged = [];

        foreach ($json_decoded as $item) {
            $data_arranged[] = [
                'USN' => $item->USN,
                'name' => $item->name,
            ];
        }

        return response()->json([
            'data' => $data_arranged
        ], 200);
    }

    public function staff_batch_creation(Request $request){
        $request->validate([
            'batch_id' => 'required|regex:/(^([A-Z])(\d+)?$)/u|exists:batch_list',
            'USN' => 'required|unique:batch|exists:student',
        ]);
        $batch = new Batch([
            'batch_id' => $request->batch_id ,
            'USN' => $request->USN ,
        ]);

        try{
            $batch->save();
        }
        catch(Exception $e){
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }

        return response()->json([
            'message' => 'Successfully inserted student in batch'
        ],);
    }

    // Weekly Report
    public function staff_batch_weekly_report_creation(Request $request){
        $request->validate([
            'batch_id' => 'required|regex:/(^([A-Z])(\d+)?$)/u|exists:batch_list',
            'date' => 'required|date_format:d/m/Y',
            'remarks' => 'required|string',
            'comments' => 'required|string'
        ]);


        $processed_date = Carbon::createFromFormat('d/m/Y', $request->date);
        $day = $processed_date->format('l');
        $week = $processed_date->weekNumberInMonth;
        $date = $processed_date->format('Y-m-d');

        if(WeeklyReport::where([['batch_id', '=', $request->batch_id], ['week', '=', $week]])->first() != null){
            return response()->json([
                'message' => 'Weekly report of the given batch week already exists'
            ],200);
        }

        $weekly_report = new WeeklyReport([
            'batch_id' => $request->batch_id ,
            'day' => $day,
            'week' => $week,
            'date' => $date,
            'remarks' => $request->remarks,
            'comments' => $request->comments,
        ]);
        $weekly_report->save();

        return response()->json([
            'weekly_report' => $weekly_report,
            'message' => 'Successfully created weekly report'
        ],201);
    }

    // Create project
    public function staff_create_project(Request $request){
        $request->validate([
            'batch_id' => 'required|regex:/(^([A-Z])(\d+)?$)/u|exists:batch_list',
            'project_name' => 'required|string',
        ]);
        $SID = Auth::guard('api-staff')->user()->S_ID;

        if(Project::where([['batch_id', '=', $request->batch_id]])->first() != null){
            return response()->json([
                'message' => 'Entry exists for the given batch'
            ],200);
        }

        $project = new Project([
            'batch_id' => $request->batch_id ,
            'project_name' => $request->project_name,
            'S_ID' => $SID,
        ]);
        $project->save();
        return response()->json([
            'weekly_report' => $project,
            'message' => 'Successfully created Project'
        ],201);
    }

    public function staff_get_batches_for_evaluation(Request $request){
        $SID = Auth::guard('api-staff')->user()->S_ID;
        $data = Project::where([['S_ID', '=', $SID]])->get();
        $json_decoded = json_decode($data);
        $batch = [];

        foreach ($json_decoded as $item) {
            $batch[] = [
                'data' => $item
            ];
        }

        $student = [];
        foreach($batch as $item){
            $student[] = [
                'batch' => $item['data']->batch_id,
                'USN' => Batch::where([['batch_id', '=' , $item['data']->batch_id]])->get("USN")
            ];
        }

        $std = [];
        foreach($student as $item){
            $student_data = [];
            foreach($item['USN'] as $usn){
                $student_data[] = Student::where([['USN' , '=' ,  $usn->USN]])->first();

            }
            $std[$item['batch']] = $student_data;
        }


        return response()->json([
            'data' => $std,
        ],200);

    }

}
