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
use App\Models\TbCmnRoleMaster;
use App\Models\TbCmnUser;
use App\Models\TbCmnModuleMaster;
use App\Models\TbCmnRoleAccess;

class ConfigurationController extends Controller
{
    public function getConfigurationPage()
    {
        $accessBoolean = (new CommonController)->checkAccessRightToController('getConfigurationPage');
        if ($accessBoolean) {

            $user      =       TbCmnUser::join('tb_cmn_status_master', 'tb_cmn_user.Approval_Flag', '=', 'tb_cmn_status_master.Pkid')
                ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
                ->where('tb_cmn_user.Record_Active_Flag', '1')
                ->get(['tb_cmn_user.*', 'tb_cmn_status_master.Status_Name'])->sortBy('Pkid');


            $role      =       TbCmnRoleMaster::join('tb_cmn_status_master', 'tb_cmn_role_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
                ->join('tb_cmn_module_master', 'tb_cmn_role_master.Module_Id_Fk', '=', 'tb_cmn_module_master.Pkid')
                ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
                ->where('tb_cmn_role_master.Record_Active_Flag', '1')
                ->get(['tb_cmn_role_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_module_master.Module_Name'])->sortBy('Pkid');

            $accessControl      =       TbCmnRoleAccess::join('tb_cmn_status_master', 'tb_cmn_role_access.Status_Id', '=', 'tb_cmn_status_master.Pkid')
                ->join('tb_cmn_user', 'tb_cmn_role_access.User_Id_Fk', '=', 'tb_cmn_user.Pkid')
                ->join('tb_cmn_module_master', 'tb_cmn_role_access.Module_Id_Fk', '=', 'tb_cmn_module_master.Pkid')
                ->join('tb_cmn_role_master', 'tb_cmn_role_access.Role_Id_Fk', '=', 'tb_cmn_role_master.Pkid')
                ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
                ->where('tb_cmn_role_access.Record_Active_Flag', '1')
                ->get(['tb_cmn_role_access.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_user.User_Id', 'tb_cmn_module_master.Module_Name', 'tb_cmn_role_master.Role_Name', 'tb_cmn_role_master.Controller_Path'])->sortBy('Pkid');

            $bankMaster      =       TbCmnNedfiBankMaster::join('tb_cmn_status_master', 'tb_cmn_nedfi_bank_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
                ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
                ->where('tb_cmn_nedfi_bank_master.Record_Active_Flag', '1')
                ->get(['tb_cmn_nedfi_bank_master.*', 'tb_cmn_status_master.Status_Name'])->sortBy('Pkid');

            $moduleMaster      =       TbCmnModuleMaster::whereIn('Status_Id', [5])->get()->sortBy('Pkid');
            $userMaster      =       TbCmnUser::whereIn('Approval_Flag', [1, 2, 3, 4, 5])->get()->sortBy('User_Id');
            $statusMaster      =       TbCmnStatusMaster::all()->sortBy('Pkid');
            $approvalStatusMaster      =       TbCmnStatusMaster::select('Pkid', 'Status_Name')->where('Pkid', '>', '2')->get();

            $html =  view('configuration-ui.configuration-pages', compact('user', 'role', 'moduleMaster', 'accessControl', 'userMaster', 'statusMaster', 'approvalStatusMaster'))->render();
            //return view('benificiary-ui.benificiary-search-result');
            return response()->json(['status' => "success", 'body' => $html]);
        } else {
            $html = view('pages.error-pages.access-deny-modal')->render();
            return response()->json(['status' => "success", 'body' => $html]);
        }
    }
    public function getChangePasswordPage(Request $request)
    {
        $userID = $request->session()->get('id');
        $user      =  TbCmnUser::where('Pkid', $userID)->firstOrFail();
        $html =  view('configuration-ui.change-password-page', compact('user'))->render();
        return response()->json(['status' => "success", 'body' => $html]);
    }
}
