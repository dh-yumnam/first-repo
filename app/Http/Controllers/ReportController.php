<?php

namespace App\Http\Controllers;

use App\Models\ReportModel;
use Illuminate\Http\Request;
use App\Models\TbCmnFinishGoodsMaster;
use App\Models\TbCmnFundMaster;
use App\Models\TbCmnNedfiBankMaster;
use App\Models\TbCmnReasonMaster;
use App\Models\TbCmnUnitMaster;
use App\Models\TbCmnRawMaterialMaster;
use App\Models\TbCmnProductMaster;
use App\Models\TbCmnSchemeMaster;
use App\Models\TbCmnStateMaster;
use App\Models\TbCmnStatusMaster;
use App\Models\TbCmnSectorMaster;
use DateTime;
use App\Models\TbCmnApproval;
use App\Models\TbSmsDisbursementDetail;
use Hamcrest\Arrays\IsArray;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function claimReport()
    {
        $TbCmnSchemeMaster = TbCmnSchemeMaster::where('Record_Active_Flag', 1)->where('Status_Id', 5)
            ->get()->sortBy('Scheme_Name');
        $TbCmnStateMaster = TbCmnStateMaster::where('Record_Active_Flag', 1)->get()->sortBy('State_Name');

        $html_view = view('report-ui.claim-report', compact('TbCmnSchemeMaster', 'TbCmnStateMaster'))->render();
        return response()->json(["status" => "success", "body" => $html_view]);
    }

    public function claimReportResult(Request $request)
    {
        $scheme_name = null;
        $dataUI = json_decode($request->getContent());
        $query = DB::table('tb_sms_claim_master')
            ->join('tb_cmn_benificiary_master', 'tb_sms_claim_master.Benificiary_Id_Fk', '=', 'tb_cmn_benificiary_master.Pkid')
            ->join('tb_cmn_address', 'tb_cmn_benificiary_master.Address_Id_Fk', '=', 'tb_cmn_address.Pkid')
            ->join('tb_cmn_state_master', 'tb_cmn_address.State_Code', '=', 'tb_cmn_state_master.Pkid')
            ->join('tb_cmn_scheme_master', 'tb_sms_claim_master.Scheme_Id_Fk', '=', 'tb_cmn_scheme_master.Pkid')
            ->leftjoin('tb_subsidy_slc_date_txn', 'tb_subsidy_slc_date_txn.Subsidy_Id_fk', '=', 'tb_sms_claim_master.Pkid')
            ->join('tb_cmn_status_master', 'tb_sms_claim_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->leftjoin('tb_cmn_policy_master', 'tb_cmn_benificiary_master.Gov_Policy_Id', '=', 'tb_cmn_policy_master.Pkid')
            ->where('tb_sms_claim_master.Status_Id', 5)
            ->where('tb_subsidy_slc_date_txn.Record_Active_Flag', 1)
            ->where('tb_sms_claim_master.Record_Active_Flag', 1);

        try {
            if (!empty($dataUI->benificiary_name)) {
                $query->where('tb_cmn_benificiary_master.Benificiary_Name', 'LIKE', "%{$dataUI->benificiary_name}%");
            }
            if (!empty($dataUI->from_date) && !empty($dataUI->to_date)) {
                // if ($dataUI->scheme_id == '40')  //Investment
                // {
                //     $query->whereBetween('tb_sms_claim_master.Claim_From_Date', [$dataUI->from_date, $dataUI->to_date]);
                // } else {
                $query->whereBetween('tb_subsidy_slc_date_txn.Slc_Date', [$dataUI->from_date, $dataUI->to_date]);
                // }
            }
            if (!empty($dataUI->state_id)) {
                $query->where('tb_cmn_address.State_Code', $dataUI->state_id);
            }
            if (!empty($dataUI->district_id)) {
                $query->where('tb_cmn_address.District_Id', $dataUI->district_id);
            }
            if (!empty($dataUI->scheme_id)) {
                $query->where('tb_cmn_scheme_master.Pkid', $dataUI->scheme_id);
                $scheme = TbCmnSchemeMaster::where('Pkid', $dataUI->scheme_id)->firstOrFail();
                $scheme_name = $scheme->Scheme_Name;
            }
            if (!empty($dataUI->policy_id)) {
                $query->where('tb_cmn_benificiary_master.Gov_Policy_Id', $dataUI->policy_id);
            }

            $current = Carbon::now();
            $from = $dataUI->from_date;
            $to = $dataUI->to_date;
            $amoutIn = $dataUI->amount_id;
            $smsClaimMaster = $query
                ->select(DB::raw('tb_sms_claim_master.*, (tb_sms_claim_master.Claim_Amount/' . $amoutIn . ') as Claim_Amt,(tb_sms_claim_master.Investment_On_Plant_Machinery/' . $amoutIn . ') as PM,tb_cmn_policy_master.Policy_Name,tb_subsidy_slc_date_txn.Slc_Date, tb_cmn_address.Address1, tb_cmn_address.Address2, tb_cmn_status_master.Status_Name, tb_cmn_benificiary_master.Benificiary_Name, tb_cmn_benificiary_master.Pan_No, tb_cmn_benificiary_master.Benificiary_Name, tb_cmn_scheme_master.Scheme_Name, tb_cmn_state_master.State_Name'))
                ->get()->sortBy('State_Name');
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
        $html = null;
        if ($dataUI->scheme_id == '40')  //Investment
        {
            $html = view('report-ui.investment-claim-report-result', compact('smsClaimMaster', 'current', 'from', 'to', 'scheme_name'))->render();
        } else {
            $html = view('report-ui.claim-report-result', compact('smsClaimMaster', 'current', 'from', 'to', 'scheme_name'))->render();
        }
        return response()->json(['status' => "success", 'body' => $html]);
    }

    public function pendingClaimReport()
    {
        $TbCmnSchemeMaster = TbCmnSchemeMaster::where('Record_Active_Flag', 1)->where('Status_Id', 5)
            ->get()->sortBy('Scheme_Name');
        $TbCmnStateMaster = TbCmnStateMaster::where('Record_Active_Flag', 1)->get()->sortBy('State_Name');

        $html_view = view('report-ui.pending-claim-report', compact('TbCmnSchemeMaster', 'TbCmnStateMaster'))->render();
        return response()->json(["status" => "success", "body" => $html_view]);
    }

    public function pendingClaimReportResult(Request $request)
    {
        $scheme_name = null;
        $dataUI = json_decode($request->getContent());
        $query = DB::table('tb_sms_claim_master')
            ->join('tb_cmn_benificiary_master', 'tb_sms_claim_master.Benificiary_Id_Fk', '=', 'tb_cmn_benificiary_master.Pkid')
            ->join('tb_cmn_address', 'tb_cmn_benificiary_master.Address_Id_Fk', '=', 'tb_cmn_address.Pkid')
            ->join('tb_cmn_state_master', 'tb_cmn_address.State_Code', '=', 'tb_cmn_state_master.Pkid')
            ->join('tb_cmn_scheme_master', 'tb_sms_claim_master.Scheme_Id_Fk', '=', 'tb_cmn_scheme_master.Pkid')
            ->join('tb_subsidy_slc_date_txn', 'tb_subsidy_slc_date_txn.Subsidy_Id_fk', '=', 'tb_sms_claim_master.Pkid')
            ->join('tb_cmn_status_master', 'tb_sms_claim_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->join('tb_cmn_fund_allocation_txn', 'tb_sms_claim_master.Pkid', '=', 'tb_cmn_fund_allocation_txn.Claim_Id_Fk')            
            ->join(DB::raw('(SELECT sum(`tb_cmn_fund_allocation_txn`.`Paid_Amount`) TotalPaid,`tb_cmn_fund_allocation_txn`.`Claim_Id_Fk` FROM `tb_cmn_fund_allocation_txn` GROUP BY `tb_cmn_fund_allocation_txn`.`Claim_Id_Fk`)
               TotalClaim'), 
            function($join)
            {
            $join->on('tb_sms_claim_master.Pkid', '=', 'TotalClaim.Claim_Id_Fk');
            })

            ->leftjoin('tb_cmn_policy_master', 'tb_cmn_benificiary_master.Gov_Policy_Id', '=', 'tb_cmn_policy_master.Pkid')
            ->where('tb_sms_claim_master.Status_Id', 5)
            ->where('tb_subsidy_slc_date_txn.Record_Active_Flag', 1)
            ->whereColumn('tb_cmn_fund_allocation_txn.Claimed_Amount', '>', 'tb_cmn_fund_allocation_txn.Allocated_Amount')
           // ->where('(tb_sms_claim_master.Claim_Amount'-'TotalClaim.TotalPaid)','>=',0)
            ->where('tb_sms_claim_master.Record_Active_Flag', 1)  
            ->whereColumn('tb_sms_claim_master.Claim_Amount','>','TotalClaim.TotalPaid')         
            ->groupBy('tb_sms_claim_master.Received_Date','tb_sms_claim_master.Claim_From_Date','tb_sms_claim_master.Claim_To_Date','tb_sms_claim_master.Investment_On_Plant_Machinery','tb_sms_claim_master.Claim_Amount','tb_cmn_policy_master.Policy_Name','tb_subsidy_slc_date_txn.Slc_Date', 'tb_cmn_address.Address1', 'tb_cmn_address.Address2', 'tb_cmn_status_master.Status_Name', 'tb_cmn_benificiary_master.Benificiary_Name',
            'tb_cmn_benificiary_master.Pan_No','tb_cmn_scheme_master.Scheme_Name', 'tb_cmn_state_master.State_Name');

        try {
            if (!empty($dataUI->benificiary_name)) {
                $query->where('tb_cmn_benificiary_master.Benificiary_Name', 'LIKE', "%{$dataUI->benificiary_name}%");
            }
            if (!empty($dataUI->from_date) && !empty($dataUI->to_date)) {
                // if ($dataUI->scheme_id == '40')  //Investment
                // {
                //     $query->whereBetween('tb_sms_claim_master.Claim_From_Date', [$dataUI->from_date, $dataUI->to_date]);
                // } else {
                $query->whereBetween('tb_subsidy_slc_date_txn.Slc_Date', [$dataUI->from_date, $dataUI->to_date]);
                // }
            }
            if (!empty($dataUI->state_id)) {
                $query->where('tb_cmn_address.State_Code', $dataUI->state_id);
            }
            if (!empty($dataUI->district_id)) {
                $query->where('tb_cmn_address.District_Id', $dataUI->district_id);
            }
            if (!empty($dataUI->scheme_id)) {
                $query->where('tb_cmn_scheme_master.Pkid', $dataUI->scheme_id);
                $scheme = TbCmnSchemeMaster::where('Pkid', $dataUI->scheme_id)->firstOrFail();
                $scheme_name = $scheme->Scheme_Name;
            }
            if (!empty($dataUI->policy_id)) {
                $query->where('tb_cmn_benificiary_master.Gov_Policy_Id', $dataUI->policy_id);
            }
            $current = Carbon::now();
            $from = $dataUI->from_date;
            $to = $dataUI->to_date;
            $amoutIn = $dataUI->amount_id;
            $smsClaimMaster = $query
                ->select(DB::raw('tb_sms_claim_master.Received_Date, tb_sms_claim_master.Claim_From_Date,tb_sms_claim_master.Claim_To_Date,
                (tb_sms_claim_master.Investment_On_Plant_Machinery/' . $amoutIn . ') as PM, (tb_sms_claim_master.Claim_Amount/' . $amoutIn . ') as Claim_Amt,
                sum(tb_cmn_fund_allocation_txn.Allocated_Amount/' . $amoutIn . ') as Allocated_Amt,sum(tb_cmn_fund_allocation_txn.Paid_Amount/' . $amoutIn . ') as Paid_Amt,(tb_sms_claim_master.Claim_Amount-sum(tb_cmn_fund_allocation_txn.Paid_Amount))/' . $amoutIn . ' Balance,
                tb_cmn_policy_master.Policy_Name,tb_subsidy_slc_date_txn.Slc_Date, tb_cmn_address.Address1, tb_cmn_address.Address2, tb_cmn_status_master.Status_Name, tb_cmn_benificiary_master.Benificiary_Name,
                tb_cmn_benificiary_master.Pan_No,tb_cmn_scheme_master.Scheme_Name, tb_cmn_state_master.State_Name'))
                ->get()->sortBy('State_Name'); 
            
                // $smsClaimMaster = $query->get(['tb_sms_claim_master.*','tb_cmn_fund_allocation_txn.Claimed_Amount', 'tb_cmn_fund_allocation_txn.Allocated_Amount','tb_cmn_fund_allocation_txn.Paid_Amount','tb_cmn_policy_master.Policy_Name', 'tb_subsidy_slc_date_txn.Slc_Date', 'tb_cmn_address.Address1', 'tb_cmn_address.Address2', 'tb_cmn_status_master.Status_Name', 'tb_cmn_benificiary_master.Benificiary_Name', 'tb_cmn_benificiary_master.Pan_No', 'tb_cmn_benificiary_master.Benificiary_Name', 'tb_cmn_scheme_master.Scheme_Name', 'tb_cmn_state_master.State_Name'])->sortBy('State_Name');
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
        $html = null;
        if ($dataUI->scheme_id == '40')  //Investment
        {
            $html = view('report-ui.pending-investment-claim-report-result', compact('smsClaimMaster', 'current', 'from', 'to', 'scheme_name'))->render();
        } else {
            $html = view('report-ui.pending-claim-report-result', compact('smsClaimMaster', 'current', 'from', 'to', 'scheme_name'))->render();
        }
        return response()->json(['status' => "success", 'body' => $html]);
    }
    public function fundReceiptReport()
    {
        $statusMaster      =       TbCmnStatusMaster::all()->sortByDesc('Pkid');
        $TbCmnSchemeMaster = TbCmnSchemeMaster::where('Record_Active_Flag', 1)->where('Status_Id', 5)
            ->get()->sortBy('Scheme_Name');
        $TbCmnStateMaster = TbCmnStateMaster::where('Record_Active_Flag', 1)->get()->sortBy('State_Name');
        $html_view = view('report-ui.fund-receipt-report', compact('TbCmnSchemeMaster', 'TbCmnStateMaster', 'statusMaster'))->render();
        return response()->json(["status" => "success", "body" => $html_view]);
    }
    public function fundReportResult(Request $request)
    {
        $dataUI = json_decode($request->getContent());
        try {

            $query      =       TbCmnFundMaster::join('tb_cmn_status_master', 'tb_cmn_fund_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
                ->join('tb_cmn_scheme_master', 'tb_cmn_fund_master.Scheme_Id', '=', 'tb_cmn_scheme_master.Pkid');

            if (!empty($dataUI->sanction_letter)) {
                $query->where('tb_cmn_fund_master.Sanction_Letter', 'LIKE', "%{$dataUI->sanction_letter}%");
            }
            if (!empty($dataUI->registered_From_Date) && !empty($dataUI->registered_To_Date)) {
                $query->whereBetween('tb_cmn_fund_master.Sanction_Date', [$dataUI->registered_From_Date, $dataUI->registered_To_Date]);
            }
            if (!empty($dataUI->scheme_id)) {
                $query->where('tb_cmn_scheme_master.Pkid', $dataUI->scheme_id);
            }
            if (!empty($dataUI->status_id)) {
                $query->where('tb_cmn_status_master.Pkid', $dataUI->status_id);
            }
            $current = Carbon::now();
            $from = $dataUI->registered_From_Date;
            $to = $dataUI->registered_To_Date;
            $amountIn = $dataUI->amount_id;
            $subsidyFund = $query->select(DB::raw('tb_cmn_fund_master.*, (tb_cmn_fund_master.Sanction_Amount/' . $amountIn . ') as Sanction_Amt,(tb_cmn_fund_master.Fund_Balance/' . $amountIn . ') as Fund_Bal, tb_cmn_status_master.Status_Name, tb_cmn_status_master.Pkid AS Status_Id, tb_cmn_scheme_master.Scheme_Name'))
                ->get()->sortBy('Pkid');
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }

        $html = view('report-ui.fund-report-result', compact('subsidyFund', 'current', 'from', 'to'))->render();
        return response()->json(['status' => "success", 'body' => $html]);
    }

    public function disbursementReport()
    {
        $TbCmnSchemeMaster = TbCmnSchemeMaster::where('Record_Active_Flag', 1)->where('Status_Id', 5)
            ->get()->sortBy('Scheme_Name');
        $TbCmnStateMaster = TbCmnStateMaster::where('Record_Active_Flag', 1)->get()->sortBy('State_Name');

        $html_view = view('report-ui.disbursement-report', compact('TbCmnSchemeMaster', 'TbCmnStateMaster'))->render();
        return response()->json(["status" => "success", "body" => $html_view]);
        // $form = view('benificiary-ui.approve-benificiary');
        // return Response::json($form);
    }

    public function disbursementReportResult(Request $request)
    {
        $scheme_name = null;
        $dataUI = json_decode($request->getContent());
        $query = DB::table('tb_sms_disbursement_details')
            ->join('tb_cmn_status_master', 'tb_sms_disbursement_details.Status_Id_Fk', '=', 'tb_cmn_status_master.Pkid')
            ->join('tb_sms_claim_master', 'tb_sms_claim_master.Pkid', '=', 'tb_sms_disbursement_details.Claim_Id_Fk')
            ->join('tb_cmn_fund_allocation_master', 'tb_cmn_fund_allocation_master.Pkid', '=', 'tb_sms_disbursement_details.Allocation_Id_Fk')
            ->join('tb_cmn_benificiary_master', 'tb_sms_claim_master.Benificiary_Id_Fk', '=', 'tb_cmn_benificiary_master.Pkid')
            ->join('tb_cmn_scheme_master', 'tb_sms_claim_master.Scheme_Id_Fk', '=', 'tb_cmn_scheme_master.Pkid')
            ->join('tb_cmn_address', 'tb_cmn_benificiary_master.Address_Id_Fk', '=', 'tb_cmn_address.Pkid')
            ->join('tb_cmn_state_master', 'tb_cmn_address.State_Code', '=', 'tb_cmn_state_master.Pkid')
            ->leftjoin('tb_subsidy_slc_date_txn', 'tb_subsidy_slc_date_txn.Subsidy_Id_fk', '=', 'tb_sms_claim_master.Pkid')
            ->leftjoin('tb_cmn_finish_goods_master', 'tb_cmn_benificiary_master.Finish_Goods_Id_Fk', '=', 'tb_cmn_finish_goods_master.Pkid')
            ->leftjoin('tb_cmn_policy_master', 'tb_cmn_benificiary_master.Gov_Policy_Id', '=', 'tb_cmn_policy_master.Pkid')
            ->leftjoin('tb_cmn_raw_material_master', 'tb_cmn_benificiary_master.Raw_Materials_Id_Fk', '=', 'tb_cmn_raw_material_master.Pkid')
            ->whereIn('tb_sms_disbursement_details.Status_Id_Fk', [5])
            ->whereIn('tb_cmn_fund_allocation_master.Status_Id_Fk', [5])
            ->where('tb_subsidy_slc_date_txn.Record_Active_Flag', 1)
            ->where('tb_sms_disbursement_details.Record_Active_Flag', 1);
        try {
            if (!empty($dataUI->benificiary_name)) {
                $query->where('tb_cmn_benificiary_master.Benificiary_Name', 'LIKE', "%{$dataUI->benificiary_name}%");
            }
            if (!empty($dataUI->from_date) && !empty($dataUI->to_date)) {
                $query->whereBetween('tb_subsidy_slc_date_txn.Slc_Date', [$dataUI->from_date, $dataUI->to_date]);
            }
            if (!empty($dataUI->state_id)) {
                $query->where('tb_cmn_address.State_Code', $dataUI->state_id);
            }
            if (!empty($dataUI->district_id)) {
                $query->where('tb_cmn_address.District_Id', $dataUI->district_id);
            }
            if (!empty($dataUI->scheme_id)) {
                $query->where('tb_cmn_scheme_master.Pkid', $dataUI->scheme_id);
                $scheme = TbCmnSchemeMaster::where('Pkid', $dataUI->scheme_id)->firstOrFail();
                $scheme_name = $scheme->Scheme_Name;
            }
            if (!empty($dataUI->policy_id)) {
                $query->where('tb_cmn_benificiary_master.Gov_Policy_Id', $dataUI->policy_id);
            }
            $current = Carbon::now();
            $from = $dataUI->from_date;
            $to = $dataUI->to_date;
            $amountIn = $dataUI->amount_id;
            $smsClaimMaster = $query
                ->select(DB::raw('tb_sms_claim_master.*,(tb_sms_disbursement_details.Disbursement_Amount/' . $amountIn . ') as Disbursement_Amt, tb_cmn_benificiary_master.Production_Date, tb_cmn_status_master.Status_Name, 
            tb_subsidy_slc_date_txn.Slc_Date, tb_cmn_address.Address1, tb_cmn_address.Address2, tb_cmn_benificiary_master.Benificiary_Name, tb_cmn_benificiary_master.Pan_No, tb_cmn_scheme_master.Scheme_Name, tb_cmn_state_master.State_Name, tb_sms_disbursement_details.Instrument_No, tb_sms_disbursement_details.Instrument_Date, tb_cmn_finish_goods_master.Goods_Name, tb_cmn_policy_master.Policy_Name, tb_cmn_raw_material_master.Material_Name'))
                ->get()->sortBy('State_Name');
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
        $html = null;
        if ($dataUI->scheme_id == '40')  //Investment
        {
            $html = view('report-ui.investment-disbursement-report-result', compact('smsClaimMaster', 'current', 'from', 'to', 'scheme_name'))->render();
        } else {
            $html = view('report-ui.disbursement-report-result', compact('smsClaimMaster', 'current', 'from', 'to', 'scheme_name'))->render();
        }
        return response()->json(['status' => "success", 'body' => $html]);
    }
    public function compositReport()
    {
        $TbCmnSchemeMaster = TbCmnSchemeMaster::where('Record_Active_Flag', 1)->where('Status_Id', 5)
            ->get()->sortBy('Scheme_Name');
        $TbCmnStateMaster = TbCmnStateMaster::where('Record_Active_Flag', 1)->get()->sortBy('State_Name');
        $html_view = view('report-ui.composit-report', compact('TbCmnSchemeMaster', 'TbCmnStateMaster'))->render();
        return response()->json(["status" => "success", "body" => $html_view]);
        // $form = view('benificiary-ui.approve-benificiary');
        // return Response::json($form);
    }
    public function compositReportResult(Request $request)
    {
        $scheme_name = null;
        $dataUI = json_decode($request->getContent());
        $query = DB::table('tb_sms_disbursement_details')
            ->join('tb_cmn_status_master', 'tb_sms_disbursement_details.Status_Id_Fk', '=', 'tb_cmn_status_master.Pkid')
            ->join('tb_sms_claim_master', 'tb_sms_claim_master.Pkid', '=', 'tb_sms_disbursement_details.Claim_Id_Fk')
            ->join('tb_cmn_fund_allocation_master', 'tb_cmn_fund_allocation_master.Pkid', '=', 'tb_sms_disbursement_details.Allocation_Id_Fk')
            ->join('tb_cmn_benificiary_master', 'tb_sms_claim_master.Benificiary_Id_Fk', '=', 'tb_cmn_benificiary_master.Pkid')
            ->join('tb_cmn_scheme_master', 'tb_sms_claim_master.Scheme_Id_Fk', '=', 'tb_cmn_scheme_master.Pkid')
            ->join('tb_cmn_address', 'tb_cmn_benificiary_master.Address_Id_Fk', '=', 'tb_cmn_address.Pkid')
            ->join('tb_cmn_state_master', 'tb_cmn_address.State_Code', '=', 'tb_cmn_state_master.Pkid')
            ->leftjoin('tb_subsidy_slc_date_txn', 'tb_subsidy_slc_date_txn.Subsidy_Id_fk', '=', 'tb_sms_claim_master.Pkid')
            ->leftjoin('tb_cmn_finish_goods_master', 'tb_cmn_benificiary_master.Finish_Goods_Id_Fk', '=', 'tb_cmn_finish_goods_master.Pkid')
            ->leftjoin('tb_cmn_policy_master', 'tb_cmn_benificiary_master.Gov_Policy_Id', '=', 'tb_cmn_policy_master.Pkid')
            ->leftjoin('tb_cmn_raw_material_master', 'tb_cmn_benificiary_master.Raw_Materials_Id_Fk', '=', 'tb_cmn_raw_material_master.Pkid')
            ->where('tb_sms_disbursement_details.Status_Id_Fk', 5)
            ->where('tb_subsidy_slc_date_txn.Record_Active_Flag', 1)
            ->whereIn('tb_cmn_fund_allocation_master.Status_Id_Fk', [5])
            ->where('tb_sms_disbursement_details.Record_Active_Flag', 1);
        try {
            if (!empty($dataUI->benificiary_name)) {
                $query->where('tb_cmn_benificiary_master.Benificiary_Name', 'LIKE', "%{$dataUI->benificiary_name}%");
            }
            if (!empty($dataUI->from_date) && !empty($dataUI->to_date)) {
                $query->whereBetween('tb_subsidy_slc_date_txn.Slc_Date', [$dataUI->from_date, $dataUI->to_date]);
            }
            // if (!empty($dataUI->from_date) && !empty($dataUI->to_date)) {
            //     $query->whereBetween('tb_sms_disbursement_details.Disbursement_Date', [$dataUI->from_date, $dataUI->to_date]);
            // }
            if (!empty($dataUI->state_id)) {
                $query->where('tb_cmn_address.State_Code', $dataUI->state_id);
            }
            if (!empty($dataUI->scheme_id)) {
                $query->where('tb_cmn_scheme_master.Pkid', $dataUI->scheme_id);
                $scheme = TbCmnSchemeMaster::where('Pkid', $dataUI->scheme_id)->firstOrFail();
                $scheme_name = $scheme->Scheme_Name;
            }
            $current = Carbon::now();
            $from = $dataUI->from_date;
            $to = $dataUI->to_date;
            $amountIn = $dataUI->amount_id;
            $smsCompositReport = $query
                ->select(DB::raw('tb_sms_claim_master.*,tb_cmn_benificiary_master.Subsidy_Regn_No,tb_cmn_benificiary_master.Unit_Status,tb_cmn_benificiary_master.Production_Capacity,(tb_sms_disbursement_details.Disbursement_Amount/' . $amountIn . ') as Disbursement_Amt, tb_cmn_benificiary_master.Production_Date, tb_cmn_status_master.Status_Name, 
            tb_subsidy_slc_date_txn.Slc_Date, tb_cmn_address.Address1, tb_cmn_address.Address2, tb_cmn_benificiary_master.Benificiary_Name, tb_cmn_benificiary_master.Pan_No, tb_cmn_scheme_master.Scheme_Name, tb_cmn_state_master.State_Name, tb_sms_disbursement_details.Instrument_No, tb_sms_disbursement_details.Instrument_Date, tb_cmn_finish_goods_master.Goods_Name, tb_cmn_policy_master.Policy_Name, tb_cmn_raw_material_master.Material_Name'))
            ->get()->sortBy('State_Name');
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
        $html = view('report-ui.composit-report-result', compact('smsCompositReport', 'current', 'from', 'to', 'scheme_name'))->render();
        return response()->json(['status' => "success", 'body' => $html]);
    }
    public function sectorWiseReport()
    {
        $sectorMaster      =        TbCmnSectorMaster::all()
            ->where('Record_Active_Flag', '1')
            ->where('Custom1', 'A')
            ->sortBy('Sector_Name');
        $TbCmnSchemeMaster = TbCmnSchemeMaster::where('Record_Active_Flag', 1)->where('Status_Id', 5)
            ->get()->sortBy('Scheme_Name');
        $TbCmnStateMaster = TbCmnStateMaster::where('Record_Active_Flag', 1)->get()->sortBy('State_Name');
        $html_view = view('report-ui.sector-wise-report', compact('TbCmnSchemeMaster', 'TbCmnStateMaster', 'sectorMaster'))->render();
        return response()->json(["status" => "success", "body" => $html_view]);
        // $form = view('benificiary-ui.approve-benificiary');
        // return Response::json($form);
    }

    public function sectorWiseReportResult(Request $request)
    {
        $scheme_name = null;
        $dataUI = json_decode($request->getContent());
        $query = DB::table('tb_sms_disbursement_details')
            ->join('tb_cmn_status_master', 'tb_sms_disbursement_details.Status_Id_Fk', '=', 'tb_cmn_status_master.Pkid')
            ->join('tb_sms_claim_master', 'tb_sms_claim_master.Pkid', '=', 'tb_sms_disbursement_details.Claim_Id_Fk')
            ->join('tb_cmn_fund_allocation_master', 'tb_cmn_fund_allocation_master.Pkid', '=', 'tb_sms_disbursement_details.Allocation_Id_Fk')
            ->join('tb_cmn_benificiary_master', 'tb_sms_claim_master.Benificiary_Id_Fk', '=', 'tb_cmn_benificiary_master.Pkid')
            ->join('tb_cmn_scheme_master', 'tb_sms_claim_master.Scheme_Id_Fk', '=', 'tb_cmn_scheme_master.Pkid')
            ->join('tb_cmn_address', 'tb_cmn_benificiary_master.Address_Id_Fk', '=', 'tb_cmn_address.Pkid')
            ->join('tb_cmn_sector_master', 'tb_cmn_benificiary_master.Sector_Id_Fk', '=', 'tb_cmn_sector_master.Pkid')
            ->join('tb_cmn_state_master', 'tb_cmn_address.State_Code', '=', 'tb_cmn_state_master.Pkid')
            ->leftjoin('tb_subsidy_slc_date_txn', 'tb_subsidy_slc_date_txn.Subsidy_Id_fk', '=', 'tb_sms_claim_master.Pkid')
            ->where('tb_sms_disbursement_details.Status_Id_Fk', 5)
            ->where('tb_subsidy_slc_date_txn.Record_Active_Flag', 1)
            ->whereIn('tb_cmn_fund_allocation_master.Status_Id_Fk', [5])
            ->where('tb_sms_disbursement_details.Record_Active_Flag', 1);
        try {
            if (!empty($dataUI->sector_id)) {
                $query->where('tb_cmn_sector_master.Pkid', 'LIKE', "%{$dataUI->sector_id}%");
            }
            if (!empty($dataUI->from_date) && !empty($dataUI->to_date)) {
                $query->whereBetween('tb_subsidy_slc_date_txn.Slc_Date', [$dataUI->from_date, $dataUI->to_date]);
            }
            if (!empty($dataUI->state_id)) {
                $query->where('tb_cmn_address.State_Code', $dataUI->state_id);
            }
            if (!empty($dataUI->scheme_id)) {
                $query->where('tb_cmn_scheme_master.Pkid', $dataUI->scheme_id);
                $scheme = TbCmnSchemeMaster::where('Pkid', $dataUI->scheme_id)->firstOrFail();
                $scheme_name = $scheme->Scheme_Name;
            }
            $current = Carbon::now();
            $from = $dataUI->from_date;
            $to = $dataUI->to_date;
            $amountIn = $dataUI->amount_id;
            $smsSectorWiseReport = $query
                ->select(DB::raw('count(*) claim_no,tb_cmn_sector_master.Sector_Name,sum(tb_sms_claim_master.Claim_Amount/' . $amountIn . ') as Claim_Amount,sum(tb_sms_disbursement_details.Disbursement_Amount/' . $amountIn . ') as Disbursement_Amt, 
                tb_cmn_state_master.State_Name'))->groupBy('tb_cmn_sector_master.Sector_Name')
                ->groupBy('tb_cmn_state_master.State_Name')
                ->get()->sortBy('State_Name');
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
        $html = view('report-ui.sector-wise-report-result', compact('smsSectorWiseReport', 'current', 'from', 'to', 'scheme_name'))->render();
        return response()->json(['status' => "success", 'body' => $html]);
    }

    public function bankLedgerReport()
    {
        $TbCmnSchemeMaster = TbCmnSchemeMaster::where('Record_Active_Flag', 1)->where('Status_Id', 5)
            ->get()->sortBy('Scheme_Name');
        $TbCmnStateMaster = TbCmnStateMaster::where('Record_Active_Flag', 1)->get()->sortBy('State_Name');
        $html_view = view('report-ui.bank-ledger-report', compact('TbCmnSchemeMaster', 'TbCmnStateMaster'))->render();
        return response()->json(["status" => "success", "body" => $html_view]);
        // $form = view('benificiary-ui.approve-benificiary');
        // return Response::json($form);
    }
    public function cashBookReport()
    {
        $TbCmnSchemeMaster = TbCmnSchemeMaster::where('Record_Active_Flag', 1)->where('Status_Id', 5)
            ->get()->sortBy('Scheme_Name');
        $TbCmnStateMaster = TbCmnStateMaster::where('Record_Active_Flag', 1)->get()->sortBy('State_Name');
        $html_view = view('report-ui.cash-book-report', compact('TbCmnSchemeMaster', 'TbCmnStateMaster'))->render();
        return response()->json(["status" => "success", "body" => $html_view]);
        // $form = view('benificiary-ui.approve-benificiary');
        // return Response::json($form);
    }

    public function chequeReturnReport()
    {
        $TbCmnSchemeMaster = TbCmnSchemeMaster::where('Record_Active_Flag', 1)->where('Status_Id', 5)
            ->get()->sortBy('Scheme_Name');
        $TbCmnStateMaster = TbCmnStateMaster::where('Record_Active_Flag', 1)->get()->sortBy('State_Name');
        $html_view = view('report-ui.cheque-return-report', compact('TbCmnSchemeMaster', 'TbCmnStateMaster'))->render();
        return response()->json(["status" => "success", "body" => $html_view]);
        // $form = view('benificiary-ui.approve-benificiary');
        // return Response::json($form);
    }
}
