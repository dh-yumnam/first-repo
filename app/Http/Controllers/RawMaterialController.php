<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use App\Models\TbCmnFinishGoodsMaster;
use App\Models\TbCmnFundMaster;
use App\Models\TbCmnNedfiBankMaster;
use App\Models\TbCmnReasonMaster;
use App\Models\TbCmnUnitMaster;
use App\Models\TbCmnRawMaterialMaster;
use App\Models\TbCmnProductMaster;
use DateTime;
use App\Models\TbCmnApproval;
use App\Models\TbSmsDisbursementDetail;
use Hamcrest\Arrays\IsArray;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RawMaterialController extends Controller
{
    //Panels
    public function rawMaterialPanel()
    {
        //return view('benificiary-ui.benificiary-search-result');
        //return view('master-ui.panels.raw-material-panel');
        //$rawMaterials      =       TbCmnRawMaterialMaster::all()->sortByDesc('Pkid');
        //return view("master-ui.panels.raw-material-panel", compact('rawMaterials'));

        // return response()->json(["status" => "success", "data" => $rawMaterials, "body" => $html_view]);
    }
    // Models
    public function rawMaterialModal()
    {
        $unitMaster      =       TbCmnUnitMaster::whereIn('Status_Id', [1, 2, 3, 4, 5])->get()->sortBy('Pkid');
        $html = view('master-ui.modal.raw-material-modal', compact('unitMaster'))->render();
        //return view('benificiary-ui.benificiary-search-result');
        return response()->json(['status' => "success", 'body' => $html]);
    }

    public function viewEditMaterialModal($id, $MODE)
    {
        $unitMaster      =       TbCmnUnitMaster::whereIn('Status_Id', [1, 2, 3, 4, 5])->get()->sortBy('Pkid');
        $rawMaterialsUpdate      = TbCmnRawMaterialMaster::join('tb_cmn_status_master', 'tb_cmn_raw_material_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->leftJoin('tb_cmn_unit_master', 'tb_cmn_raw_material_master.Unit_Id_Fk', '=', 'tb_cmn_unit_master.Pkid')
            ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
            ->where('tb_cmn_raw_material_master.Pkid', $id)
            ->where('tb_cmn_raw_material_master.Record_Active_Flag', '1')
            ->get(['tb_cmn_raw_material_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_unit_master.Unit_Name'])->sortBy('Pkid');
        $html = view('master-ui.modal.edit-view-raw-material-modal', compact('unitMaster', 'rawMaterialsUpdate', 'MODE'))->render();
        //return view('benificiary-ui.benificiary-search-result');
        return response()->json(['status' => "success", 'body' => $html]);
    }
    public function searchRawMatetialMasterResult(Request $request)
    {
        $dataUI = json_decode($request->getContent());
        $material_name = $dataUI->material_name;
        $status = $dataUI->status_id;
        $query = TbCmnRawMaterialMaster::join('tb_cmn_status_master', 'tb_cmn_raw_material_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->leftJoin('tb_cmn_unit_master', 'tb_cmn_raw_material_master.Unit_Id_Fk', '=', 'tb_cmn_unit_master.Pkid')
            ->whereIn('tb_cmn_status_master.Pkid', [1, 2, 3, 4, 5])
            ->where('tb_cmn_raw_material_master.Record_Active_Flag', '1');

        if (!empty($dataUI->material_name)) {
            $query->where('Material_Name', 'LIKE', "%{$material_name}%");
        }
        if (!empty($dataUI->status_id)) {
            $query->where('tb_cmn_status_master.Pkid', $status);
        }
        $rawMaterials = $query->get(['tb_cmn_raw_material_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_unit_master.Unit_Name'])->sortBy('Pkid');
        $html = view('master-ui.master-search.raw-materials-search', compact('rawMaterials'))->render();
        //return view('benificiary-ui.benificiary-search-result');
        return response()->json(['status' => "success", 'body' => $html]);
    }

    public function showRawMaterialMaster()
    {
        $rawMaterials      = TbCmnRawMaterialMaster::join('tb_cmn_status_master', 'tb_cmn_raw_material_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->leftJoin('tb_cmn_unit_master', 'tb_cmn_raw_material_master.Unit_Id_Fk', '=', 'tb_cmn_unit_master.Pkid')
            ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
            ->where('tb_cmn_raw_material_master.Record_Active_Flag', '1')
            ->get(['tb_cmn_raw_material_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_unit_master.Unit_Name'])->sortBy('Pkid');
        $html_view = view("master-ui.list-view.raw-material-list-view", compact('rawMaterials'))->render();
        return response()->json(["status" => "success", "body" => $html_view]);
    }
    public function showRawMaterialMasterApproval()
    {
        // DB::table('TbCmnRawMaterialMaster')->where('Status', '1')->get();
        $rawMaterials      =    TbCmnRawMaterialMaster::join('tb_cmn_status_master', 'tb_cmn_raw_material_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->leftJoin('tb_cmn_unit_master', 'tb_cmn_raw_material_master.Unit_Id_Fk', '=', 'tb_cmn_unit_master.Pkid')
            ->whereIn('tb_cmn_status_master.Pkid', [2])
            ->where('tb_cmn_raw_material_master.Record_Active_Flag', '1')
            ->get(['tb_cmn_raw_material_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_unit_master.Unit_Name'])->sortBy('Pkid');
        $html_view = view("master-ui.approval-view.raw-material-approval-view", compact('rawMaterials'))->render();
        return response()->json(["status" => "success", "body" => $html_view]);
    }

    // ------------- [ store post ] -----------------
    public function addRawMaterialMaster(Request $request)
    {
        // $dataUI = json_decode($request->getContent());
        $validationRules = array(
            'Material_Name'         =>      'required|unique:tb_cmn_raw_material_master',
            'Unit_Id_Fk'   =>      'required',
        );

        $validator = Validator::make($request->all(), $validationRules);
        if ($validator->fails()) {
            return response()->json(["status" => "failed", "message" => "Alert! Material Name has already been taken !"]);
        }
        $status = "success";
        try {
            //  $addMaterials            =           TbCmnRawMaterialMaster::create($request->all());
            $addMaterials = new TbCmnRawMaterialMaster();
            $addMaterials->Material_Name = $request->Material_Name;
            $addMaterials->Description = $request->Description;
            $addMaterials->Unit_Id_Fk = $request->Unit_Id_Fk;
            $addMaterials->Status_Id = config("constants.CREATED");
            $result = (new CommonController)->insertDefaultColumns($request, $addMaterials);
            $addMaterials->save();
            $status = "success";
        } catch (\Exception $ex) {
            throw $ex;
            $status = "failed";
        }
        $rawMaterials      =       TbCmnRawMaterialMaster::join('tb_cmn_status_master', 'tb_cmn_raw_material_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->leftJoin('tb_cmn_unit_master', 'tb_cmn_raw_material_master.Unit_Id_Fk', '=', 'tb_cmn_unit_master.Pkid')
            ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
            ->where('tb_cmn_raw_material_master.Record_Active_Flag', '1')
            ->get(['tb_cmn_raw_material_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_unit_master.Unit_Name'])->sortBy('Pkid');
        $html_view = view("master-ui.list-view.raw-material-list-view", compact('rawMaterials'))->render();

        $MODE = 'EDT';
        $unitMaster      =       TbCmnUnitMaster::whereIn('Status_Id', [1, 2, 3, 4, 5])->get()->sortBy('Pkid');
        $rawMaterialsUpdate      = TbCmnRawMaterialMaster::join('tb_cmn_status_master', 'tb_cmn_raw_material_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->leftJoin('tb_cmn_unit_master', 'tb_cmn_raw_material_master.Unit_Id_Fk', '=', 'tb_cmn_unit_master.Pkid')
            ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
            ->where('tb_cmn_raw_material_master.Pkid', $addMaterials->Pkid)
            ->where('tb_cmn_raw_material_master.Record_Active_Flag', '1')
            ->get(['tb_cmn_raw_material_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_unit_master.Unit_Name'])->sortBy('Pkid');
        $html_view1 = view('master-ui.modal.edit-view-raw-material-modal', compact('unitMaster', 'rawMaterialsUpdate', 'MODE'))->render();

        if ($status == "success") {
            return response()->json(["status" => "success", "message" => "Success! Raw Material created.", "data" => $addMaterials, "body" => $html_view, "body1" => $html_view1]);
        } else {
            return response()->json(["status" => "failed", "message" => "Alert! Raw Material not created"]);
        }
    }
    // ---------------- [ Update post ] -------------
    public function updateRawMaterialMaster(Request $request, $id)
    {
        $dataUI = json_decode($request->getContent());
        DB::beginTransaction();
        try {
            $material = TbCmnRawMaterialMaster::where('Pkid', $id)
                ->where('tb_cmn_raw_material_master.Record_Active_Flag', '1')->get(['tb_cmn_raw_material_master.*']);
            // $UniqueName = TbCmnBenificiaryMaster::where('Benificiary_Name', $Benificiary_Name)->get();
            $materialName = null;
            foreach ($material as $material) {
                $materialName = $material->Material_Name;
            }

            if ($materialName != $dataUI->Material_Name) {
                $DuplicateMaterial = TbCmnRawMaterialMaster::where('Material_Name', $dataUI->Material_Name)
                    ->where('tb_cmn_raw_material_master.Record_Active_Flag', 1)->get(['tb_cmn_raw_material_master.*']);
                if (count($DuplicateMaterial) > 0) {
                    return response()->json(["status" => "failed", "message" => "Alert! Duplicate raw material !"]);
                }
            }
            $rawMaterials = array(
                'Material_Name' => $dataUI->Material_Name, "Unit_Id_Fk" => $dataUI->Unit_Id_Fk, 'Description' => $dataUI->Description,
                'Record_Update_Date' => new \DateTime(), 'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'), 'Updated_By' => $request->session()->get('id')
            );
            $addresBenificiary = TbCmnRawMaterialMaster::where('Pkid', $id)->update($rawMaterials);
            $status = "success";

            // all good
            DB::commit();
        } catch (\Exception $e) {
            throw $e;
            $status = "failed";
            DB::rollback();
            // something went wrong
        }
        $rawMaterials      =       TbCmnRawMaterialMaster::join('tb_cmn_status_master', 'tb_cmn_raw_material_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->leftJoin('tb_cmn_unit_master', 'tb_cmn_raw_material_master.Unit_Id_Fk', '=', 'tb_cmn_unit_master.Pkid')
            ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
            ->where('tb_cmn_raw_material_master.Record_Active_Flag', '1')
            ->get(['tb_cmn_raw_material_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_unit_master.Unit_Name'])->sortBy('Pkid');
        $html_view = view("master-ui.list-view.raw-material-list-view", compact('rawMaterials'))->render();

        $MODE = 'EDT';
        $unitMaster      =       TbCmnUnitMaster::whereIn('Status_Id', [1, 2, 3, 4, 5])->get()->sortBy('Pkid');
        $rawMaterialsUpdate      = TbCmnRawMaterialMaster::join('tb_cmn_status_master', 'tb_cmn_raw_material_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->leftJoin('tb_cmn_unit_master', 'tb_cmn_raw_material_master.Unit_Id_Fk', '=', 'tb_cmn_unit_master.Pkid')
            ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
            ->where('tb_cmn_raw_material_master.Pkid', $id)
            ->where('tb_cmn_raw_material_master.Record_Active_Flag', '1')
            ->get(['tb_cmn_raw_material_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_unit_master.Unit_Name'])->sortBy('Pkid');
        $html_view1 = view('master-ui.modal.edit-view-raw-material-modal', compact('unitMaster', 'rawMaterialsUpdate', 'MODE'))->render();

        if ($status == "success") {
            return response()->json(["status" => $status, "message" => "Success! Raw material updated.", "body" => $html_view, "body1" => $html_view1]);
        } else {
            return response()->json(["status" => $status, "message" => "Alert! Raw material not updates"]);
        }
    }

    // -------------- [ Delete post ] ---------------
    public function destroyRawMaterialMaster(Request $request, $raw_id)
    {
        $deleteMaterials       =       TbCmnRawMaterialMaster::where("Pkid", $raw_id)
            ->update([
                'Record_Active_Flag' => '0',
                'Record_Update_Date' => new \DateTime(),
                'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'),
                'Updated_By' => $request->session()->get('id')
            ]);

        $rawMaterials      =       TbCmnRawMaterialMaster::join('tb_cmn_status_master', 'tb_cmn_raw_material_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->leftJoin('tb_cmn_unit_master', 'tb_cmn_raw_material_master.Unit_Id_Fk', '=', 'tb_cmn_unit_master.Pkid')
            ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
            ->where('tb_cmn_raw_material_master.Record_Active_Flag', '1')
            ->get(['tb_cmn_raw_material_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_unit_master.Unit_Name'])->sortBy('Pkid');
        $html_view = view("master-ui.list-view.raw-material-list-view", compact('rawMaterials'))->render();
        if ($deleteMaterials == 1) {
            return response()->json(["status" => "success", "message" => "Success! Raw Material deleted", "body" => $html_view]);
        } else {
            return response()->json(["status" => "failed", "message" => "Alert! Raw Material not deleted"]);
        }
    }


    public function approveMaterialEntry(Request $request)
    {
        DB::beginTransaction();
        try {
            $dataUI = json_decode($request->getContent());
            $approvalStatus = $dataUI->decision;
            $approvalDate = $dataUI->approval_date;
            $remarks = $dataUI->remarks;
            if (Is_Array($dataUI->check_id)) {
                foreach ($dataUI->check_id as $value) {
                    TbCmnRawMaterialMaster::where('Pkid', $value)->update(
                        [
                            'Status_Id' => $approvalStatus, 'Record_Update_Date' => new \DateTime(), 'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'),
                            'Updated_By' => $request->session()->get('id')
                        ]
                    );
                    $saveCmnApproval = new TbCmnApproval();
                    $saveCmnApproval->Approval_Date = $approvalDate;
                    $saveCmnApproval->Status_Id_Fk = $approvalStatus;
                    $saveCmnApproval->Remarks = $remarks;
                    $saveCmnApproval->Module = 'Raw Material master';
                    $saveCmnApproval->Record_Id_Fk =  $value;
                    $saveCmnApproval->save();
                }
            } else {
                TbCmnRawMaterialMaster::where('Pkid', $dataUI->check_id)->update(
                    [
                        'Status_Id' => $approvalStatus, 'Record_Update_Date' => new \DateTime(), 'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'),
                        'Updated_By' => $request->session()->get('id')
                    ]
                );
                $saveCmnApproval = new TbCmnApproval();
                $saveCmnApproval->Approval_Date = $approvalDate;
                $saveCmnApproval->Status_Id_Fk = $approvalStatus;
                $saveCmnApproval->Remarks = $remarks;
                $saveCmnApproval->Module = 'Raw Material master';
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
        $rawMaterials      = TbCmnRawMaterialMaster::join('tb_cmn_status_master', 'tb_cmn_raw_material_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->leftJoin('tb_cmn_unit_master', 'tb_cmn_raw_material_master.Unit_Id_Fk', '=', 'tb_cmn_unit_master.Pkid')
            ->whereIn('tb_cmn_status_master.Pkid', [2])
            ->where('tb_cmn_raw_material_master.Record_Active_Flag', '1')
            ->get(['tb_cmn_raw_material_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_unit_master.Unit_Name'])->sortBy('Pkid');
        $html_view = view("master-ui.list-view.raw-material-list-view", compact('rawMaterials'))->render();
        if ($status == 'success') {
            return response()->json(["status" => "success", "message" => "Success! Raw material master " . $decision, "body" => $html_view]);
        } else {
            return response()->json(["status" => "success", "message" => "Alert! Raw material master not " . $decision]);
        }
    }

    // Approval record
    public function rawMaterialApproval(Request $request, $id)
    {
        $dataUI = json_decode($request->getContent());
        DB::beginTransaction();
        try {
            $tableData = array(
                'Status_Id' => '2', 'Record_Update_Date' => new \DateTime(), 'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'), 'Updated_By' => $request->session()->get('id')
            );
            $updateTable = TbCmnRawMaterialMaster::where('Pkid',  $id)
                ->update($tableData);
            $status = "success";
            // all good
            DB::commit();
        } catch (\Exception $e) {
            throw $e;
            $status = "failed";
            DB::rollback();
            // something went wrong
        }
        $rawMaterials      =       TbCmnRawMaterialMaster::join('tb_cmn_status_master', 'tb_cmn_raw_material_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->leftJoin('tb_cmn_unit_master', 'tb_cmn_raw_material_master.Unit_Id_Fk', '=', 'tb_cmn_unit_master.Pkid')
            ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
            ->where('tb_cmn_raw_material_master.Record_Active_Flag', '1')
            ->get(['tb_cmn_raw_material_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_unit_master.Unit_Name'])->sortBy('Pkid');
        $html_view = view("master-ui.list-view.raw-material-list-view", compact('rawMaterials'))->render();
        if ($status == "success") {
            return response()->json(["status" => $status, "message" => "Success! Raw material submitted for approval.", "body" => $html_view]);
        } else {
            return response()->json(["status" => $status, "message" => "Alert! Raw material not submitted for approval"]);
        }
    }
}
