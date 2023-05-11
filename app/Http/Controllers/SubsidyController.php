<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\TbBenificiarySchemeTxn;
use App\Models\TbCmnAddress;
use Illuminate\Http\Request;
use App\Models\TbCmnFinishGoodsMaster;
use App\Models\TbCmnFundMaster;
use App\Models\TbCmnNedfiBankMaster;
use App\Models\TbCmnReasonMaster;
use App\Models\TbCmnRawMaterialMaster;
use App\Models\TbCmnProductMaster;
use App\Models\TbCmnBenificiaryMaster;
use App\Models\TbCmnDistrictMaster;
use App\Models\TbCmnStateMaster;
use App\Models\TbCmnPolicyMaster;
use App\Models\TbCmnSchemeMaster;
use App\Models\TbSmsClaimMaster;
use App\Models\TbCmnApproval;
use App\Models\TbCmnStatusMaster;
use App\Models\TbSubsidySlcDateTxn;
use DateTime;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SubsidyController extends Controller
{
    public function addSubsidy()
    {

        $subsidyMaster      =       TbCmnSchemeMaster::whereIn('Status_Id', [5])
            ->where('Record_Active_Flag', '1')
            ->get(['tb_cmn_scheme_master.*'])
            ->sortBy('Scheme_Name');
        $govPolicy      =       TbCmnPolicyMaster::all()
            ->sortBy('Policy_Name');
        $stateMaster = TbCmnStateMaster::all()
            ->sortBy('State_Name');

        $smsClaimMaster = DB::table('tb_sms_claim_master')
            ->join('tb_cmn_status_master', 'tb_sms_claim_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->join('tb_cmn_benificiary_master', 'tb_sms_claim_master.Benificiary_Id_Fk', '=', 'tb_cmn_benificiary_master.Pkid')
            ->join('tb_cmn_scheme_master', 'tb_sms_claim_master.Scheme_Id_Fk', '=', 'tb_cmn_scheme_master.Pkid')
            ->join('tb_cmn_address', 'tb_cmn_benificiary_master.Address_Id_Fk', '=', 'tb_cmn_address.Pkid')
            ->join('tb_cmn_state_master', 'tb_cmn_address.State_Code', '=', 'tb_cmn_state_master.Pkid')
            ->where('tb_sms_claim_master.Record_Active_Flag', 1)
            ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
            ->get(['tb_sms_claim_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_benificiary_master.Benificiary_Name', 'tb_cmn_benificiary_master.Pan_No', 'tb_cmn_benificiary_master.Benificiary_Name', 'tb_cmn_scheme_master.Scheme_Name', 'tb_cmn_state_master.State_Name'])->sortBy('Pkid');
        $html = view('subsidy-ui.create-subsidy', compact('subsidyMaster', 'govPolicy', 'smsClaimMaster', 'stateMaster'))->render();
        return response()->json(['status' => "success", 'body' => $html]);
    }
    public function approveSubsidy()
    {
        try {
            $accessBoolean = (new CommonController)->checkAccessRightToController('approveSubsidy');
            if ($accessBoolean) {
                $approvalStatusMaster = TbCmnStatusMaster::whereIn('Pkid', [3, 4, 5])->get();
                $smsClaimMaster = DB::table('tb_sms_claim_master')
                    ->join('tb_cmn_status_master', 'tb_sms_claim_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
                    ->join('tb_cmn_benificiary_master', 'tb_sms_claim_master.Benificiary_Id_Fk', '=', 'tb_cmn_benificiary_master.Pkid')
                    ->join('tb_cmn_scheme_master', 'tb_sms_claim_master.Scheme_Id_Fk', '=', 'tb_cmn_scheme_master.Pkid')
                    ->join('tb_cmn_scheme_short_name', 'tb_cmn_scheme_master.Scheme_Short_Name_Id_Fk', '=', 'tb_cmn_scheme_short_name.Pkid')
                    ->join('tb_cmn_address', 'tb_cmn_benificiary_master.Address_Id_Fk', '=', 'tb_cmn_address.Pkid')
                    ->join('tb_cmn_state_master', 'tb_cmn_address.State_Code', '=', 'tb_cmn_state_master.Pkid')
                    ->whereIn('tb_cmn_status_master.Pkid', [2])
                    ->where('tb_sms_claim_master.Record_Active_Flag', 1)
                    ->get(['tb_sms_claim_master.*', 'tb_cmn_scheme_short_name.Short_Name', 'tb_cmn_status_master.Status_Name', 'tb_cmn_benificiary_master.Benificiary_Name', 'tb_cmn_benificiary_master.Pan_No', 'tb_cmn_benificiary_master.Benificiary_Name', 'tb_cmn_scheme_master.Scheme_Name', 'tb_cmn_state_master.State_Name'])
                    ->sortBy('Pkid');
                $html = view('subsidy-ui.approve-subsidy', compact('smsClaimMaster', 'approvalStatusMaster'))->render();
                return response()->json(['status' => "success", 'body' => $html]);
            } else {
                $html_view = view('pages.error-pages.access-deny-modal')->render();
                return response()->json(["status" => "success", "body" => $html_view]);
            }
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
    public function searchSubsidyResult(Request $request)
    {
        $dataUI = json_decode($request->getContent());
        $query = DB::table('tb_sms_claim_master')
            ->join('tb_cmn_status_master', 'tb_sms_claim_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->join('tb_cmn_benificiary_master', 'tb_sms_claim_master.Benificiary_Id_Fk', '=', 'tb_cmn_benificiary_master.Pkid')
            ->leftjoin('tb_cmn_scheme_master', 'tb_sms_claim_master.Scheme_Id_Fk', '=', 'tb_cmn_scheme_master.Pkid')
            ->leftjoin('tb_cmn_scheme_short_name', 'tb_cmn_scheme_master.Scheme_Short_Name_Id_Fk', '=', 'tb_cmn_scheme_short_name.Pkid')
            ->join('tb_cmn_address', 'tb_cmn_benificiary_master.Address_Id_Fk', '=', 'tb_cmn_address.Pkid')
            ->join('tb_cmn_state_master', 'tb_cmn_address.State_Code', '=', 'tb_cmn_state_master.Pkid')
            ->whereIn('tb_cmn_status_master.Pkid', [1, 2, 3, 4, 5])
            ->where('tb_sms_claim_master.Record_Active_Flag', 1);


        try {         
            if (!empty($dataUI->claim_from) && !empty($dataUI->claim_to)) {
                $query->whereBetween('tb_sms_claim_master.Claim_From_Date', [$dataUI->claim_from, $dataUI->claim_to]);
            }
            if (!empty($dataUI->search_benificiary)) {
                $query->where('tb_cmn_benificiary_master.Benificiary_Name', 'LIKE', "%{$dataUI->search_benificiary}%");
            }
            if (!empty($dataUI->scheme)) {
                $query->where('tb_sms_claim_master.Scheme_Id_Fk', $dataUI->scheme);
            }
            if (!empty($dataUI->state_id)) {
                $query->where('tb_cmn_address.State_Code', $dataUI->state_id);
            }
            if (!empty($dataUI->district_id)) {
                $query->where('tb_cmn_address.District_Id', $dataUI->district_id);
            }
            // if (!empty($dataUI->policy_id)) {
            //     $query->where('tb_cmn_benificiary_master.Gov_Policy_Id', $dataUI->policy_id);
            // }

            $smsClaimMaster = $query->get(['tb_sms_claim_master.*','tb_sms_claim_master.Claim_From_Date','tb_sms_claim_master.Claim_To_Date', 'tb_cmn_scheme_short_name.Short_Name',  'tb_cmn_status_master.Status_Name', 'tb_cmn_benificiary_master.Benificiary_Name', 'tb_cmn_benificiary_master.Pan_No', 'tb_cmn_benificiary_master.Benificiary_Name', 'tb_cmn_scheme_master.Scheme_Name', 'tb_cmn_state_master.State_Name'])->sortBy('Benificiary_Name');
        } catch (\Exception $ex) {
            throw $ex;
        }
        $html = view('subsidy-ui.search-subsidy-result', compact('smsClaimMaster'))->render();
        return response()->json(['status' => "success", 'body' => $html]);
    }

    public function addSubsidyModal()
    {
        $accessBoolean = (new CommonController)->checkAccessRightToController('addSubsidyModal');
        if ($accessBoolean) {
            $benificiary = TbCmnBenificiaryMaster::all()->where("Status_Id", '5')
                ->where('Record_Active_Flag', 1)              
                ->sortBy('Benificiary_Name');
            $remarks      =       TbCmnReasonMaster::all()
                ->where("Status_Id", '5')
                ->where('Record_Active_Flag', 1)
                ->sortBy('Reason_Details');
            $scheme = TbCmnSchemeMaster::all()
                ->where('Record_Active_Flag', 1)
                ->where("Status_Id", '5')
                ->sortBy('Scheme_Name');
            $html = view('subsidy-ui.create-subsidy-modal', compact('remarks', 'scheme', 'benificiary'))->render();
            //return view('benificiary-ui.benificiary-search-result');
            return response()->json(['status' => "success", 'body' => $html]);
        } else {
            $html_view = view('pages.error-pages.access-deny-modal')->render();
            return response()->json(["status" => "success", "body" => $html_view]);
        }
    }

    public function fundAllocation()
    {
        $subsidyMaster      =       TbSmsClaimMaster::all()
            ->where('Record_Active_Flag', 1)
            ->where("Status_Id", '5')
            ->sortBy('Pkid');
        $html = view('subsidy-ui.fund-allocation', compact('subsidyMaster'))->render();
        //return view('benificiary-ui.benificiary-search-result');
        return response()->json(['status' => "success", 'body' => $html]);
    }

    public function saveSubsidy(Request $request)
    {
        $dataUI = json_decode($request->getContent());
        //  DB::beginTransaction();
        try {
            // Create 
            $saveSubsidyClaim = new TbSmsClaimMaster();
            $whereCondition = '';
            $short_name = $dataUI->id_hidden_short_name;
            if ($short_name == 'CCIS' or $short_name == 'CCIIAC') {
                $whereCondition = [
                    ['Benificiary_Id_Fk', '=', $dataUI->Benificiary_Id],
                    ['Scheme_Id_Fk', '=',  $dataUI->scheme_id],
                ];
            } else {
                $whereCondition = [
                    ['Benificiary_Id_Fk', '=', $dataUI->Benificiary_Id],
                    ['Scheme_Id_Fk', '=',  $dataUI->scheme_id],
                    ['Claim_From_Date', '=',  $dataUI->claim_from],
                    ['Claim_To_Date', '=',  $dataUI->claim_to],
                ];
            }
            $UniqueName = TbSmsClaimMaster::where($whereCondition)
                ->where('tb_sms_claim_master.Record_Active_Flag', 1)
                ->get(['tb_sms_claim_master.*'])
                ->sortBy('Pkid');
            if (count($UniqueName) > 0) {
                return response()->json(["status" => "failed", "message" => "Alert! Benificiary is already added for Claim !"]);
            }
            $date = Carbon::now();
            $currentYear = $date->format('Y');
            $currentMonth = $date->format('m');
            $yearFormat = '';
            if ((int)$currentMonth > 3) {
                $yearFormat = $currentYear . '/' . ((int)$currentYear + 1);
            } else {
                $yearFormat = ((int)$currentYear - 1) . '/' . $currentYear;
            }
            $countCurrentYearCounter = TbSmsClaimMaster::where('Claim_Year', '=', $yearFormat)->get();
            $count = count($countCurrentYearCounter);
            $saveSubsidyClaim->Scheme_Id_Fk = $dataUI->scheme_id;
            $saveSubsidyClaim->Claim_Id = 'CL/' . $yearFormat . '/' . (int)($count + 1);
            $saveSubsidyClaim->Claim_Year = $yearFormat;
            $saveSubsidyClaim->Benificiary_Id_Fk = $dataUI->Benificiary_Id;
            $saveSubsidyClaim->Remarks = $dataUI->remarks_id;
            $saveSubsidyClaim->Claim_Status = '0';
            $saveSubsidyClaim->Audit_Status = $dataUI->audit_status;
            $saveSubsidyClaim->Received_Date = $dataUI->claim_receive_date;
            if ($short_name == 'CCIS' or $short_name == 'CCIIAC') {
                //CCIS_CCIIAC
                $saveSubsidyClaim->File_Volume_No = $dataUI->c_file_volume;
                $saveSubsidyClaim->Investment_On_Plant_Machinery = $dataUI->c_investment_on_plant;
                $saveSubsidyClaim->Investment_On_Building = $dataUI->c_investment_on_building;
                $saveSubsidyClaim->Approved_On_Plant_Machinery = $dataUI->c_approve_cs_on_plant;
                $saveSubsidyClaim->Subsidy_Claim_Amount = $dataUI->c_subsidy_claim_amount;
                if (!empty($dataUI->c_under_eccc)) {
                    $saveSubsidyClaim->Under_EC_CC = $dataUI->c_under_eccc;
                }
                if (!empty($dataUI->c_ec_cc_date)) {
                    $saveSubsidyClaim->EC_CC_Date = $dataUI->c_ec_cc_date;
                }
                $saveSubsidyClaim->Claim_Amount = $dataUI->c_subsidy_claim_amount;
                $saveSubsidyClaim->Claim_Balance_Amount = $dataUI->c_subsidy_claim_amount;
            } elseif ($short_name == 'TSS' or $short_name == 'FSS' or $short_name == 'TI') {
                //TSS_FSS_TI
                $saveSubsidyClaim->File_Volume_No = $dataUI->a_file_volume;
                if (!empty($dataUI->a_raw_material)) {
                $saveSubsidyClaim->Raw_Materials_Quantity = $dataUI->a_raw_material;
                }
                if (!empty($dataUI->a_finish_goods)) {
                $saveSubsidyClaim->Finish_Goods_Quantity = $dataUI->a_finish_goods;
                }
                $saveSubsidyClaim->Approved_Raw_Materials = $dataUI->a_raw_approve_ts;
                $saveSubsidyClaim->Approved_Finish_Goods = $dataUI->a_goods_approve_ts;
                $saveSubsidyClaim->Subsidy_Claim_Amount = $dataUI->a_subsidy_claim_amount;
                $saveSubsidyClaim->Claim_From_Date = $dataUI->claim_from;
                $saveSubsidyClaim->Claim_To_Date = $dataUI->claim_to;
                $saveSubsidyClaim->Claim_Amount = $dataUI->a_subsidy_claim_amount;
                $saveSubsidyClaim->Claim_Balance_Amount = $dataUI->a_subsidy_claim_amount;
            } elseif ($short_name == 'INS' or $short_name == 'CCII') {
                //INS_CCII
                $saveSubsidyClaim->Sum_Insured = $dataUI->e_sum_insured;
                $saveSubsidyClaim->Insured_Stock = $dataUI->e_insured_stock;
                $saveSubsidyClaim->Value_Covered_Insurance = $dataUI->e_value_covered;
                $saveSubsidyClaim->Premium_Actually_Paid = $dataUI->e_premium_actualy_paid;
                if (!empty($dataUI->e_refund)) {
                    $saveSubsidyClaim->Refund = $dataUI->e_refund;
                }
                if (!empty($dataUI->e_premium_eligible)) {
                    $saveSubsidyClaim->Premium_Eligible_For_Reimbursement = $dataUI->e_premium_eligible;
                }
                if (!empty($dataUI->e_commencement_date)) {
                    $saveSubsidyClaim->Premium_Commencement_Date = $dataUI->e_commencement_date;
                }
                if (!empty($dataUI->e_insurance_policy)) {
                    $saveSubsidyClaim->Insurance_Policy_No = $dataUI->e_insurance_policy;
                }
                if (!empty($dataUI->e_endorsement_policy)) {
                    $saveSubsidyClaim->Endorsement_Policy_No = $dataUI->e_endorsement_policy;
                }
                $saveSubsidyClaim->Claim_From_Date = $dataUI->claim_from;
                $saveSubsidyClaim->Claim_To_Date = $dataUI->claim_to;
                $saveSubsidyClaim->File_Volume_No = $dataUI->e_file_volume;
                $saveSubsidyClaim->Subsidy_Claim_Amount = $dataUI->e_subsidy_claim_amount;
                $saveSubsidyClaim->Claim_Amount = $dataUI->e_subsidy_claim_amount;
                $saveSubsidyClaim->Claim_Balance_Amount = $dataUI->e_subsidy_claim_amount;
            } elseif ($short_name == 'CIS') {
                //CIS
                $saveSubsidyClaim->File_Volume_No = $dataUI->d_file_volume;
                $saveSubsidyClaim->Approved_Interest_Subsidy = $dataUI->d_approved_interest_subsidy;
                $saveSubsidyClaim->Subsidy_Claim_Amount = $dataUI->d_subsidy_claim_amount;
                $saveSubsidyClaim->Claim_Amount = $dataUI->d_subsidy_claim_amount;
                $saveSubsidyClaim->Claim_Balance_Amount = $dataUI->d_subsidy_claim_amount;
                $saveSubsidyClaim->Claim_From_Date = $dataUI->claim_from;
                $saveSubsidyClaim->Claim_To_Date = $dataUI->claim_to;
            }



            // $saveSubsidyClaim->Claim_From_Date = $dataUI->claim_from;
            // $saveSubsidyClaim->Claim_To_Date = $dataUI->claim_to;
            // $saveSubsidyClaim->File_Volume_No = $dataUI->file_volume;
            // $saveSubsidyClaim->Raw_Materials_Quantity = $dataUI->raw_material;
            // $saveSubsidyClaim->Finish_Goods_Quantity = $dataUI->finish_goods;
            // $saveSubsidyClaim->Approved_Raw_Materials = $dataUI->raw_approve_ts;
            // $saveSubsidyClaim->Approved_Finish_Goods = $dataUI->goods_approve_ts;
            // $saveSubsidyClaim->Investment_On_Plant_Machinery = $dataUI->investment_on_plant;
            // $saveSubsidyClaim->Approved_On_Plant_Machinery = $dataUI->approve_cs_on_plant;
            // $saveSubsidyClaim->Approved_Interest_Subsidy = $dataUI->approve_interest_subsidy;

            // $saveSubsidyClaim->Sum_Insured = $dataUI->sum_insured;
            // $saveSubsidyClaim->Insured_Stock = $dataUI->insured_stock;
            // $saveSubsidyClaim->Value_Covered_Insurance = $dataUI->value_covered;
            // $saveSubsidyClaim->Premium_Actually_Paid = $dataUI->premium_actualy_paid;
            // $saveSubsidyClaim->Subsidy_Claim_Amount = $dataUI->subsidy_claim_amount;

            // if (!empty($dataUI->refund)) {
            //     $saveSubsidyClaim->Refund = $dataUI->refund;
            // }
            // if (!empty($dataUI->premium_eligible)) {
            //     $saveSubsidyClaim->Premium_Eligible_For_Reimbursement = $dataUI->premium_eligible;
            // }
            // if (!empty($dataUI->commencement_date)) {
            //     $saveSubsidyClaim->Premium_Commencement_Date = $dataUI->commencement_date;
            // }
            // if (!empty($dataUI->insurance_policy)) {
            //     $saveSubsidyClaim->Insurance_Policy_No = $dataUI->insurance_policy;
            // }
            // if (!empty($dataUI->endorsement_policy)) {
            //     $saveSubsidyClaim->Endorsement_Policy_No = $dataUI->endorsement_policy;
            // }
            // if (!empty($dataUI->under_eccc)) {
            //     $saveSubsidyClaim->Under_EC_CC = $dataUI->under_eccc;
            // }
            // if (!empty($dataUI->ec_cc_date)) {
            //     $saveSubsidyClaim->EC_CC_Date = $dataUI->ec_cc_date;
            // }


            $saveSubsidyClaim->Status_Id = config("constants.CREATED");
            $result = (new CommonController)->insertDefaultColumns($request, $saveSubsidyClaim);
            $saveSubsidyClaim->save();

            if (is_array($dataUI->slc_date)) {
                foreach ($dataUI->slc_date as $key => $slc) {
                    $saveSlcDate = new TbSubsidySlcDateTxn();
                    $saveSlcDate->Subsidy_Id_fk = $saveSubsidyClaim->Pkid;
                    $saveSlcDate->Slc_Date = $dataUI->slc_date[$key];
                    $defaultColumns = (new CommonController)->insertDefaultColumns($request, $saveSlcDate);
                    $saveSlcDate->save();
                }
            } else {
                $saveSlcDate = new TbSubsidySlcDateTxn();
                $saveSlcDate->Subsidy_Id_fk = $saveSubsidyClaim->Pkid;
                $saveSlcDate->Slc_Date = $dataUI->slc_date;
                $defaultColumns = (new CommonController)->insertDefaultColumns($request, $saveSlcDate);
                $saveSlcDate->save();
            }
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


            $smsClaimMaster = DB::table('tb_sms_claim_master')
                ->join('tb_cmn_status_master', 'tb_sms_claim_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
                ->join('tb_cmn_benificiary_master', 'tb_sms_claim_master.Benificiary_Id_Fk', '=', 'tb_cmn_benificiary_master.Pkid')
                ->join('tb_cmn_scheme_master', 'tb_sms_claim_master.Scheme_Id_Fk', '=', 'tb_cmn_scheme_master.Pkid')
                ->join('tb_cmn_scheme_short_name', 'tb_cmn_scheme_master.Scheme_Short_Name_Id_Fk', '=', 'tb_cmn_scheme_short_name.Pkid')
                ->join('tb_cmn_address', 'tb_cmn_benificiary_master.Address_Id_Fk', '=', 'tb_cmn_address.Pkid')
                ->join('tb_cmn_state_master', 'tb_cmn_address.State_Code', '=', 'tb_cmn_state_master.Pkid')
                ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
                ->where('tb_sms_claim_master.Record_Active_Flag', 1)
                ->get(['tb_sms_claim_master.*', 'tb_cmn_scheme_short_name.Short_Name', 'tb_cmn_status_master.Status_Name', 'tb_cmn_benificiary_master.Benificiary_Name', 'tb_cmn_benificiary_master.Pan_No', 'tb_cmn_benificiary_master.Benificiary_Name', 'tb_cmn_scheme_master.Scheme_Name', 'tb_cmn_state_master.State_Name'])
                ->sortBy('Pkid');

            $html_view = view("subsidy-ui.search-subsidy", compact('smsClaimMaster'))->render();

            $benificiary = TbCmnBenificiaryMaster::all()
                ->whereIn('Status_Id', [5])
                ->where('Record_Active_Flag', '1')
                ->sortBy('Benificiary_Name');
            $remarks      =       TbCmnReasonMaster::all()
                ->whereIn('Status_Id', [5])
                ->where('Record_Active_Flag', '1')
                ->sortBy('Reason_Details');
            $scheme = TbCmnSchemeMaster::all()
                ->whereIn('Status_Id', [5])
                ->where('Record_Active_Flag', '1')
                ->sortBy('Scheme_Name');
            $MODE = 'EDT';
            $smsClaimMasterUpdate = DB::table('tb_sms_claim_master')
                ->join('tb_cmn_status_master', 'tb_sms_claim_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
                ->join('tb_cmn_benificiary_master', 'tb_sms_claim_master.Benificiary_Id_Fk', '=', 'tb_cmn_benificiary_master.Pkid')
                ->join('tb_cmn_scheme_master', 'tb_sms_claim_master.Scheme_Id_Fk', '=', 'tb_cmn_scheme_master.Pkid')
                ->join('tb_cmn_scheme_short_name', 'tb_cmn_scheme_master.Scheme_Short_Name_Id_Fk', '=', 'tb_cmn_scheme_short_name.Pkid')
                ->join('tb_cmn_address', 'tb_cmn_benificiary_master.Address_Id_Fk', '=', 'tb_cmn_address.Pkid')
                ->join('tb_cmn_state_master', 'tb_cmn_address.State_Code', '=', 'tb_cmn_state_master.Pkid')
                ->join('tb_cmn_reason_master', 'tb_sms_claim_master.Remarks', '=', 'tb_cmn_reason_master.Pkid')
                ->where('tb_sms_claim_master.Pkid', $saveSubsidyClaim->Pkid)
                ->where('tb_sms_claim_master.Record_Active_Flag', 1)
                ->get(['tb_sms_claim_master.*', 'tb_cmn_scheme_short_name.Short_Name',  'tb_cmn_reason_master.Reason_Details', 'tb_cmn_status_master.Status_Name', 'tb_cmn_benificiary_master.Benificiary_Name', 'tb_cmn_benificiary_master.Pan_No', 'tb_cmn_scheme_master.Scheme_Name', 'tb_cmn_scheme_master.Pkid AS Scheme_Id', 'tb_cmn_state_master.State_Name'])->sortBy('Pkid');

            $slcDateTxn =  DB::table('tb_subsidy_slc_date_txn')
                ->join('tb_sms_claim_master', 'tb_sms_claim_master.Pkid', '=', 'tb_subsidy_slc_date_txn.Subsidy_Id_Fk')
                ->where('tb_sms_claim_master.Pkid', $saveSubsidyClaim->Pkid)
                ->where('tb_subsidy_slc_date_txn.Record_Active_Flag', 1)
                ->get(['tb_subsidy_slc_date_txn.*']);
            $html_view1 = view("subsidy-ui.view-edit-subsidy-claim", compact('smsClaimMasterUpdate', 'remarks', 'scheme', 'benificiary', 'MODE', 'slcDateTxn'))->render();

            if ($status == "success") {
                return response()->json(["status" => $status, "message" => "Success! Subsidy Claim created.", "data" => $smsClaimMaster, "body" => $html_view, "body1" => $html_view1]);
            } else {
                return response()->json(["status" => $status, "message" => "Alert! Subsidy Claim not created"]);
            }
        } catch (\Exception $e) {
            $status = "failed";
            DB::rollback();
            return $e->getMessage();
            // something went wrong
        }
    }

    public function destroySubsidyClaim(Request $request, $subsidy_id)
    {
        $accessBoolean = (new CommonController)->checkAccessRightToController('destroySubsidyClaim');
        if ($accessBoolean) {
            DB::beginTransaction();
            try {
                $deleteSubsidyClaim       =       TbSmsClaimMaster::where("Pkid", $subsidy_id)
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
            $smsClaimMaster = DB::table('tb_sms_claim_master')
                ->join('tb_cmn_status_master', 'tb_sms_claim_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
                ->join('tb_cmn_benificiary_master', 'tb_sms_claim_master.Benificiary_Id_Fk', '=', 'tb_cmn_benificiary_master.Pkid')
                ->join('tb_cmn_scheme_master', 'tb_sms_claim_master.Scheme_Id_Fk', '=', 'tb_cmn_scheme_master.Pkid')
                ->join('tb_cmn_scheme_short_name', 'tb_cmn_scheme_master.Scheme_Short_Name_Id_Fk', '=', 'tb_cmn_scheme_short_name.Pkid')
                ->join('tb_cmn_address', 'tb_cmn_benificiary_master.Address_Id_Fk', '=', 'tb_cmn_address.Pkid')
                ->join('tb_cmn_state_master', 'tb_cmn_address.State_Code', '=', 'tb_cmn_state_master.Pkid')
                ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
                ->where('tb_sms_claim_master.Record_Active_Flag', 1)
                ->get(['tb_sms_claim_master.*', 'tb_cmn_scheme_short_name.Short_Name', 'tb_cmn_status_master.Status_Name', 'tb_cmn_benificiary_master.Benificiary_Name', 'tb_cmn_benificiary_master.Pan_No', 'tb_cmn_benificiary_master.Benificiary_Name', 'tb_cmn_scheme_master.Scheme_Name', 'tb_cmn_state_master.State_Name'])
                ->sortBy('Pkid');

            $html_view = view("subsidy-ui.search-subsidy", compact('smsClaimMaster'))->render();
            if ($status == "success") {
                return response()->json(["status" => $status, "message" => "Success! Subsidy Claim deleted.", "data" => $smsClaimMaster, "body" => $html_view]);
            } else {
                return response()->json(["status" => $status, "message" => "Alert! Subsidy Claim not deleted"]);
            }
        } else {
            $html_view = view('pages.error-pages.access-deny-modal')->render();
            return response()->json(["status" => "access_deny", "body" => $html_view]);
        }
    }

    public function viewEditSubsidy($id, $MODE)
    {

        try {

            $benificiary = TbCmnBenificiaryMaster::all()
                ->whereIn('Status_Id', [5])
                ->where('Record_Active_Flag', '1')
                ->sortBy('Benificiary_Name');
            $remarks      =       TbCmnReasonMaster::all()
                ->whereIn('Status_Id', [5])
                ->where('Record_Active_Flag', '1')
                ->sortBy('Reason_Details');
            $scheme = TbCmnSchemeMaster::all()
                ->whereIn('Status_Id', [5])
                ->where('Record_Active_Flag', '1')
                ->sortBy('Scheme_Name');
            $smsClaimMasterUpdate = DB::table('tb_sms_claim_master')
                ->join('tb_cmn_status_master', 'tb_sms_claim_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
                ->join('tb_cmn_benificiary_master', 'tb_sms_claim_master.Benificiary_Id_Fk', '=', 'tb_cmn_benificiary_master.Pkid')
                ->join('tb_cmn_scheme_master', 'tb_sms_claim_master.Scheme_Id_Fk', '=', 'tb_cmn_scheme_master.Pkid')
                ->leftjoin('tb_cmn_scheme_short_name', 'tb_cmn_scheme_master.Scheme_Short_Name_Id_Fk', '=', 'tb_cmn_scheme_short_name.Pkid')
                ->join('tb_cmn_address', 'tb_cmn_benificiary_master.Address_Id_Fk', '=', 'tb_cmn_address.Pkid')
                ->join('tb_cmn_state_master', 'tb_cmn_address.State_Code', '=', 'tb_cmn_state_master.Pkid')
                ->leftjoin('tb_cmn_reason_master', 'tb_sms_claim_master.Remarks', '=', 'tb_cmn_reason_master.Pkid')
                ->where('tb_sms_claim_master.Pkid', $id)
                ->where('tb_sms_claim_master.Record_Active_Flag', 1)
                ->get(['tb_sms_claim_master.*', 'tb_cmn_scheme_short_name.Short_Name', 'tb_cmn_reason_master.Reason_Details', 'tb_cmn_status_master.Status_Name', 'tb_cmn_benificiary_master.Benificiary_Name', 'tb_cmn_benificiary_master.Pan_No', 'tb_cmn_scheme_master.Scheme_Name', 'tb_cmn_scheme_master.Pkid AS Scheme_Id', 'tb_cmn_state_master.State_Name'])->sortBy('Pkid');
            $slcDateTxn =  DB::table('tb_subsidy_slc_date_txn')
                ->join('tb_sms_claim_master', 'tb_sms_claim_master.Pkid', '=', 'tb_subsidy_slc_date_txn.Subsidy_Id_Fk')
                ->where('tb_sms_claim_master.Pkid', $id)
                ->where('tb_subsidy_slc_date_txn.Record_Active_Flag', 1)
                ->get(['tb_subsidy_slc_date_txn.*']);
            if ($MODE == 'EDT') {
                $accessBoolean = (new CommonController)->checkAccessRightToController('viewEditSubsidy');
                if ($accessBoolean) {
                    $html_view = view("subsidy-ui.view-edit-subsidy-claim", compact('smsClaimMasterUpdate', 'remarks', 'scheme', 'benificiary', 'MODE', 'slcDateTxn'))->render();
                    return response()->json(["status" => "success", "body" => $html_view]);
                } else {
                    $html_view = view('pages.error-pages.access-deny-modal')->render();
                    return response()->json(["status" => "access_deny", "body" => $html_view]);
                }
            } else {
                $html_view = view("subsidy-ui.view-edit-subsidy-claim", compact('smsClaimMasterUpdate', 'remarks', 'scheme', 'benificiary', 'MODE', 'slcDateTxn'))->render();
                return response()->json(["status" => "success", "body" => $html_view]);
            }
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }

    public function claimApproval(Request $request, $id)
    {
        $dataUI = json_decode($request->getContent());
        DB::beginTransaction();
        try {
            $tableData = array(
                'Status_Id' => '2', 'Record_Update_Date' => new \DateTime(),
                'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'),
                'Updated_By' => $request->session()->get('id')
            );
            $updateTable = TbSmsClaimMaster::where('Pkid',  $id)
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
        $smsClaimMaster = DB::table('tb_sms_claim_master')
            ->join('tb_cmn_status_master', 'tb_sms_claim_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->join('tb_cmn_benificiary_master', 'tb_sms_claim_master.Benificiary_Id_Fk', '=', 'tb_cmn_benificiary_master.Pkid')
            ->join('tb_cmn_scheme_master', 'tb_sms_claim_master.Scheme_Id_Fk', '=', 'tb_cmn_scheme_master.Pkid')
            ->join('tb_cmn_scheme_short_name', 'tb_cmn_scheme_master.Scheme_Short_Name_Id_Fk', '=', 'tb_cmn_scheme_short_name.Pkid')
            ->join('tb_cmn_address', 'tb_cmn_benificiary_master.Address_Id_Fk', '=', 'tb_cmn_address.Pkid')
            ->join('tb_cmn_state_master', 'tb_cmn_address.State_Code', '=', 'tb_cmn_state_master.Pkid')
            ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
            ->where('tb_sms_claim_master.Record_Active_Flag', 1)
            ->get(['tb_sms_claim_master.*', 'tb_cmn_scheme_short_name.Short_Name',  'tb_cmn_status_master.Status_Name', 'tb_cmn_benificiary_master.Benificiary_Name', 'tb_cmn_benificiary_master.Pan_No', 'tb_cmn_benificiary_master.Benificiary_Name', 'tb_cmn_scheme_master.Scheme_Name', 'tb_cmn_state_master.State_Name'])
            ->sortBy('Pkid');

        $html_view = view("subsidy-ui.search-subsidy", compact('smsClaimMaster'))->render();
        if ($status == "success") {
            return response()->json(["status" => $status, "message" => "Success! Claim submitted for approval.", "body" => $html_view]);
        } else {
            return response()->json(["status" => $status, "message" => "Alert! Claim not submitted for approval"]);
        }
    }

    // Update record
    public function updateClaim(Request $request, $id)
    {
        $dataUI = json_decode($request->getContent());
        DB::beginTransaction();
        try {
            $whereCondition = '';
            $short_name = $dataUI->id_hidden_short_name;
            if ($short_name == 'CCIS' or $short_name == 'CCIIAC') {
                $whereCondition = [
                    ['Benificiary_Id_Fk', '=', $dataUI->Benificiary_Id],
                    ['Scheme_Id_Fk', '=',  $dataUI->scheme_id],
                ];
            } else {
                $whereCondition = [
                    ['Benificiary_Id_Fk', '=', $dataUI->Benificiary_Id],
                    ['Scheme_Id_Fk', '=',  $dataUI->scheme_id],
                    ['Claim_From_Date', '=',  $dataUI->claim_from],
                    ['Claim_To_Date', '=',  $dataUI->claim_to],
                ];
            }
            $UniqueName = TbSmsClaimMaster::where($whereCondition)->get();

            if (count($UniqueName) > 0) {
                if ($UniqueName->Pkid = $id) {
                } else {
                    return response()->json(["status" => "failed", "message" => "Alert! Benificiary is already added for Claim !"]);
                }
            }
            //update claim details
            $claimData = null;
            if ($short_name == 'CCIS' or $short_name == 'CCIIAC') {
                //CCIS_CCIIAC
                $Under_EC_CC = null;
                $EC_CC_Date = null;
                if (!empty($dataUI->c_under_eccc)) {
                    $Under_EC_CC = $dataUI->c_under_eccc;
                }
                if (!empty($dataUI->c_ec_cc_date)) {
                    $EC_CC_Date = $dataUI->c_ec_cc_date;
                }
                $claimData = array(
                    'Scheme_Id_Fk' => $dataUI->scheme_id, "Benificiary_Id_Fk" => $dataUI->Benificiary_Id,
                    'Remarks' => $dataUI->remarks_id, 'Audit_Status' => $dataUI->audit_status,
                    'File_Volume_No' => $dataUI->c_file_volume, 'Claim_Status' => '0',
                    'Investment_On_Plant_Machinery' => $dataUI->c_investment_on_plant,
                    'Investment_On_Building' => $dataUI->c_investment_on_building,
                    'Approved_On_Plant_Machinery' => $dataUI->c_approve_cs_on_plant,
                    'Subsidy_Claim_Amount' => $dataUI->c_subsidy_claim_amount,
                    'Claim_Amount' => $dataUI->c_subsidy_claim_amount,
                    'Claim_Balance_Amount' => $dataUI->c_subsidy_claim_amount,
                    'Under_EC_CC' => $Under_EC_CC, 'EC_CC_Date' => $EC_CC_Date,
                    'Record_Update_Date' => new \DateTime(),
                    'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'),
                    'Updated_By' => $request->session()->get('id')
                );
            } elseif ($short_name == 'TSS' or $short_name == 'FSS' or $short_name == 'TI') {
                //TSS_FSS_TI
                $filedData = [];
                if (!empty($dataUI->a_raw_material)) {
                    $filedData = array_merge($filedData, array('Raw_Materials_Quantity' => $dataUI->a_raw_material));
                } else {
                    $filedData = array_merge($filedData, array('Raw_Materials_Quantity' => null));
                }
                if (!empty($dataUI->a_finish_goods)) {
                    $filedData = array_merge($filedData,  array('Finish_Goods_Quantity' => $dataUI->a_finish_goods));
                } else {
                    $filedData = array_merge($filedData,  array('Finish_Goods_Quantity' => null));
                }
                // 'Raw_Materials_Quantity' => $dataUI->a_raw_material,
                //     'Finish_Goods_Quantity' => $dataUI->a_finish_goods,
                $claimData =array_merge($filedData, array(
                    'Scheme_Id_Fk' => $dataUI->scheme_id, "Benificiary_Id_Fk" => $dataUI->Benificiary_Id,
                    'Remarks' => $dataUI->remarks_id, 'Audit_Status' => $dataUI->audit_status,
                    'File_Volume_No' => $dataUI->a_file_volume, 'Claim_Status' => '0',                    
                    'Approved_Raw_Materials' => $dataUI->a_raw_approve_ts,
                    'Approved_Finish_Goods' => $dataUI->a_goods_approve_ts,
                    'Claim_From_Date' => $dataUI->claim_from,
                    'Claim_To_Date' => $dataUI->claim_to,
                    'Subsidy_Claim_Amount' => $dataUI->a_subsidy_claim_amount,
                    'Claim_Amount' => $dataUI->a_subsidy_claim_amount,
                    'Claim_Balance_Amount' => $dataUI->a_subsidy_claim_amount,
                    'Record_Update_Date' => new \DateTime(),
                    'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'),
                    'Updated_By' => $request->session()->get('id')
                ));
            } elseif ($short_name == 'INS' or $short_name == 'CCII') {
                //INS_CCII
                $Refund = null;
                $Premium_Eligible_For_Reimbursement = null;
                $Premium_Commencement_Date = null;
                $Insurance_Policy_No = null;
                $Endorsement_Policy_No = null;
                if (!empty($dataUI->e_refund)) {
                    $Refund = $dataUI->e_refund;
                }
                if (!empty($dataUI->e_premium_eligible)) {
                    $Premium_Eligible_For_Reimbursement = $dataUI->e_premium_eligible;
                }
                if (!empty($dataUI->e_commencement_date)) {
                    $Premium_Commencement_Date = $dataUI->e_commencement_date;
                }
                if (!empty($dataUI->e_insurance_policy)) {
                    $Insurance_Policy_No = $dataUI->e_insurance_policy;
                }
                if (!empty($dataUI->e_endorsement_policy)) {
                    $Endorsement_Policy_No = $dataUI->e_endorsement_policy;
                }
                $claimData = array(
                    'Scheme_Id_Fk' => $dataUI->scheme_id, "Benificiary_Id_Fk" => $dataUI->Benificiary_Id,
                    'Remarks' => $dataUI->remarks_id, 'Audit_Status' => $dataUI->audit_status,
                    'Sum_Insured' => $dataUI->e_sum_insured, 'Claim_Status' => '0',
                    'Insured_Stock' => $dataUI->e_insured_stock,
                    'Value_Covered_Insurance' => $dataUI->e_value_covered,
                    'Premium_Actually_Paid' => $dataUI->e_premium_actualy_paid,
                    'Refund' => $Refund, 'Premium_Eligible_For_Reimbursement' => $Premium_Eligible_For_Reimbursement,
                    'Premium_Commencement_Date' => $Premium_Commencement_Date, 'Insurance_Policy_No' => $Insurance_Policy_No,
                    'Endorsement_Policy_No' => $Endorsement_Policy_No,
                    'Claim_From_Date' => $dataUI->claim_from,
                    'Claim_To_Date' => $dataUI->claim_to,
                    'File_Volume_No' => $dataUI->e_file_volume,
                    'Subsidy_Claim_Amount' => $dataUI->e_subsidy_claim_amount,
                    'Claim_Amount' => $dataUI->e_subsidy_claim_amount,
                    'Claim_Balance_Amount' => $dataUI->e_subsidy_claim_amount,
                    'Record_Update_Date' => new \DateTime(),
                    'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'),
                    'Updated_By' => $request->session()->get('id')
                );
            } elseif ($short_name == 'CIS') {
                //CIS
                $claimData = array(
                    'Scheme_Id_Fk' => $dataUI->scheme_id, "Benificiary_Id_Fk" => $dataUI->Benificiary_Id,
                    'Remarks' => $dataUI->remarks_id, 'Audit_Status' => $dataUI->audit_status,
                    'File_Volume_No' => $dataUI->d_file_volume, 'Claim_Status' => '0',
                    'Approved_Interest_Subsidy' => $dataUI->d_approved_interest_subsidy,
                    'Claim_From_Date' => $dataUI->claim_from,
                    'Claim_To_Date' => $dataUI->claim_to,
                    'Subsidy_Claim_Amount' => $dataUI->d_subsidy_claim_amount,
                    'Claim_Amount' => $dataUI->d_subsidy_claim_amount,
                    'Claim_Balance_Amount' => $dataUI->d_subsidy_claim_amount,
                    'Record_Update_Date' => new \DateTime(),
                    'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'),
                    'Updated_By' => $request->session()->get('id')
                );
            }

            //print_r($claimData);
            TbSmsClaimMaster::where('Pkid', $id)->update($claimData);
            TbSubsidySlcDateTxn::where('Subsidy_Id_Fk', $id)->update(array('Record_Active_Flag' => '0'));
            if (is_array($dataUI->slc_date)) {
                foreach ($dataUI->slc_date as $key => $slc) {
                    $saveSlcDate = new TbSubsidySlcDateTxn();
                    $saveSlcDate->Subsidy_Id_fk = $id;
                    $saveSlcDate->Slc_Date = $dataUI->slc_date[$key];
                    $defaultColumns = (new CommonController)->insertDefaultColumns($request, $saveSlcDate);
                    $saveSlcDate->save();
                }
            } else {
                $saveSlcDate = new TbSubsidySlcDateTxn();
                $saveSlcDate->Subsidy_Id_fk = $id;
                $saveSlcDate->Slc_Date = $dataUI->slc_date;
                $defaultColumns = (new CommonController)->insertDefaultColumns($request, $saveSlcDate);
                $saveSlcDate->save();
            }

            $status = "success";

            // all good
            DB::commit();
        } catch (\Exception $e) {
            return $e->getMessage();
            $status = "failed";
            DB::rollback();
            // something went wrong
        }
        $smsClaimMaster = DB::table('tb_sms_claim_master')
            ->join('tb_cmn_status_master', 'tb_sms_claim_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->join('tb_cmn_benificiary_master', 'tb_sms_claim_master.Benificiary_Id_Fk', '=', 'tb_cmn_benificiary_master.Pkid')
            ->join('tb_cmn_scheme_master', 'tb_sms_claim_master.Scheme_Id_Fk', '=', 'tb_cmn_scheme_master.Pkid')
            ->join('tb_cmn_scheme_short_name', 'tb_cmn_scheme_master.Scheme_Short_Name_Id_Fk', '=', 'tb_cmn_scheme_short_name.Pkid')
            ->join('tb_cmn_address', 'tb_cmn_benificiary_master.Address_Id_Fk', '=', 'tb_cmn_address.Pkid')
            ->join('tb_cmn_state_master', 'tb_cmn_address.State_Code', '=', 'tb_cmn_state_master.Pkid')
            ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
            ->where('tb_sms_claim_master.Record_Active_Flag', 1)
            ->get(['tb_sms_claim_master.*', 'tb_cmn_scheme_short_name.Short_Name', 'tb_cmn_status_master.Status_Name', 'tb_cmn_benificiary_master.Benificiary_Name', 'tb_cmn_benificiary_master.Pan_No', 'tb_cmn_benificiary_master.Benificiary_Name', 'tb_cmn_scheme_master.Scheme_Name', 'tb_cmn_state_master.State_Name'])
            ->sortBy('Pkid');

        $html_view = view("subsidy-ui.search-subsidy", compact('smsClaimMaster'))->render();

        $benificiary = TbCmnBenificiaryMaster::all()
            ->whereIn('Status_Id', [5])
            ->where('Record_Active_Flag', '1')
            ->sortBy('Benificiary_Name');
        $remarks      =       TbCmnReasonMaster::all()
            ->whereIn('Status_Id', [5])
            ->where('Record_Active_Flag', '1')
            ->sortBy('Reason_Details');
        $scheme = TbCmnSchemeMaster::all()
            ->whereIn('Status_Id', [5])
            ->where('Record_Active_Flag', '1')
            ->sortBy('Scheme_Name');

        $MODE = 'EDT';
        $smsClaimMasterUpdate = DB::table('tb_sms_claim_master')
            ->join('tb_cmn_status_master', 'tb_sms_claim_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->join('tb_cmn_benificiary_master', 'tb_sms_claim_master.Benificiary_Id_Fk', '=', 'tb_cmn_benificiary_master.Pkid')
            ->join('tb_cmn_scheme_master', 'tb_sms_claim_master.Scheme_Id_Fk', '=', 'tb_cmn_scheme_master.Pkid')
            ->join('tb_cmn_scheme_short_name', 'tb_cmn_scheme_master.Scheme_Short_Name_Id_Fk', '=', 'tb_cmn_scheme_short_name.Pkid')
            ->join('tb_cmn_address', 'tb_cmn_benificiary_master.Address_Id_Fk', '=', 'tb_cmn_address.Pkid')
            ->join('tb_cmn_state_master', 'tb_cmn_address.State_Code', '=', 'tb_cmn_state_master.Pkid')
            ->join('tb_cmn_reason_master', 'tb_sms_claim_master.Remarks', '=', 'tb_cmn_reason_master.Pkid')
            ->where('tb_sms_claim_master.Pkid', $id)
            ->where('tb_sms_claim_master.Record_Active_Flag', 1)
            ->get(['tb_sms_claim_master.*', 'tb_cmn_scheme_short_name.Short_Name', 'tb_cmn_reason_master.Reason_Details', 'tb_cmn_status_master.Status_Name', 'tb_cmn_benificiary_master.Benificiary_Name', 'tb_cmn_benificiary_master.Pan_No', 'tb_cmn_scheme_master.Scheme_Name', 'tb_cmn_scheme_master.Pkid AS Scheme_Id', 'tb_cmn_state_master.State_Name'])->sortBy('Pkid');

        $slcDateTxn =  DB::table('tb_subsidy_slc_date_txn')
            ->join('tb_sms_claim_master', 'tb_sms_claim_master.Pkid', '=', 'tb_subsidy_slc_date_txn.Subsidy_Id_Fk')
            ->where('tb_sms_claim_master.Pkid', $id)
            ->where('tb_subsidy_slc_date_txn.Record_Active_Flag', 1)
            ->get(['tb_subsidy_slc_date_txn.*']);
        $html_view1 = view("subsidy-ui.view-edit-subsidy-claim", compact('smsClaimMasterUpdate', 'remarks', 'scheme', 'benificiary', 'MODE', 'slcDateTxn'))->render();
        if ($status == "success") {
            return response()->json(["status" => $status, "message" => "Success! Subsidy Claim updated.", "data" => $smsClaimMaster, "body" => $html_view, "body1" => $html_view1]);
        } else {
            return response()->json(["status" => $status, "message" => "Alert! Subsidy Claim not updated"]);
        }
    }

    // save approve subsidy claim

    public function approveSubsidyClaimEntry(Request $request)
    {
        DB::beginTransaction();
        try {
            $dataUI = json_decode($request->getContent());
            $approvalStatus = $dataUI->decision;
            $approvalDate = $dataUI->approval_date;
            $remarks = $dataUI->remarks;
            if (Is_Array($dataUI->check_id)) {
                foreach ($dataUI->check_id as $value) {
                    TbSmsClaimMaster::where('Pkid', $value)->update(
                        [
                            'Status_Id' => $approvalStatus, 'Record_Update_Date' => new \DateTime(), 'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'),
                            'Updated_By' => $request->session()->get('id')
                        ]
                    );
                    $saveCmnApproval = new TbCmnApproval();
                    $saveCmnApproval->Approval_Date = $approvalDate;
                    $saveCmnApproval->Status_Id_Fk = $approvalStatus;
                    $saveCmnApproval->Remarks = $remarks;
                    $saveCmnApproval->Module = 'SubsidyClaim';
                    $saveCmnApproval->Record_Id_Fk = $value;
                    $saveCmnApproval->save();
                }
            } else {
                TbSmsClaimMaster::where('Pkid', $dataUI->check_id)->update(
                    [
                        'Status_Id' => $approvalStatus, 'Record_Update_Date' => new \DateTime(), 'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'),
                        'Updated_By' => $request->session()->get('id')
                    ]
                );
                $saveCmnApproval = new TbCmnApproval();
                $saveCmnApproval->Approval_Date = $approvalDate;
                $saveCmnApproval->Status_Id_Fk = $approvalStatus;
                $saveCmnApproval->Remarks = $remarks;
                $saveCmnApproval->Module = 'SubsidyClaim';
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
        $smsClaimMaster = DB::table('tb_sms_claim_master')
            ->join('tb_cmn_status_master', 'tb_sms_claim_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->join('tb_cmn_benificiary_master', 'tb_sms_claim_master.Benificiary_Id_Fk', '=', 'tb_cmn_benificiary_master.Pkid')
            ->join('tb_cmn_scheme_master', 'tb_sms_claim_master.Scheme_Id_Fk', '=', 'tb_cmn_scheme_master.Pkid')
            ->join('tb_cmn_scheme_short_name', 'tb_cmn_scheme_master.Scheme_Short_Name_Id_Fk', '=', 'tb_cmn_scheme_short_name.Pkid')
            ->join('tb_cmn_address', 'tb_cmn_benificiary_master.Address_Id_Fk', '=', 'tb_cmn_address.Pkid')
            ->join('tb_cmn_state_master', 'tb_cmn_address.State_Code', '=', 'tb_cmn_state_master.Pkid')
            ->whereIn('tb_cmn_status_master.Pkid', [2])
            ->where('tb_sms_claim_master.Record_Active_Flag', 1)
            ->get('tb_sms_claim_master.*', 'tb_cmn_scheme_short_name.Short_Name',  'tb_cmn_status_master.Status_Name', 'tb_cmn_benificiary_master.Benificiary_Name', 'tb_cmn_benificiary_master.Pan_No', 'tb_cmn_benificiary_master.Benificiary_Name', 'tb_cmn_scheme_master.Scheme_Name', 'tb_cmn_state_master.State_Name')->sortBy('Pkid');
        $html_view = view('subsidy-ui.after-subsidy-claim-approval-list', compact('smsClaimMaster'))->render();
        if ($status == 'success') {
            return response()->json(["status" => "success", "message" => "Success! Subsidy Claim " . $decision, "body" => $html_view]);
        } else {
            return response()->json(["status" => "success", "message" => "Alert!  Subsidy Claim not " . $decision]);
        }
    }
}
