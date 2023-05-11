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
use Illuminate\Support\Facades\Validator;
use App\Models\TbCmnApproval;
use App\Models\TbSmsDisbursementDetail;
use Hamcrest\Arrays\IsArray;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Input;
use Symfony\Component\Console\Input\Input as InputInput;

class UnitController extends Controller
{
    //
    public function unitMasterPanel()
    {
        $unitMaster      =       TbCmnUnitMaster::whereIn('Status_Id', [1, 3])
            ->where('tb_cmn_unit_master.Record_Active_Flag',  1)
            ->get(['tb_cmn_unit_master.*'])->sortBy('Pkid');
        return view('master-ui.panels.unit-master-panel', compact('unitMaster'))->render();
    }

    public function unitMasterModal()
    {
        $html = view('master-ui.modal.unit-master-modal')->render();
        return response()->json(['status' => "success", 'body' => $html]);
    }

    public function searchUnitMasterResult(Request $request)
    {
        $dataUI = json_decode($request->getContent());
        $unit_name = $dataUI->search_unit_name;
        $status = $dataUI->status_id;
        $query = TbCmnUnitMaster::join('tb_cmn_status_master', 'tb_cmn_unit_master.Status_Id', '=', 'tb_cmn_status_master.Pkid');

        if (!empty($dataUI->search_unit_name)) {
            $query->where('Unit_Name', 'LIKE', "%{$unit_name}%");
        }
        if (!empty($dataUI->status_id)) {
            $query->where('Status_Id', $status);
        }

        $unitMaster = $query
            ->where('tb_cmn_unit_master.Record_Active_Flag', 1)
            ->get(['tb_cmn_unit_master.*', 'tb_cmn_status_master.Status_Name']);
        $html = view('master-ui.master-search.unit-master-search', compact('unitMaster'))->render();
        return response()->json(['status' => "success", 'body' => $html]);
    }

    // ---------------- [ Update post ] -------------
    /*  public function updateRawMaterialMaster(Request $request)
    {
        $post_id        =       $request->id;
        $post           =       TbCmnRawMaterialMaster::where("id", $post_id)->update($request->all());
        $list      =       TbCmnRawMaterialMaster::latest()->paginate(5);
        $html_view = view("list",compact('list'))->render();
        if ($post == 1) {
            return response()->json(["status" => "success", "message" => "Success! post updated","body"=> $html_view]);
        } else {
            return response()->json(["status" => "failed", "message" => "Alert! post not updated"]);
        }
    }*/


    public function showUnitMaster()
    {
        $unitMaster      =       TbCmnUnitMaster::join('tb_cmn_status_master', 'tb_cmn_unit_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
            ->where('tb_cmn_unit_master.Record_Active_Flag',  1)
            ->get(['tb_cmn_unit_master.*', 'tb_cmn_status_master.Status_Name'])
            ->sortByDesc('Pkid');
        $html_view = view("master-ui.list-view.unit-master-list-view", compact('unitMaster'))->render();
        return response()->json(["status" => "success", "message" => "Success! Subsidy Master created.", "body" => $html_view]);
    }

    public function showUnitApproval()
    {
        $unitMaster      =       TbCmnUnitMaster::join('tb_cmn_status_master', 'tb_cmn_unit_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->whereIn('Status_Id', [2])
            ->where('tb_cmn_unit_master.Record_Active_Flag', 1)
            ->get(['tb_cmn_unit_master.*', 'tb_cmn_status_master.Status_Name'])->sortByDesc('Pkid');
        $html_view = view("master-ui.approval-view.unit-master-approval-view", compact('unitMaster'))->render();
        return response()->json(["status" => "success", "message" => "Success! Subsidy Master created.", "body" => $html_view]);
    }
    // ------------- [ store post ] -----------------
    public function addUnitMaster(Request $request)
    {
        $validationRules = array(
            'Unit_Name'         =>      'required|unique:tb_cmn_unit_master',
            'Description'   =>      'required',
        );
        $validator = Validator::make($request->all(), $validationRules);
        if ($validator->fails()) {
            return response()->json(["status" => "failed", "message" => "Alert! Unit Name has already been taken !"]);
        }
        $status = "success";
        try {
            //  $addMaterials            =           TbCmnRawMaterialMaster::create($request->all());
            $addUnitMaster = new TbCmnUnitMaster();
            $addUnitMaster->Unit_Name = $request->Unit_Name;
            $addUnitMaster->Description = $request->Description;
            $addUnitMaster->Status_Id = config("constants.CREATED");
            $result = (new CommonController)->insertDefaultColumns($request, $addUnitMaster);
            $addUnitMaster->save();
            $status = "success";
        } catch (\Exception $ex) {
            throw $ex;
            $status = "failed";
        }
        $unitMaster      =       TbCmnUnitMaster::join('tb_cmn_status_master', 'tb_cmn_unit_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
            ->where('tb_cmn_unit_master.Record_Active_Flag', 1)
            ->get(['tb_cmn_unit_master.*', 'tb_cmn_status_master.Status_Name'])->sortBy('Pkid');
        $html_view = view("master-ui.list-view.unit-master-list-view", compact('unitMaster'))->render();
        $MODE = 'EDT';
        $unitMasterUpdate     =       TbCmnUnitMaster::join('tb_cmn_status_master', 'tb_cmn_unit_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->where('tb_cmn_unit_master.Pkid', $addUnitMaster->Pkid)
            ->where('tb_cmn_unit_master.Record_Active_Flag',  1)
            ->get(['tb_cmn_unit_master.*', 'tb_cmn_status_master.Status_Name'])->sortBy('Pkid');
        $html_view1 = view("master-ui.modal.edit-view-unit-master", compact('unitMasterUpdate', 'MODE'))->render();

        if ($status == "success") {
            return response()->json(["status" => "success", "message" => "Success! Unit Master created.", "data" => $addUnitMaster, "body" => $html_view, "body1" => $html_view1]);
        } else {
            return response()->json(["status" => "failed", "message" => "Alert! Unit Master not created"]);
        }
    }

    // -------------- [ Delete post ] ---------------
    public function destroyUnitMaster(Request $request, $unit_id)
    {
        $deleteProductMaster       =       TbCmnUnitMaster::where("Pkid", $unit_id)
            ->update([
                'Record_Active_Flag' => '0',
                'Record_Update_Date' => new \DateTime(),
                'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'),
                'Updated_By' => $request->session()->get('id')
            ]);
        $unitMaster     =       TbCmnUnitMaster::join('tb_cmn_status_master', 'tb_cmn_unit_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
            ->where('tb_cmn_unit_master.Record_Active_Flag', 1)
            ->get(['tb_cmn_unit_master.*', 'tb_cmn_status_master.Status_Name'])->sortBy('Pkid');
        $html_view = view("master-ui.list-view.unit-master-list-view", compact('unitMaster'))->render();
        if ($deleteProductMaster == 1) {
            return response()->json(["status" => "success", "message" => "Success! Unit Master deleted", "body" => $html_view]);
        } else {
            return response()->json(["status" => "failed", "message" => "Alert! Unit Master not deleted"]);
        }
    }
    public function approveUnitEntry(Request $request)
    {
        DB::beginTransaction();
        try {
            $dataUI = json_decode($request->getContent());
            $approvalStatus = $dataUI->decision;
            $approvalDate = $dataUI->approval_date;
            $remarks = $dataUI->remarks;
            if (Is_Array($dataUI->check_id)) {
                foreach ($dataUI->check_id as $value) {
                    TbCmnUnitMaster::where('Pkid', $value)->update(
                        [
                            'Status_Id' => $approvalStatus, 'Record_Update_Date' => new \DateTime(), 'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'),
                            'Updated_By' => $request->session()->get('id')
                        ]
                    );
                    $saveCmnApproval = new TbCmnApproval();
                    $saveCmnApproval->Approval_Date = $approvalDate;
                    $saveCmnApproval->Status_Id_Fk = $approvalStatus;
                    $saveCmnApproval->Remarks = $remarks;
                    $saveCmnApproval->Module = 'Unit master';
                    $saveCmnApproval->Record_Id_Fk =  $value;
                    $saveCmnApproval->save();
                }
            } else {
                TbCmnUnitMaster::where('Pkid', $dataUI->check_id)->update(
                    [
                        'Status_Id' => $approvalStatus, 'Record_Update_Date' => new \DateTime(), 'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'),
                        'Updated_By' => $request->session()->get('id')
                    ]
                );
                $saveCmnApproval = new TbCmnApproval();
                $saveCmnApproval->Approval_Date = $approvalDate;
                $saveCmnApproval->Status_Id_Fk = $approvalStatus;
                $saveCmnApproval->Remarks = $remarks;
                $saveCmnApproval->Module = 'Unit master';
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
        $unitMaster      =       TbCmnUnitMaster::join('tb_cmn_status_master', 'tb_cmn_unit_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->whereIn('Status_Id', [2])
            ->where('tb_cmn_unit_master.Record_Active_Flag', 1)
            ->get(['tb_cmn_unit_master.*', 'tb_cmn_status_master.Status_Name'])->sortBy('Pkid');
        $html_view = view("master-ui.approval-view.unit-master-approval-view", compact('unitMaster'))->render();
        if ($status == 'success') {
            return response()->json(["status" => "success", "message" => "Success! Unit master " . $decision, "body" => $html_view]);
        } else {
            return response()->json(["status" => "success", "message" => "Alert! Unit master not " . $decision]);
        }
    }

    public function viewEditUnitModal($id, $MODE)
    {
        $unitMasterUpdate     =       TbCmnUnitMaster::join('tb_cmn_status_master', 'tb_cmn_unit_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->where('tb_cmn_unit_master.Pkid', $id)
            ->where('tb_cmn_unit_master.Record_Active_Flag', 1)
            ->get(['tb_cmn_unit_master.*', 'tb_cmn_status_master.Status_Name'])->sortBy('Pkid');
        $html_view = view("master-ui.modal.edit-view-unit-master", compact('unitMasterUpdate', 'MODE'))->render();
        //return view('benificiary-ui.benificiary-search-result');
        return response()->json(['status' => "success", 'body' => $html_view]);
    }

    // ---------------- [ Update post ] -------------
    public function updateUnitMaster(Request $request, $id)
    {
        $dataUI = json_decode($request->getContent());
        DB::beginTransaction();
        try {
            $unit = TbCmnUnitMaster::where('Pkid', $id)->get(['tb_cmn_unit_master.*']);
            // $UniqueName = TbCmnBenificiaryMaster::where('Benificiary_Name', $Benificiary_Name)->get();
            $unitName = null;
            foreach ($unit as $unit) {
                $unitName = $unit->Unit_Name;
            }
            if ($unitName != $dataUI->Unit_Name) {
                $DuplicateRecord = TbCmnUnitMaster::where('Unit_Name', $dataUI->Unit_Name)
                    ->where('tb_cmn_unit_master.Record_Active_Flag', 1)
                    ->get();
                if (count($DuplicateRecord) > 0) {
                    return response()->json(["status" => "failed", "message" => "Alert! Duplicate Unit name !"]);
                }
            }
            $addressData = array(
                'Unit_Name' => $dataUI->Unit_Name, "Description" => $dataUI->Description,
                'Record_Update_Date' => new \DateTime(), 'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'), 'Updated_By' => $request->session()->get('id')
            );
            $addresBenificiary = TbCmnUnitMaster::where('Pkid', $id)->update($addressData);
            $status = "success";

            // all good
            DB::commit();
        } catch (\Exception $e) {
            throw $e;
            $status = "failed";
            DB::rollback();
            // something went wrong
        }
        $unitMaster     =       TbCmnUnitMaster::join('tb_cmn_status_master', 'tb_cmn_unit_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
            ->where('tb_cmn_unit_master.Record_Active_Flag',  1)
            ->get(['tb_cmn_unit_master.*', 'tb_cmn_status_master.Status_Name'])->sortBy('Pkid');
        $html_view = view("master-ui.list-view.unit-master-list-view", compact('unitMaster'))->render();
        $MODE = 'EDT';
        $unitMasterUpdate     =       TbCmnUnitMaster::join('tb_cmn_status_master', 'tb_cmn_unit_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->where('tb_cmn_unit_master.Pkid', $id)
            ->where('tb_cmn_unit_master.Record_Active_Flag',  1)
            ->get(['tb_cmn_unit_master.*', 'tb_cmn_status_master.Status_Name'])->sortBy('Pkid');
        $html_view1 = view("master-ui.modal.edit-view-unit-master", compact('unitMasterUpdate', 'MODE'))->render();
        if ($status == "success") {
            return response()->json(["status" => $status, "message" => "Success! Unit name updated.", "body" => $html_view, "body1" => $html_view1]);
        } else {
            return response()->json(["status" => $status, "message" => "Alert! Unit name not updates"]);
        }
    }

    // Approval record
    public function unitMasterApproval(Request $request, $id)
    {
        $dataUI = json_decode($request->getContent());
        DB::beginTransaction();
        try {
            $tableData = array(
                'Status_Id' => '2', 'Record_Update_Date' => new \DateTime(), 'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'), 'Updated_By' => $request->session()->get('id')
            );
            $updateTable = TbCmnUnitMaster::where('Pkid',  $id)
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
        $unitMaster     =       TbCmnUnitMaster::join('tb_cmn_status_master', 'tb_cmn_unit_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
            ->where('tb_cmn_unit_master.Record_Active_Flag',  1)
            ->get(['tb_cmn_unit_master.*', 'tb_cmn_status_master.Status_Name'])->sortBy('Pkid');
        $html_view = view("master-ui.list-view.unit-master-list-view", compact('unitMaster'))->render();
        if ($status == "success") {
            return response()->json(["status" => $status, "message" => "Success! Unit master submitted for approval.", "body" => $html_view]);
        } else {
            return response()->json(["status" => $status, "message" => "Alert! Unit master not submitted for approval"]);
        }
    }
}
