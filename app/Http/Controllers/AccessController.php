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
use App\Models\TbCmnModuleMaster;
use App\Models\TbCmnRoleMaster;
use App\Models\TbCmnRoleAccess;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Input;

use App\Models\TbCmnApproval;
use App\Models\TbSmsDisbursementDetail;
use Hamcrest\Arrays\IsArray;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Input\Input as InputInput;

class AccessController extends Controller
{
    // Models
    public function createAccessModal()
    {
        $moduleMaster      =       TbCmnModuleMaster::whereIn('Status_Id', [5])->get()->sortBy('Module_Name');
        //  $roleMaster      =       TbCmnRoleMaster::whereIn('Status_Id', [1,2,3,4,5])->get()->sortBy('Role_Name');
        $userMaster      =       TbCmnUser::whereIn('Approval_Flag', [1, 2, 3, 4, 5])->get()->sortBy('User_Id');
        $html = view('configuration-ui.modal.create-access-modal', compact('moduleMaster', 'userMaster'))->render();
        //return view('benificiary-ui.benificiary-search-result');
        return response()->json(['status' => "success", 'body' => $html]);
    }

    // ------------- [ store post ] -----------------
    public function saveAccess(Request $request)
    {

        $duplicateRole = TbCmnRoleAccess::where('Role_Id_Fk', $request->role_id_access)
            ->where('User_Id_Fk', $request->user_id_access)->get();
        if (count($duplicateRole) > 0) {
            return response()->json(["status" => "failed", "message" => "Alert! Role already assigned !"]);
        }
        $status = "success";
        try {
            $addRole = new TbCmnRoleAccess();
            $addRole->User_Id_Fk = $request->user_id_access;
            $addRole->Module_Id_Fk = $request->module_id_access;
            $addRole->Role_Id_Fk = $request->role_id_access;
            $addRole->Status_Id = config("constants.CREATED");
            $result = (new CommonController)->insertDefaultColumns($request, $addRole);
            $addRole->save();
            $status = "success";
        } catch (\Exception $ex) {
            throw $ex;
            $status = "failed";
        }
        $accessControl      =       TbCmnRoleAccess::join('tb_cmn_status_master', 'tb_cmn_role_access.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->join('tb_cmn_user', 'tb_cmn_role_access.User_Id_Fk', '=', 'tb_cmn_user.Pkid')
            ->join('tb_cmn_module_master', 'tb_cmn_role_access.Module_Id_Fk', '=', 'tb_cmn_module_master.Pkid')
            ->join('tb_cmn_role_master', 'tb_cmn_role_access.Role_Id_Fk', '=', 'tb_cmn_role_master.Pkid')
            ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
            ->get(['tb_cmn_role_access.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_user.User_Id', 'tb_cmn_module_master.Module_Name', 'tb_cmn_role_master.Role_Name', 'tb_cmn_role_master.Controller_Path'])->sortBy('Pkid');

        $html_view = view("configuration-ui.view.access-list", compact('accessControl'))->render();
        if ($status == "success") {
            return response()->json(["status" => "success", "message" => "Success! Access assigned.", "body" => $html_view]);
        } else {
            return response()->json(["status" => "failed", "message" => "Alert! Access not assigned."]);
        }
    }
    // -------------- [ Delete post ] ---------------
    public function destroyAccess($role_id)
    {
        $deleteRole     =       TbCmnRoleAccess::where("Pkid", $role_id)->delete();
        try {
            $accessControl      =       TbCmnRoleAccess::join('tb_cmn_status_master', 'tb_cmn_role_access.Status_Id', '=', 'tb_cmn_status_master.Pkid')
                ->join('tb_cmn_user', 'tb_cmn_role_access.User_Id_Fk', '=', 'tb_cmn_user.Pkid')
                ->join('tb_cmn_module_master', 'tb_cmn_role_access.Module_Id_Fk', '=', 'tb_cmn_module_master.Pkid')
                ->join('tb_cmn_role_master', 'tb_cmn_role_access.Role_Id_Fk', '=', 'tb_cmn_role_master.Pkid')
                ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
                ->get(['tb_cmn_role_access.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_user.User_Id', 'tb_cmn_module_master.Module_Name', 'tb_cmn_role_master.Role_Name', 'tb_cmn_role_master.Controller_Path'])->sortBy('Pkid');
        } catch (\Exception $ex) {
            throw $ex;
        }
        $html_view = view("configuration-ui.view.access-list", compact('accessControl'))->render();
        if ($deleteRole == 1) {
            return response()->json(["status" => "success", "message" => "Success! User role deleted", "body" => $html_view]);
        } else {
            return response()->json(["status" => "failed", "message" => "Alert! User not deleted"]);
        }
    }

    public function showAccess()
    {
        try {
            $accessControl      =       TbCmnRoleAccess::join('tb_cmn_status_master', 'tb_cmn_role_access.Status_Id', '=', 'tb_cmn_status_master.Pkid')
                ->join('tb_cmn_user', 'tb_cmn_role_access.User_Id_Fk', '=', 'tb_cmn_user.Pkid')
                ->join('tb_cmn_module_master', 'tb_cmn_role_access.Module_Id_Fk', '=', 'tb_cmn_module_master.Pkid')
                ->join('tb_cmn_role_master', 'tb_cmn_role_access.Role_Id_Fk', '=', 'tb_cmn_role_master.Pkid')
                ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
                ->get(['tb_cmn_role_access.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_user.User_Id', 'tb_cmn_module_master.Module_Name', 'tb_cmn_role_master.Role_Name', 'tb_cmn_role_master.Controller_Path'])->sortBy('Pkid');
        } catch (\Exception $ex) {
            throw $ex;
        }
        $html_view = view("configuration-ui.view.access-list", compact('accessControl'))->render();
        return response()->json(["status" => "success", "body" => $html_view]);
    }

    public function approvalAccess()
    {
        $accessControl      =       TbCmnRoleAccess::join('tb_cmn_status_master', 'tb_cmn_role_access.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->join('tb_cmn_user', 'tb_cmn_role_access.User_Id_Fk', '=', 'tb_cmn_user.Pkid')
            ->join('tb_cmn_module_master', 'tb_cmn_role_access.Module_Id_Fk', '=', 'tb_cmn_module_master.Pkid')
            ->join('tb_cmn_role_master', 'tb_cmn_role_access.Role_Id_Fk', '=', 'tb_cmn_role_master.Pkid')
            ->whereIn('tb_cmn_status_master.Pkid', [1, 2])
            ->get(['tb_cmn_role_access.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_user.User_Id', 'tb_cmn_module_master.Module_Name', 'tb_cmn_role_master.Role_Name', 'tb_cmn_role_master.Controller_Path'])->sortBy('Pkid');
        $html_view = view("configuration-ui.approval-view.access-approval-view", compact('accessControl'))->render();
        return response()->json(["status" => "success", "body" => $html_view]);
    }


    public function approveAccessEntry(Request $request)
    {
        DB::beginTransaction();
        try {
            $dataUI = json_decode($request->getContent());
            $approvalStatus = $dataUI->decision;
            $approvalDate = $dataUI->approval_date;
            $remarks = $dataUI->remarks;
            if (Is_Array($dataUI->check_id)) {
                foreach ($dataUI->check_id as $value) {
                    TbCmnRoleAccess::where('Pkid', $value)->update(
                        [
                            'Status_Id' => $approvalStatus, 'Record_Update_Date' => new \DateTime(), 'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'),
                            'Updated_By' => $request->session()->get('id')
                        ]
                    );
                    $saveCmnApproval = new TbCmnApproval();
                    $saveCmnApproval->Approval_Date = $approvalDate;
                    $saveCmnApproval->Status_Id_Fk = $approvalStatus;
                    $saveCmnApproval->Remarks = $remarks;
                    $saveCmnApproval->Module = 'Access controller';
                    $saveCmnApproval->Record_Id_Fk =  $value;
                    $saveCmnApproval->save();
                }
            } else {
                TbCmnRoleAccess::where('Pkid', $dataUI->check_id)->update(
                    [
                        'Status_Id' => $approvalStatus, 'Record_Update_Date' => new \DateTime(), 'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'),
                        'Updated_By' => $request->session()->get('id')
                    ]
                );
                $saveCmnApproval = new TbCmnApproval();
                $saveCmnApproval->Approval_Date = $approvalDate;
                $saveCmnApproval->Status_Id_Fk = $approvalStatus;
                $saveCmnApproval->Remarks = $remarks;
                $saveCmnApproval->Module = 'Access controller';
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
        $decision = '';
        if ($dataUI->decision == 3) {
            $decision = 'Return';
        } elseif ($dataUI->decision == 4) {
            $decision = 'Rejected';
        } elseif ($dataUI->decision == 5) {
            $decision = 'Approved';
        }
        $accessControl      =       TbCmnRoleAccess::join('tb_cmn_status_master', 'tb_cmn_role_access.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->join('tb_cmn_user', 'tb_cmn_role_access.User_Id_Fk', '=', 'tb_cmn_user.Pkid')
            ->join('tb_cmn_module_master', 'tb_cmn_role_access.Module_Id_Fk', '=', 'tb_cmn_module_master.Pkid')
            ->join('tb_cmn_role_master', 'tb_cmn_role_access.Role_Id_Fk', '=', 'tb_cmn_role_master.Pkid')
            ->whereIn('tb_cmn_status_master.Pkid', [1, 2])
            ->get(['tb_cmn_role_access.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_user.User_Id', 'tb_cmn_module_master.Module_Name', 'tb_cmn_role_master.Role_Name', 'tb_cmn_role_master.Controller_Path'])->sortBy('Pkid');
        $html_view = view("configuration-ui.approval-view.access-approval-view", compact('accessControl'))->render();
        if ($status == 'success') {
            return response()->json(["status" => "success", "message" => "Success! Access controls " . $decision, "body" => $html_view]);
        } else {
            return response()->json(["status" => "success", "message" => "Alert! Access controls not " . $decision]);
        }
    }


    public function SearchAccess(Request $request)
    {
        try {
            $dataUI = json_decode($request->getContent());
            $user_id = $dataUI->user_id;
            $module = $dataUI->module;
            $role_id = $dataUI->role_id;
            // $status = $dataUI->status_id;
            $query =  TbCmnRoleAccess::join('tb_cmn_status_master', 'tb_cmn_role_access.Status_Id', '=', 'tb_cmn_status_master.Pkid')
                ->join('tb_cmn_user', 'tb_cmn_role_access.User_Id_Fk', '=', 'tb_cmn_user.Pkid')
                ->join('tb_cmn_module_master', 'tb_cmn_role_access.Module_Id_Fk', '=', 'tb_cmn_module_master.Pkid')
                ->join('tb_cmn_role_master', 'tb_cmn_role_access.Role_Id_Fk', '=', 'tb_cmn_role_master.Pkid');

            if (!empty($dataUI->user_id)) {
                $query->where('tb_cmn_user.Pkid', '=', "$user_id");
            }
            if (!empty($dataUI->module)) {
                $query->where('tb_cmn_module_master.Pkid', '=', "$module");
            }
            if (!empty($dataUI->role_id)) {
                $query->where('tb_cmn_role_master.Pkid', '=', "$role_id");
            }
            // if (!empty($dataUI->status_id)) {
            //     $query->where('tb_cmn_status_master.Pkid', $status);
            // }
            $accessControl      =    $query->whereIn('tb_cmn_status_master.Pkid', [1, 3])
                ->get(['tb_cmn_role_access.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_user.User_Id', 'tb_cmn_module_master.Module_Name', 'tb_cmn_role_master.Role_Name', 'tb_cmn_role_master.Controller_Path'])->sortBy('Pkid');

            $html_view = view("configuration-ui.view.access-list", compact('accessControl'))->render();
            return response()->json(["status" => "success", "body" => $html_view]);
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }
}
