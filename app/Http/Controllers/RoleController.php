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
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Input;

use App\Models\TbCmnApproval;
use App\Models\TbSmsDisbursementDetail;
use Hamcrest\Arrays\IsArray;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Input\Input as InputInput;

class RoleController extends Controller
{
    // Models
    public function createRoleModal()
    {
        $moduleMaster      =       TbCmnModuleMaster::whereIn('Status_Id', [5])
            ->get()->sortBy('Pkid');
        $html = view('configuration-ui.modal.create-role-modal', compact('moduleMaster'))->render();
        //return view('benificiary-ui.benificiary-search-result');
        return response()->json(['status' => "success", 'body' => $html]);
    }

    // ------------- [ store post ] -----------------
    public function saveRole(Request $request)
    {

        $duplicateRole = TbCmnRoleMaster::where('Role_Name', $request->role_name)
            ->where('Record_Active_Flag', 1)
            ->get();
        $duplicateController = TbCmnRoleMaster::where('Controller_Path', $request->controller)
            ->where('Record_Active_Flag', 1)->get();
        $duplicateRoleControler = TbCmnRoleMaster::where('Role_Name', $request->role_name)
            ->where('Controller_Path', $request->controller)
            ->where('Record_Active_Flag', 1)->get();
        $duplicateRoleEntry = TbCmnRoleMaster::where('Module_Id_Fk')
            ->where('Module_Id_Fk', $request->module_id)
            ->where('Role_Name', $request->role_name)
            ->where('Controller_Path', $request->controller)
            ->where('Record_Active_Flag',)->get();

        if (count($duplicateRole) > 0) {
            return response()->json(["status" => "failed", "message" => "Alert! Role name already enter !"]);
        }
        if (count($duplicateController) > 0) {
            return response()->json(["status" => "failed", "message" => "Alert! Controller path already enter !"]);
        }
        if (count($duplicateRoleControler) > 0) {
            return response()->json(["status" => "failed", "message" => "Alert! Role already enter !"]);
        }
        if (count($duplicateRoleEntry) > 0) {
            return response()->json(["status" => "failed", "message" => "Alert! Role already enter !"]);
        }
        $status = "success";
        try {
            $addRole = new TbCmnRoleMaster();
            $addRole->Module_Id_Fk = $request->module_id;
            $addRole->Role_Name = $request->role_name;
            $addRole->Controller_Path = $request->controller;
            $addRole->Status_Id = config("constants.CREATED");
            $result = (new CommonController)->insertDefaultColumns($request, $addRole);
            $addRole->save();
            $status = "success";
        } catch (\Exception $ex) {
            throw $ex;
            $status = "failed";
        }
        $role      =       TbCmnRoleMaster::join('tb_cmn_status_master', 'tb_cmn_role_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->join('tb_cmn_module_master', 'tb_cmn_role_master.Module_Id_Fk', '=', 'tb_cmn_module_master.Pkid')
            ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
            ->where('tb_cmn_role_master.Record_Active_Flag', 1)
            ->get(['tb_cmn_role_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_module_master.Module_Name'])->sortBy('Pkid');
        $html_view = view("configuration-ui.view.role-list", compact('role'))->render();
        if ($status == "success") {
            return response()->json(["status" => "success", "message" => "Success! Role created.", "body" => $html_view]);
        } else {
            return response()->json(["status" => "failed", "message" => "Alert! Role not created"]);
        }
    }
    // -------------- [ Delete post ] ---------------
    public function destroyRole(Request $request, $role_id)
    {
        $deleteRole     =       TbCmnRoleMaster::where("Pkid", $role_id)
            ->update([
                'Record_Active_Flag' => '0',
                'Record_Update_Date' => new \DateTime(),
                'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'),
                'Updated_By' => $request->session()->get('id')
            ]);
        $role      =       TbCmnRoleMaster::join('tb_cmn_status_master', 'tb_cmn_role_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->join('tb_cmn_module_master', 'tb_cmn_role_master.Module_Id_Fk', '=', 'tb_cmn_module_master.Pkid')
            ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
            ->where('tb_cmn_role_master.Record_Active_Flag', 1)
            ->get(['tb_cmn_role_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_module_master.Module_Name'])->sortBy('Pkid');

        $html_view = view("configuration-ui.view.role-list", compact('role'))->render();
        if ($deleteRole == 1) {
            return response()->json(["status" => "success", "message" => "Success! User deleted", "body" => $html_view]);
        } else {
            return response()->json(["status" => "failed", "message" => "Alert! User not deleted"]);
        }
    }

    public function showRole()
    {
        $role      =       TbCmnRoleMaster::join('tb_cmn_status_master', 'tb_cmn_role_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->join('tb_cmn_module_master', 'tb_cmn_role_master.Module_Id_Fk', '=', 'tb_cmn_module_master.Pkid')
            ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
            ->where('tb_cmn_role_master.Record_Active_Flag', 1)
            ->get(['tb_cmn_role_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_module_master.Module_Name'])->sortBy('Pkid');
        $html_view = view("configuration-ui.view.role-list", compact('role'))->render();
        return response()->json(["status" => "success", "body" => $html_view]);
    }

    public function approvalRole()
    {
        $role      =       TbCmnRoleMaster::join('tb_cmn_status_master', 'tb_cmn_role_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->join('tb_cmn_module_master', 'tb_cmn_role_master.Module_Id_Fk', '=', 'tb_cmn_module_master.Pkid')
            ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
            ->where('tb_cmn_role_master.Record_Active_Flag', 1)
            ->get(['tb_cmn_role_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_module_master.Module_Name'])->sortBy('Pkid');
        $html_view = view("configuration-ui.approval-view.role-approval-view", compact('role'))->render();
        return response()->json(["status" => "success", "body" => $html_view]);
    }


    public function approveRoleEntry(Request $request)
    {
        DB::beginTransaction();
        try {
            $dataUI = json_decode($request->getContent());
            $approvalStatus = $dataUI->decision;
            $approvalDate = $dataUI->approval_date;
            $remarks = $dataUI->remarks;
            if (Is_Array($dataUI->check_id)) {
                foreach ($dataUI->check_id as $value) {
                    TbCmnRoleMaster::where('Pkid', $value)->update(
                        [
                            'Status_Id' => $approvalStatus, 'Record_Update_Date' => new \DateTime(), 'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'),
                            'Updated_By' => $request->session()->get('id')
                        ]
                    );
                    $saveCmnApproval = new TbCmnApproval();
                    $saveCmnApproval->Approval_Date = $approvalDate;
                    $saveCmnApproval->Status_Id_Fk = $approvalStatus;
                    $saveCmnApproval->Remarks = $remarks;
                    $saveCmnApproval->Module = 'Role';
                    $saveCmnApproval->Record_Id_Fk =  $value;
                    $saveCmnApproval->save();
                }
            } else {
                TbCmnRoleMaster::where('Pkid', $dataUI->check_id)->update(
                    [
                        'Status_Id' => $approvalStatus, 'Record_Update_Date' => new \DateTime(), 'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'),
                        'Updated_By' => $request->session()->get('id')
                    ]
                );
                $saveCmnApproval = new TbCmnApproval();
                $saveCmnApproval->Approval_Date = $approvalDate;
                $saveCmnApproval->Status_Id_Fk = $approvalStatus;
                $saveCmnApproval->Remarks = $remarks;
                $saveCmnApproval->Module = 'Role';
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
        $role      =       TbCmnRoleMaster::join('tb_cmn_status_master', 'tb_cmn_role_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->join('tb_cmn_module_master', 'tb_cmn_role_master.Module_Id_Fk', '=', 'tb_cmn_module_master.Pkid')
            ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
            ->where('tb_cmn_role_master.Record_Active_Flag', 1)
            ->get(['tb_cmn_role_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_module_master.Module_Name'])->sortBy('Pkid');
        $html_view = view("configuration-ui.approval-view.role-approval-view", compact('role'))->render();
        if ($status == 'success') {
            return response()->json(["status" => "success", "message" => "Success! Role " . $decision, "body" => $html_view]);
        } else {
            return response()->json(["status" => "success", "message" => "Alert! Role not " . $decision]);
        }
    }

    public function SearchRoles(Request $request)
    {
        try{
            $dataUI = json_decode($request->getContent());
            $module = $dataUI->module;
            $role_name = $dataUI->search_role_name;
            $status = $dataUI->status_id;
            $query = TbCmnRoleMaster::join('tb_cmn_status_master', 'tb_cmn_role_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
                ->join('tb_cmn_module_master', 'tb_cmn_role_master.Module_Id_Fk', '=', 'tb_cmn_module_master.Pkid');
    
            if (!empty($dataUI->module)) {
                $query->where('tb_cmn_module_master.Pkid', '=', "$module");
            }
            if (!empty($dataUI->search_role_name)) {
                $query->where('tb_cmn_role_master.Role_Name', 'LIKE', "%{ $role_name }%");
            }
            if (!empty($dataUI->status_id)) {
                $query->where('tb_cmn_status_master.Pkid', $status);
            }
            $role = $query->whereIn('tb_cmn_status_master.Pkid', [1, 3])
                ->where('tb_cmn_role_master.Record_Active_Flag', 1)
                ->get(['tb_cmn_role_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_module_master.Module_Name'])->sortBy('Pkid');
    
            $html_view = view("configuration-ui.view.role-list", compact('role'))->render();
            return response()->json(["status" => "success", "body" => $html_view]);
        }
        catch(\Exception $ex)
        {
            return $ex->getMessage();

        }
        
    }
}
