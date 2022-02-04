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
use Illuminate\Support\Facades\DB;

use App\Models\BatchList;
use App\Models\Batch;
use App\Models\Student;
use App\Models\WeeklyReport;
use App\Models\Project;
use App\Models\ProjectEvaluation;
use Exception;
use Illuminate\Queue\Console\BatchesTableCommand;

class StaffController extends Controller
{
    public function staff_signup(Request $request)
    {
        $request->validate([
            's_name' => 'required',
            'S_ID' => 'required|string|unique:staff',
            'ph_number' => 'required|integer',
            'email' => 'required|email',
            'branch' => 'required|string',
            'password' => 'required|string|confirmed',
        ]);

        $staff = new Staff([
            's_name' => $request->s_name,
            'S_ID' => strtoupper($request->S_ID),
            'ph_number' => $request->ph_number,
            'email' => $request->email,
            'branch' => $request->branch,
            'password' => bcrypt($request->password),
        ]);

        $staff->save();

        return response()->json([
            'data' => $staff,
            'message' => 'Successfully created staff!'
        ], 201);
    }

    public function staff_login(Request $request)
    {
        $request->validate([
            'S_ID' => 'required|string',
            'password' => 'required|string',
        ]);
        $credentials = request(['S_ID', 'password']);
        if (!Auth::guard('staff')->attempt($credentials))
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
            'token' => $tokenResult->accessToken,
        ]);
    }


    public function staff_reset_password(Request $request)
    {
        $input = $request->all();
        $userid = Auth::guard('api-staff')->user()->S_ID;
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
                if ((Hash::check(request('old_password'), Auth::guard('api-staff')->user()->password)) == false) {
                    $arr = array("status" => 400, "message" => "Check your old password.", "data" => array());
                } else if ((Hash::check(request('new_password'), Auth::guard('api-staff')->user()->password)) == true) {
                    $arr = array("status" => 400, "message" => "Please enter a password which is not similar then current password.", "data" => array());
                } else {
                    Staff::where('S_ID', $userid)->update(['password' => Hash::make($input['new_password'])]);
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

    public function logout_staff(Request $request)
    {
        Auth::guard('api-staff')->user()->token()->revoke();
        return response()->json([
            'message' => 'Successfully logged out'
        ], 200);
    }

    // Dashboard
    public function staff(Request $request)
    {
        $staff = Auth::guard('api-staff')->user();
        $batch = BatchList::select('batch_id', 'branch')->Where('S_ID', '=', $staff->S_ID)->orderBy('batch_id')->get();
        return response()->json([
            'staff' => $staff,
            'batches' => $batch,
        ]);
    }

    // Batches Assigned
    public function staff_get_batches_assigned(Request $request)
    {
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
        foreach ($batch as $item) {
            $student[] = [
                'batch' => $item['data']->batch_id,
                'USN' => Batch::where([['batch_id', '=', $item['data']->batch_id]])->get("USN")
            ];
        }

        $std = [];
        foreach ($student as $item) {
            $student_data = [];
            foreach ($item['USN'] as $usn) {
                $student_data[] = Student::where([['USN', '=',  $usn->USN]])->first();
            }
            $std[$item['batch']] = $student_data;
        }

        return response()->json([
            'data' => $std,
        ], 200);
    }


    // Projects
    public function staff_get_projects_of_batches(Request $request)
    {
        $SID = Auth::guard('api-staff')->user()->S_ID;

        $projects = Project::where([['S_ID', '=',  $SID]])->get();

        return response()->json([
            'data' => $projects,
        ], 200);
    }

    // Weekly Report
    public function staff_get_weekly_report_dash(Request $request)
    {
        $SID = Auth::guard('api-staff')->user()->S_ID;
        $batch_list = BatchList::where([['S_ID', '=',  $SID]])->get();
        return response()->json([
            'data' => $batch_list,
        ], 200);
    }

    public function staff_get_weekly_report_batch_dates(Request $request)
    {
        // TODO : eradicate the security flaw
        $request->validate([
            'batch_id' => 'required|string',
        ]);
        $dates = WeeklyReport::where([['batch_id', '=',  $request->batch_id]])->orderBy('date', 'ASC')->get();
        if ($dates == null) {
            return response()->json([
                'data' => null,
            ], 200);
        } else {
            return response()->json([
                'data' => $dates,
            ], 200);
        }
    }

    public function staff_get_weekly_report_by_date(Request $request)
    {
        // TODO : eradicate the security flaw
        $request->validate([
            'batch_id' => 'required|string',
            'date' => 'required|string',
        ]);
        $dates = WeeklyReport::where([['batch_id', '=',  $request->batch_id], ['date', '=',  $request->date]])->first();
        if ($dates == null) {
            return response()->json([
                'data' => null,
            ], 200);
        } else {
            return response()->json([
                'data' => $dates,
            ], 200);
        }
    }

    public function staff_get_weekly_report_by_date_delete(Request $request)
    {
        // TODO : eradicate the security flaw
        $request->validate([
            'batch_id' => 'required|string',
            'date' => 'required|string',
        ]);
        $dates = WeeklyReport::where([['batch_id', '=',  $request->batch_id], ['date', '=',  $request->date]])->delete();
        if ($dates == null) {
            return response()->json([
                'data' => null,
            ], 200);
        } else {
            return response()->json([
                'data' => $dates,
            ], 200);
        }
    }

    public function staff_post_weekly_report_creation(Request $request)
    {
        $request->validate([
            'batch_id' => 'required|regex:/(^([A-Z])(\d+)?$)/u|exists:batch_list',
            'date' => 'required',
            'remarks' => 'required|string',
            'comments' => 'required|string'
        ]);


        $processed_date = Carbon::createFromFormat('m/d/Y', $request->date);
        $day = $processed_date->format('l');
        $week = $processed_date->weekNumberInMonth;
        $date = $processed_date->format('Y-m-d');

        if (WeeklyReport::where([['batch_id', '=', $request->batch_id], ['week', '=', $week]])->first() != null) {
            return response()->json([
                'message' => 'Weekly report of the given batch week already exists',
            ], 200);
        }

        $weekly_report = new WeeklyReport([
            'batch_id' => $request->batch_id,
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
        ], 201);
    }

    // Evaluation
    public function staff_get_project_to_evaluate(Request $request)
    {
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
        foreach ($batch as $item) {
            $student[] = [
                'batch' => $item['data']->batch_id,
                'project_data' => $item['data'],
                'USN' => Batch::where([['batch_id', '=', $item['data']->batch_id]])->get("USN"),
            ];
        }
        $temp = [];
        $i = 0;
        foreach ($student as $item) {
            $std = [];
            $student_data = [];
            foreach ($item['USN'] as $usn) {
                $student_data[] = [
                    'student' => Student::where([['USN', '=',  $usn->USN]])->first(),
                    'evaluation' => ProjectEvaluation::where([['USN', '=',  $usn->USN]])->first()
                ];
            }

            $std[] = [
                'batch' => $item['batch'],
                'student' => $student_data,
                'project' => $item['project_data'],
            ];
            $temp[$i] = $std;
            $i = $i + 1;
        }

        return response()->json([
            'data' => $temp,
        ], 200);
    }

    public function staff_evaluate_project(Request $request){
        $request->validate([
            'pid' => 'required',
            'USN' => 'required',
            'marks' => 'required',
        ]);

        $project_evaluation = new ProjectEvaluation([
            'pid' => $request->pid,
            'USN' => $request->USN,
            'marks' => $request->marks,
        ]);
        $project_evaluation->save();
        return response()->json([
            'data' => 'Successfully evaluated '.$request->USN,
        ], 200);
    }

    // Batch List creation

    public function staff_batch_list_creation_branch_list(Request $request)
    {
        $data = Student::select("branch")->get();
        $data = json_decode($data);
        return response()->json([
            'data' => array_slice(array_unique($data, SORT_REGULAR) , 0)
        ], 201);
    }

    public function staff_batch_list_creation_usn_list(Request $request)
    {
        $data = Student::select("USN")->get();
        $data = json_decode($data);
        return response()->json([
            'data' => array_slice(array_unique($data, SORT_REGULAR) , 0)
        ], 201);
    }

    public function staff_batch_list_creation(Request $request)
    {
        $request->validate([
            'batch_id' => 'required|regex:/(^([A-Z])(\d+)?$)/u|unique:batch_list',
            'branch' => 'required|string',
        ]);
        $SID = Auth::guard('api-staff')->user()->S_ID;

        $batch_list = new BatchList([
            'batch_id' => $request->batch_id,
            'branch' => $request->branch,
            'S_ID' => $SID,
        ]);
        $batch_list->save();

        return response()->json([
            'message' => 'Successfully created batch'
        ], 201);
    }


    public function staff_batch_list_series_get_count(Request $request)
    {
        $batches = BatchList::select('batch_id' , 'branch')->get();
        $json_decoded = json_decode($batches);

        $std = [];
        foreach ($json_decoded as $item) {
            $count = BatchList::where([['batch_id', 'like', '%' . $item->batch_id[0] . '%'], ['branch', '=', $item->branch]])->select('batch_id' , 'branch')->get();
            $std[] = [
                'batch' => $item->batch_id[0],
                'branch' => $item->branch,
                'count' => count($count)
            ];
        }

        return response()->json([
            'data' => array_slice(array_unique($std, SORT_REGULAR) , 0)
        ], 200);
    }

    public function staff_batch_list_series_get_count_by_initial(Request $request)
    {
        $request->validate([
            'batch' => 'required|max:1',
            'branch' => 'required|max:3'
        ]);

        $branches = BatchList::where([['batch_id', 'like', '%' . $request->batch . '%'],['branch', '=', $request->branch]])->orderBy('batch_id','asc')->get();

        return response()->json([
            'data' => $branches
        ], 200);
    }

    public function staff_batch_list_series_get_student_details(Request $request)
    {
        $request->validate([
            'batch_id' => 'required',
            'branch' => 'required'
        ]);
        $data = Batch::select('USN')->where([['batch_id', '=', $request->batch_id], ['branch', '=', $request->branch]])->get();
        $json_decoded = json_decode($data);

        $cont = [];
        foreach($json_decoded as $item){
            $cont[] = [
                'student' => Student::where('USN', '=', $item->USN)->get()
            ];
        }

        return response()->json([
            'data' => $cont
        ], 200);
    }

    public function staff_batch_list_series_get_batch_delete(Request $request)
    {
        $request->validate([
            'letter' => 'required|max:1'
        ]);
        BatchList::where('batch_id', 'A')->delete();
        return response()->json([
            'data' => "Deleted successfully."
        ], 200);
    }

    public function staff_batch_creation_adder(Request $request)
    {
        $request->validate([
            'batch_id' => 'required|regex:/(^([A-Z])(\d+)?$)/u|exists:batch_list',
            'USN' => 'required|unique:batch|exists:student',
        ]);

        $student= Student::where('USN', '=', $request->USN)->first();

        $batch_exist = BatchList::where([['batch_id', '=', $request->batch_id] , ['branch', '=', $student->branch]])->first();

        if($batch_exist == null){
            return response()->json([
                'message' => 'Failed to create entry.'
            ],400);
        }

        $batch = new Batch([
            'batch_id' => $request->batch_id,
            'USN' => $request->USN,
            'branch' => $student->branch,
        ]);


        try {
            $batch->save();
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }

        return response()->json([
            'message' => 'Successfully inserted student in batch'
        ],201);
    }

    public function staff_batch_branch_batchid_view(Request $request)
    {
        $request->validate([
            'batch_id' => 'required|regex:/(^([A-Z])(\d+)?$)/u|exists:batch_list',
            'branch' => 'required',
        ]);

        $usn= Batch::where([['batch_id', '=', $request->batch_id],['branch', '=', $request->branch]])->select('USN')->get();

        $cont = [];
        foreach(json_decode($usn) as $item){
            $cont [] =[
                'detail' => Student::where([['USN', '=', $item->USN]])->get()
            ];
        }
        return response()->json([
            'data' => $cont
            // 'message' => 'Successfully inserted student in batch'
        ],);
    }

    public function staff_batch_list_assigned(Request $request)
    {
        $batch= BatchList::where([['S_ID', '=', Auth::guard('api-staff')->user()->S_ID]])->select('batch_id')->get();
        return response()->json([
            'data' => $batch
        ],);
    }

    public function staff_create_project(Request $request)
    {
        $request->validate([
            'batch_id' => 'required|regex:/(^([A-Z])(\d+)?$)/u|exists:batch_list',
            'project_name' => 'required|string',
        ]);
        $SID = Auth::guard('api-staff')->user()->S_ID;

        if (Project::where([['batch_id', '=', $request->batch_id]])->first() != null) {
            return response()->json([
                'message' => 'Entry exists for the given batch'
            ], 200);
        }

        $project = new Project([
            'batch_id' => $request->batch_id,
            'project_name' => $request->project_name,
            'S_ID' => $SID,
        ]);
        $project->save();
        return response()->json([
            'weekly_report' => $project,
            'message' => 'Successfully created Project'
        ], 201);
    }

    public function staff_get_project(Request $request)
    {
        return response()->json([
            'data' => Project::where([['S_ID', '=', Auth::guard('api-staff')->user()->S_ID]])->get()
        ], 201);
    }

    //Student creator
    public function student_creator(Request $request){
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

    //Staff creator
    public function staff_creator(Request $request)
    {
        $request->validate([
            's_name' => 'required',
            'S_ID' => 'required|string|unique:staff',
            'ph_number' => 'required|integer',
            'email' => 'required|email',
            'branch' => 'required|string',
            'password' => 'required|string|confirmed',
        ]);

        $staff = new Staff([
            's_name' => $request->s_name,
            'S_ID' => strtoupper($request->S_ID),
            'ph_number' => $request->ph_number,
            'email' => $request->email,
            'branch' => $request->branch,
            'password' => bcrypt($request->password),
        ]);

        $staff->save();

        return response()->json([
            'data' => $staff,
            'message' => 'Successfully created staff!'
        ], 201);
    }


    // Batch Creation
    public function staff_check_student_exists(Request $request)
    {
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




    // Create project


    public function staff_get_batches_for_evaluation(Request $request)
    {
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
        foreach ($batch as $item) {
            $student[] = [
                'batch' => $item['data']->batch_id,
                'USN' => Batch::where([['batch_id', '=', $item['data']->batch_id]])->get("USN")
            ];
        }

        $std = [];
        foreach ($student as $item) {
            $student_data = [];
            foreach ($item['USN'] as $usn) {
                $student_data[] = Student::where([['USN', '=',  $usn->USN]])->first();
            }
            $std[$item['batch']] = $student_data;
        }


        return response()->json([
            'data' => $std,
        ], 200);
    }
}
