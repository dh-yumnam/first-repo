<?php

namespace App\Http\Controllers;


use App\Models\Post;
use Illuminate\Http\Request;
use App\Models\TbCmnFinishGoodsMaster;
use App\Models\TbCmnFundMaster;
use App\Models\TbCmnNedfiBankMaster;
use App\Models\TbCmnReasonMaster;
use App\Models\TbCmnRawMaterialMaster;
use App\Models\TbCmnProductMaster;
use App\Models\TbCmnUnitMaster;
use App\Models\TbCmnSchemeMaster;
use App\Models\TbCmnStatusMaster;
use App\Models\TbCmnUser;
use App\Models\TbChangePasswordHistory;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Input;

use App\Models\TbCmnApproval;
use App\Models\TbSmsDisbursementDetail;
use Hamcrest\Arrays\IsArray;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Input\Input as InputInput;

class UserController extends Controller
{
    // Models
    public function createUserModal()
    {
        $unitMaster      =       TbCmnUnitMaster::whereIn('Status_Id', [1, 2, 3, 4, 5])
            ->where('tb_cmn_unit_master.Record_Active_Flag', '=', 1)
            ->get()->sortBy('Pkid');
        $html = view('configuration-ui.modal.create-user-modal', compact('unitMaster'))->render();
        //return view('benificiary-ui.benificiary-search-result');
        return response()->json(['status' => "success", 'body' => $html]);
    }

    // ------------- [ store post ] -----------------
    public function saveUser(Request $request)
    {
        $duplicateUser = TbCmnUser::where('User_Id', $request->user_name)
            ->where('tb_cmn_user.Record_Active_Flag',  1)
            ->get();
        $duplicatePhone = TbCmnUser::where('Primary_Mobile_No', $request->phone_no)
            ->where('tb_cmn_user.Record_Active_Flag',  1)
            ->get();
        $duplicateEmail = TbCmnUser::where('User_Email', $request->email)
            ->where('tb_cmn_user.Record_Active_Flag',  1)
            ->get();
        if (count($duplicateUser) > 0) {
            return response()->json(["status" => "failed", "message" => "Alert! User Name has already been taken !"]);
        }
        if (count($duplicatePhone) > 0) {
            return response()->json(["status" => "failed", "message" => "Alert! Phone No. has already been use !"]);
        }
        if (count($duplicateEmail) > 0) {
            return response()->json(["status" => "failed", "message" => "Alert! Email Id has already been use !"]);
        }
        // if (!validator($request->password)) {
        //     return response()->json(["status" => "failed", "message" => "Your password must be more than 8 characters long, should contain at-least 1 Uppercase, 1 Lowercase, 1 Numeric and 1 special character. !"]);
        // }

        $inputs = [
            'password' =>  $request->password,
            'phone_no'    => $request->phone_no,
            'email'    => $request->email,

        ];
        $rules = [
            'password' => [
                'required',
                'string',
                'min:8',             // must be at least 10 characters in length
                'regex:/[a-z]/',      // must contain at least one lowercase letter
                'regex:/[A-Z]/',      // must contain at least one uppercase letter
                'regex:/[0-9]/',      // must contain at least one digit
                'regex:/[@$!%*#?&]/', // must contain a special character
            ],
            'email'    => 'required|email',
            'phone_no' => 'required|digits:10',
        ];

        $validation = Validator::make($inputs, $rules);
        if ($validation->fails()) {
            if (!empty($validation->errors()->first('password'))) {
                return response()->json(["status" => "failed", "message" => "Your password must be at least 8 characters long, should contain at-least 1 Uppercase, 1 Lowercase, 1 Numeric and 1 special character."]);
            } else {
                return response()->json(["status" => "failed", "message" => $validation->errors()->all()]);
            }
        }
        $status = "success";
        try {
            $addUser = new TbCmnUser();
            $addUser->User_Id = $request->user_name;
            $addUser->User_Password = hash('sha3-512', $request->password);
            $addUser->Confirm_Password = hash('sha3-512', $request->confirm_password);
            $addUser->Primary_Mobile_No = $request->phone_no;
            $addUser->User_Email = $request->email;
            $addUser->User_Type = $request->type;
            $addUser->Approval_Flag = config("constants.CREATED");
            $result = (new CommonController)->insertDefaultColumns($request, $addUser);
            $addUser->save();
            $status = "success";
        } catch (\Exception $ex) {
            throw $ex;
            $status = "failed";
        }
        $user      =       TbCmnUser::join('tb_cmn_status_master', 'tb_cmn_user.Approval_Flag', '=', 'tb_cmn_status_master.Pkid')
            ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
            ->where('tb_cmn_user.Record_Active_Flag',  1)
            ->get(['tb_cmn_user.*', 'tb_cmn_status_master.Status_Name'])->sortBy('Pkid');
        $html_view = view("configuration-ui.view.user-list", compact('user'))->render();
        if ($status == "success") {
            return response()->json(["status" => "success", "message" => "Success! User created.", "body" => $html_view]);
        } else {
            return response()->json(["status" => "failed", "message" => "Alert! User not created"]);
        }
    }



    // -------------- [ Delete post ] ---------------
    public function destroyUser(Request $request, $user_id)
    {
        $deleteUser     =       TbCmnUser::where("Pkid", $user_id)
            ->update([
                'Record_Active_Flag' => '0',
                'Record_Update_Date' => new \DateTime(),
                'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'),
                'Updated_By' => $request->session()->get('id')
            ]);
        $user      =       TbCmnUser::join('tb_cmn_status_master', 'tb_cmn_user.Approval_Flag', '=', 'tb_cmn_status_master.Pkid')
            ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
            ->where('tb_cmn_user.Record_Active_Flag',  1)
            ->get(['tb_cmn_user.*', 'tb_cmn_status_master.Status_Name'])->sortBy('Pkid');
        $html_view = view("configuration-ui.view.user-list", compact('user'))->render();
        if ($deleteUser == 1) {
            return response()->json(["status" => "success", "message" => "Success! User deleted", "body" => $html_view]);
        } else {
            return response()->json(["status" => "failed", "message" => "Alert! User not deleted"]);
        }
    }

    public function showUser()
    {
        $user      =       TbCmnUser::join('tb_cmn_status_master', 'tb_cmn_user.Approval_Flag', '=', 'tb_cmn_status_master.Pkid')
            ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
            ->where('tb_cmn_user.Record_Active_Flag',  1)
            ->get(['tb_cmn_user.*', 'tb_cmn_status_master.Status_Name'])->sortBy('Pkid');
        $html_view = view("configuration-ui.view.user-list", compact('user'))->render();
        return response()->json(["status" => "success", "body" => $html_view]);
    }

    public function approvalUser()
    {
        $user      =       TbCmnUser::join('tb_cmn_status_master', 'tb_cmn_user.Approval_Flag', '=', 'tb_cmn_status_master.Pkid')
            ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
            ->where('tb_cmn_user.Record_Active_Flag',  1)
            ->get(['tb_cmn_user.*', 'tb_cmn_status_master.Status_Name'])->sortBy('Pkid');
        $html_view = view("configuration-ui.approval-view.user-approval-view", compact('user'))->render();
        return response()->json(["status" => "success", "body" => $html_view]);
    }

    public function approveUserEntry(Request $request)
    {
        DB::beginTransaction();
        try {
            $dataUI = json_decode($request->getContent());
            $approvalStatus = $dataUI->decision;
            $approvalDate = $dataUI->approval_date;
            $remarks = $dataUI->remarks;
            if (Is_Array($dataUI->check_id)) {
                foreach ($dataUI->check_id as $value) {
                    TbCmnUser::where('Pkid', $value)->update(
                        [
                            'Approval_Flag' => $approvalStatus, 'Record_Update_Date' => new \DateTime(), 'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'),
                            'Updated_By' => $request->session()->get('id')
                        ]
                    );
                    $saveCmnApproval = new TbCmnApproval();
                    $saveCmnApproval->Approval_Date = $approvalDate;
                    $saveCmnApproval->Status_Id_Fk = $approvalStatus;
                    $saveCmnApproval->Remarks = $remarks;
                    $saveCmnApproval->Module = 'User';
                    $saveCmnApproval->Record_Id_Fk =  $value;
                    $saveCmnApproval->save();
                }
            } else {
                TbCmnUser::where('Pkid', $dataUI->check_id)->update(
                    [
                        'Approval_Flag' => $approvalStatus, 'Record_Update_Date' => new \DateTime(), 'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'),
                        'Updated_By' => $request->session()->get('id')
                    ]
                );
                $saveCmnApproval = new TbCmnApproval();
                $saveCmnApproval->Approval_Date = $approvalDate;
                $saveCmnApproval->Status_Id_Fk = $approvalStatus;
                $saveCmnApproval->Remarks = $remarks;
                $saveCmnApproval->Module = 'User';
                $saveCmnApproval->Record_Id_Fk =  $dataUI->check_id;
                $saveCmnApproval->save();
            }

            $status = "success";
            DB::commit();
        } catch (\Exception $ex) {
            throw $ex;
            $status = "failed";
            DB::rollback();
        }
        $user      =       TbCmnUser::join('tb_cmn_status_master', 'tb_cmn_user.Approval_Flag', '=', 'tb_cmn_status_master.Pkid')
            ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
            ->where('tb_cmn_user.Record_Active_Flag',  1)
            ->get(['tb_cmn_user.*', 'tb_cmn_status_master.Status_Name'])->sortBy('Pkid');
        $html_view = view("configuration-ui.approval-view.user-approval-view", compact('user'))->render();
        if ($status == 'success') {
            return response()->json(["status" => "success", "message" => "Success! User approved", "body" => $html_view]);
        } else {
            return response()->json(["status" => "success", "message" => "Alert! User not approved"]);
        }
    }

    public function SearchUser(Request $request)
    {

        $dataUI = json_decode($request->getContent());
        $unit_name = $dataUI->search_user_name;
        $mobile_no = $dataUI->mobile_no;
        $status = $dataUI->status_id;
        $query = TbCmnUser::join('tb_cmn_status_master', 'tb_cmn_user.Approval_Flag', '=', 'tb_cmn_status_master.Pkid');

        if (!empty($dataUI->search_user_name)) {
            $query->where('User_Id', 'LIKE', "%{$unit_name}%");
        }
        if (!empty($dataUI->mobile_no)) {
            $query->where('Primary_Mobile_No', 'LIKE', "%{$mobile_no}%");
        }
        if (!empty($dataUI->status_id)) {
            $query->where('Approval_Flag', $status);
        }
        $user = $query->whereIn('tb_cmn_status_master.Pkid', [1, 3])
            ->where('tb_cmn_user.Record_Active_Flag', 1)
            ->get(['tb_cmn_user.*', 'tb_cmn_status_master.Status_Name'])->sortBy('Pkid');
        $html_view = view("configuration-ui.view.user-list", compact('user'))->render();
        return response()->json(["status" => "success", "body" => $html_view]);
    }

    // ------------- [ store post ] -----------------
    public function updatePassword(Request $request)
    {
        $dataUI = json_decode($request->getContent());
        $userId = $dataUI->userPk;
        $inputs = [
            'new_pwd' =>  $dataUI->new_pwd,
        ];
        $rules = [
            'new_pwd' => [
                'required',
                'string',
                'min:8',             // must be at least 10 characters in length
                'regex:/[a-z]/',      // must contain at least one lowercase letter
                'regex:/[A-Z]/',      // must contain at least one uppercase letter
                'regex:/[0-9]/',      // must contain at least one digit
                'regex:/[@$!%*#?&]/', // must contain a special character
            ],
        ];
        $currentPwd_hash = hash('sha3-512', $dataUI->current_pwd);
        $user      =  TbCmnUser::where('Pkid', $userId)->firstOrFail();
        if ($user->User_Password != $currentPwd_hash) {
            return response()->json(["status" => "failed", "message" => "Alert! Current password does not match with the existing one.!"]);
        }
        $validation = Validator::make($inputs, $rules);
        if ($validation->fails()) {
            if (!empty($validation->errors()->first('new_pwd'))) {
                return response()->json(["status" => "failed", "message" => "Alert! Your password must be at least 8 characters long, should contain at-least 1 Uppercase, 1 Lowercase, 1 Numeric and 1 special character."]);
            } else {
                return response()->json(["status" => "failed", "message" => $validation->errors()->all()]);
            }
        }
        if ($dataUI->new_pwd != $dataUI->confirm_pwd) {
            return response()->json(["status" => "failed", "message" => "Alert! New and Confirm password does not match.!"]);
        }
        $status = "success";
        try {
            $changePwd = new TbCmnUser();
            $changePwd->User_Password = hash('sha3-512', $dataUI->new_pwd);
            $changePwd->Confirm_Password = hash('sha3-512', $dataUI->confirm_pwd);
            $userData = array(
                'User_Password' =>  hash('sha3-512', $dataUI->new_pwd), "Confirm_Password" => hash('sha3-512', $dataUI->confirm_pwd),
                'Record_Update_Date' => new \DateTime()
            );
            $userPassword = TbCmnUser::where('Pkid', $userId)->update($userData);
            $status = "success";
        } catch (\Exception $ex) {
            return $ex->getMessage();
            $status = "failed";
        }
        if ($status == "success") {
            return response()->json(["status" => "success", "message" => "Success! Password has been changed.", "body" => null]);
        } else {
            return response()->json(["status" => "failed", "message" => "Alert! Password not changed"]);
        }
    }

    // ------------- [ store post ] -----------------
    public function resetPasswordOnFirstLogin(Request $request)
    {
        $dataUI = json_decode($request->getContent());
        $userId = $dataUI->userPk;
        $inputs = [
            'new_pwd' =>  $dataUI->new_pwd,
        ];
        $rules = [
            'new_pwd' => [
                'required',
                'string',
                'min:8',             // must be at least 10 characters in length
                'regex:/[a-z]/',      // must contain at least one lowercase letter
                'regex:/[A-Z]/',      // must contain at least one uppercase letter
                'regex:/[0-9]/',      // must contain at least one digit
                'regex:/[@$!%*#?&]/', // must contain a special character
            ],
        ];
        $currentPwd_hash = hash('sha3-512', $dataUI->current_pwd);
        $user      =   TbChangePasswordHistory::where(['Record_Active_Flag' => 1, 'UserId' => $userId])->get()->first();

        if ($user->NewPassword != $currentPwd_hash) {
            return response()->json(["status" => "failed", "message" => "Alert! Current password does not match with the existing one.!"]);
        }
        $validation = Validator::make($inputs, $rules);
        if ($validation->fails()) {
            if (!empty($validation->errors()->first('new_pwd'))) {
                return response()->json(["status" => "failed", "message" => "Alert! Your password must be at least 8 characters long, should contain at-least 1 Uppercase, 1 Lowercase, 1 Numeric and 1 special character."]);
            } else {
                return response()->json(["status" => "failed", "message" => $validation->errors()->all()]);
            }
        }
        if ($dataUI->new_pwd != $dataUI->confirm_pwd) {
            return response()->json(["status" => "failed", "message" => "Alert! New and Confirm password does not match.!"]);
        }
        $status = "success";
        try {
            // deactivate password change history 
            $tableData = array(
                'Record_Update_Date' => new \DateTime(), 'Record_Active_Flag' => 0
            );
            $resetPassword = TbChangePasswordHistory::where('Pkid', $user->Pkid)
                ->update($tableData);
            $userData = array(
                'User_Password' =>  hash('sha3-512', $dataUI->new_pwd), "Confirm_Password" => hash('sha3-512', $dataUI->confirm_pwd),
                'Record_Update_Date' => new \DateTime()
            );
            $userPassword = TbCmnUser::where('Pkid', $user->UserId)->update($userData);
            $status = "success";
        } catch (\Exception $ex) {
            return $ex->getMessage();
            $status = "failed";
        }
        $html_view = view("pages.user-pages.successful-reset-password")->render();
        if ($status == "success") {
            return response()->json(["status" => "success", "message" => "Success! Password has been changed.", "body" => $html_view]);
        } else {
            return response()->json(["status" => "failed", "message" => "Alert! Password not changed"]);
        }
    }
}
