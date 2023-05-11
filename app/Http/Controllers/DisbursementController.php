<?php

namespace App\Http\Controllers;

use App\Models\TbCmnAddress;
use Illuminate\Http\Request;
use App\Models\TbCmnFinishGoodsMaster;
use App\Models\TbCmnFundMaster;
use App\Models\TbCmnNedfiBankMaster;
use App\Models\TbCmnBenificiaryAddressTxn;
use App\Models\TbCmnReasonMaster;
use App\Models\TbCmnRawMaterialMaster;
use App\Models\TbCmnProductMaster;
use App\Models\TbCmnBenificiaryMaster;
use App\Models\TbCmnDistrictMaster;
use App\Models\TbCmnFundAllocationMaster;
use App\Models\TbCmnFundAllocationTxn;
use App\Models\TbCmnStateMaster;
use App\Models\TbCmnPolicyMaster;
use App\Models\TbCmnSchemeMaster;
use App\Models\TbCmnStatusMaster;
use App\Models\TbSmsClaimMaster;
use App\Models\TbCmnApproval;
use App\Models\TbSmsDisbursementDetail;
use Hamcrest\Arrays\IsArray;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Stmt\Foreach_;
use Ramsey\Uuid\Type\Decimal;

class DisbursementController extends Controller
{
    public function addDisbursement()
    {
        try {
            $subsidyMaster      =       TbCmnSchemeMaster::whereIn('Status_Id', [5])
                ->where('Record_Active_Flag', '1')
                ->get(['tb_cmn_scheme_master.*'])
                ->sortBy('Scheme_Name');

            $bankMaster      =       TbCmnNedfiBankMaster::whereIn('Status_Id', [5])
                ->where('Record_Active_Flag', '1')
                ->get(['tb_cmn_nedfi_bank_master.*'])
                ->sortBy('Bank_Name');
            $stateMaster = TbCmnStateMaster::all()->sortBy('State_Name');
            $statusMaster      =       TbCmnStatusMaster::all()->sortBy('Status_Name');

            $claimListForDisbursement      =       TbCmnFundAllocationTxn::join('tb_cmn_fund_allocation_master', 'tb_cmn_fund_allocation_txn.Allocation_Master_Id_Fk', '=', 'tb_cmn_fund_allocation_master.Pkid')
                ->join('tb_cmn_scheme_master', 'tb_cmn_fund_allocation_master.Scheme_Id_Fk', '=', 'tb_cmn_scheme_master.Pkid')
                ->join('tb_cmn_fund_master', 'tb_cmn_fund_allocation_master.Fund_Id_Fk', '=', 'tb_cmn_fund_master.Pkid')
                ->join('tb_cmn_status_master', 'tb_cmn_fund_allocation_master.Status_Id_Fk', '=', 'tb_cmn_status_master.Pkid')
                ->join('tb_sms_claim_master', 'tb_cmn_fund_allocation_txn.Claim_Id_Fk', '=', 'tb_sms_claim_master.Pkid')
                ->join('tb_cmn_benificiary_master', 'tb_sms_claim_master.Benificiary_Id_Fk', '=', 'tb_cmn_benificiary_master.Pkid')
                ->whereColumn('tb_cmn_fund_allocation_txn.Allocated_Amount', '>', 'tb_cmn_fund_allocation_txn.Paid_Amount')
                ->where('tb_cmn_fund_allocation_txn.Record_Active_Flag', '1')
                ->whereIn('tb_cmn_status_master.Pkid', [5])
                ->get(['tb_cmn_fund_allocation_txn.*', 'tb_cmn_fund_allocation_txn.Pkid AS Allocation_Pk', 'tb_sms_claim_master.Pkid AS Claim_Id_Pk', 'tb_cmn_status_master.Pkid AS Status_Id', 'tb_cmn_benificiary_master.Benificiary_Name', 'tb_sms_claim_master.Claim_Amount', 'tb_sms_claim_master.Claim_Id', 'tb_cmn_status_master.Status_Name', 'tb_cmn_fund_master.Sanction_Amount',  'tb_cmn_fund_master.Sanction_Letter', 'tb_cmn_scheme_master.Scheme_Name'])
                ->sortBy('Pkid');
                $MODE = 'Search';

            $disbursementDetailList      =       TbSmsDisbursementDetail::join('tb_cmn_fund_allocation_txn', 'tb_sms_disbursement_details.Allocation_Id_Fk', '=', 'tb_cmn_fund_allocation_txn.Pkid')
                ->join('tb_cmn_status_master', 'tb_sms_disbursement_details.Status_Id_Fk', '=', 'tb_cmn_status_master.Pkid')
                ->join('tb_sms_claim_master', 'tb_sms_disbursement_details.Claim_Id_Fk', '=', 'tb_sms_claim_master.Pkid')
                ->join('tb_cmn_benificiary_master', 'tb_sms_claim_master.Benificiary_Id_Fk', '=', 'tb_cmn_benificiary_master.Pkid')
                ->where('tb_sms_disbursement_details.Record_Active_Flag', '1')
                ->where('tb_cmn_fund_allocation_txn.Record_Active_Flag', '1')
                ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
                ->get(['tb_sms_disbursement_details.*', 'tb_cmn_fund_allocation_txn.Pkid AS Allocation_Pk', 'tb_sms_claim_master.Pkid AS Claim_Id_Pk', 'tb_sms_claim_master.Claim_Id', 'tb_cmn_status_master.Pkid AS Status_Id', 'tb_cmn_benificiary_master.Benificiary_Name', 'tb_sms_claim_master.Claim_Amount', 'tb_cmn_status_master.Status_Name'])
                ->sortBy('Pkid');
            $html = view('disbursement-ui.create-disbursement', compact('disbursementDetailList', 'stateMaster', 'subsidyMaster', 'claimListForDisbursement', 'MODE'))->render();
            return response()->json(['status' => "success", 'body' => $html]);
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }
    public function approveDisbursement()
    {
        $accessBoolean = (new CommonController)->checkAccessRightToController('approveDisbursement');
        if ($accessBoolean) {
            $approvalStatusMaster = TbCmnStatusMaster::whereIn('Pkid', [3, 4, 5])->get();
            $disbursementDetailList      =       TbSmsDisbursementDetail::join('tb_cmn_fund_allocation_txn', 'tb_sms_disbursement_details.Allocation_Id_Fk', '=', 'tb_cmn_fund_allocation_txn.Pkid')
                ->join('tb_cmn_status_master', 'tb_sms_disbursement_details.Status_Id_Fk', '=', 'tb_cmn_status_master.Pkid')
                ->join('tb_sms_claim_master', 'tb_sms_disbursement_details.Claim_Id_Fk', '=', 'tb_sms_claim_master.Pkid')
                ->join('tb_cmn_benificiary_master', 'tb_sms_claim_master.Benificiary_Id_Fk', '=', 'tb_cmn_benificiary_master.Pkid')
                ->whereIn('tb_cmn_status_master.Pkid', [2])
                ->where('tb_sms_disbursement_details.Record_Active_Flag', '1')
                ->where('tb_cmn_fund_allocation_txn.Record_Active_Flag', '1')
                ->get(['tb_sms_disbursement_details.*', 'tb_cmn_fund_allocation_txn.Pkid AS Allocation_Pk', 'tb_sms_claim_master.Pkid AS Claim_Id_Pk',  'tb_sms_claim_master.Claim_Id', 'tb_cmn_status_master.Pkid AS Status_Id', 'tb_cmn_benificiary_master.Benificiary_Name', 'tb_sms_claim_master.Claim_Amount', 'tb_cmn_status_master.Status_Name'])
                ->sortBy('Pkid');
            $html = view('disbursement-ui.approve-disbursement', compact('disbursementDetailList', 'approvalStatusMaster'))->render();
            return response()->json(['status' => "success", 'body' => $html]);
        } else {
            $html_view = view('pages.error-pages.access-deny-modal')->render();
            return response()->json(["status" => "success", "body" => $html_view]);
        }
    }
    public function searchDisbursementResult(Request $request)
    {
        try {
            $dataUI = json_decode($request->getContent());
            $query   =              DB::table('tb_sms_disbursement_details')->join('tb_cmn_fund_allocation_txn', 'tb_sms_disbursement_details.Allocation_Id_Fk', '=', 'tb_cmn_fund_allocation_txn.Pkid')
                ->join('tb_cmn_status_master', 'tb_sms_disbursement_details.Status_Id_Fk', '=', 'tb_cmn_status_master.Pkid')
                ->join('tb_sms_claim_master', 'tb_sms_disbursement_details.Claim_Id_Fk', '=', 'tb_sms_claim_master.Pkid')
                ->join('tb_cmn_scheme_master', 'tb_sms_claim_master.Scheme_Id_Fk', '=', 'tb_cmn_scheme_master.Pkid')
                ->join('tb_cmn_benificiary_master', 'tb_sms_claim_master.Benificiary_Id_Fk', '=', 'tb_cmn_benificiary_master.Pkid')
                ->join('tb_cmn_address', 'tb_cmn_benificiary_master.Address_Id_Fk', '=', 'tb_cmn_address.Pkid')
                ->where('tb_sms_disbursement_details.Record_Active_Flag', '1')
                ->where('tb_cmn_fund_allocation_txn.Record_Active_Flag', '1')
                ->whereIn('tb_cmn_status_master.Pkid', [1, 2, 3, 4, 5]);
            if (!empty($dataUI->benificiary_name)) {
                $query->where('tb_cmn_benificiary_master.Benificiary_Name', 'LIKE', "%{$dataUI->benificiary_name}%");
            }
            if (!empty($dataUI->scheme)) {
                $query->where('tb_sms_claim_master.Scheme_Id_Fk', $dataUI->scheme);
            }
            if (!empty($dataUI->disbursement_from) && !empty($dataUI->disbursement_to)) {
                $query->whereBetween('tb_sms_disbursement_details.Disbursement_Date', [$dataUI->disbursement_from, $dataUI->disbursement_to]);
            }
            if (!empty($dataUI->claim_from)) {
                $query->whereBetween('tb_sms_claim_master.Claim_From_Date', [$dataUI->claim_from, $dataUI->claim_to]);                
            }
            if (!empty($dataUI->state_id)) {
                $query->where('tb_cmn_address.State_Code', $dataUI->state_id);
            }
            $MODE = 'Search';
            $disbursementDetailList = $query->get(['tb_sms_disbursement_details.*', 'tb_sms_claim_master.Claim_From_Date','tb_sms_claim_master.Claim_To_Date','tb_cmn_fund_allocation_txn.Pkid AS Allocation_Pk', 'tb_sms_claim_master.Pkid AS Claim_Id_Pk', 'tb_sms_claim_master.Claim_Id', 'tb_cmn_status_master.Pkid AS Status_Id', 'tb_cmn_benificiary_master.Benificiary_Name', 'tb_sms_claim_master.Claim_Amount', 'tb_cmn_status_master.Status_Name'])
                ->sortBy('Pkid');
            $html = view('disbursement-ui.search-disbursement-result', compact('disbursementDetailList', 'MODE'))->render();
            //return view('benificiary-ui.benificiary-search-result');
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
        return response()->json(['status' => "success", 'body' => $html]);
    }
    public function addDisbursementModal($id, $pkid)
    {
        $accessBoolean = (new CommonController)->checkAccessRightToController('addDisbursementModal');
        if ($accessBoolean) {
            try {
                $bankMaster      =       TbCmnNedfiBankMaster::whereIn('Status_Id', [5])
                    ->where('Record_Active_Flag', '1')
                    ->get(['tb_cmn_nedfi_bank_master.*'])
                    ->sortBy('Bank_Name');
                $smsClaimMaster = DB::table('tb_sms_claim_master')
                    ->join('tb_cmn_status_master', 'tb_sms_claim_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
                    ->join('tb_cmn_benificiary_master', 'tb_sms_claim_master.Benificiary_Id_Fk', '=', 'tb_cmn_benificiary_master.Pkid')
                    ->join('tb_cmn_scheme_master', 'tb_sms_claim_master.Scheme_Id_Fk', '=', 'tb_cmn_scheme_master.Pkid')
                    ->join('tb_cmn_address', 'tb_cmn_benificiary_master.Address_Id_Fk', '=', 'tb_cmn_address.Pkid')
                    ->join('tb_cmn_state_master', 'tb_cmn_address.State_Code', '=', 'tb_cmn_state_master.Pkid')
                    ->join('tb_cmn_fund_allocation_txn', 'tb_cmn_fund_allocation_txn.Claim_Id_Fk', '=', 'tb_sms_claim_master.Pkid')
                    ->join('tb_cmn_policy_master', 'tb_cmn_scheme_master.Gov_policy', '=', 'tb_cmn_policy_master.Pkid')
                    ->where('tb_cmn_fund_allocation_txn.Pkid', $pkid)
                    ->where('tb_sms_claim_master.Record_Active_Flag', '1')
                    ->where('tb_cmn_fund_allocation_txn.Record_Active_Flag', '1')
                    ->get(['tb_sms_claim_master.*', 'tb_sms_claim_master.Pkid AS Claim_Id_Pk', 'tb_cmn_policy_master.Policy_Name', 'tb_cmn_fund_allocation_txn.Pkid AS Allocation_Id', 'tb_cmn_fund_allocation_txn.Allocated_Amount', 'tb_cmn_fund_allocation_txn.Paid_Amount', 'tb_cmn_status_master.Status_Name', 'tb_cmn_benificiary_master.Benificiary_Name', 'tb_cmn_benificiary_master.Pan_No', 'tb_cmn_benificiary_master.Benificiary_Name', 'tb_cmn_scheme_master.Scheme_Name', 'tb_cmn_state_master.State_Name'])->sortBy('Pkid');

                $disbursementDetailList = [];
                $addedDisbursementList = [];
                $disbursementDetailListUpdate = [];
                if ($id != '0') {
                    $disbursementDetailList = DB::table('tb_sms_disbursement_details')
                        ->join('tb_sms_claim_master', 'tb_sms_disbursement_details.Claim_Id_Fk', '=', 'tb_sms_claim_master.Pkid')
                        ->join('tb_cmn_fund_allocation_txn', 'tb_cmn_fund_allocation_txn.Pkid', '=', 'tb_sms_disbursement_details.Allocation_Id_Fk')
                        ->join('tb_cmn_benificiary_master', 'tb_sms_claim_master.Benificiary_Id_Fk', '=', 'tb_cmn_benificiary_master.Pkid')
                        ->join('tb_cmn_status_master', 'tb_sms_disbursement_details.Status_Id_Fk', '=', 'tb_cmn_status_master.Pkid')
                        ->where('tb_sms_disbursement_details.Pkid', $id)
                        ->where('tb_cmn_fund_allocation_txn.Pkid', $pkid)
                        ->where('tb_sms_disbursement_details.Record_Active_Flag', '1')
                        ->where('tb_cmn_fund_allocation_txn.Record_Active_Flag', '1')
                        ->get(['tb_sms_disbursement_details.*', 'tb_cmn_fund_allocation_txn.Pkid AS Allocation_Pk', 'tb_sms_claim_master.Pkid AS Claim_Id_Pk',  'tb_cmn_fund_allocation_txn.Paid_Amount', 'tb_cmn_status_master.Status_Name', 'tb_cmn_benificiary_master.Benificiary_Name'])->sortBy('Pkid');

                    $disbursementDetailListUpdate      =       DB::table('tb_sms_disbursement_details')
                        ->join('tb_sms_claim_master', 'tb_sms_disbursement_details.Claim_Id_Fk', '=', 'tb_sms_claim_master.Pkid')
                        ->join('tb_cmn_fund_allocation_txn', 'tb_cmn_fund_allocation_txn.Claim_Id_Fk', '=', 'tb_sms_claim_master.Pkid')
                        ->join('tb_cmn_benificiary_master', 'tb_sms_claim_master.Benificiary_Id_Fk', '=', 'tb_cmn_benificiary_master.Pkid')
                        ->join('tb_cmn_status_master', 'tb_sms_disbursement_details.Status_Id_Fk', '=', 'tb_cmn_status_master.Pkid')
                        ->where('tb_sms_disbursement_details.Pkid', $id)
                        ->where('tb_cmn_fund_allocation_txn.Pkid', $pkid)
                        ->where('tb_sms_disbursement_details.Record_Active_Flag', '1')
                        ->where('tb_cmn_fund_allocation_txn.Record_Active_Flag', '1')
                        ->get(['tb_sms_disbursement_details.*', 'tb_cmn_fund_allocation_txn.Pkid AS Allocation_Pk', 'tb_sms_claim_master.Pkid AS Claim_Id_Pk',  'tb_cmn_fund_allocation_txn.Paid_Amount', 'tb_cmn_status_master.Status_Name', 'tb_cmn_benificiary_master.Benificiary_Name'])->sortBy('Pkid');
                    $addedDisbursementList = DB::table('tb_sms_disbursement_details')
                        ->join('tb_sms_claim_master', 'tb_sms_disbursement_details.Claim_Id_Fk', '=', 'tb_sms_claim_master.Pkid')
                        ->join('tb_cmn_fund_allocation_txn', 'tb_cmn_fund_allocation_txn.Pkid', '=', 'tb_sms_disbursement_details.Allocation_Id_Fk')
                        ->join('tb_cmn_benificiary_master', 'tb_sms_claim_master.Benificiary_Id_Fk', '=', 'tb_cmn_benificiary_master.Pkid')
                        ->join('tb_cmn_status_master', 'tb_sms_disbursement_details.Status_Id_Fk', '=', 'tb_cmn_status_master.Pkid')
                        ->where('tb_sms_disbursement_details.Pkid', $id)
                        ->where('tb_cmn_fund_allocation_txn.Pkid', $pkid)
                        ->where('tb_sms_disbursement_details.Record_Active_Flag', '1')
                        ->where('tb_cmn_fund_allocation_txn.Record_Active_Flag', '1')
                        ->get(['tb_sms_disbursement_details.*', 'tb_sms_claim_master.Pkid AS Claim_Id_Pk',  'tb_cmn_fund_allocation_txn.Paid_Amount', 'tb_cmn_status_master.Status_Name', 'tb_cmn_benificiary_master.Benificiary_Name'])->sortBy('Pkid');
                }
                $html = view('disbursement-ui.create-disbursement-modal', compact('bankMaster', 'smsClaimMaster', 'disbursementDetailList', 'addedDisbursementList'))->render();

                $html1 = view('disbursement-ui.disbursement-payment-page', compact('bankMaster', 'smsClaimMaster', 'disbursementDetailListUpdate'))->render();
            } catch (\Exception $ex) {
                return $ex->getMessage();
            }
            //return view('benificiary-ui.benificiary-search-result');
            return response()->json(['status' => "success", 'body' => $html, 'body1' => $html1]);
        } else {
            $html_view = view('pages.error-pages.access-deny-modal')->render();
            return response()->json(["status" => "success", "body" => $html_view]);
        }
    }

    public function editViewDisbursementModal($id, $pkid)
    {
        $accessBoolean = (new CommonController)->checkAccessRightToController('editViewDisbursementModal');
        if ($accessBoolean) {
            try {
                $bankMaster      =       TbCmnNedfiBankMaster::whereIn('Status_Id', [5])
                    ->where('Record_Active_Flag', '1')
                    ->get(['tb_cmn_nedfi_bank_master.*'])
                    ->sortBy('Bank_Name');
                $smsClaimMaster = DB::table('tb_sms_claim_master')
                    ->join('tb_cmn_status_master', 'tb_sms_claim_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
                    ->join('tb_cmn_benificiary_master', 'tb_sms_claim_master.Benificiary_Id_Fk', '=', 'tb_cmn_benificiary_master.Pkid')
                    ->join('tb_cmn_scheme_master', 'tb_sms_claim_master.Scheme_Id_Fk', '=', 'tb_cmn_scheme_master.Pkid')
                    ->join('tb_cmn_address', 'tb_cmn_benificiary_master.Address_Id_Fk', '=', 'tb_cmn_address.Pkid')
                    ->join('tb_cmn_state_master', 'tb_cmn_address.State_Code', '=', 'tb_cmn_state_master.Pkid')
                    ->join('tb_cmn_fund_allocation_txn', 'tb_cmn_fund_allocation_txn.Claim_Id_Fk', '=', 'tb_sms_claim_master.Pkid')
                    ->join('tb_cmn_policy_master', 'tb_cmn_scheme_master.Gov_policy', '=', 'tb_cmn_policy_master.Pkid')
                    ->where('tb_cmn_fund_allocation_txn.Pkid', $pkid)
                    ->where('tb_sms_claim_master.Record_Active_Flag', '1')
                    ->where('tb_cmn_fund_allocation_txn.Record_Active_Flag', '1')
                    ->get(['tb_sms_claim_master.*', 'tb_sms_claim_master.Pkid AS Claim_Id_Pk', 'tb_cmn_policy_master.Policy_Name', 'tb_cmn_fund_allocation_txn.Pkid AS Allocation_Id', 'tb_cmn_fund_allocation_txn.Allocated_Amount', 'tb_cmn_fund_allocation_txn.Paid_Amount', 'tb_cmn_status_master.Status_Name', 'tb_cmn_benificiary_master.Benificiary_Name', 'tb_cmn_benificiary_master.Pan_No', 'tb_cmn_benificiary_master.Benificiary_Name', 'tb_cmn_scheme_master.Scheme_Name', 'tb_cmn_state_master.State_Name'])->sortBy('Pkid');

                $disbursementDetailList = [];
                $addedDisbursementList = [];
                $disbursementDetailListUpdate = [];
                if ($id != '0') {
                    $disbursementDetailList = DB::table('tb_sms_disbursement_details')
                        ->join('tb_sms_claim_master', 'tb_sms_disbursement_details.Claim_Id_Fk', '=', 'tb_sms_claim_master.Pkid')
                        ->join('tb_cmn_fund_allocation_txn', 'tb_cmn_fund_allocation_txn.Pkid', '=', 'tb_sms_disbursement_details.Allocation_Id_Fk')
                        ->join('tb_cmn_benificiary_master', 'tb_sms_claim_master.Benificiary_Id_Fk', '=', 'tb_cmn_benificiary_master.Pkid')
                        ->join('tb_cmn_status_master', 'tb_sms_disbursement_details.Status_Id_Fk', '=', 'tb_cmn_status_master.Pkid')
                        ->where('tb_sms_disbursement_details.Pkid', $id)
                        ->where('tb_cmn_fund_allocation_txn.Pkid', $pkid)
                        ->where('tb_sms_disbursement_details.Record_Active_Flag', '1')
                        ->where('tb_cmn_fund_allocation_txn.Record_Active_Flag', '1')
                        ->get(['tb_sms_disbursement_details.*', 'tb_cmn_fund_allocation_txn.Pkid AS Allocation_Pk', 'tb_sms_claim_master.Pkid AS Claim_Id_Pk',  'tb_cmn_fund_allocation_txn.Paid_Amount', 'tb_cmn_status_master.Status_Name', 'tb_cmn_benificiary_master.Benificiary_Name'])->sortBy('Pkid');

                    $disbursementDetailListUpdate      =       DB::table('tb_sms_disbursement_details')
                        ->join('tb_sms_claim_master', 'tb_sms_disbursement_details.Claim_Id_Fk', '=', 'tb_sms_claim_master.Pkid')
                        ->join('tb_cmn_fund_allocation_txn', 'tb_cmn_fund_allocation_txn.Claim_Id_Fk', '=', 'tb_sms_claim_master.Pkid')
                        ->join('tb_cmn_benificiary_master', 'tb_sms_claim_master.Benificiary_Id_Fk', '=', 'tb_cmn_benificiary_master.Pkid')
                        ->join('tb_cmn_status_master', 'tb_sms_disbursement_details.Status_Id_Fk', '=', 'tb_cmn_status_master.Pkid')
                        ->where('tb_sms_disbursement_details.Pkid', $id)
                        ->where('tb_cmn_fund_allocation_txn.Pkid', $pkid)
                        ->where('tb_sms_disbursement_details.Record_Active_Flag', '1')
                        ->where('tb_cmn_fund_allocation_txn.Record_Active_Flag', '1')
                        ->get(['tb_sms_disbursement_details.*', 'tb_cmn_fund_allocation_txn.Pkid AS Allocation_Pk', 'tb_sms_claim_master.Pkid AS Claim_Id_Pk',  'tb_cmn_fund_allocation_txn.Paid_Amount', 'tb_cmn_status_master.Status_Name', 'tb_cmn_benificiary_master.Benificiary_Name'])->sortBy('Pkid');
                    $addedDisbursementList = DB::table('tb_sms_disbursement_details')
                        ->join('tb_sms_claim_master', 'tb_sms_disbursement_details.Claim_Id_Fk', '=', 'tb_sms_claim_master.Pkid')
                        ->join('tb_cmn_fund_allocation_txn', 'tb_cmn_fund_allocation_txn.Pkid', '=', 'tb_sms_disbursement_details.Allocation_Id_Fk')
                        ->join('tb_cmn_benificiary_master', 'tb_sms_claim_master.Benificiary_Id_Fk', '=', 'tb_cmn_benificiary_master.Pkid')
                        ->join('tb_cmn_status_master', 'tb_sms_disbursement_details.Status_Id_Fk', '=', 'tb_cmn_status_master.Pkid')
                        ->where('tb_sms_disbursement_details.Pkid', $id)
                        ->where('tb_cmn_fund_allocation_txn.Pkid', $pkid)
                        ->where('tb_sms_disbursement_details.Record_Active_Flag', '1')
                        ->where('tb_cmn_fund_allocation_txn.Record_Active_Flag', '1')
                        ->get(['tb_sms_disbursement_details.*', 'tb_sms_claim_master.Pkid AS Claim_Id_Pk',  'tb_cmn_fund_allocation_txn.Paid_Amount', 'tb_cmn_status_master.Status_Name', 'tb_cmn_benificiary_master.Benificiary_Name'])->sortBy('Pkid');
                }
                $html = view('disbursement-ui.create-disbursement-modal', compact('bankMaster', 'smsClaimMaster', 'disbursementDetailList', 'addedDisbursementList'))->render();

                $html1 = view('disbursement-ui.disbursement-payment-page', compact('bankMaster', 'smsClaimMaster', 'disbursementDetailListUpdate'))->render();
            } catch (\Exception $ex) {
                return $ex->getMessage();
            }
            //return view('benificiary-ui.benificiary-search-result');
            return response()->json(['status' => "success", 'body' => $html, 'body1' => $html1]);
        } else {
            $html_view = view('pages.error-pages.access-deny-modal')->render();
            return response()->json(["status" => "success", "body" => $html_view]);
        }
    }

    public function viewDisbursementDetailsModal($id, $Allocation_Pk, $MODE)
    {
        $bankMaster      =       TbCmnNedfiBankMaster::whereIn('Status_Id', [5])
            ->where('Record_Active_Flag', '1')
            ->get(['tb_cmn_nedfi_bank_master.*'])
            ->sortBy('Bank_Name');
        $smsClaimMaster = DB::table('tb_sms_claim_master')
            ->join('tb_cmn_status_master', 'tb_sms_claim_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->join('tb_cmn_benificiary_master', 'tb_sms_claim_master.Benificiary_Id_Fk', '=', 'tb_cmn_benificiary_master.Pkid')
            ->join('tb_cmn_scheme_master', 'tb_sms_claim_master.Scheme_Id_Fk', '=', 'tb_cmn_scheme_master.Pkid')
            ->join('tb_cmn_address', 'tb_cmn_benificiary_master.Address_Id_Fk', '=', 'tb_cmn_address.Pkid')
            ->join('tb_cmn_state_master', 'tb_cmn_address.State_Code', '=', 'tb_cmn_state_master.Pkid')
            ->join('tb_cmn_fund_allocation_txn', 'tb_cmn_fund_allocation_txn.Claim_Id_Fk', '=', 'tb_sms_claim_master.Pkid')
            ->join('tb_cmn_policy_master', 'tb_cmn_scheme_master.Gov_policy', '=', 'tb_cmn_policy_master.Pkid')
            ->where('tb_cmn_fund_allocation_txn.Pkid', $Allocation_Pk)
            ->where('tb_sms_claim_master.Record_Active_Flag', '1')
            ->where('tb_cmn_fund_allocation_txn.Record_Active_Flag', '1')
            ->get(['tb_sms_claim_master.*', 'tb_cmn_policy_master.Policy_Name', 'tb_cmn_fund_allocation_txn.Allocated_Amount', 'tb_cmn_fund_allocation_txn.Paid_Amount', 'tb_cmn_status_master.Status_Name', 'tb_cmn_benificiary_master.Benificiary_Name', 'tb_cmn_benificiary_master.Pan_No', 'tb_cmn_benificiary_master.Benificiary_Name', 'tb_cmn_scheme_master.Scheme_Name', 'tb_cmn_state_master.State_Name'])->sortBy('Pkid');


        $addedDisbursementList = DB::table('tb_sms_disbursement_details')
            ->join('tb_sms_claim_master', 'tb_sms_disbursement_details.Claim_Id_Fk', '=', 'tb_sms_claim_master.Pkid')
            ->join('tb_cmn_fund_allocation_txn', 'tb_cmn_fund_allocation_txn.Pkid', '=', 'tb_sms_disbursement_details.Allocation_Id_Fk')
            ->join('tb_cmn_benificiary_master', 'tb_sms_claim_master.Benificiary_Id_Fk', '=', 'tb_cmn_benificiary_master.Pkid')
            ->join('tb_cmn_status_master', 'tb_sms_disbursement_details.Status_Id_Fk', '=', 'tb_cmn_status_master.Pkid')
            ->where('tb_cmn_fund_allocation_txn.Pkid', $Allocation_Pk)
            ->where('tb_sms_disbursement_details.Record_Active_Flag', '1')
            ->where('tb_cmn_fund_allocation_txn.Record_Active_Flag', '1')
            ->get(['tb_sms_disbursement_details.*', 'tb_cmn_fund_allocation_txn.Pkid AS Allocation_Pk', 'tb_sms_claim_master.Pkid AS Claim_Id_Pk',  'tb_cmn_fund_allocation_txn.Paid_Amount', 'tb_cmn_status_master.Status_Name', 'tb_cmn_benificiary_master.Benificiary_Name'])->sortBy('Pkid');

        $html = view('disbursement-ui.view-disbursement-details-modal', compact('bankMaster', 'smsClaimMaster', 'addedDisbursementList', 'MODE'))->render();
        //return view('benificiary-ui.benificiary-search-result');
        return response()->json(['status' => "success", 'body' => $html]);
    }

    public function viewDisbursementClaimDetailsModal($Allocation_Id, $MODE)
    {
        try {
            $bankMaster      =       TbCmnNedfiBankMaster::whereIn('Status_Id', [5])
                ->where('Record_Active_Flag', '1')
                ->get(['tb_cmn_nedfi_bank_master.*'])
                ->sortBy('Bank_Name');
            $smsClaimMaster = DB::table('tb_sms_claim_master')
                ->join('tb_cmn_status_master', 'tb_sms_claim_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
                ->join('tb_cmn_benificiary_master', 'tb_sms_claim_master.Benificiary_Id_Fk', '=', 'tb_cmn_benificiary_master.Pkid')
                ->join('tb_cmn_scheme_master', 'tb_sms_claim_master.Scheme_Id_Fk', '=', 'tb_cmn_scheme_master.Pkid')
                ->join('tb_cmn_address', 'tb_cmn_benificiary_master.Address_Id_Fk', '=', 'tb_cmn_address.Pkid')
                ->join('tb_cmn_state_master', 'tb_cmn_address.State_Code', '=', 'tb_cmn_state_master.Pkid')
                ->join('tb_cmn_fund_allocation_txn', 'tb_cmn_fund_allocation_txn.Claim_Id_Fk', '=', 'tb_sms_claim_master.Pkid')
                ->join('tb_cmn_policy_master', 'tb_cmn_scheme_master.Gov_policy', '=', 'tb_cmn_policy_master.Pkid')
                ->where('tb_cmn_fund_allocation_txn.Pkid', $Allocation_Id)
                //->where('tb_sms_claim_master.Claim_Status', '1')
                ->where('tb_sms_claim_master.Record_Active_Flag', '1')
                ->where('tb_cmn_fund_allocation_txn.Record_Active_Flag', '1')
                ->get(['tb_sms_claim_master.*', 'tb_cmn_policy_master.Policy_Name', 'tb_cmn_fund_allocation_txn.Allocated_Amount', 'tb_cmn_fund_allocation_txn.Paid_Amount', 'tb_cmn_status_master.Status_Name', 'tb_cmn_benificiary_master.Benificiary_Name', 'tb_cmn_benificiary_master.Pan_No', 'tb_cmn_benificiary_master.Benificiary_Name', 'tb_cmn_scheme_master.Scheme_Name', 'tb_cmn_state_master.State_Name'])->sortBy('Pkid');

            $addedDisbursementList = DB::table('tb_sms_disbursement_details')
                ->join('tb_cmn_fund_allocation_txn', 'tb_cmn_fund_allocation_txn.Pkid', '=', 'tb_sms_disbursement_details.Allocation_Id_Fk')
                ->join('tb_sms_claim_master', 'tb_sms_disbursement_details.Claim_Id_Fk', '=', 'tb_sms_claim_master.Pkid')
                ->join('tb_cmn_benificiary_master', 'tb_sms_claim_master.Benificiary_Id_Fk', '=', 'tb_cmn_benificiary_master.Pkid')
                ->join('tb_cmn_status_master', 'tb_sms_disbursement_details.Status_Id_Fk', '=', 'tb_cmn_status_master.Pkid')
                ->where('tb_cmn_fund_allocation_txn.Pkid', $Allocation_Id)
                ->where('tb_sms_disbursement_details.Record_Active_Flag', '1')
                ->where('tb_cmn_fund_allocation_txn.Record_Active_Flag', '1')
                ->get(['tb_sms_disbursement_details.*', 'tb_cmn_fund_allocation_txn.Pkid AS Allocation_Pk', 'tb_sms_claim_master.Pkid AS Claim_Id_Pk',  'tb_cmn_fund_allocation_txn.Paid_Amount', 'tb_cmn_status_master.Status_Name', 'tb_cmn_benificiary_master.Benificiary_Name'])->sortBy('Pkid');

            // if ($MODE == 'EDT') {
            //     $accessBoolean = (new CommonController)->checkAccessRightToController('viewDisbursementClaimDetailsModal');
            //     if ($accessBoolean) {
            //         $html = view('disbursement-ui.view-disbursement-details-modal', compact('bankMaster', 'smsClaimMaster', 'addedDisbursementList', 'MODE'))->render();
            //     } else {
            //         $html_view = view('pages.error-pages.access-deny-modal')->render();
            //         return response()->json(["status" => "success", "body" => $html_view]);
            //     }
            // } else {
            $html = view('disbursement-ui.view-disbursement-details-modal', compact('bankMaster', 'smsClaimMaster', 'addedDisbursementList', 'MODE'))->render();
            // }
            //return view('benificiary-ui.benificiary-search-result');
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
        return response()->json(['status' => "success", 'body' => $html]);
    }
    public function searchDisbursementDetails()
    {
        $subsidyMaster      =       TbCmnSchemeMaster::whereIn('Status_Id', [5])
            ->where('Record_Active_Flag', '1')
            ->get(['tb_cmn_scheme_master.*'])
            ->sortBy('Scheme_Name');

        $bankMaster      =       TbCmnNedfiBankMaster::whereIn('Status_Id', [5])
            ->where('Record_Active_Flag', '1')
            ->get(['tb_cmn_nedfi_bank_master.*'])
            ->sortBy('Bank_Name');
        $stateMaster = TbCmnStateMaster::all()->sortBy('State_Name');
        $statusMaster      =       TbCmnStatusMaster::all()->sortBy('Status_Name');
        $html = view('disbursement-ui.search-disbursement-entry-detail', compact('subsidyMaster', 'stateMaster'))->render();
        //return view('benificiary-ui.benificiary-search-result');
        return view('disbursement-ui.search-disbursement-entry-detail', compact('subsidyMaster', 'stateMaster'))->render();
    }

    public function saveDisbursementDetails(Request $request, $Allocation_Id, $Claim_Id)
    {
        $dataUI = json_decode($request->getContent());
        DB::beginTransaction();
        try {
            // Create 
            $saveDisbursementDetail = new TbSmsDisbursementDetail();
            $saveDisbursementDetail->Allocation_Id_Fk = $Allocation_Id;
            $saveDisbursementDetail->Claim_Id_Fk = $Claim_Id;
            $saveDisbursementDetail->Account_No = $dataUI->account_no;
            $saveDisbursementDetail->IFSC_Code = $dataUI->ifsc_code;
            $saveDisbursementDetail->Bank_Name = $dataUI->bank_name;
            $saveDisbursementDetail->Payment_Mode = $dataUI->payment_mode;
            $saveDisbursementDetail->Instrument_No = $dataUI->insurance_no;
            $saveDisbursementDetail->Instrument_Date = $dataUI->instrument_date;
            $saveDisbursementDetail->Disbursement_Amount = $dataUI->paid_amount;
            $saveDisbursementDetail->Narration = $dataUI->narration;
            $saveDisbursementDetail->Disbursement_Date = new \DateTime();
            $saveDisbursementDetail->Status_Id_Fk = config("constants.CREATED");
            $result = (new CommonController)->insertDefaultColumns($request, $saveDisbursementDetail);
            $addDisbursementDetail = $saveDisbursementDetail->save();

            $updateAllocationAmt = TbCmnFundAllocationTxn::where('Pkid', $Allocation_Id)->get();
            $Already_Paid = null;
            foreach ($updateAllocationAmt as $updateAllocationAmt) {
                $Already_Paid = $Already_Paid + $updateAllocationAmt->Paid_Amount;
            }
            $TotalPaid = (float)($Already_Paid + $dataUI->paid_amount);
            $ClaimDetail = TbCmnFundAllocationTxn::where('Pkid', $Allocation_Id)->get();
            foreach ($ClaimDetail as  $ClaimDetail) {
                if ($ClaimDetail->Allocated_Amount < $TotalPaid) {
                    $status = "failed";
                    return response()->json(["status" => $status, "message" => "Alert! Total disbursement more then allocated amount"]);
                }
            }
            TbCmnFundAllocationTxn::where('Pkid', $Allocation_Id)->update(
                [
                    'Paid_Amount' => $TotalPaid
                ]
            );

            $status = "success";
            // all good
            DB::commit();
        } catch (\Exception $e) {
            return $e->getMessage();
            $status = "failed";
            DB::rollback();
            // something went wrong
        }
        $subsidyMaster      =       TbCmnSchemeMaster::whereIn('Status_Id', [5])
            ->where('Record_Active_Flag', '1')
            ->get(['tb_cmn_scheme_master.*'])
            ->sortBy('Scheme_Name');

        $bankMaster      =       TbCmnNedfiBankMaster::whereIn('Status_Id', [5])
            ->where('Record_Active_Flag', '1')
            ->get(['tb_cmn_nedfi_bank_master.*'])
            ->sortBy('Bank_Name');
        $stateMaster = TbCmnStateMaster::all()->sortBy('State_Name');
        $statusMaster      =       TbCmnStatusMaster::all()->sortBy('Status_Name');
        $disbursementDetailList      =       TbSmsDisbursementDetail::join('tb_cmn_fund_allocation_txn', 'tb_sms_disbursement_details.Allocation_Id_Fk', '=', 'tb_cmn_fund_allocation_txn.Pkid')
            ->join('tb_cmn_status_master', 'tb_sms_disbursement_details.Status_Id_Fk', '=', 'tb_cmn_status_master.Pkid')
            ->join('tb_sms_claim_master', 'tb_sms_disbursement_details.Claim_Id_Fk', '=', 'tb_sms_claim_master.Pkid')
            ->join('tb_cmn_benificiary_master', 'tb_sms_claim_master.Benificiary_Id_Fk', '=', 'tb_cmn_benificiary_master.Pkid')
            ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
            ->where('tb_sms_disbursement_details.Record_Active_Flag', '1')
            ->where('tb_cmn_fund_allocation_txn.Record_Active_Flag', '1')
            ->get(['tb_sms_disbursement_details.*', 'tb_cmn_fund_allocation_txn.Pkid AS Allocation_Pk', 'tb_sms_claim_master.Pkid AS Claim_Id_Pk', 'tb_sms_claim_master.Claim_Id', 'tb_cmn_status_master.Pkid AS Status_Id', 'tb_cmn_benificiary_master.Benificiary_Name', 'tb_sms_claim_master.Claim_Amount', 'tb_cmn_status_master.Status_Name'])
            ->sortBy('Pkid');

        $html = view('disbursement-ui.search-disbursement', compact('stateMaster', 'subsidyMaster', 'disbursementDetailList'))->render();

        $disbursementDetailListUpdate      =       TbSmsDisbursementDetail::join('tb_cmn_fund_allocation_txn', 'tb_sms_disbursement_details.Allocation_Id_Fk', '=', 'tb_cmn_fund_allocation_txn.Pkid')
            ->join('tb_cmn_status_master', 'tb_sms_disbursement_details.Status_Id_Fk', '=', 'tb_cmn_status_master.Pkid')
            ->join('tb_sms_claim_master', 'tb_sms_disbursement_details.Claim_Id_Fk', '=', 'tb_sms_claim_master.Pkid')
            ->join('tb_cmn_benificiary_master', 'tb_sms_claim_master.Benificiary_Id_Fk', '=', 'tb_cmn_benificiary_master.Pkid')
            ->where('tb_sms_disbursement_details.Pkid', $saveDisbursementDetail->Pkid)
            ->where('tb_cmn_fund_allocation_txn.Pkid', $Allocation_Id)
            ->where('tb_sms_disbursement_details.Record_Active_Flag', '1')
            ->where('tb_cmn_fund_allocation_txn.Record_Active_Flag', '1')
            ->get(['tb_sms_disbursement_details.*', 'tb_cmn_fund_allocation_txn.Pkid AS Allocation_Pk', 'tb_sms_claim_master.Pkid AS Claim_Id_Pk', 'tb_sms_claim_master.Claim_Id', 'tb_cmn_status_master.Pkid AS Status_Id', 'tb_cmn_benificiary_master.Benificiary_Name', 'tb_sms_claim_master.Claim_Amount', 'tb_cmn_status_master.Status_Name'])
            ->sortBy('Pkid');

        $html1 = view('disbursement-ui.disbursement-payment-page', compact('bankMaster', 'disbursementDetailListUpdate'))->render();
        if ($status == "success") {
            return response()->json(["status" => $status, "message" => "Success! Disbursement created.", "body" => $html, "body1" => $html1]);
        } else {
            return response()->json(["status" => $status, "message" => "Alert! Disbursemen not created"]);
        }
    }

    public function upateDisbursementDetails(Request $request, $disbursement_id)
    {
        $dataUI = json_decode($request->getContent());
        DB::beginTransaction();
        try {
            // Update 
            $Disbursement_Amount = null;
            $Allocation_Id = null;
            $disbursementDetails = TbSmsDisbursementDetail::where('Pkid', $disbursement_id)->get();

            foreach ($disbursementDetails as $disbursementDetails) {
                $Disbursement_Amount = $disbursementDetails->Disbursement_Amount;
                $Allocation_Id = $disbursementDetails->Allocation_Id_Fk;
            }

            $updateAllocationAmt = TbCmnFundAllocationTxn::where('Pkid', $Allocation_Id)->get();
            $Already_Paid = null;
            foreach ($updateAllocationAmt as $updateAllocationAmt) {
                $Already_Paid = ($Already_Paid + $updateAllocationAmt->Paid_Amount) - $Disbursement_Amount;
            }

            $TotalPaid = (float)($Already_Paid + $dataUI->paid_amount);
            $ClaimDetail = TbCmnFundAllocationTxn::where('Pkid', $Allocation_Id)->get();
            foreach ($ClaimDetail as  $ClaimDetail) {
                if ($ClaimDetail->Allocated_Amount < $TotalPaid) {
                    $status = "failed";
                    return response()->json(["status" => $status, "message" => "Alert! Total disbursemen more then claim amount"]);
                }
            }
            TbCmnFundAllocationTxn::where('Pkid', $Allocation_Id)->update(
                [
                    'Paid_Amount' => $TotalPaid
                ]
            );

            $disbursementData = array(
                'Account_No' => $dataUI->account_no, 'IFSC_Code' => $dataUI->ifsc_code, 'Bank_Name' => $dataUI->bank_name, "Payment_Mode" => $dataUI->payment_mode, 'Instrument_No' => $dataUI->insurance_no,
                'Instrument_Date' => $dataUI->instrument_date, 'Disbursement_Amount' => $dataUI->paid_amount, 'Narration' => $dataUI->narration, 'Record_Update_Date' => new \DateTime(), 'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'),
                'Updated_By' => $request->session()->get('id')
            );
            $addresBenificiary = TbSmsDisbursementDetail::where('Pkid', $disbursement_id)->update($disbursementData);
            $status = "success";
            // all good
            DB::commit();
        } catch (\Exception $e) {
            throw $e;
            $status = "failed";
            DB::rollback();
            // something went wrong
        }
        $subsidyMaster      =       TbCmnSchemeMaster::whereIn('Status_Id', [5])
            ->where('Record_Active_Flag', '1')
            ->get(['tb_cmn_scheme_master.*'])
            ->sortBy('Scheme_Name');

        $bankMaster      =       TbCmnNedfiBankMaster::whereIn('Status_Id', [5])
            ->where('Record_Active_Flag', '1')
            ->get(['tb_cmn_nedfi_bank_master.*'])
            ->sortBy('Bank_Name');
        $stateMaster = TbCmnStateMaster::all()->sortBy('State_Name');
        $statusMaster      =       TbCmnStatusMaster::all()->sortBy('Status_Name');
        $disbursementDetailList      =       TbSmsDisbursementDetail::join('tb_cmn_fund_allocation_txn', 'tb_sms_disbursement_details.Allocation_Id_Fk', '=', 'tb_cmn_fund_allocation_txn.Pkid')
            ->join('tb_cmn_status_master', 'tb_sms_disbursement_details.Status_Id_Fk', '=', 'tb_cmn_status_master.Pkid')
            ->join('tb_sms_claim_master', 'tb_sms_disbursement_details.Claim_Id_Fk', '=', 'tb_sms_claim_master.Pkid')
            ->join('tb_cmn_benificiary_master', 'tb_sms_claim_master.Benificiary_Id_Fk', '=', 'tb_cmn_benificiary_master.Pkid')
            ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
            ->where('tb_sms_disbursement_details.Record_Active_Flag', '1')
            ->where('tb_cmn_fund_allocation_txn.Record_Active_Flag', '1')
            ->get(['tb_sms_disbursement_details.*', 'tb_cmn_fund_allocation_txn.Pkid AS Allocation_Pk', 'tb_sms_claim_master.Pkid AS Claim_Id_Pk', 'tb_sms_claim_master.Claim_Id', 'tb_cmn_status_master.Pkid AS Status_Id', 'tb_cmn_benificiary_master.Benificiary_Name', 'tb_sms_claim_master.Claim_Amount', 'tb_cmn_status_master.Status_Name'])
            ->sortBy('Pkid');

        $html = view('disbursement-ui.search-disbursement', compact('stateMaster', 'subsidyMaster', 'disbursementDetailList'))->render();

        $disbursementDetailListUpdate      =       TbSmsDisbursementDetail::join('tb_cmn_fund_allocation_txn', 'tb_sms_disbursement_details.Allocation_Id_Fk', '=', 'tb_cmn_fund_allocation_txn.Pkid')
            ->join('tb_cmn_status_master', 'tb_sms_disbursement_details.Status_Id_Fk', '=', 'tb_cmn_status_master.Pkid')
            ->join('tb_sms_claim_master', 'tb_sms_disbursement_details.Claim_Id_Fk', '=', 'tb_sms_claim_master.Pkid')
            ->join('tb_cmn_benificiary_master', 'tb_sms_claim_master.Benificiary_Id_Fk', '=', 'tb_cmn_benificiary_master.Pkid')
            ->where('tb_sms_disbursement_details.Pkid', $disbursement_id)
            ->where('tb_cmn_fund_allocation_txn.Pkid', $Allocation_Id)
            ->where('tb_sms_disbursement_details.Record_Active_Flag', '1')
            ->where('tb_cmn_fund_allocation_txn.Record_Active_Flag', '1')
            ->get(['tb_sms_disbursement_details.*', 'tb_cmn_fund_allocation_txn.Pkid AS Allocation_Pk', 'tb_sms_claim_master.Pkid AS Claim_Id_Pk', 'tb_sms_claim_master.Claim_Id', 'tb_cmn_status_master.Pkid AS Status_Id', 'tb_cmn_benificiary_master.Benificiary_Name', 'tb_sms_claim_master.Claim_Amount', 'tb_cmn_status_master.Status_Name'])
            ->sortBy('Pkid');
        $html1 = view('disbursement-ui.disbursement-payment-page', compact('bankMaster', 'disbursementDetailListUpdate'))->render();

        if ($status == "success") {
            return response()->json(["status" => $status, "message" => "Success! Disbursement updated.", "body" => $html, "body1" => $html1]);
        } else {
            return response()->json(["status" => $status, "message" => "Alert! Disbursemen not updated"]);
        }
    }

    public function destroyDisbursement(Request $request, $disbursement_id)
    {
        $accessBoolean = (new CommonController)->checkAccessRightToController('destroyDisbursement');
        if ($accessBoolean) {
            DB::beginTransaction();
            try {
                $disbursement = TbSmsDisbursementDetail::where("Pkid", $disbursement_id)->get();
                $Disbursement_Amount = null;
                $Allocation_Id = null;
                $Fund_Balance = null;
                foreach ($disbursement as $disbursement) {
                    $Disbursement_Amount = $Disbursement_Amount + $disbursement->Disbursement_Amount;
                    $Allocation_Id = $disbursement->Allocation_Id_Fk;
                }
                $FundAllocation = TbCmnFundAllocationTxn::where("Pkid", $Allocation_Id)->get();
                foreach ($FundAllocation as $FundAllocation) {
                    $Fund_Balance = $FundAllocation->Paid_Amount;
                }
                TbCmnFundAllocationTxn::where('Pkid', $Allocation_Id)->update(
                    [
                        'Paid_Amount' => (float)($Fund_Balance - $Disbursement_Amount)
                    ]
                );
                $deleteDisbursementDetail       =       TbSmsDisbursementDetail::where("Pkid", $disbursement_id)->delete();

                $status = "success";
                DB::commit();
            } catch (\Exception $ex) {
                throw $ex;
                $status = "failed";
                DB::rollback();
            }

            $disbursementDetailList      =       TbSmsDisbursementDetail::join('tb_cmn_fund_allocation_txn', 'tb_sms_disbursement_details.Allocation_Id_Fk', '=', 'tb_cmn_fund_allocation_txn.Pkid')
                ->join('tb_cmn_status_master', 'tb_sms_disbursement_details.Status_Id_Fk', '=', 'tb_cmn_status_master.Pkid')
                ->join('tb_sms_claim_master', 'tb_sms_disbursement_details.Claim_Id_Fk', '=', 'tb_sms_claim_master.Pkid')
                ->join('tb_cmn_benificiary_master', 'tb_sms_claim_master.Benificiary_Id_Fk', '=', 'tb_cmn_benificiary_master.Pkid')
                ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
                ->where('tb_sms_disbursement_details.Record_Active_Flag', '1')
                ->where('tb_cmn_fund_allocation_txn.Record_Active_Flag', '1')
                ->get(['tb_sms_disbursement_details.*', 'tb_cmn_fund_allocation_txn.Pkid AS Allocation_Pk', 'tb_sms_claim_master.Pkid AS Claim_Id_Pk',  'tb_sms_claim_master.Claim_Id', 'tb_cmn_status_master.Pkid AS Status_Id', 'tb_cmn_benificiary_master.Benificiary_Name', 'tb_sms_claim_master.Claim_Amount', 'tb_cmn_status_master.Status_Name'])
                ->sortBy('Pkid');

            $html_view = view("disbursement-ui.search-disbursement", compact('disbursementDetailList'))->render();
            if ($status == "success") {
                return response()->json(["status" => "success", "message" => "Success! Disbursement deleted", "body" => $html_view]);
            } else {
                return response()->json(["status" => "failed", "message" => "Alert! Disbursement deleted"]);
            }
        } else {
            $html_view = view('pages.error-pages.access-deny-modal')->render();
            return response()->json(["status" => "access_deny", "body" => $html_view]);
        }
    }

    // Approval record
    public function disbursementApproval(Request $request, $id)
    {
        $dataUI = json_decode($request->getContent());
        DB::beginTransaction();
        try {
            $tableData = array(
                'Status_Id_Fk' => '2', 'Record_Update_Date' => new \DateTime(), 'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'),
                'Updated_By' => $request->session()->get('id')
            );
            $updateTable = TbSmsDisbursementDetail::where('Pkid',  $id)
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
        $disbursementDetailList      =       TbSmsDisbursementDetail::join('tb_cmn_fund_allocation_txn', 'tb_sms_disbursement_details.Allocation_Id_Fk', '=', 'tb_cmn_fund_allocation_txn.Pkid')
            ->join('tb_cmn_status_master', 'tb_sms_disbursement_details.Status_Id_Fk', '=', 'tb_cmn_status_master.Pkid')
            ->join('tb_sms_claim_master', 'tb_sms_disbursement_details.Claim_Id_Fk', '=', 'tb_sms_claim_master.Pkid')
            ->join('tb_cmn_benificiary_master', 'tb_sms_claim_master.Benificiary_Id_Fk', '=', 'tb_cmn_benificiary_master.Pkid')
            ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
            ->where('tb_sms_disbursement_details.Record_Active_Flag', '1')
            ->where('tb_cmn_fund_allocation_txn.Record_Active_Flag', '1')
            ->get(['tb_sms_disbursement_details.*', 'tb_cmn_fund_allocation_txn.Pkid AS Allocation_Pk', 'tb_sms_claim_master.Pkid AS Claim_Id_Pk',  'tb_sms_claim_master.Claim_Id', 'tb_cmn_status_master.Pkid AS Status_Id', 'tb_cmn_benificiary_master.Benificiary_Name', 'tb_sms_claim_master.Claim_Amount', 'tb_cmn_status_master.Status_Name'])
            ->sortBy('Pkid');
        $html_view = view("disbursement-ui.search-disbursement", compact('disbursementDetailList'))->render();
        if ($status == "success") {
            return response()->json(["status" => "success", "message" => "Success! Disbursement submitted for approval", "body" => $html_view]);
        } else {
            return response()->json(["status" => "failed", "message" => "Alert! Disbursement not submitted for approval"]);
        }
    }
    // save approve benificiry

    public function approveDisbursementEntry(Request $request)
    {
        $accessBoolean = (new CommonController)->checkAccessRightToController('approveDisbursementEntry');
        if ($accessBoolean) {
            DB::beginTransaction();
            try {
                $dataUI = json_decode($request->getContent());
                $approvalStatus = $dataUI->decision;
                $approvalDate = $dataUI->approval_date;
                $remarks = $dataUI->remarks;
                if (Is_Array($dataUI->check_id)) {
                    foreach ($dataUI->check_id as $value) {
                        TbSmsDisbursementDetail::where('Pkid', $value)->update(
                            [
                                'Status_Id_Fk' => $approvalStatus, 'Record_Update_Date' => new \DateTime(), 'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'),
                                'Updated_By' => $request->session()->get('id')
                            ]
                        );
                        $saveCmnApproval = new TbCmnApproval();
                        $saveCmnApproval->Approval_Date = $approvalDate;
                        $saveCmnApproval->Status_Id_Fk = $approvalStatus;
                        $saveCmnApproval->Remarks = $remarks;
                        $saveCmnApproval->Module = 'Disbursement';
                        $saveCmnApproval->Record_Id_Fk =  $value;
                        $saveCmnApproval->save();
                    }
                } else {
                    TbSmsDisbursementDetail::where('Pkid', $dataUI->check_id)->update(
                        [
                            'Status_Id_Fk' => $approvalStatus, 'Record_Update_Date' => new \DateTime(), 'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'),
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
            $disbursementDetailList      =       TbSmsDisbursementDetail::join('tb_cmn_fund_allocation_txn', 'tb_sms_disbursement_details.Allocation_Id_Fk', '=', 'tb_cmn_fund_allocation_txn.Pkid')
                ->join('tb_cmn_status_master', 'tb_sms_disbursement_details.Status_Id_Fk', '=', 'tb_cmn_status_master.Pkid')
                ->join('tb_sms_claim_master', 'tb_sms_disbursement_details.Claim_Id_Fk', '=', 'tb_sms_claim_master.Pkid')
                ->join('tb_cmn_benificiary_master', 'tb_sms_claim_master.Benificiary_Id_Fk', '=', 'tb_cmn_benificiary_master.Pkid')
                ->whereIn('tb_cmn_status_master.Pkid', [2])
                ->where('tb_sms_disbursement_details.Record_Active_Flag', '1')
                ->where('tb_cmn_fund_allocation_txn.Record_Active_Flag', '1')
                ->get(['tb_sms_disbursement_details.*', 'tb_sms_claim_master.Pkid AS Claim_Id_Pk',  'tb_sms_claim_master.Claim_Id', 'tb_cmn_status_master.Pkid AS Status_Id', 'tb_cmn_benificiary_master.Benificiary_Name', 'tb_sms_claim_master.Claim_Amount', 'tb_cmn_status_master.Status_Name'])
                ->sortBy('Pkid');
            $html_view = view("disbursement-ui.after-approve-disbursement-list", compact('disbursementDetailList'))->render();

            //$html_view = view('disbursement-ui.search-disbursement-copy', compact('disbursementDetailList'));
            if ($status == 'success') {
                return response()->json(["status" => "success", "message" => "Success! Disbursement " . $decision, "body" => $html_view]);
            } else {
                return response()->json(["status" => "success", "message" => "Alert! Benificiary not " . $decision]);
            }
        } else {
            return view('pages.error-pages.access-deny-modal')->render();
        }
    }
}
