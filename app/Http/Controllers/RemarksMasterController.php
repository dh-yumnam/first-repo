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

use App\Models\TbCmnApproval;
use App\Models\TbSmsDisbursementDetail;
use Hamcrest\Arrays\IsArray;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RemarksMasterController extends Controller
{

    public function remarksMasterPanel()
    {
        //return view('benificiary-ui.benificiary-search-result');
        return view('master-ui.panels.remark-master-panel');
    }

    public function remarksMasterModal()
    {
        $html = view('master-ui.modal.remarks-master-modal')->render();
        //return view('benificiary-ui.benificiary-search-result');
        return response()->json(['status' => "success", 'body' => $html]);
    }
    public function searchRemarksMasterResult(Request $request)
    {

        $dataUI = json_decode($request->getContent());
        $remarks_name = $dataUI->remarks_name;
        $status = $dataUI->status_id;
        $query = TbCmnReasonMaster::join('tb_cmn_status_master', 'tb_cmn_reason_master.Status_Id', '=', 'tb_cmn_status_master.Pkid');

        if (!empty($dataUI->remarks_name)) {
            $query->where('Reason_Details', 'LIKE', "%{$remarks_name}%");
        }
        if (!empty($dataUI->status_id)) {
            $query->where('Status_Id', $status);
        }

        $reasonMaster = $query
            ->where('tb_cmn_reason_master.Record_Active_Flag', '=', 1)->get(['tb_cmn_reason_master.*', 'tb_cmn_status_master.Status_Name']);
        $html = view('master-ui.master-search.remarks-master-search', compact('reasonMaster'))->render();
        //return view('benificiary-ui.benificiary-search-result');
        return response()->json(['status' => "success", 'body' => $html]);
    }

    public function showRemarksMaster()
    {
        $reasonMaster      =        TbCmnReasonMaster::join('tb_cmn_status_master', 'tb_cmn_reason_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
            ->where('tb_cmn_reason_master.Record_Active_Flag', '=', 1)
            ->get(['tb_cmn_reason_master.*', 'tb_cmn_status_master.Status_Name'])
            ->sortBy('Pkid');
        $html_view = view("master-ui.list-view.remarks-master-list-view", compact('reasonMaster'))->render();
        return response()->json(["status" => "success", "message" => "Success! Remarks Master created.",  "body" => $html_view]);
    }

    public function showRemarksMasterApproval()
    {
        $reasonMaster      =        TbCmnReasonMaster::join('tb_cmn_status_master', 'tb_cmn_reason_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->whereIn('Status_Id', [2])
            ->where('tb_cmn_reason_master.Record_Active_Flag', '=', 1)
            ->get(['tb_cmn_reason_master.*', 'tb_cmn_status_master.Status_Name'])->sortBy('Pkid');
        $html_view = view("master-ui.approval-view.remarks-master-approval-view", compact('reasonMaster'))->render();
        return response()->json(["status" => "success", "message" => "Success! Remarks Master created.",  "body" => $html_view]);
    }
    // ------------- [ store post ] -----------------
    public function addRemarksMaster(Request $request)
    {
        $validationRules = array(
            'Reason_Details'         =>      'required|unique:tb_cmn_reason_master',
            'Description'   =>      'required',
        );
        $validator = Validator::make($request->all(), $validationRules);
        if ($validator->fails()) {
            return response()->json(["status" => "failed", "message" => "Alert! Reason Details has already been taken !"]);
        }

        $status = "success";
        try {
            //  $addMaterials            =           TbCmnRawMaterialMaster::create($request->all());
            $addReasonMaster = new TbCmnReasonMaster();
            $addReasonMaster->Reason_Details = $request->Reason_Details;
            $addReasonMaster->Description = $request->Description;
            $addReasonMaster->Status_Id = config("constants.CREATED");
            $result = (new CommonController)->insertDefaultColumns($request, $addReasonMaster);
            $addReasonMaster->save();
            $status = "success";
        } catch (\Exception $ex) {
            throw $ex;
            $status = "failed";
        }

        $reasonMaster      =        TbCmnReasonMaster::join('tb_cmn_status_master', 'tb_cmn_reason_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
            ->where('tb_cmn_reason_master.Record_Active_Flag', '=', 1)
            ->get(['tb_cmn_reason_master.*', 'tb_cmn_status_master.Status_Name'])->sortBy('Pkid');
        $html_view = view("master-ui.list-view.remarks-master-list-view", compact('reasonMaster'))->render();
        $MODE = 'EDT';
        $reasonMasterUpdate     =        TbCmnReasonMaster::join('tb_cmn_status_master', 'tb_cmn_reason_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->where('tb_cmn_reason_master.Pkid', $addReasonMaster->Pkid)
            ->where('tb_cmn_reason_master.Record_Active_Flag', '=', 1)
            ->get(['tb_cmn_reason_master.*', 'tb_cmn_status_master.Status_Name'])->sortBy('Pkid');

        $html_view1 = view("master-ui.modal.edit-view-ramarks-master-modal", compact('reasonMasterUpdate', 'MODE'))->render();

        if ($status == "success") {
            return response()->json(["status" => "success", "message" => "Success! Remarks Master created.", "body" => $html_view, "body1" => $html_view1]);
        } else {
            return response()->json(["status" => "failed", "message" => "Alert! Remarks Master not created"]);
        }
    }

    // -------------- [ Delete post ] ---------------
    public function destroyRemarksMaster(Request $request, $raw_id)
    {
        $deleteRemarksMaster       =       TbCmnReasonMaster::where("Pkid", $raw_id)
            ->update([
                'Record_Active_Flag' => '0',
                'Record_Update_Date' => new \DateTime(),
                'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'),
                'Updated_By' => $request->session()->get('id')
            ]);
        $reasonMaster     =        TbCmnReasonMaster::join('tb_cmn_status_master', 'tb_cmn_reason_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
            ->where('tb_cmn_reason_master.Record_Active_Flag', '=', 1)
            ->where('tb_cmn_reason_master.Record_Active_Flag', '=', 1)
            ->get(['tb_cmn_reason_master.*', 'tb_cmn_status_master.Status_Name'])->sortBy('Pkid');
        $html_view = view("master-ui.list-view.remarks-master-list-view", compact('reasonMaster'))->render();
        if ($deleteRemarksMaster == 1) {
            return response()->json(["status" => "success", "message" => "Success! Remarks Master deleted", "body" => $html_view]);
        } else {
            return response()->json(["status" => "failed", "message" => "Alert! Remarks Master not deleted"]);
        }
    }


    public function approveRemarksEntry(Request $request)
    {
        DB::beginTransaction();
        try {
            $dataUI = json_decode($request->getContent());
            $approvalStatus = $dataUI->decision;
            $approvalDate = $dataUI->approval_date;
            $remarks = $dataUI->remarks;
            if (Is_Array($dataUI->check_id)) {
                foreach ($dataUI->check_id as $value) {
                    TbCmnReasonMaster::where('Pkid', $value)->update(
                        [
                            'Status_Id' => $approvalStatus, 'Record_Update_Date' => new \DateTime(), 'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'),
                            'Updated_By' => $request->session()->get('id')
                        ]
                    );
                    $saveCmnApproval = new TbCmnApproval();
                    $saveCmnApproval->Approval_Date = $approvalDate;
                    $saveCmnApproval->Status_Id_Fk = $approvalStatus;
                    $saveCmnApproval->Remarks = $remarks;
                    $saveCmnApproval->Module = 'Remarks master';
                    $saveCmnApproval->Record_Id_Fk =  $value;
                    $saveCmnApproval->save();
                }
            } else {
                TbCmnReasonMaster::where('Pkid', $dataUI->check_id)->update(
                    [
                        'Status_Id' => $approvalStatus, 'Record_Update_Date' => new \DateTime(), 'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'),
                        'Updated_By' => $request->session()->get('id')
                    ]
                );
                $saveCmnApproval = new TbCmnApproval();
                $saveCmnApproval->Approval_Date = $approvalDate;
                $saveCmnApproval->Status_Id_Fk = $approvalStatus;
                $saveCmnApproval->Remarks = $remarks;
                $saveCmnApproval->Module = 'Remarks master';
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
        $reasonMaster      =        TbCmnReasonMaster::join('tb_cmn_status_master', 'tb_cmn_reason_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->whereIn('Status_Id', [2])
            ->where('tb_cmn_reason_master.Record_Active_Flag', '=', 1)
            ->get(['tb_cmn_reason_master.*', 'tb_cmn_status_master.Status_Name'])->sortByDesc('Pkid');
        $html_view = view("master-ui.approval-view.remarks-master-approval-view", compact('reasonMaster'))->render();
        if ($status == 'success') {
            return response()->json(["status" => "success", "message" => "Success! Remarks master " . $decision, "body" => $html_view]);
        } else {
            return response()->json(["status" => "success", "message" => "Alert! Remarks master not " . $decision]);
        }
    }
    public function viewEditRemarksModal($id, $MODE)
    {

        $reasonMasterUpdate     =        TbCmnReasonMaster::join('tb_cmn_status_master', 'tb_cmn_reason_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->where('tb_cmn_reason_master.Pkid', $id)
            ->where('tb_cmn_reason_master.Record_Active_Flag', '=', 1)
            ->get(['tb_cmn_reason_master.*', 'tb_cmn_status_master.Status_Name'])->sortBy('Pkid');

        $html_view = view("master-ui.modal.edit-view-ramarks-master-modal", compact('reasonMasterUpdate', 'MODE'))->render();
        return response()->json(['status' => "success", 'body' => $html_view]);
    }
    // Approval record
    public function remarksMasterApproval(Request $request, $id)
    {
        $dataUI = json_decode($request->getContent());
        DB::beginTransaction();
        try {
            $tableData = array(
                'Status_Id' => '2', 'Record_Update_Date' => new \DateTime(), 'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'), 'Updated_By' => $request->session()->get('id')
            );
            $updateTable = TbCmnReasonMaster::where('Pkid',  $id)
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
        $reasonMaster     =        TbCmnReasonMaster::join('tb_cmn_status_master', 'tb_cmn_reason_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
            ->where('tb_cmn_reason_master.Record_Active_Flag', '=', 1)
            ->get(['tb_cmn_reason_master.*', 'tb_cmn_status_master.Status_Name'])->sortBy('Pkid');
        $html_view = view("master-ui.list-view.remarks-master-list-view", compact('reasonMaster'))->render();
        if ($status == "success") {
            return response()->json(["status" => $status, "message" => "Success! Remarks master submitted for approval.", "body" => $html_view]);
        } else {
            return response()->json(["status" => $status, "message" => "Alert! Remarks master not submitted for approval"]);
        }
    }

    // ---------------- [ Update post ] -------------
    public function updateRemarksMaster(Request $request, $id)
    {
        $dataUI = json_decode($request->getContent());
        DB::beginTransaction();
        try {
            $remarks = TbCmnReasonMaster::where('Pkid', $id)
                ->where('tb_cmn_reason_master.Record_Active_Flag', '=', 1)->get(['tb_cmn_reason_master.*']);
            // $UniqueName = TbCmnBenificiaryMaster::where('Benificiary_Name', $Benificiary_Name)->get();
            $remarksName = null;
            foreach ($remarks as $remarks) {
                $remarksName = $remarks->Reason_Details;
            }
            if ($remarksName != $dataUI->Reason_Details) {
                $DuplicateData = TbCmnReasonMaster::where('Reason_Details', $dataUI->Reason_Details)
                    ->where('tb_cmn_reason_master.Record_Active_Flag', '=', 1)->get();
                if (count($DuplicateData) > 0) {
                    return response()->json(["status" => "failed", "message" => "Alert! Duplicate remarks master !"]);
                }
            }
            $addressData = array(
                'Reason_Details' => $dataUI->Reason_Details, 'Description' => $dataUI->Description,
                'Record_Update_Date' => new \DateTime(), 'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'), 'Updated_By' => $request->session()->get('id')
            );
            $addresBenificiary = TbCmnReasonMaster::where('Pkid', $id)->update($addressData);
            $status = "success";

            // all good
            DB::commit();
        } catch (\Exception $e) {
            throw $e;
            $status = "failed";
            DB::rollback();
            // something went wrong
        }
        $reasonMaster     =        TbCmnReasonMaster::join('tb_cmn_status_master', 'tb_cmn_reason_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
            ->where('tb_cmn_reason_master.Record_Active_Flag', '=', 1)
            ->get(['tb_cmn_reason_master.*', 'tb_cmn_status_master.Status_Name'])->sortBy('Pkid');
        $html_view = view("master-ui.list-view.remarks-master-list-view", compact('reasonMaster'))->render();
        $MODE = 'EDT';
        $reasonMasterUpdate     =        TbCmnReasonMaster::join('tb_cmn_status_master', 'tb_cmn_reason_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->where('tb_cmn_reason_master.Pkid', $id)
            ->where('tb_cmn_reason_master.Record_Active_Flag', '=', 1)
            ->get(['tb_cmn_reason_master.*', 'tb_cmn_status_master.Status_Name'])->sortBy('Pkid');

        $html_view1 = view("master-ui.modal.edit-view-ramarks-master-modal", compact('reasonMasterUpdate', 'MODE'))->render();

        if ($status == "success") {
            return response()->json(["status" => $status, "message" => "Success! Remarks master updated.", "body" => $html_view, "body1" => $html_view1]);
        } else {
            return response()->json(["status" => $status, "message" => "Alert! Remarks master not updates"]);
        }
    }
}
