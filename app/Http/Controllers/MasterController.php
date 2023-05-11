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
use App\Models\TbCmnPolicyMaster;


class MasterController extends Controller
{

    public function materialPanel()
    {
        $accessBoolean = (new CommonController)->checkAccessRightToController('materialPanel');
        if ($accessBoolean) {
            $unitMaster      =       TbCmnUnitMaster::join('tb_cmn_status_master', 'tb_cmn_unit_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
                ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
                ->where('tb_cmn_unit_master.Record_Active_Flag', 1)
                ->get(['tb_cmn_unit_master.*', 'tb_cmn_status_master.Status_Name'])->sortBy('Pkid');
            $rawMaterials      =       TbCmnRawMaterialMaster::join('tb_cmn_status_master', 'tb_cmn_raw_material_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
                ->leftJoin('tb_cmn_unit_master', 'tb_cmn_raw_material_master.Unit_Id_Fk', '=', 'tb_cmn_unit_master.Pkid')
                ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
                ->where('tb_cmn_raw_material_master.Record_Active_Flag', 1)
                ->get(['tb_cmn_raw_material_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_unit_master.Unit_Name'])->sortBy('Pkid');
            $finishGoods      =       TbCmnFinishGoodsMaster::join('tb_cmn_status_master', 'tb_cmn_finish_goods_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
                ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
                ->where('tb_cmn_finish_goods_master.Record_Active_Flag', 1)
                ->get(['tb_cmn_finish_goods_master.*', 'tb_cmn_status_master.Status_Name'])->sortBy('Pkid');

            $bankMaster      =        TbCmnNedfiBankMaster::join('tb_cmn_status_master', 'tb_cmn_nedfi_bank_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
                ->join('tb_cmn_scheme_master', 'tb_cmn_nedfi_bank_master.Scheme_Id_Fk', '=', 'tb_cmn_scheme_master.Pkid')
                ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
                ->where('tb_cmn_nedfi_bank_master.Record_Active_Flag', 1)
                ->get(['tb_cmn_nedfi_bank_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_scheme_master.Scheme_Name'])
                ->sortBy('Pkid');
            $subsidyMaster      =       TbCmnSchemeMaster::all()->sortBy('Scheme_Name');
            $fundMaster      =         TbCmnFundMaster::join('tb_cmn_status_master', 'tb_cmn_fund_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
                ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
                ->where('tb_cmn_fund_master.Record_Active_Flag', 1)
                ->get(['tb_cmn_fund_master.*', 'tb_cmn_status_master.Status_Name'])->sortBy('Pkid');
            $reasonMaster      =       TbCmnReasonMaster::join('tb_cmn_status_master', 'tb_cmn_reason_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
                ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
                ->where('tb_cmn_reason_master.Record_Active_Flag', 1)
                ->get(['tb_cmn_reason_master.*', 'tb_cmn_status_master.Status_Name'])->sortBy('Pkid');
            $schemeMaster      =       TbCmnSchemeMaster::join('tb_cmn_status_master', 'tb_cmn_scheme_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
                ->join('tb_cmn_policy_master', 'tb_cmn_scheme_master.Gov_policy', '=', 'tb_cmn_policy_master.Pkid')
                ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
                ->where('tb_cmn_scheme_master.Record_Active_Flag', 1)
                ->get(['tb_cmn_scheme_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_policy_master.Policy_Name'])
                ->sortBy('Pkid');

            $govPolicy      =       TbCmnPolicyMaster::all()->sortBy('Policy_Name');
            $statusMaster      =       TbCmnStatusMaster::all()->sortBy('Pkid');
            $approvalStatusMaster      =       TbCmnStatusMaster::select('Pkid', 'Status_Name')->where('Pkid', '>', '2')->get();
            // $users = DB::table('users')->get();
            $html = view('master-ui.master-setting', compact('unitMaster', 'rawMaterials', 'finishGoods', 'bankMaster', 'fundMaster', 'reasonMaster', 'schemeMaster', 'statusMaster', 'approvalStatusMaster', 'govPolicy', 'subsidyMaster'))->render();
            return response()->json(['status' => "success", 'body' => $html]);
        } else {
            $html = view('pages.error-pages.access-deny-modal')->render();
            return response()->json(['status' => "success", 'body' => $html]);
        }
    }
}
