<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\TbCmnAddress;
use Illuminate\Http\Request;
use App\Models\TbCmnFinishGoodsMaster;
use App\Models\TbCmnBankMaster;
use App\Models\TbCmnRawMaterialMaster;
use App\Models\TbCmnBenificiaryMaster;
use App\Models\TbCmnDistrictMaster;
use App\Models\TbCmnStateMaster;
use App\Models\TbCmnPolicyMaster;
use App\Models\TbCmnSchemeMaster;
use App\Models\TbCmnUnitMaster;
use App\Models\TbCmnApproval;
use App\Models\TbCmnStatusMaster;
use App\Models\TbCmnSectorMaster;
use DateTime;
use Exception;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class BenificiaryController extends Controller
{
    public function addBenificiary()
    {
        // $accessBoolean = (new CommonController)->checkAccessRightToController('approveBenificiary');
        // if ($accessBoolean) {
        try {
            $benificiaryList      =       TbCmnBenificiaryMaster::join('tb_cmn_status_master', 'tb_cmn_benificiary_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
                ->leftjoin('tb_cmn_bank_master', 'tb_cmn_benificiary_master.Bank_Id_Fk', '=', 'tb_cmn_bank_master.Pkid')
                ->where('tb_cmn_benificiary_master.Record_Active_Flag', '1')
                ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
                ->get(['tb_cmn_benificiary_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_bank_master.Bank_Name'])
                ->sortBy('Benificiary_Name');
            $subsidyMaster      =       TbCmnSchemeMaster::all()
                ->whereIn('Status_Id', [5])
                ->where('Record_Active_Flag', '1')
                ->sortByDesc('Scheme_Name');
            $stateMaster      =       TbCmnStateMaster::all()
                ->sortBy('State_Name');
            $govPolicy      =       TbCmnPolicyMaster::all()
                ->sortByDesc('Policy_Name');
            //$districtMaster      =       TbCmnDistrictMaster::all()->sortByDesc('District_Name');
            $html = view('benificiary-ui.add-benificiary', compact('benificiaryList', 'subsidyMaster', 'stateMaster', 'govPolicy'))->render();
            return response()->json(['status' => "success", 'body' => $html]);
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
        // } else {
        //     return view('pages.error-pages.access-deny')->render();
        // }
    }
    public function approveBenificiary()
    {
        $accessBoolean = (new CommonController)->checkAccessRightToController('approveBenificiary');
        if ($accessBoolean) {
            $approvalStatusMaster = TbCmnStatusMaster::whereIn('Pkid', [3, 4, 5])->get();
            $benificiaryList      =       TbCmnBenificiaryMaster::join('tb_cmn_status_master', 'tb_cmn_benificiary_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
                ->leftjoin('tb_cmn_bank_master', 'tb_cmn_benificiary_master.Bank_Id_Fk', '=', 'tb_cmn_bank_master.Pkid')
                ->where('tb_cmn_benificiary_master.Record_Active_Flag', '1')
                ->whereIn('tb_cmn_status_master.Pkid', [2])
                ->get(['tb_cmn_benificiary_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_bank_master.Bank_Name'])
                ->sortBy('Pkid');
            $html = view('benificiary-ui.approve-benificiary', compact('benificiaryList', 'approvalStatusMaster'))->render();
            return response()->json(['status' => "success", 'body' => $html]);
            // $form = view('benificiary-ui.approve-benificiary'); 
            // return Response::json($form);
        } else {
            $html_view = view('pages.error-pages.access-deny-modal')->render();
            return response()->json(["status" => "success", "body" => $html_view]);
        }
    }
    public function addBenificiaryModal()
    {
        $accessBoolean = (new CommonController)->checkAccessRightToController('addBenificiaryModal');
        if ($accessBoolean) {
            $rawMaterial      =       TbCmnRawMaterialMaster::all()
                ->whereIn('Status_Id', [5])
                ->where('Record_Active_Flag', '1')
                ->sortByDesc('Material_Name');
            $finishGoods      =       TbCmnFinishGoodsMaster::all()
                ->whereIn('Status_Id', [5])
                ->where('Record_Active_Flag', '1')
                ->sortByDesc('Goods_Name');
            $govPolicy      =       TbCmnPolicyMaster::all()
                ->sortByDesc('Policy_Name');
            $unitMaster      =       TbCmnUnitMaster::all()
                ->whereIn('Status_Id', [5])
                ->where('Record_Active_Flag', '1')
                ->sortByDesc('Unit_Name');
            $stateMaster      =       TbCmnStateMaster::all()
                ->sortBy('State_Name');
            $scheme = TbCmnSchemeMaster::all()
                ->whereIn('Status_Id', [5])
                ->where('Record_Active_Flag', '1')
                ->sortBy('Pkid');
            $bankMaster      =      TbCmnBankMaster::all()
                ->where('Record_Active_Flag', '1')
                ->sortBy('Bank_Name');
            $sectorMaster      =        TbCmnSectorMaster::all()
                ->where('Record_Active_Flag', '1')
                ->where('Custom1', 'A')
                ->sortBy('Sector_Name');
            $html = view('benificiary-ui.add-benificiary-modal', compact('rawMaterial', 'unitMaster', 'finishGoods', 'govPolicy', 'stateMaster', 'scheme', 'bankMaster', 'sectorMaster'))->render();
            //return view('benificiary-ui.benificiary-search-result');
            return response()->json(['status' => "success", 'body' => $html]);
        } else {
            $html_view = view('pages.error-pages.access-deny-modal')->render();
            return response()->json(["status" => "success", "body" => $html_view]);
        }
    }
    public function searchBenificiary(Request $request)
    {
        $dataUI = json_decode($request->getContent());
        try {

            $query = DB::table('tb_cmn_benificiary_master')->join('tb_cmn_status_master', 'tb_cmn_benificiary_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
                ->join('tb_cmn_address', 'tb_cmn_benificiary_master.Address_Id_Fk', '=', 'tb_cmn_address.Pkid')
                ->where('tb_cmn_benificiary_master.Record_Active_Flag', '1')
                ->whereIn('tb_cmn_status_master.Pkid', [1, 2, 3, 4, 5]);

            if (!empty($dataUI->search_benificiary)) {
                $query->where('tb_cmn_benificiary_master.Benificiary_Name', 'LIKE', "%{$dataUI->search_benificiary}%");
            }
            if (!empty($dataUI->pan)) {
                $query->where('tb_cmn_benificiary_master.Pan_No', 'LIKE', "%{$dataUI->pan}%");
            }
            if (!empty($dataUI->state)) {
                $query->where('tb_cmn_address.State_Code', $dataUI->state);
            }
            if (!empty($dataUI->district_id)) {
                $query->where('tb_cmn_address.District_Id', $dataUI->district_id);
            }
            if (!empty($dataUI->policy_id)) {
                $query->where('tb_cmn_benificiary_master.Gov_Policy_Id', $dataUI->policy_id);
            }

            $benificiaryList = $query->get(['tb_cmn_benificiary_master.*', 'tb_cmn_status_master.Status_Name'])->sortBy('Benificiary_Name');

            $html = view('benificiary-ui.benificiary-search-result', compact('benificiaryList'))->render();
            return response()->json(['status' => "success", 'body' => $html]);
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }

    public function saveBenificiary(Request $request)
    {
        $dataUI = json_decode($request->getContent());
        DB::beginTransaction();
        try {
            // Create 
            $saveBenificiary = new TbCmnBenificiaryMaster();
            $saveAddress = new TbCmnAddress();
            $Benificiary_Name = $dataUI->Benificiary_Name;
            $Pan_No = $dataUI->Pan_No;
            $saveBenificiary->Benificiary_Name = $Benificiary_Name;
            if (!empty($Pan_No)) {
                $saveBenificiary->Pan_No = $Pan_No;
            }
            // $UniqueName = TbCmnBenificiaryMaster::where('Benificiary_Name', $Benificiary_Name)->get();
            $DuplicatePan = TbCmnBenificiaryMaster::where('Pan_No', $Pan_No)->get();
            // if (count($UniqueName) > 0) {
            //     return response()->json(["status" => "failed", "message" => "Alert! Benificiary Name has already been taken !"]);
            // }
            if (count($DuplicatePan) > 0) {
                return response()->json(["status" => "failed", "message" => "Alert! Pan No has already been taken !"]);
            }
            // Address detail save
            $saveAddress->State_Code = $dataUI->state_id;
            $saveAddress->District_Id = $dataUI->district;
            $saveAddress->Address1 = $dataUI->beneficiary_address;
            $saveAddress->Address2 = $dataUI->manufacture_address;
            $saveAddress->Record_Active_Flag = '1';
            $saveAddress->Record_Insert_Date = new \DateTime();
            $addAddress = $saveAddress->save();
            //save benificiary details
            if (!empty($dataUI->material_id)) {
                $saveBenificiary->Raw_Materials_Id_Fk = $dataUI->material_id;
            }
            if (!empty($dataUI->goods_id)) {
                $saveBenificiary->Finish_Goods_Id_Fk = $dataUI->goods_id;
            }
            if (!empty($dataUI->sub_registration)) {
                $saveBenificiary->Subsidy_Regn_No = $dataUI->sub_registration;
            }
            if (!empty($dataUI->sub_registration_date)) {
                $saveBenificiary->Subsidy_Regn_Date = $dataUI->sub_registration_date;
            }
            if (!empty($dataUI->ind_registration)) {
                $saveBenificiary->Industry_Regn_No = $dataUI->ind_registration;
            }
            if (!empty($dataUI->ind_registration_date)) {
                $saveBenificiary->Industry_Regn_Date = $dataUI->ind_registration_date;
            }
            $saveBenificiary->Gov_Policy_Id = $dataUI->gov_policy;
            if (!empty($dataUI->GST)) {
                $saveBenificiary->GST_No = $dataUI->GST;
            }
            $saveBenificiary->Production_Date = $dataUI->production_date;
            if (!empty($dataUI->prod_capacity)) {
                $saveBenificiary->Production_Capacity = $dataUI->prod_capacity;
            }
            //$saveBenificiary->Proposal_For = $dataUI->purposal_id;
            if (!empty($dataUI->emp_generation)) {
                $saveBenificiary->Emp_Generation_No = $dataUI->emp_generation;
            }
            if (!empty($dataUI->distance)) {
                $saveBenificiary->Distance = $dataUI->distance;
            }
            // if (!empty($dataUI->bank)) {
            //     $saveBenificiary->Bank_Acc_no = $dataUI->bank;
            // }
            if (!empty($dataUI->Bank_Id)) {
                $saveBenificiary->Bank_Id_Fk = $dataUI->Bank_Id;
            }
            if (!empty($dataUI->unit_id)) {
                $saveBenificiary->Unit_Id_Fk = $dataUI->unit_id;
            }
            $saveBenificiary->Status_Id = config("constants.CREATED");
            if (!empty($dataUI->unit_status_id)) {
                $saveBenificiary->Unit_Status = $dataUI->unit_status_id;
            }
            if (!empty($dataUI->sector_id)) {
                $saveBenificiary->Sector_Id_Fk = $dataUI->sector_id;
            }

            $saveBenificiary->Address_Id_Fk = $saveAddress->Pkid;
            $result = (new CommonController)->insertDefaultColumns($request, $saveBenificiary);
            $saveBenificiary->save();
            //Scheme details
            // $saveScheme = new TbBenificiarySchemeTxn();
            // $scheme_Date = array();
            // foreach ($dataUI->scheme_id as $scemeArray) {
            //     $scheme_Date[] = array('Scheme_Id_Fk' => $scemeArray, 'Benificiary_Id_Fk' => $saveBenificiary->Pkid, 'Record_Active_Flag' => '1', 'Record_Insert_Date' => new \DateTime());
            // }
            // TbBenificiarySchemeTxn::insert($scheme_Date);
            $status = "success";
            // all good
            DB::commit();
        } catch (\Exception $e) {
            $status = "failed";
            DB::rollback();
            // something went wrong
        }
        $bankMaster      =       TbCmnBankMaster::all()
            ->where('Record_Active_Flag', '1')
            ->sortBy('Bank_Name');
        $benificiaryList      =       TbCmnBenificiaryMaster::join('tb_cmn_status_master', 'tb_cmn_benificiary_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->leftjoin('tb_cmn_bank_master', 'tb_cmn_bank_master.Pkid', '=', 'tb_cmn_benificiary_master.Bank_Id_Fk')
            ->where('tb_cmn_benificiary_master.Record_Active_Flag', '1')
            ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
            ->get(['tb_cmn_benificiary_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_bank_master.Bank_Name'])
            ->sortBy('Pkid');
        $html_view = view("benificiary-ui.search-benificiary", compact('benificiaryList', 'bankMaster'))->render();

        $rawMaterial      =       TbCmnRawMaterialMaster::all()
            ->whereIn('Status_Id', [5])
            ->where('Record_Active_Flag', '1')
            ->sortByDesc('Material_Name');
        $finishGoods      =       TbCmnFinishGoodsMaster::all()
            ->whereIn('Status_Id', [5])
            ->where('Record_Active_Flag', '1')
            ->sortByDesc('Goods_Name');
        $unitMaster      =       TbCmnUnitMaster::all()
            ->whereIn('Status_Id', [5])
            ->where('Record_Active_Flag', '1')
            ->sortByDesc('Unit_Name');
        $govPolicy      =       TbCmnPolicyMaster::all()
            ->sortByDesc('Policy_Name');
        $stateMaster      =       TbCmnStateMaster::all()
            ->sortBy('State_Name');
        $scheme = TbCmnSchemeMaster::all()
            ->whereIn('Status_Id', [5])
            ->where('Record_Active_Flag', '1')
            ->sortBy('Pkid');
        $bankMaster      =       TbCmnBankMaster::all()
            ->where('Record_Active_Flag', '1')
            ->sortBy('Bank_Name');
        $sectorMaster      =        TbCmnSectorMaster::all()
            ->where('Record_Active_Flag', '1')
            ->sortBy('Sector_Name');
        $stateID = null;
        $editBenificiaryList       =       TbCmnBenificiaryMaster::leftjoin('tb_cmn_raw_material_master', 'tb_cmn_benificiary_master.Raw_Materials_Id_Fk', '=', 'tb_cmn_raw_material_master.Pkid')
            ->leftjoin('tb_cmn_finish_goods_master', 'tb_cmn_benificiary_master.Finish_Goods_Id_Fk', '=', 'tb_cmn_finish_goods_master.Pkid')
            ->join('tb_cmn_address', 'tb_cmn_benificiary_master.Address_Id_Fk', '=', 'tb_cmn_address.Pkid')
            ->leftjoin('tb_cmn_district_master', 'tb_cmn_address.District_Id', '=', 'tb_cmn_district_master.Pkid')
            ->join('tb_cmn_state_master', 'tb_cmn_address.State_Code', '=', 'tb_cmn_state_master.Pkid')
            ->leftjoin('tb_cmn_bank_master', 'tb_cmn_benificiary_master.Bank_Id_Fk', '=', 'tb_cmn_bank_master.Pkid')
            ->join('tb_cmn_status_master', 'tb_cmn_benificiary_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->join('tb_cmn_sector_master', 'tb_cmn_benificiary_master.Sector_Id_Fk', '=', 'tb_cmn_sector_master.Pkid')
            ->where('tb_cmn_benificiary_master.Record_Active_Flag', '1')
            ->where("tb_cmn_benificiary_master.Pkid", $saveBenificiary->Pkid)->get(['tb_cmn_benificiary_master.*', 'tb_cmn_sector_master.Sector_Name', 'tb_cmn_bank_master.Bank_Name', 'tb_cmn_state_master.State_Name', 'tb_cmn_state_master.Pkid AS State_Code', 'tb_cmn_district_master.Pkid AS District_Id', 'tb_cmn_district_master.District_Name', 'tb_cmn_address.Address1', 'tb_cmn_address.Address2', 'tb_cmn_status_master.Status_Name', 'tb_cmn_raw_material_master.Material_Name', 'tb_cmn_finish_goods_master.Goods_Name']);


        foreach ($editBenificiaryList as $list) {
            $stateID = $list->State_Code;
        }
        $MODE = 'EDT';
        // echo count($editBenificiaryList); exit;
        $districtMaster = TbCmnDistrictMaster::where('State_Id_Fk', $stateID)
            ->get(['tb_cmn_district_master.*'])->sortBy('District_Name');

        $html_view1 = view("benificiary-ui.view-edit-benificiary", compact('editBenificiaryList', 'districtMaster', 'finishGoods', 'stateMaster', 'unitMaster', 'rawMaterial', 'finishGoods', 'govPolicy', 'bankMaster', 'MODE', 'sectorMaster'))->render();

        if ($status == "success") {
            return response()->json(["status" => $status, "message" => "Success! Benificiary created.", "body" => $html_view, "body1" => $html_view1]);
        } else {
            return response()->json(["status" => $status, "message" => "Alert! Benificiary not created"]);
        }
    }

    public function destroyBenificiary(Request $request, $benificiary_id)
    {
        $accessBoolean = (new CommonController)->checkAccessRightToController('destroyBenificiary');
        if ($accessBoolean) {

            DB::beginTransaction();
            try {
                $deleteBenificiaryMaster       =       TbCmnBenificiaryMaster::where("Pkid", $benificiary_id)
                    ->update([
                        'Record_Active_Flag' => '0',
                        'Record_Update_Date' => new \DateTime(),
                        'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'),
                        'Updated_By' => $request->session()->get('id')
                    ]);
                // $deleteBenificiarySchemeTxn       =       TbBenificiarySchemeTxn::where("Benificiary_Id_Fk", $benificiary_id)->delete();
                $status = "success";
                DB::commit();
            } catch (\Exception $ex) {
                throw $ex;
                $status = "failed";
                DB::rollback();
            }
            $bankMaster      =       TbCmnBankMaster::all()
                ->where('Record_Active_Flag', '1')
                ->sortBy('Bank_Name');
            $benificiaryList      =       TbCmnBenificiaryMaster::join('tb_cmn_status_master', 'tb_cmn_benificiary_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
                ->leftjoin('tb_cmn_bank_master', 'tb_cmn_bank_master.Pkid', '=', 'tb_cmn_benificiary_master.Bank_Id_Fk')
                ->where('tb_cmn_benificiary_master.Record_Active_Flag', '1')
                ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
                ->get(['tb_cmn_benificiary_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_bank_master.Bank_Name'])
                ->sortBy('Pkid');
            $html_view = view("benificiary-ui.search-benificiary", compact('benificiaryList', 'bankMaster'))->render();
            if ($status == "success") {
                return response()->json(["status" => "success", "message" => "Success! Benificiary deleted", "body" => $html_view]);
            } else {
                return response()->json(["status" => "failed", "message" => "Alert! Benificiary not deleted"]);
            }
        } else {
            $html_view = view('pages.error-pages.access-deny-modal')->render();
            return response()->json(["status" => "access_deny", "body" => $html_view]);
        }
    }
    // Fetch records
    public function viewEditBenificiary($benificiary_id, $MODE)
    {
        try {
            $rawMaterial      =       TbCmnRawMaterialMaster::all()
                ->whereIn('Status_Id', [5])
                ->where('Record_Active_Flag', '1')
                ->sortByDesc('Material_Name');
            $finishGoods      =       TbCmnFinishGoodsMaster::all()
                ->whereIn('Status_Id', [5])
                ->where('Record_Active_Flag', '1')
                ->sortByDesc('Goods_Name');
            $unitMaster      =       TbCmnUnitMaster::all()
                ->whereIn('Status_Id', [5])
                ->where('Record_Active_Flag', '1')
                ->sortByDesc('Unit_Name');
            $govPolicy      =       TbCmnPolicyMaster::all()
                ->sortByDesc('Policy_Name');
            $stateMaster      =       TbCmnStateMaster::all()
                ->sortBy('State_Name');
            $scheme = TbCmnSchemeMaster::all()
                ->whereIn('Status_Id', [5])
                ->where('Record_Active_Flag', '1')
                ->sortBy('Pkid');
            $bankMaster      =       TbCmnBankMaster::all()
                ->where('Record_Active_Flag', '1')
                ->sortBy('Bank_Name');
            $sectorMaster      =        TbCmnSectorMaster::all()
                ->where('Record_Active_Flag', '1')
                ->sortBy('Sector_Name');
            $stateID = null;

            $editBenificiaryList       =       TbCmnBenificiaryMaster::leftjoin('tb_cmn_raw_material_master', 'tb_cmn_benificiary_master.Raw_Materials_Id_Fk', '=', 'tb_cmn_raw_material_master.Pkid')
                ->leftjoin('tb_cmn_finish_goods_master', 'tb_cmn_benificiary_master.Finish_Goods_Id_Fk', '=', 'tb_cmn_finish_goods_master.Pkid')
                ->join('tb_cmn_address', 'tb_cmn_benificiary_master.Address_Id_Fk', '=', 'tb_cmn_address.Pkid')
                ->leftjoin('tb_cmn_district_master', 'tb_cmn_address.District_Id', '=', 'tb_cmn_district_master.Pkid')
                ->join('tb_cmn_state_master', 'tb_cmn_address.State_Code', '=', 'tb_cmn_state_master.Pkid')
                ->leftjoin('tb_cmn_bank_master', 'tb_cmn_benificiary_master.Bank_Id_Fk', '=', 'tb_cmn_bank_master.Pkid')
                ->join('tb_cmn_status_master', 'tb_cmn_benificiary_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
                ->join('tb_cmn_sector_master', 'tb_cmn_benificiary_master.Sector_Id_Fk', '=', 'tb_cmn_sector_master.Pkid')
                ->where('tb_cmn_benificiary_master.Record_Active_Flag', '1')
                ->where("tb_cmn_benificiary_master.Pkid", $benificiary_id)->get(['tb_cmn_benificiary_master.*', 'tb_cmn_sector_master.Sector_Name', 'tb_cmn_bank_master.Bank_Name', 'tb_cmn_state_master.State_Name', 'tb_cmn_state_master.Pkid AS State_Code', 'tb_cmn_district_master.Pkid AS District_Id', 'tb_cmn_district_master.District_Name', 'tb_cmn_address.Address1', 'tb_cmn_address.Address2', 'tb_cmn_status_master.Status_Name', 'tb_cmn_raw_material_master.Material_Name', 'tb_cmn_finish_goods_master.Goods_Name']);

            foreach ($editBenificiaryList as $list) {
                $stateID = $list->State_Code;
            }

            // echo count($editBenificiaryList); exit;
            $districtMaster = TbCmnDistrictMaster::where('State_Id_Fk', $stateID)
                ->get(['tb_cmn_district_master.*'])->sortBy('District_Name');
            if ($MODE == 'EDT') {
                $accessBoolean = (new CommonController)->checkAccessRightToController('viewEditBenificiary');
                if ($accessBoolean) {
                    $html_view = view("benificiary-ui.view-edit-benificiary", compact('editBenificiaryList', 'districtMaster', 'finishGoods', 'stateMaster', 'unitMaster', 'rawMaterial', 'finishGoods', 'govPolicy', 'MODE', 'bankMaster', 'sectorMaster'))->render();
                    return response()->json(["status" => "success", "body" => $html_view]);
                } else {
                    $html_view = view('pages.error-pages.access-deny-modal')->render();
                    return response()->json(["status" => "access_deny", "body" => $html_view]);
                }
            } else {
                $html_view = view("benificiary-ui.view-edit-benificiary", compact('editBenificiaryList', 'districtMaster', 'finishGoods', 'stateMaster', 'unitMaster', 'rawMaterial', 'finishGoods', 'govPolicy', 'MODE', 'bankMaster', 'sectorMaster'))->render();
                return response()->json(["status" => "success", "body" => $html_view]);
            }
        } catch (\Exception $e) {
            return redirect('/session-expire');
        }
    }

    // save approve benificiry

    public function approveBenificiaryEntry(Request $request)
    {
        $accessBoolean = (new CommonController)->checkAccessRightToController('approveBenificiaryEntry');
        if ($accessBoolean) {
            DB::beginTransaction();
            try {
                $dataUI = json_decode($request->getContent());
                $approvalStatus = $dataUI->decision;
                $approvalDate = $dataUI->approval_date;
                $remarks = $dataUI->remarks;
                if (Is_Array($dataUI->check_id)) {
                    foreach ($dataUI->check_id as $value) {
                        TbCmnBenificiaryMaster::where('Pkid', $value)->update(
                            [
                                'Status_Id' => $approvalStatus, 'Record_Update_Date' => new \DateTime(), 'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'),
                                'Updated_By' => $request->session()->get('id')
                            ]
                        );
                        $saveCmnApproval = new TbCmnApproval();
                        $saveCmnApproval->Approval_Date = $approvalDate;
                        $saveCmnApproval->Status_Id_Fk = $approvalStatus;
                        $saveCmnApproval->Remarks = $remarks;
                        $saveCmnApproval->Module = 'Benificiary';
                        $saveCmnApproval->Record_Id_Fk =  $value;
                        $saveCmnApproval->save();
                    }
                } else {
                    TbCmnBenificiaryMaster::where('Pkid', $dataUI->check_id)->update(
                        [
                            'Status_Id' => $approvalStatus, 'Record_Update_Date' => new \DateTime(), 'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'),
                            'Updated_By' => $request->session()->get('id')
                        ]
                    );
                    $saveCmnApproval = new TbCmnApproval();
                    $saveCmnApproval->Approval_Date = $approvalDate;
                    $saveCmnApproval->Status_Id_Fk = $approvalStatus;
                    $saveCmnApproval->Remarks = $remarks;
                    $saveCmnApproval->Module = 'Benificiary';
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
            $benificiaryList      =       TbCmnBenificiaryMaster::join('tb_cmn_status_master', 'tb_cmn_benificiary_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
                ->where('tb_cmn_benificiary_master.Record_Active_Flag', '1')
                ->whereIn('tb_cmn_status_master.Pkid', [2])
                ->get(['tb_cmn_benificiary_master.*', 'tb_cmn_status_master.Status_Name'])
                ->sortBy('Pkid');
            $html_view = view('benificiary-ui.after-benificiary-approval-list', compact('benificiaryList'))->render();
            if ($status == 'success') {
                return response()->json(["status" => "success", "message" => "Success! Beneficiary " . $decision, "body" => $html_view]);
            } else {
                return response()->json(["status" => "success", "message" => "Alert! Beneficiary not " . $decision]);
            }
        } else {
            return view('pages.error-pages.access-deny')->render();
        }
    }

    // Update record
    public function updateBenificiary(Request $request, $id)
    {
        $dataUI = json_decode($request->getContent());
        DB::beginTransaction();
        try {
            $address = TbCmnBenificiaryMaster::where('Pkid', $id)->get(['tb_cmn_benificiary_master.Address_Id_Fk']);
            // $UniqueName = TbCmnBenificiaryMaster::where('Benificiary_Name', $Benificiary_Name)->get();
            $DuplicatePan = TbCmnBenificiaryMaster::where('Pan_No', $dataUI->Pan_No)
                ->whereNotIn('Pkid', [$id])->get();
            // if (count($UniqueName) > 0) {
            //     return response()->json(["status" => "failed", "message" => "Alert! Benificiary Name has already been taken !"]);
            // }
            $addressID = null;
            foreach ($address as $address) {
                $addressID = $address->Address_Id_Fk;
            }
            if (count($DuplicatePan) > 0) {
                return response()->json(["status" => "failed", "message" => "Alert! Pan No has already been taken !"]);
            }
            // Address detail update
            $addressData = array(
                'State_Code' => $dataUI->state_id, "District_Id" => $dataUI->district, 'Address1' => $dataUI->beneficiary_address,
                'Address2' => $dataUI->manufacture_address, 'Record_Update_Date' => new \DateTime(), 'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'), 'Updated_By' => $request->session()->get('id')
            );
            $addresSave = TbCmnAddress::where('Pkid', $addressID)
                ->update($addressData);


            if (!is_null($addresSave)) {
                //update benificiary details
                $filedData = [];
                if (!empty($dataUI->material_id)) {
                    $filedData = array_merge($filedData, array('Raw_Materials_Id_Fk' => $dataUI->material_id));
                } else {
                    $filedData = array_merge($filedData, array('Raw_Materials_Id_Fk' => null));
                }
                if (!empty($dataUI->goods_id)) {
                    $filedData = array_merge($filedData,  array('Finish_Goods_Id_Fk' => $dataUI->goods_id));
                } else {
                    $filedData = array_merge($filedData,  array('Finish_Goods_Id_Fk' => null));
                }
                if (!empty($dataUI->GST)) {
                    $filedData = array_merge($filedData, array('GST_No' => $dataUI->GST));
                } else {
                    $filedData = array_merge($filedData, array('GST_No' => null));
                }
                if (!empty($dataUI->emp_generation)) {
                    $filedData = array_merge($filedData, array('Emp_Generation_No' => $dataUI->emp_generation));
                } else {
                    $filedData = array_merge($filedData, array('Emp_Generation_No' => null));
                }
                if (!empty($dataUI->distance)) {
                    $filedData = array_merge($filedData, array('Distance' => $dataUI->distance));
                } else {
                    $filedData = array_merge($filedData, array('Distance' => null));
                }
                if (!empty($dataUI->unit_status_id)) {
                    $filedData = array_merge($filedData, array('Unit_Status' => $dataUI->unit_status_id));
                } else {
                    $filedData = array_merge($filedData, array('Unit_Status' => null));
                }
                if (!empty($dataUI->unit_id)) {
                    $filedData = array_merge($filedData, array('Unit_Id_Fk' => $dataUI->unit_id));
                } else {
                    $filedData = array_merge($filedData, array('Unit_Id_Fk' => null));
                }
                if (!empty($dataUI->sector_id)) {
                    $filedData = array_merge($filedData, array('Sector_Id_Fk' => $dataUI->sector_id));
                } else {
                    $filedData = array_merge($filedData, array('Sector_Id_Fk' => null));
                }

                

                if (!empty($dataUI->Pan_No)) {
                    $filedData = array_merge($filedData, array('Pan_No' => $dataUI->Pan_No));
                } else {
                    $filedData = array_merge($filedData, array('Pan_No' => null));
                }

                if (!empty($dataUI->sub_registration)) {
                    $filedData = array_merge($filedData, array('Subsidy_Regn_No' => $dataUI->sub_registration));
                } else {
                    $filedData = array_merge($filedData, array('Subsidy_Regn_No' => null));
                }

                if (!empty($dataUI->sub_registration_date)) {
                    $filedData = array_merge($filedData, array('Subsidy_Regn_Date' => $dataUI->sub_registration_date));
                } else {
                    $filedData = array_merge($filedData, array('Subsidy_Regn_Date' => null));
                }

                if (!empty($dataUI->ind_registration)) {
                    $filedData = array_merge($filedData, array('Industry_Regn_No' => $dataUI->ind_registration));
                } else {
                    $filedData = array_merge($filedData, array('Industry_Regn_No' => null));
                }

                if (!empty($dataUI->ind_registration_date)) {
                    $filedData = array_merge($filedData, array('Industry_Regn_Date' => $dataUI->ind_registration_date));
                } else {
                    $filedData = array_merge($filedData, array('Industry_Regn_Date' => null));
                }
                $updateBenificiary = array_merge($filedData, array(
                    'Benificiary_Name' => $dataUI->Benificiary_Name,
                    'Production_Date' => $dataUI->production_date, 'Bank_Id_Fk' => $dataUI->Bank_Id, 'Gov_Policy_Id' => $dataUI->gov_policy,
                    'Address_Id_Fk' => $addressID, 'Sector_Id_Fk' => $dataUI->sector_id,
                    'Record_Update_Date' => new \DateTime(), 'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'), 'Updated_By' => $request->session()->get('id')
                ));
                $addresBenificiary = TbCmnBenificiaryMaster::where('Pkid', $id)->update($updateBenificiary);
                $status = "success";
            } else {
                $status = "failed";
            }
            // all good
            DB::commit();

            $bankMaster      =       TbCmnBankMaster::all()
                ->where('Record_Active_Flag', '1')
                ->sortBy('Bank_Name');

            $benificiaryList      =       TbCmnBenificiaryMaster::join('tb_cmn_status_master', 'tb_cmn_benificiary_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
                ->leftjoin('tb_cmn_bank_master', 'tb_cmn_bank_master.Pkid', '=', 'tb_cmn_benificiary_master.Bank_Id_Fk')
                ->where('tb_cmn_benificiary_master.Record_Active_Flag', '1')
                ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
                ->get(['tb_cmn_benificiary_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_bank_master.Bank_Name'])
                ->sortBy('Pkid');
            $html_view = view("benificiary-ui.search-benificiary", compact('benificiaryList', 'bankMaster'))->render();
            $rawMaterial      =       TbCmnRawMaterialMaster::all()
                ->whereIn('Status_Id', [5])
                ->where('Record_Active_Flag', '1')
                ->sortByDesc('Material_Name');
            $finishGoods      =       TbCmnFinishGoodsMaster::all()
                ->whereIn('Status_Id', [5])
                ->where('Record_Active_Flag', '1')
                ->sortByDesc('Goods_Name');
            $unitMaster      =       TbCmnUnitMaster::all()
                ->whereIn('Status_Id', [5])
                ->where('Record_Active_Flag', '1')
                ->sortByDesc('Unit_Name');
            $govPolicy      =       TbCmnPolicyMaster::all()
                ->sortByDesc('Policy_Name');
            $stateMaster      =       TbCmnStateMaster::all()
                ->sortBy('State_Name');
            $scheme = TbCmnSchemeMaster::all()
                ->whereIn('Status_Id', [5])
                ->where('Record_Active_Flag', '1')
                ->sortBy('Pkid');

            $stateID = null;
            $editBenificiaryList       =       TbCmnBenificiaryMaster::leftjoin('tb_cmn_raw_material_master', 'tb_cmn_benificiary_master.Raw_Materials_Id_Fk', '=', 'tb_cmn_raw_material_master.Pkid')
                ->leftjoin('tb_cmn_finish_goods_master', 'tb_cmn_benificiary_master.Finish_Goods_Id_Fk', '=', 'tb_cmn_finish_goods_master.Pkid')
                ->join('tb_cmn_address', 'tb_cmn_benificiary_master.Address_Id_Fk', '=', 'tb_cmn_address.Pkid')
                ->leftjoin('tb_cmn_district_master', 'tb_cmn_address.District_Id', '=', 'tb_cmn_district_master.Pkid')
                ->join('tb_cmn_state_master', 'tb_cmn_address.State_Code', '=', 'tb_cmn_state_master.Pkid')
                ->leftjoin('tb_cmn_bank_master', 'tb_cmn_benificiary_master.Bank_Id_Fk', '=', 'tb_cmn_bank_master.Pkid')
                ->join('tb_cmn_status_master', 'tb_cmn_benificiary_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
                ->join('tb_cmn_sector_master', 'tb_cmn_benificiary_master.Sector_Id_Fk', '=', 'tb_cmn_sector_master.Pkid')
                ->where('tb_cmn_benificiary_master.Record_Active_Flag', '1')
                ->where("tb_cmn_benificiary_master.Pkid", $id)->get(['tb_cmn_benificiary_master.*', 'tb_cmn_sector_master.Sector_Name', 'tb_cmn_bank_master.Bank_Name', 'tb_cmn_state_master.State_Name', 'tb_cmn_state_master.Pkid AS State_Code', 'tb_cmn_district_master.Pkid AS District_Id', 'tb_cmn_district_master.District_Name', 'tb_cmn_address.Address1', 'tb_cmn_address.Address2', 'tb_cmn_status_master.Status_Name', 'tb_cmn_raw_material_master.Material_Name', 'tb_cmn_finish_goods_master.Goods_Name']);

            foreach ($editBenificiaryList as $list) {
                $stateID = $list->State_Code;
            }
            $MODE = 'EDT';
            // echo count($editBenificiaryList); exit;
            $districtMaster = TbCmnDistrictMaster::where('State_Id_Fk', $stateID)
                ->get(['tb_cmn_district_master.*'])->sortBy('District_Name');
            $html_view1 = view("benificiary-ui.after-edit-benificiary", compact('editBenificiaryList', 'districtMaster', 'finishGoods', 'stateMaster', 'unitMaster', 'rawMaterial', 'finishGoods', 'govPolicy', 'MODE'))->render();
        } catch (\Exception $e) {
            $status = "failed";
            DB::rollback();
            return $e->getMessage();
            // something went wrong
        }

        if ($status == "success") {
            return response()->json(["status" => $status, "message" => "Success! Beneficiary updated.", "body" => $html_view, "body1" => $html_view1]);
        } else {
            return response()->json(["status" => $status, "message" => "Alert! Beneficiary not updates"]);
        }
    }

    // Approval record
    public function benificiaryApproval(Request $request, $id)
    {
        $dataUI = json_decode($request->getContent());
        DB::beginTransaction();
        try {
            $tableData = array(
                'Status_Id' => '2', 'Record_Update_Date' => new \DateTime(), 'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'), 'Updated_By' => $request->session()->get('id')
            );
            $updateTable = TbCmnBenificiaryMaster::where('Pkid',  $id)
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
        $bankMaster      =       TbCmnBankMaster::all()
            ->where('Record_Active_Flag', '1')
            ->sortBy('Bank_Name');
        $benificiaryList      =       TbCmnBenificiaryMaster::join('tb_cmn_status_master', 'tb_cmn_benificiary_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->leftjoin('tb_cmn_bank_master', 'tb_cmn_bank_master.Pkid', '=', 'tb_cmn_benificiary_master.Bank_Id_Fk')
            ->where('tb_cmn_benificiary_master.Record_Active_Flag', '1')
            ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
            ->get(['tb_cmn_benificiary_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_bank_master.Bank_Name'])
            ->sortBy('Pkid');
        $html_view = view("benificiary-ui.search-benificiary", compact('benificiaryList', 'bankMaster'))->render();
        if ($status == "success") {
            return response()->json(["status" => $status, "message" => "Success! Beneficiary submitted for approval.", "body" => $html_view]);
        } else {
            return response()->json(["status" => $status, "message" => "Alert! Beneficiary not submitted for approval"]);
        }
    }
}
