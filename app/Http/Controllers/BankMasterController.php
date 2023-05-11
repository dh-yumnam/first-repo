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
use App\Models\TbCmnSchemeMaster;

use App\Models\TbCmnApproval;
use App\Models\TbSmsDisbursementDetail;
use Hamcrest\Arrays\IsArray;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BankMasterController extends Controller
{

    public function bankMasterPanel()
    {
        //return view('benificiary-ui.benificiary-search-result');
        return view('master-ui.panels.bank-master-panel');
    }
    public function bankMasterModal()
    {
        $subsidyMaster      =       TbCmnSchemeMaster::all()->sortBy('Scheme_Name');
        $html = view('master-ui.modal.bank-master-modal', compact('subsidyMaster'))->render();
        //return view('benificiary-ui.benificiary-search-result');
        return response()->json(['status' => "success", 'body' => $html]);
    }

    public function searchBankMasterResult(Request $request)
    {

        $dataUI = json_decode($request->getContent());
        $bank_name = $dataUI->bank_name;
        $status = $dataUI->status_id;
        $query = TbCmnNedfiBankMaster::join('tb_cmn_status_master', 'tb_cmn_nedfi_bank_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->leftjoin('tb_cmn_scheme_master', 'tb_cmn_nedfi_bank_master.Scheme_Id_Fk', '=', 'tb_cmn_scheme_master.Pkid')
            ->where('tb_cmn_nedfi_bank_master.Record_Active_Flag', '1');

        if (!empty($dataUI->bank_name)) {
            $query->where('Bank_Name', 'LIKE', "%{$bank_name}%");
        }
        if (!empty($dataUI->status_id)) {
            $query->where('tb_cmn_status_master.Pkid', $status);
        }

        $bankMaster = $query->get(['tb_cmn_nedfi_bank_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_scheme_master.Scheme_Name']);
        $subsidyMaster      =       TbCmnSchemeMaster::all()->sortBy('Scheme_Name');
        $html = view('master-ui.master-search.bank-master-search', compact('bankMaster', 'subsidyMaster'))->render();
        //return view('benificiary-ui.benificiary-search-result');
        return response()->json(['status' => "success", 'body' => $html]);
    }


    public function showBankMaster()
    {
        $bankMaster      =        TbCmnNedfiBankMaster::join('tb_cmn_status_master', 'tb_cmn_nedfi_bank_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->join('tb_cmn_scheme_master', 'tb_cmn_nedfi_bank_master.Scheme_Id_Fk', '=', 'tb_cmn_scheme_master.Pkid')
            ->where('tb_cmn_nedfi_bank_master.Record_Active_Flag', '1')
            ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
            ->get(['tb_cmn_nedfi_bank_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_scheme_master.Scheme_Name'])
            ->sortBy('Pkid');
        $subsidyMaster      =       TbCmnSchemeMaster::all()->sortBy('Scheme_Name');
        $html_view = view("master-ui.list-view.bank-master-list-view", compact('bankMaster', 'subsidyMaster'))->render();
        return response()->json(["status" => "success", "message" => "Success! Bank Master created.", "body" => $html_view]);
    }

    public function showBankApproval()
    {
        $bankMaster      =        TbCmnNedfiBankMaster::join('tb_cmn_status_master', 'tb_cmn_nedfi_bank_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->join('tb_cmn_scheme_master', 'tb_cmn_nedfi_bank_master.Scheme_Id_Fk', '=', 'tb_cmn_scheme_master.Pkid')
            ->where('tb_cmn_nedfi_bank_master.Record_Active_Flag', '1')
            ->whereIn('tb_cmn_status_master.Pkid', [2])
            ->get(['tb_cmn_nedfi_bank_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_scheme_master.Scheme_Name'])
            ->sortBy('Pkid');
        $subsidyMaster      =       TbCmnSchemeMaster::all()->sortBy('Scheme_Name');
        // $html_view = view("master-ui.list-view.bank-master-list-view", compact('bankMaster', 'subsidyMaster'))->render();
        $html_view = view("master-ui.approval-view.bank-master-approval-view", compact('bankMaster'))->render();
        return response()->json(["status" => "success", "message" => "Success! Bank Master created.", "body" => $html_view]);
    }

    // ------------- [ store post ] -----------------
    public function addBankMaster(Request $request)
    {

        $validationRules = array(
            'Bank_Name'         =>      'required|unique:tb_cmn_nedfi_bank_master',
            'Account_No'   =>      'required',
            'Branch_Name'   =>      'required',
            'Scheme_Id_Fk'   =>      'required',
        );

        $validator = Validator::make($request->all(), $validationRules);
        if ($validator->fails()) {
            return response()->json(["status" => "failed", "message" => "Alert! Bank Name has already been taken !"]);
        }

        $status = "success";
        try {
            //  $addMaterials            =           TbCmnRawMaterialMaster::create($request->all());
            $addBankMaster = new TbCmnNedfiBankMaster();
            $addBankMaster->Bank_Name = $request->Bank_Name;
            $addBankMaster->Account_No = $request->Account_No;
            $addBankMaster->Branch_Name = $request->Branch_Name;
            $addBankMaster->Scheme_Id_Fk = $request->Scheme_Id_Fk;
            $addBankMaster->Description = $request->Description;
            $addBankMaster->Status_Id = config("constants.CREATED");
            $result = (new CommonController)->insertDefaultColumns($request, $addBankMaster);
            $addBankMaster->save();
            $status = "success";
        } catch (\Exception $ex) {
            throw $ex;
            $status = "failed";
        }
        $bankMaster      =        TbCmnNedfiBankMaster::join('tb_cmn_status_master', 'tb_cmn_nedfi_bank_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->join('tb_cmn_scheme_master', 'tb_cmn_nedfi_bank_master.Scheme_Id_Fk', '=', 'tb_cmn_scheme_master.Pkid')
            ->where('tb_cmn_nedfi_bank_master.Record_Active_Flag', '1')
            ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
            ->get(['tb_cmn_nedfi_bank_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_scheme_master.Scheme_Name'])
            ->sortBy('Pkid');
        $subsidyMaster      =       TbCmnSchemeMaster::all()->sortBy('Scheme_Name');
        $html_view = view("master-ui.list-view.bank-master-list-view", compact('bankMaster', 'subsidyMaster'))->render();
        $MODE = 'EDT';
        $bankMasterUpdate      =        TbCmnNedfiBankMaster::join('tb_cmn_status_master', 'tb_cmn_nedfi_bank_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->join('tb_cmn_scheme_master', 'tb_cmn_nedfi_bank_master.Scheme_Id_Fk', '=', 'tb_cmn_scheme_master.Pkid')
            ->where('tb_cmn_nedfi_bank_master.Record_Active_Flag', '1')
            ->where('tb_cmn_nedfi_bank_master.Pkid', $addBankMaster->Pkid)
            ->get(['tb_cmn_nedfi_bank_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_scheme_master.Scheme_Name']);
        $html_view1 = view("master-ui.modal.edit-view-bank-master-modal", compact('bankMasterUpdate', 'MODE', 'subsidyMaster'))->render();
        if ($status == "success") {
            return response()->json(["status" => "success", "message" => "Success! Bank Master created.", "data" => $addBankMaster, "body" => $html_view, "body1" => $html_view1]);
        } else {
            return response()->json(["status" => "failed", "message" => "Alert! Bank Master not created"]);
        }
    }
    // -------------- [ Delete post ] ---------------
    public function destroyBankMaster(Request $request, $raw_id)
    {
        $deleteBankMaster       =       TbCmnNedfiBankMaster::where("Pkid", $raw_id)
            ->update([
                'Record_Active_Flag' => '0',
                'Record_Update_Date' => new \DateTime(),
                'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'),
                'Updated_By' => $request->session()->get('id')
            ]);
        $bankMaster      =        TbCmnNedfiBankMaster::join('tb_cmn_status_master', 'tb_cmn_nedfi_bank_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->join('tb_cmn_scheme_master', 'tb_cmn_nedfi_bank_master.Scheme_Id_Fk', '=', 'tb_cmn_scheme_master.Pkid')
            ->where('tb_cmn_nedfi_bank_master.Record_Active_Flag', '1')
            ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
            ->get(['tb_cmn_nedfi_bank_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_scheme_master.Scheme_Name'])
            ->sortBy('Pkid');
        $subsidyMaster      =       TbCmnSchemeMaster::all()->sortBy('Scheme_Name');
        $html_view = view("master-ui.list-view.bank-master-list-view", compact('bankMaster', 'subsidyMaster'))->render();
        if ($deleteBankMaster == 1) {
            return response()->json(["status" => "success", "message" => "Success! Bank Master deleted", "body" => $html_view]);
        } else {
            return response()->json(["status" => "failed", "message" => "Alert! Bank Master not deleted"]);
        }
    }


    public function approveBankEntry(Request $request)
    {
        DB::beginTransaction();
        try {
            $dataUI = json_decode($request->getContent());
            $approvalStatus = $dataUI->decision;
            $approvalDate = $dataUI->approval_date;
            $remarks = $dataUI->remarks;
            if (Is_Array($dataUI->check_id)) {
                foreach ($dataUI->check_id as $value) {
                    TbCmnNedfiBankMaster::where('Pkid', $value)->update(
                        [
                            'Status_Id' => $approvalStatus, 'Record_Update_Date' => new \DateTime(), 'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'),
                            'Updated_By' => $request->session()->get('id')
                        ]
                    );
                    $saveCmnApproval = new TbCmnApproval();
                    $saveCmnApproval->Approval_Date = $approvalDate;
                    $saveCmnApproval->Status_Id_Fk = $approvalStatus;
                    $saveCmnApproval->Remarks = $remarks;
                    $saveCmnApproval->Module = 'Bank master';
                    $saveCmnApproval->Record_Id_Fk =  $value;
                    $saveCmnApproval->save();
                }
            } else {
                TbCmnNedfiBankMaster::where('Pkid', $dataUI->check_id)->update(
                    [
                        'Status_Id' => $approvalStatus, 'Record_Update_Date' => new \DateTime(), 'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'),
                        'Updated_By' => $request->session()->get('id')
                    ]
                );
                $saveCmnApproval = new TbCmnApproval();
                $saveCmnApproval->Approval_Date = $approvalDate;
                $saveCmnApproval->Status_Id_Fk = $approvalStatus;
                $saveCmnApproval->Remarks = $remarks;
                $saveCmnApproval->Module = 'Bank master';
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
        $bankMaster      =        TbCmnNedfiBankMaster::join('tb_cmn_status_master', 'tb_cmn_nedfi_bank_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->join('tb_cmn_scheme_master', 'tb_cmn_nedfi_bank_master.Scheme_Id_Fk', '=', 'tb_cmn_scheme_master.Pkid')
            ->where('tb_cmn_nedfi_bank_master.Record_Active_Flag', '1')
            ->whereIn('tb_cmn_status_master.Pkid', [2])
            ->get(['tb_cmn_nedfi_bank_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_scheme_master.Scheme_Name'])
            ->sortBy('Pkid');
        $subsidyMaster      =       TbCmnSchemeMaster::all()->sortBy('Scheme_Name');
        $html_view = view("master-ui.approval-view.bank-master-approval-view", compact('bankMaster', 'subsidyMaster'))->render();
        if ($status == 'success') {
            return response()->json(["status" => "success", "message" => "Success! Bank master " . $decision, "body" => $html_view]);
        } else {
            return response()->json(["status" => "success", "message" => "Alert! Bank master not " . $decision]);
        }
    }


    public function viewEditBankModal($id, $MODE)
    {
        $bankMasterUpdate      =        TbCmnNedfiBankMaster::join('tb_cmn_status_master', 'tb_cmn_nedfi_bank_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->join('tb_cmn_scheme_master', 'tb_cmn_nedfi_bank_master.Scheme_Id_Fk', '=', 'tb_cmn_scheme_master.Pkid')
            ->where('tb_cmn_nedfi_bank_master.Record_Active_Flag', '1')
            ->where('tb_cmn_nedfi_bank_master.Pkid', $id)
            ->get(['tb_cmn_nedfi_bank_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_scheme_master.Scheme_Name']);
        $subsidyMaster      =       TbCmnSchemeMaster::all()->sortBy('Scheme_Name');
        $html_view = view("master-ui.modal.edit-view-bank-master-modal", compact('bankMasterUpdate', 'MODE', 'subsidyMaster'))->render();
        return response()->json(['status' => "success", 'body' => $html_view]);
    }

    // Approval record
    public function bankMasterApproval(Request $request, $id)
    {
        $dataUI = json_decode($request->getContent());
        DB::beginTransaction();
        try {
            $tableData = array(
                'Status_Id' => '2', 'Record_Update_Date' => new \DateTime(), 'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'), 'Updated_By' => $request->session()->get('id')
            );
            $updateTable = TbCmnNedfiBankMaster::where('Pkid',  $id)
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
        $bankMaster      =        TbCmnNedfiBankMaster::join('tb_cmn_status_master', 'tb_cmn_nedfi_bank_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->join('tb_cmn_scheme_master', 'tb_cmn_nedfi_bank_master.Scheme_Id_Fk', '=', 'tb_cmn_scheme_master.Pkid')
            ->where('tb_cmn_nedfi_bank_master.Record_Active_Flag', '1')
            ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
            ->get(['tb_cmn_nedfi_bank_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_scheme_master.Scheme_Name'])
            ->sortBy('Pkid');
        $subsidyMaster      =       TbCmnSchemeMaster::all()->sortBy('Scheme_Name');
        $html_view = view("master-ui.list-view.bank-master-list-view", compact('bankMaster', 'subsidyMaster'))->render();
        if ($status == "success") {
            return response()->json(["status" => $status, "message" => "Success! Bank master submitted for approval.", "body" => $html_view]);
        } else {
            return response()->json(["status" => $status, "message" => "Alert! Bank master submitted for approval"]);
        }
    }

    // ---------------- [ Update post ] -------------
    public function updateBankMaster(Request $request, $id)
    {
        $dataUI = json_decode($request->getContent());
        DB::beginTransaction();
        try {
            $bank = TbCmnNedfiBankMaster::where('Pkid', $id)->get(['tb_cmn_nedfi_bank_master.*']);
            // $UniqueName = TbCmnBenificiaryMaster::where('Benificiary_Name', $Benificiary_Name)->get();
            $bankName = null;
            $branchName = null;
            foreach ($bank as $bank) {
                $bankName = $bank->Bank_Name;
                $branchName = $bank->Branch_Name;
            }
            if ($bankName != $dataUI->Bank_Name or $branchName != $dataUI->Branch_Name) {
                $DuplicateData = TbCmnNedfiBankMaster::where('Bank_Name', $dataUI->Bank_Name)
                    ->where('Branch_Name', $dataUI->Branch_Name)
                    ->where('tb_cmn_nedfi_bank_master.Record_Active_Flag', '1')
                    ->get();
                if (count($DuplicateData) > 0) {
                    return response()->json(["status" => "failed", "message" => "Alert! Duplicate Bank master !"]);
                }
            }
            $addressData = array(
                'Bank_Name' => $dataUI->Bank_Name, 'Account_No' => $dataUI->Account_No, 'Branch_Name' => $dataUI->Branch_Name, 'Description' => $dataUI->Description, 'Scheme_Id_Fk' => $dataUI->Scheme_Id_Fk,
                'Record_Update_Date' => new \DateTime(), 'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'), 'Updated_By' => $request->session()->get('id')
            );
            $addresBenificiary = TbCmnNedfiBankMaster::where('Pkid', $id)->update($addressData);
            $status = "success";
            // all good
            DB::commit();
        } catch (\Exception $e) {
            throw $e;
            $status = "failed";
            DB::rollback();
            // something went wrong
        }
        $bankMaster      =        TbCmnNedfiBankMaster::join('tb_cmn_status_master', 'tb_cmn_nedfi_bank_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->join('tb_cmn_scheme_master', 'tb_cmn_nedfi_bank_master.Scheme_Id_Fk', '=', 'tb_cmn_scheme_master.Pkid')
            ->where('tb_cmn_nedfi_bank_master.Record_Active_Flag', '1')
            ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
            ->get(['tb_cmn_nedfi_bank_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_scheme_master.Scheme_Name'])
            ->sortBy('Pkid');
        $subsidyMaster      =       TbCmnSchemeMaster::all()->sortBy('Scheme_Name');
        $html_view = view("master-ui.list-view.bank-master-list-view", compact('bankMaster', 'subsidyMaster'))->render();

        $MODE = 'EDT';
        $bankMasterUpdate      =        TbCmnNedfiBankMaster::join('tb_cmn_status_master', 'tb_cmn_nedfi_bank_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->join('tb_cmn_scheme_master', 'tb_cmn_nedfi_bank_master.Scheme_Id_Fk', '=', 'tb_cmn_scheme_master.Pkid')
            ->where('tb_cmn_scheme_master.Record_Active_Flag', '1')
            ->where('tb_cmn_nedfi_bank_master.Pkid', $id)
            ->get(['tb_cmn_nedfi_bank_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_scheme_master.Scheme_Name']);
        $html_view1 = view("master-ui.modal.edit-view-bank-master-modal", compact('bankMasterUpdate', 'MODE', 'subsidyMaster'))->render();

        if ($status == "success") {
            return response()->json(["status" => $status, "message" => "Success! Bank master updated.", "body" => $html_view, "body1" => $html_view1]);
        } else {
            return response()->json(["status" => $status, "message" => "Alert! Bank master not updates"]);
        }
    }
}
