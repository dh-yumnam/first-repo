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
use App\Models\TbSmsClaimTxn;
use App\Models\TbCmnApproval;
use Faker\Core\Number;
use Hamcrest\Arrays\IsArray;
use Illuminate\Support\Facades\DB;
use PhpParser\NodeVisitor\FirstFindingVisitor;
use Ramsey\Uuid\Type\Decimal;

class FundAllocationController extends Controller
{
    public function addFundAllocation()
    {
        $subsidyMaster      =       TbCmnSchemeMaster::all()
            ->whereIn('Status_Id', [5])
            ->where('Record_Active_Flag', '1')
            ->sortBy('Scheme_Name');
        $bankMaster      =       TbCmnNedfiBankMaster::all()
            ->whereIn('Status_Id', [5])
            ->where('Record_Active_Flag', '1')
            ->sortBy('Bank_Name');
        $stateMaster = TbCmnStateMaster::all()->sortBy('State_Name');
        $statusMaster      =       TbCmnStatusMaster::all()->sortBy('Status_Name');
        $subsidyFund      =       TbCmnFundMaster::join('tb_cmn_status_master', 'tb_cmn_fund_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->join('tb_cmn_scheme_master', 'tb_cmn_fund_master.Scheme_Id', '=', 'tb_cmn_scheme_master.Pkid')
            ->where('tb_cmn_fund_master.Fund_Balance', '>', 0)
            ->where('tb_cmn_fund_master.Record_Active_Flag', '1')
            ->whereIn('tb_cmn_status_master.Pkid', [5])
            ->get(['tb_cmn_fund_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_scheme_master.Scheme_Name', 'tb_cmn_scheme_master.Pkid AS Scheme_Pk'])
            // ->groupBy('tb_cmn_fund_master.Sanction_Letter','tb_cmn_fund_master.Sanction_Date','tb_cmn_fund_master.Scheme_Id','tb_cmn_fund_master.Sanction_Amount','tb_cmn_fund_master.Fund_Balance')
            ->sortBy('Pkid');
        $fundAllocation      =       TbCmnFundAllocationMaster::join('tb_cmn_status_master', 'tb_cmn_fund_allocation_master.Status_Id_Fk', '=', 'tb_cmn_status_master.Pkid')
            ->join('tb_cmn_scheme_master', 'tb_cmn_fund_allocation_master.Scheme_Id_Fk', '=', 'tb_cmn_scheme_master.Pkid')
            ->join('tb_cmn_fund_master', 'tb_cmn_fund_allocation_master.Fund_Id_Fk', '=', 'tb_cmn_fund_master.Pkid')
            ->where('tb_cmn_fund_allocation_master.Total_Allocated_Amount', '>', 0)
            ->where('tb_cmn_fund_allocation_master.Record_Active_Flag', '=', 1)
            ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
            ->get(['tb_cmn_fund_allocation_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_fund_master.Sanction_Letter', 'tb_cmn_fund_master.Sanction_Amount', 'tb_cmn_scheme_master.Scheme_Name'])
            ->sortBy('Pkid');
        $html = view('fund-management-ui.fund-allocation.create-fund-allocation', compact('subsidyMaster', 'bankMaster', 'stateMaster', 'subsidyFund', 'fundAllocation', 'statusMaster'))->render();
        return response()->json(['status' => "success", 'body' => $html]);
    }


    public function approveFundAllocationForm()
    {
        $accessBoolean = (new CommonController)->checkAccessRightToController('approveFundAllocationForm');
        if ($accessBoolean) {
            $approvalStatusMaster = TbCmnStatusMaster::whereIn('Pkid', [3, 4, 5])->get();
            $fundAllocation      =       TbCmnFundAllocationMaster::join('tb_cmn_status_master', 'tb_cmn_fund_allocation_master.Status_Id_Fk', '=', 'tb_cmn_status_master.Pkid')
                ->join('tb_cmn_scheme_master', 'tb_cmn_fund_allocation_master.Scheme_Id_Fk', '=', 'tb_cmn_scheme_master.Pkid')
                ->join('tb_cmn_fund_master', 'tb_cmn_fund_allocation_master.Fund_Id_Fk', '=', 'tb_cmn_fund_master.Pkid')
                ->where('tb_cmn_fund_master.Record_Active_Flag', '1')
                ->whereIn('tb_cmn_status_master.Pkid', [2])
                ->get(['tb_cmn_fund_allocation_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_fund_master.Sanction_Letter', 'tb_cmn_fund_master.Sanction_Amount', 'tb_cmn_scheme_master.Scheme_Name'])
                ->sortBy('Pkid');
            //return view('fund-management-ui.fund-allocation.create-fund-allocation', compact('subsidyMaster', 'bankMaster', 'stateMaster', 'subsidyFund', 'fundAllocation'));
            $html = view('fund-management-ui.fund-allocation.approve-fund-allocation', compact('fundAllocation', 'approvalStatusMaster'))->render();
            return response()->json(['status' => "success", 'body' => $html]);
        } else {
            $html_view = view('pages.error-pages.access-deny-modal')->render();
            return response()->json(["status" => "success", "body" => $html_view]);
        }
    }
    public function searchFundAllocation(Request $request)
    {
        $dataUI = json_decode($request->getContent());
        try {

            $query = DB::table('tb_cmn_fund_allocation_master')->join('tb_cmn_fund_allocation_txn', 'tb_cmn_fund_allocation_master.Pkid', '=', 'tb_cmn_fund_allocation_txn.Allocation_Master_Id_Fk')
                ->join('tb_cmn_scheme_master', 'tb_cmn_fund_allocation_master.Scheme_Id_Fk', '=', 'tb_cmn_scheme_master.Pkid')
                ->join('tb_cmn_fund_master', 'tb_cmn_fund_allocation_master.Fund_Id_Fk', '=', 'tb_cmn_fund_master.Pkid')
                ->join('tb_cmn_status_master', 'tb_cmn_fund_allocation_master.Status_Id_Fk', '=', 'tb_cmn_status_master.Pkid')
                ->join('tb_sms_claim_master', 'tb_cmn_fund_allocation_txn.Claim_Id_Fk', '=', 'tb_sms_claim_master.Pkid')
                ->join('tb_cmn_benificiary_master', 'tb_sms_claim_master.Benificiary_Id_Fk', '=', 'tb_cmn_benificiary_master.Pkid')
                ->where('tb_cmn_fund_allocation_master.Record_Active_Flag', '1')
                ->where('tb_cmn_fund_allocation_txn.Record_Active_Flag', '1')
                ->whereIn('tb_cmn_status_master.Pkid', [1, 2, 3, 4, 5]);

                
            if (!empty($dataUI->sanction_letter)) {
                $query->where('tb_cmn_fund_master.Sanction_Letter', 'LIKE', "%{$dataUI->sanction_letter}%");
            }
            if (!empty($dataUI->benificiary_name)) {
                $query->where('tb_cmn_benificiary_master.Benificiary_Name', 'LIKE', "%{$dataUI->benificiary_name}%");
            }
            if (!empty($dataUI->scheme_id)) {
                $query->where('tb_cmn_scheme_master.Pkid', $dataUI->scheme_id);
            }
            if (!empty($dataUI->claim_from) && !empty($dataUI->claim_to)) {
                $query->whereBetween('tb_sms_claim_master.Claim_From_Date', [$dataUI->claim_from, $dataUI->claim_to]);
            }
            if (!empty($dataUI->status_id)) {
                $query->where('tb_cmn_fund_allocation_master.Status_Id_Fk', $dataUI->status_id);
            }
            $fundAllocation = $query->get(['tb_cmn_fund_allocation_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_fund_master.Sanction_Letter', 'tb_cmn_fund_master.Sanction_Amount', 'tb_cmn_scheme_master.Scheme_Name'])->sortBy('Pkid');
        } catch (\Exception $ex) {
            throw $ex;
        }
        $html = view('fund-management-ui.fund-allocation.search-fund-allocation-result', compact('fundAllocation'))->render();
        return response()->json(['status' => "success", 'body' => $html]);
    }

    public function addFundAllocationModal(Request $request)
    {
        $accessBoolean = (new CommonController)->checkAccessRightToController('addFundAllocationModal');

        if ($accessBoolean) {
            $remarks      =       TbCmnReasonMaster::all()
                ->whereIn('Status_Id', [5])
                ->where('Record_Active_Flag', '1')
                ->sortBy('Reason_Details');
            $subsidyMaster      =       TbCmnSchemeMaster::all()
                ->whereIn('Status_Id', [5])
                ->where('Record_Active_Flag', '1')
                ->sortBy('Scheme_Name');
            $bankMaster      =       TbCmnNedfiBankMaster::all()
                ->whereIn('Status_Id', [5])
                ->where('Record_Active_Flag', '1')
                ->sortBy('Bank_Name');
            $stateMaster = TbCmnStateMaster::all()->sortBy('State_Name');

            $dataUI = json_decode($request->getContent());
            // scheme =40
            // fund_id =3
            try {
                $fundId = $dataUI->fund_id;
                $scheme_id = $dataUI->scheme_id;
                $query = DB::table('tb_cmn_fund_master')->join('tb_cmn_scheme_master', 'tb_cmn_fund_master.Scheme_Id', '=', 'tb_cmn_scheme_master.Pkid')
                    ->where('tb_cmn_scheme_master.Pkid', $scheme_id)
                    ->whereIn('tb_cmn_scheme_master.Status_Id', [5])
                    ->where('tb_cmn_scheme_master.Record_Active_Flag', '1')
                    ->where('tb_cmn_fund_master.Fund_Balance', '>', 0)
                    ->where('tb_cmn_fund_master.Pkid', $fundId);
                $fundMaster = $query->get(['tb_cmn_fund_master.*', 'tb_cmn_scheme_master.Scheme_Name', 'tb_cmn_fund_master.Scheme_Id']);
                $query1 = DB::table('tb_sms_claim_master')->join('tb_cmn_benificiary_master', 'tb_sms_claim_master.Benificiary_Id_Fk', '=', 'tb_cmn_benificiary_master.Pkid')
                    ->join('tb_cmn_scheme_master', 'tb_sms_claim_master.Scheme_Id_Fk', '=', 'tb_cmn_scheme_master.Pkid')
                    ->join('tb_cmn_address', 'tb_cmn_benificiary_master.Address_Id_Fk', '=', 'tb_cmn_address.Pkid')
                    ->join('tb_cmn_fund_master', 'tb_cmn_fund_master.Scheme_Id', '=', 'tb_cmn_scheme_master.Pkid')
                    ->where('tb_cmn_scheme_master.Pkid', $scheme_id)
                    ->where('tb_cmn_fund_master.Pkid', $fundId)
                    //->where('tb_sms_claim_master.Claim_Status', [0])
                    ->where([['tb_sms_claim_master.Claim_Balance_Amount', '>', 0], ['tb_sms_claim_master.Claim_Amount', '>', 1]])
                    ->where('tb_cmn_fund_master.Fund_Balance', '>', 0)
                    ->where('tb_sms_claim_master.Record_Active_Flag', '1')
                    ->whereIn('tb_cmn_benificiary_master.Status_Id', [5])
                    ->whereIn('tb_sms_claim_master.Audit_Status', [2])
                    ->whereIn('tb_sms_claim_master.Status_Id', [5]);
           
                // $result = (new CommonController)->insertDefaultColumns($request, $saveFundAllocationTxn);

                $claimBenificiaryList = $query1->get(['tb_sms_claim_master.*', 'tb_cmn_benificiary_master.Benificiary_Name', 'tb_cmn_scheme_master.Scheme_Name', 'tb_cmn_fund_master.Scheme_Id', 'tb_cmn_fund_master.Fund_Balance', 'tb_cmn_fund_master.Pkid AS Fund_Id']);
                $query2 = DB::table('tb_cmn_fund_allocation_master')->join('tb_cmn_fund_allocation_txn', 'tb_cmn_fund_allocation_master.Pkid', '=', 'tb_cmn_fund_allocation_txn.Allocation_Master_Id_Fk')
                    ->join('tb_cmn_scheme_master', 'tb_cmn_fund_allocation_txn.Scheme_Id_Fk', '=', 'tb_cmn_scheme_master.Pkid')
                    ->join('tb_sms_claim_master', 'tb_cmn_fund_allocation_txn.Claim_Id_Fk', '=', 'tb_sms_claim_master.Pkid')
                    ->join('tb_cmn_benificiary_master', 'tb_sms_claim_master.Benificiary_Id_Fk', '=', 'tb_cmn_benificiary_master.Pkid')
                    ->join('tb_cmn_address', 'tb_cmn_benificiary_master.Address_Id_Fk', '=', 'tb_cmn_address.Pkid')
                    ->join('tb_cmn_fund_master', 'tb_cmn_fund_allocation_master.Fund_Id_Fk', '=', 'tb_cmn_fund_master.Pkid')
                    ->where('tb_cmn_scheme_master.Pkid', $scheme_id)
                    ->where('tb_cmn_fund_master.Pkid', $fundId)
                    ->where('tb_cmn_fund_allocation_txn.Record_Active_Flag', '1')
                    ->where('tb_cmn_fund_allocation_master.Record_Active_Flag', '1');
                $allocatedBenificiaryList = $query2->get(['tb_cmn_fund_allocation_master.*', 'tb_cmn_benificiary_master.Benificiary_Name', 'tb_cmn_fund_allocation_txn.Pkid AS Allocation_Pk', 'tb_cmn_fund_allocation_txn.Allocated_Amount', 'tb_cmn_benificiary_master.Benificiary_Name', 'tb_cmn_scheme_master.Scheme_Name', 'tb_cmn_fund_master.Scheme_Id', 'tb_cmn_fund_master.Fund_Balance', 'tb_cmn_fund_master.Pkid AS Fund_Id', 'tb_cmn_fund_master.Sanction_Letter', 'tb_cmn_fund_master.Sanction_Amount', 'tb_sms_claim_master.Claim_Amount', 'tb_sms_claim_master.Claim_Id']);

                $html = view('fund-management-ui.fund-allocation.create-fund-allocation-modal', compact('fundMaster', 'remarks', 'subsidyMaster', 'bankMaster', 'stateMaster', 'claimBenificiaryList', 'allocatedBenificiaryList'))->render();
                // ->whereNotIn('id', DB::table('curses')->select('id_user')->where('id_user', '=', $id)->get()->toArray())
            } catch (\Exception $ex) {
                throw $ex->getMessage();
            }
            return response()->json(['status' => "success", 'body' => $html]);
        } else {
            $html_view = view('pages.error-pages.access-deny-modal')->render();
            return response()->json(["status" => "access_deny", "body" => $html_view]);
        }
    }

    public function searchClaimedBenificiary(Request $request)
    {
        $dataUI = json_decode($request->getContent());
        try {
            $claim_scheme_id = $dataUI->claim_scheme_id;
            $claim_bank_id = $dataUI->claim_bank_id;
            $claim_benificiary_name = $dataUI->claim_benificiary_name;

            $search_claim_no = $dataUI->search_claim_no;
            $search_allcation_From_Date = $dataUI->search_allcation_From_Date;
            $search_allcation_To_Date = $dataUI->search_allcation_To_Date;
            $claim_state_id = $dataUI->claim_state_id;

            $query = DB::table('tb_sms_claim_master')->join('tb_cmn_benificiary_master', 'tb_sms_claim_master.Benificiary_Id_Fk', '=', 'tb_cmn_benificiary_master.Pkid')
                ->join('tb_cmn_scheme_master', 'tb_sms_claim_master.Scheme_Id_Fk', '=', 'tb_cmn_scheme_master.Pkid')
                ->join('tb_cmn_address', 'tb_cmn_benificiary_master.Address_Id_Fk', '=', 'tb_cmn_address.Pkid');

            if (!empty($dataUI->claim_scheme_id)) {
                $query->where('tb_cmn_scheme_master.Pkid', $claim_scheme_id);
            }
            if (!empty($dataUI->claim_benificiary_name)) {
                $query->where('Benificiary_Name', 'LIKE', "%{$claim_benificiary_name}%");
            }
            if (!empty($dataUI->search_claim_no)) {
                $query->where('tb_sms_claim_master.Claim_Id', $search_claim_no);
            }
            if (!empty($dataUI->search_allcation_From_Date)) {
                $query->where('tb_sms_claim_master.Claim_From_Date', $search_allcation_From_Date);
            }
            if (!empty($dataUI->search_allcation_To_Date)) {
                $query->where('tb_sms_claim_master.Claim_From_Date', $search_allcation_To_Date);
            }
            if (!empty($dataUI->claim_state_id)) {
                $query->where('tb_cmn_address.State_Code', $claim_state_id);
            }
            $claimBenificiaryList = $query->get();
        } catch (\Exception $ex) {
            throw $ex;
        }
        $html = view('fund-management-ui.fund-allocation.claimed-benificiary-list-for-allocation', compact('claimBenificiaryList'))->render();
        return response()->json(['status' => "success", 'body' => $html]);
    }

    public function saveFundAllocation(Request $request, $fundId, $scheme_id)
    {
        DB::beginTransaction();
        try {
            $dataUI = json_decode($request->getContent());
            //add the allocation master 
            $allocationMasterPk = null;
            $saveFundAllocationMaster = new TbCmnFundAllocationMaster();
            $saveFundAllocationMaster->Scheme_Id_Fk = $scheme_id;
            $saveFundAllocationMaster->Fund_Id_Fk = $fundId;
            $saveFundAllocationMaster->Status_Id_Fk = '1';
            $result = (new CommonController)->insertDefaultColumns($request, $saveFundAllocationMaster);
            $addFundAllocationMaster = $saveFundAllocationMaster->save();
            $allocationMasterPk = $saveFundAllocationMaster->Pkid;

            if (Is_Array($dataUI->check_id)) {
                foreach ($dataUI->check_id as $value) {
                    $allocated = 'allocated_amount' . $value;
                    if (empty($dataUI->$allocated) or ($dataUI->$allocated < 1)) {
                        return response()->json(["status" => 'failed', "message" => "Alert! Enter allocation amount"]);
                    }
                    $tbSmsClaimMaster = TbSmsClaimMaster::where('Pkid', $value)->firstOrFail();
                    $Benificiary_Id_Fk =  $tbSmsClaimMaster->Benificiary_Id_Fk;
                    $tbCmnBenificiaryMaster = TbCmnBenificiaryMaster::where('Pkid', $Benificiary_Id_Fk)->firstOrFail();
                    $Address_Id_Fk =  $tbCmnBenificiaryMaster->Address_Id_Fk;
                    $tbCmnAddress = TbCmnAddress::where('Pkid', $Address_Id_Fk)->firstOrFail();
                    //add the allocation transaction 
                    $saveFundAllocationTxn = new TbCmnFundAllocationTxn();
                    $saveFundAllocationTxn->Allocation_Master_Id_Fk = $allocationMasterPk;
                    $saveFundAllocationTxn->Claim_Id_Fk = $value;
                    $saveFundAllocationTxn->Claimed_Amount = $tbSmsClaimMaster->Claim_Amount;
                    $saveFundAllocationTxn->Allocated_Amount = $dataUI->$allocated;
                    $saveFundAllocationTxn->Paid_Amount = '0.00';
                    $saveFundAllocationTxn->From_Date = $tbSmsClaimMaster->Claim_From_Date;
                    $saveFundAllocationTxn->To_Date = $tbSmsClaimMaster->Claim_To_Date;
                    $saveFundAllocationTxn->Scheme_Id_Fk = $scheme_id;
                    $saveFundAllocationTxn->Fund_Id_Fk = $fundId;
                    $saveFundAllocationTxn->State_Id_Fk = $tbCmnAddress->State_Code;
                    $result = (new CommonController)->insertDefaultColumns($request, $saveFundAllocationTxn);
                    $addFundAllocationTxn = $saveFundAllocationTxn->save();

                    $tbCmnFundMaster = TbCmnFundMaster::where('Pkid', $fundId)
                        ->where('Scheme_Id', $scheme_id)->firstOrFail();
                    $balanceFund = $tbCmnFundMaster->Fund_Balance;

                    $getAllocatedAmt = TbCmnFundAllocationMaster::where('Fund_Id_Fk', $fundId)
                        ->where('Scheme_Id_Fk', $scheme_id)->get();
                    $allocatedAmt = 0;
                    if (count($getAllocatedAmt) > 0)
                        foreach ($getAllocatedAmt as $getAllocatedAmt) {
                            $allocatedAmt = $allocatedAmt + $getAllocatedAmt->Allocated_Amount;
                        }
                    //update fund balance on each allocation

                    TbCmnFundMaster::where('Pkid', $fundId)
                        ->update([
                            'Fund_Balance' => ($balanceFund - $dataUI->$allocated),
                            'Record_Update_Date' => new \DateTime(),
                            'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'),
                            'Updated_By' => $request->session()->get('id')
                        ]);

                    //update allocated amount on tb_cmn_fund_allocation_master
                    $getAllocationAmt = TbCmnFundAllocationMaster::where('Pkid', $allocationMasterPk)->first();
                    $alocationAmt = 0;
                    if (isset($getAllocationAmt->Total_Allocated_Amount)) {
                        $allocatedAmt = $getAllocationAmt->Total_Allocated_Amount;
                    }
                    TbCmnFundAllocationMaster::where('Pkid', $allocationMasterPk)
                        ->update([
                            'Total_Allocated_Amount' => ($allocatedAmt + $dataUI->$allocated),
                            'Record_Update_Date' => new \DateTime(),
                            'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'),
                            'Updated_By' => $request->session()->get('id')
                        ]);
                    //update claim status from 0 to 1 on each allocation

                    $Claim_Balance_Amount = (float)($tbSmsClaimMaster->Claim_Balance_Amount) - ($dataUI->$allocated);
                    TbSmsClaimMaster::where('Pkid', $value)
                        ->update([
                            'Claim_Status' => 1,
                            'Claim_Balance_Amount' => $Claim_Balance_Amount,
                            'Record_Update_Date' => new \DateTime(),
                            'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'),
                            'Updated_By' => $request->session()->get('id')
                        ]);
                }
            } else {
                $allocated = 'allocated_amount' . $dataUI->check_id;
                $tbSmsClaimMaster = TbSmsClaimMaster::where('Pkid', $dataUI->check_id)->firstOrFail();
                $Benificiary_Id_Fk =  $tbSmsClaimMaster->Benificiary_Id_Fk;
                $tbCmnBenificiaryMaster = TbCmnBenificiaryMaster::where('Pkid', $Benificiary_Id_Fk)->firstOrFail();
                $Address_Id_Fk =  $tbCmnBenificiaryMaster->Address_Id_Fk;
                $tbCmnAddress = TbCmnAddress::where('Pkid', $Address_Id_Fk)->firstOrFail();
                //add the allocation transaction 
                if (empty($dataUI->$allocated) or ($dataUI->$allocated < 1)) {
                    return response()->json(["status" => 'failed', "message" => "Alert! Enter allocation amount"]);
                }

                $saveFundAllocationTxn = new TbCmnFundAllocationTxn();
                $saveFundAllocationTxn->Allocation_Master_Id_Fk = $allocationMasterPk;
                $saveFundAllocationTxn->Claim_Id_Fk = $dataUI->check_id;
                $saveFundAllocationTxn->Claimed_Amount = $tbSmsClaimMaster->Claim_Amount;
                $saveFundAllocationTxn->Allocated_Amount = $dataUI->$allocated;
                $saveFundAllocationTxn->Paid_Amount = '0.00';
                $saveFundAllocationTxn->From_Date = $tbSmsClaimMaster->Claim_From_Date;
                $saveFundAllocationTxn->To_Date = $tbSmsClaimMaster->Claim_To_Date;
                $saveFundAllocationTxn->Scheme_Id_Fk = $scheme_id;
                $saveFundAllocationTxn->Fund_Id_Fk = $fundId;
                $saveFundAllocationTxn->State_Id_Fk = $tbCmnAddress->State_Code;
                $result = (new CommonController)->insertDefaultColumns($request, $saveFundAllocationTxn);
                $addFundAllocationTxn = $saveFundAllocationTxn->save();

                $tbCmnFundMaster = TbCmnFundMaster::where('Pkid', $fundId)
                    ->where('Scheme_Id', $scheme_id)->firstOrFail();

                $balanceFund = $tbCmnFundMaster->Fund_Balance;
                $getAllocatedAmt = TbCmnFundAllocationMaster::where('Fund_Id_Fk', $fundId)
                    ->where('Scheme_Id_Fk', $scheme_id)->get();

                $allocatedAmt = 0;
                if (count($getAllocatedAmt) > 0)
                    foreach ($getAllocatedAmt as $getAllocatedAmt) {
                        $allocatedAmt = $allocatedAmt + $getAllocatedAmt->Allocated_Amount;
                    }

                //update Allocated Amount as sum of all allocated funds
                $TbCmnFundAllocationMaster = new TbCmnFundAllocationMaster();
                TbCmnFundAllocationMaster::where(['Pkid' => $allocationMasterPk, 'Scheme_Id_Fk' => $scheme_id])
                    ->update([
                        'Total_Allocated_Amount' => ($allocatedAmt + $dataUI->$allocated),
                        'Record_Update_Date' => new \DateTime(),
                        'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'),
                        'Updated_By' => $request->session()->get('id')
                    ]);

                //update fund balance on each allocation
                TbCmnFundMaster::where('Pkid', $fundId)
                    ->update([
                        'Fund_Balance' => ($balanceFund - $dataUI->$allocated), 'Record_Update_Date' => new \DateTime(),
                        'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'),
                        'Updated_By' => $request->session()->get('id')
                    ]);

                //update claim status from 0 to 1 on each allocation
                $Claim_Balance_Amount = (float)($tbSmsClaimMaster->Claim_Balance_Amount) - ($dataUI->$allocated);
                TbSmsClaimMaster::where('Pkid', $dataUI->check_id)
                    ->update([
                        'Claim_Status' => 1,
                        'Claim_Balance_Amount' => $Claim_Balance_Amount,
                        'Record_Update_Date' => new \DateTime(),
                        'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'),
                        'Updated_By' => $request->session()->get('id')
                    ]);
            }
            $status = "success";
            DB::commit();
        } catch (\Exception $ex) {
            throw $ex;
            $status = "failed";
            DB::rollback();
        }
        $remarks      =       TbCmnReasonMaster::all()->sortBy('Reason_Details');
        $subsidyMaster      =       TbCmnSchemeMaster::all()->sortBy('Scheme_Name');
        $bankMaster      =       TbCmnNedfiBankMaster::all()->sortBy('Bank_Name');
        $stateMaster = TbCmnStateMaster::all()->sortBy('State_Name');
        $query = DB::table('tb_cmn_fund_master')->join('tb_cmn_scheme_master', 'tb_cmn_fund_master.Scheme_Id', '=', 'tb_cmn_scheme_master.Pkid')
            ->where('tb_cmn_scheme_master.Pkid', $scheme_id)
            ->where('tb_cmn_fund_master.Record_Active_Flag', '1')
            ->where('tb_cmn_fund_master.Pkid', $fundId);
        $fundMaster = $query->get(['tb_cmn_fund_master.*', 'tb_cmn_scheme_master.Scheme_Name', 'tb_cmn_fund_master.Scheme_Id']);

        $query1 = DB::table('tb_sms_claim_master')->join('tb_cmn_benificiary_master', 'tb_sms_claim_master.Benificiary_Id_Fk', '=', 'tb_cmn_benificiary_master.Pkid')
            ->join('tb_cmn_scheme_master', 'tb_sms_claim_master.Scheme_Id_Fk', '=', 'tb_cmn_scheme_master.Pkid')
            ->join('tb_cmn_address', 'tb_cmn_benificiary_master.Address_Id_Fk', '=', 'tb_cmn_address.Pkid')
            ->join('tb_cmn_fund_master', 'tb_cmn_fund_master.Scheme_Id', '=', 'tb_cmn_scheme_master.Pkid')
            ->where('tb_cmn_scheme_master.Pkid', $scheme_id)
            ->where('tb_cmn_fund_master.Pkid', $fundId)
            ->where('tb_sms_claim_master.Audit_Status', [2])
            //->where('tb_sms_claim_master.Claim_Status', [0])
            ->where('tb_sms_claim_master.Claim_Balance_Amount', '>', 0)
            ->where('tb_sms_claim_master.Record_Active_Flag', '1')
            ->whereIn('tb_cmn_benificiary_master.Status_Id', [5])
            ->whereIn('tb_sms_claim_master.Status_Id', [5]);

        // $result = (new CommonController)->insertDefaultColumns($request, $saveFundAllocationTxn);

        $claimBenificiaryList = $query1->get(['tb_sms_claim_master.*', 'tb_cmn_benificiary_master.Benificiary_Name', 'tb_cmn_scheme_master.Scheme_Name', 'tb_cmn_fund_master.Scheme_Id', 'tb_cmn_fund_master.Fund_Balance', 'tb_cmn_fund_master.Pkid AS Fund_Id']);

        $query2 = DB::table('tb_cmn_fund_allocation_master')->join('tb_cmn_fund_allocation_txn', 'tb_cmn_fund_allocation_master.Pkid', '=', 'tb_cmn_fund_allocation_txn.Allocation_Master_Id_Fk')
            ->join('tb_cmn_scheme_master', 'tb_cmn_fund_allocation_txn.Scheme_Id_Fk', '=', 'tb_cmn_scheme_master.Pkid')
            ->join('tb_sms_claim_master', 'tb_cmn_fund_allocation_txn.Claim_Id_Fk', '=', 'tb_sms_claim_master.Pkid')
            ->join('tb_cmn_benificiary_master', 'tb_sms_claim_master.Benificiary_Id_Fk', '=', 'tb_cmn_benificiary_master.Pkid')
            ->join('tb_cmn_address', 'tb_cmn_benificiary_master.Address_Id_Fk', '=', 'tb_cmn_address.Pkid')
            ->join('tb_cmn_fund_master', 'tb_cmn_fund_allocation_master.Fund_Id_Fk', '=', 'tb_cmn_fund_master.Pkid')
            ->where('tb_cmn_scheme_master.Pkid', $scheme_id)
            ->where('tb_cmn_fund_master.Pkid', $fundId)
            ->where('tb_cmn_fund_allocation_txn.Record_Active_Flag', '1')
            ->where('tb_cmn_fund_allocation_master.Record_Active_Flag', '1');

        $allocatedBenificiaryList = $query2->get(['tb_cmn_fund_allocation_master.*', 'tb_cmn_fund_allocation_txn.Pkid AS Allocation_Pk', 'tb_cmn_fund_allocation_txn.Allocated_Amount', 'tb_cmn_benificiary_master.Benificiary_Name', 'tb_cmn_scheme_master.Scheme_Name', 'tb_cmn_fund_master.Scheme_Id', 'tb_cmn_fund_master.Fund_Balance', 'tb_cmn_fund_master.Pkid AS Fund_Id', 'tb_cmn_fund_master.Sanction_Letter', 'tb_cmn_fund_master.Sanction_Amount', 'tb_sms_claim_master.Claim_Amount', 'tb_sms_claim_master.Claim_Id']);
        $html_view = view('fund-management-ui.fund-allocation.after-fund-allocation-list', compact('fundMaster', 'remarks', 'subsidyMaster', 'bankMaster', 'stateMaster', 'claimBenificiaryList', 'allocatedBenificiaryList'))->render();

        $fundAllocation      =       TbCmnFundAllocationMaster::join('tb_cmn_status_master', 'tb_cmn_fund_allocation_master.Status_Id_Fk', '=', 'tb_cmn_status_master.Pkid')
            ->join('tb_cmn_scheme_master', 'tb_cmn_fund_allocation_master.Scheme_Id_Fk', '=', 'tb_cmn_scheme_master.Pkid')
            ->join('tb_cmn_fund_master', 'tb_cmn_fund_allocation_master.Fund_Id_Fk', '=', 'tb_cmn_fund_master.Pkid')
            ->where('tb_cmn_fund_allocation_master.Total_Allocated_Amount', '>', 0)
            ->where('tb_cmn_fund_allocation_master.Record_Active_Flag', '=', 1)
            ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
            ->get(['tb_cmn_fund_allocation_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_fund_master.Sanction_Letter', 'tb_cmn_fund_master.Sanction_Amount', 'tb_cmn_scheme_master.Scheme_Name'])
            ->sortBy('Pkid');

        $html_view1 = view('fund-management-ui.fund-allocation.list-of-claim-allocation', compact('fundAllocation'))->render();


        if ($status == "success") {
            return response()->json(["status" => $status, "message" => "Success! Fund Has Been Allocated.", "body" => $html_view, "body1" => $html_view1]);
        } else {
            return response()->json(["status" => $status, "message" => "Alert! Fund Allocation Failed"]);
        }
    }
    // save approve fund allocation
    public function approveSubsidyFundAllocationEntry(Request $request)
    {
        DB::beginTransaction();
        try {
            $dataUI = json_decode($request->getContent());
            $approvalStatus = $dataUI->decision;
            $approvalDate = $dataUI->approval_date;
            $remarks = $dataUI->remarks;
            if (Is_Array($dataUI->check_id)) {
                foreach ($dataUI->check_id as $value) {
                    TbCmnFundAllocationMaster::where('Pkid', $value)
                        ->update([
                            'Status_Id_Fk' => $approvalStatus,
                            'Record_Update_Date' => new \DateTime(),
                            'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'),
                            'Updated_By' => $request->session()->get('id')
                        ]);
                    $saveCmnApproval = new TbCmnApproval();
                    $saveCmnApproval->Approval_Date = $approvalDate;
                    $saveCmnApproval->Status_Id_Fk = $approvalStatus;
                    $saveCmnApproval->Remarks = $remarks;
                    $saveCmnApproval->Module = 'Fund Allocation';
                    $saveCmnApproval->Record_Id_Fk =  $value;
                    $saveCmnApproval->save();
                }
            } else {
                TbCmnFundAllocationMaster::where('Pkid', $dataUI->check_id)
                    ->update([
                        'Status_Id_Fk' => $approvalStatus,
                        'Record_Update_Date' => new \DateTime(),
                        'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'),
                        'Updated_By' => $request->session()->get('id')
                    ]);
                $saveCmnApproval = new TbCmnApproval();
                $saveCmnApproval->Approval_Date = $approvalDate;
                $saveCmnApproval->Status_Id_Fk = $approvalStatus;
                $saveCmnApproval->Remarks = $remarks;
                $saveCmnApproval->Module = 'Fund Allocation';
                $saveCmnApproval->Record_Id_Fk =   $dataUI->check_id;
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
        $fundAllocation      =       TbCmnFundAllocationMaster::join('tb_cmn_status_master', 'tb_cmn_fund_allocation_master.Status_Id_Fk', '=', 'tb_cmn_status_master.Pkid')
            ->join('tb_cmn_scheme_master', 'tb_cmn_fund_allocation_master.Scheme_Id_Fk', '=', 'tb_cmn_scheme_master.Pkid')
            ->join('tb_cmn_fund_master', 'tb_cmn_fund_allocation_master.Fund_Id_Fk', '=', 'tb_cmn_fund_master.Pkid')
            ->where('tb_cmn_fund_allocation_master.Record_Active_Flag', '1')
            ->whereIn('tb_cmn_status_master.Pkid', [2])
            ->get(['tb_cmn_fund_allocation_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_fund_master.Sanction_Letter', 'tb_cmn_fund_master.Sanction_Amount', 'tb_cmn_status_master.Pkid', 'tb_cmn_scheme_master.Scheme_Name'])
            ->sortBy('Pkid');

        $html_view = view('fund-management-ui.fund-allocation.after-fund-allocation-approval', compact('fundAllocation'))->render();
        if ($status == 'success') {
            return response()->json(["status" => $status, "message" => "Success! Fund Allocation " . $decision, "body" => $html_view]);
        } else {
            return response()->json(["status" => $status, "message" => "Alert!  Fund Allocation not " . $decision]);
        }
    }

    public function viewEditFundAllocation($allocation_ID, $mode)
    {
        $fund_id = null;
        $scheme_id = null;
        $allocationMaster = TbCmnFundAllocationMaster::where('Pkid', $allocation_ID)->get(['Scheme_Id_Fk', 'Fund_Id_Fk']);
        foreach ($allocationMaster as $allocationMaster) {
            $fund_id = $allocationMaster->Fund_Id_Fk;
            $scheme_id = $allocationMaster->Scheme_Id_Fk;
        }

        $query2 = DB::table('tb_cmn_fund_allocation_master')->join('tb_cmn_fund_allocation_txn', 'tb_cmn_fund_allocation_master.Pkid', '=', 'tb_cmn_fund_allocation_txn.Allocation_Master_Id_Fk')
            ->join('tb_cmn_scheme_master', 'tb_cmn_fund_allocation_txn.Scheme_Id_Fk', '=', 'tb_cmn_scheme_master.Pkid')
            ->join('tb_sms_claim_master', 'tb_cmn_fund_allocation_txn.Claim_Id_Fk', '=', 'tb_sms_claim_master.Pkid')
            ->join('tb_cmn_benificiary_master', 'tb_sms_claim_master.Benificiary_Id_Fk', '=', 'tb_cmn_benificiary_master.Pkid')
            ->join('tb_cmn_address', 'tb_cmn_benificiary_master.Address_Id_Fk', '=', 'tb_cmn_address.Pkid')
            ->join('tb_cmn_fund_master', 'tb_cmn_fund_allocation_master.Fund_Id_Fk', '=', 'tb_cmn_fund_master.Pkid')
            ->where('tb_cmn_scheme_master.Pkid', $scheme_id)
            ->where('tb_cmn_fund_master.Pkid', $fund_id)
            ->where('tb_cmn_fund_allocation_master.Record_Active_Flag', '1')
            ->where('tb_cmn_fund_allocation_txn.Record_Active_Flag', '1');

        $allocatedBenificiaryList = $query2->get(['tb_cmn_fund_allocation_master.*', 'tb_cmn_fund_allocation_txn.Allocated_Amount', 'tb_cmn_benificiary_master.Benificiary_Name', 'tb_cmn_scheme_master.Scheme_Name', 'tb_cmn_fund_master.Scheme_Id', 'tb_cmn_fund_master.Fund_Balance', 'tb_cmn_fund_master.Pkid AS Fund_Id', 'tb_cmn_fund_master.Sanction_Letter', 'tb_cmn_fund_master.Sanction_Amount', 'tb_sms_claim_master.Claim_Amount', 'tb_sms_claim_master.Claim_Id']);
        if ($mode == 'EDT') {
            $accessBoolean = (new CommonController)->checkAccessRightToController('viewEditFundAllocation');
            if ($accessBoolean) {
                $html_view = view('fund-management-ui.fund-allocation.view-fund-allocation-details', compact('allocatedBenificiaryList', 'mode'))->render();
                return response()->json(["status" => "success", "body" => $html_view]);
            } else {
                $html_view = view('pages.error-pages.access-deny-modal')->render();
                return response()->json(["status" => "success", "body" => $html_view]);
            }
        } else {
            $html_view = view('fund-management-ui.fund-allocation.view-fund-allocation-details', compact('allocatedBenificiaryList', 'mode'))->render();
            return response()->json(["status" => "success", "body" => $html_view]);
        }
    }
    public function tableLayout()
    {
        $subsidyFund      =       TbCmnFundMaster::join('tb_cmn_status_master', 'tb_cmn_fund_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->join('tb_cmn_scheme_master', 'tb_cmn_fund_master.Scheme_Id', '=', 'tb_cmn_scheme_master.Pkid')
            ->where('tb_cmn_fund_master.Fund_Balance', '>', 0)
            ->where('tb_cmn_fund_master.Record_Active_Flag', '1')
            ->whereIn('tb_cmn_status_master.Pkid', [5])
            ->get(['tb_cmn_fund_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_scheme_master.Scheme_Name', 'tb_cmn_scheme_master.Pkid AS Scheme_Pk'])
            ->sortBy('Pkid');
        return view('layout.tables', compact('subsidyFund'));
    }

    // -------------- [ Delete post ] ---------------
    public function destroyFundAllocation(Request $request, $allocation_id)
    {
        $accessBoolean = (new CommonController)->checkAccessRightToController('destroyFundAllocation');
        if ($accessBoolean) {
            DB::beginTransaction();
            $status = "success";
            try {
                $claimId = TbCmnFundAllocationTxn::where("Pkid", $allocation_id)->first();
                $deleteFundAllocation    =       TbCmnFundAllocationTxn::where("Pkid", $allocation_id)
                    ->update([
                        'Record_Active_Flag' => '0',
                        'Record_Update_Date' => new \DateTime(),
                        'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'),
                        'Updated_By' => $request->session()->get('id')
                    ]);
                $claimMaster = TbSmsClaimMaster::where("Pkid", $claimId->Claim_Id_Fk)->first();
                $tableData = array(
                    'Claim_Balance_Amount' => (float)($claimMaster->Claim_Balance_Amount) + ($claimId->Allocated_Amount),
                    'Claim_Status' => '0', 'Record_Update_Date' => new \DateTime(),
                    'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'),
                    'Updated_By' => $request->session()->get('id')
                );
                $updateTable = TbSmsClaimMaster::where('Pkid',  $claimId->Claim_Id_Fk)
                    ->update($tableData);
                //update allocated amount on tb_cmn_fund_allocation_master
                $getAllocationAmt = TbCmnFundAllocationMaster::where('Pkid', $claimId->Allocation_Master_Id_Fk)->first();
                $alocationAmt = 0;
                if (isset($getAllocationAmt->Total_Allocated_Amount)) {
                    $allocatedAmt = $getAllocationAmt->Total_Allocated_Amount;
                }
                TbCmnFundAllocationMaster::where('Pkid', $claimId->Allocation_Master_Id_Fk)
                    ->update([
                        'Total_Allocated_Amount' => ($allocatedAmt - $claimId->Allocated_Amount),
                        'Record_Update_Date' => new \DateTime(),
                        'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'),
                        'Updated_By' => $request->session()->get('id')
                    ]);
                $getFundBalance = TbCmnFundMaster::where('Pkid', $claimId->Fund_Id_Fk)->first();
                TbCmnFundMaster::where('Pkid', $claimId->Fund_Id_Fk)
                    ->update([
                        'Fund_Balance' => ($getFundBalance->Fund_Balance + $claimId->Allocated_Amount),
                        'Record_Update_Date' => new \DateTime(),
                        'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'),
                        'Updated_By' => $request->session()->get('id')
                    ]);
                $status = "success";
                DB::commit();
            } catch (\Exception $ex) {
                throw $ex;
                $status = "failed";
                DB::rollback();
            }

            $remarks      =       TbCmnReasonMaster::all()
                ->whereIn('Status_Id', [5])
                ->where('Record_Active_Flag', '1')
                ->sortBy('Reason_Details');
            $subsidyMaster      =       TbCmnSchemeMaster::all()
                ->whereIn('Status_Id', [5])
                ->where('Record_Active_Flag', '1')
                ->sortBy('Scheme_Name');
            $bankMaster      =       TbCmnNedfiBankMaster::all()
                ->whereIn('Status_Id', [5])
                ->where('Record_Active_Flag', '1')
                ->sortBy('Bank_Name');
            $stateMaster = TbCmnStateMaster::all()->sortBy('State_Name');
            $query = DB::table('tb_cmn_fund_master')->join('tb_cmn_scheme_master', 'tb_cmn_fund_master.Scheme_Id', '=', 'tb_cmn_scheme_master.Pkid')
                ->where('tb_cmn_fund_master.Pkid', $claimId->Fund_Id_Fk)
                ->whereIn('tb_cmn_fund_master.Status_Id', [5])
                ->where('.tb_cmn_fund_master.Record_Active_Flag', '1');
            $fundMaster = $query->get(['tb_cmn_fund_master.*', 'tb_cmn_scheme_master.Scheme_Name', 'tb_cmn_fund_master.Scheme_Id']);

            $query1 = DB::table('tb_sms_claim_master')->join('tb_cmn_benificiary_master', 'tb_sms_claim_master.Benificiary_Id_Fk', '=', 'tb_cmn_benificiary_master.Pkid')
                ->join('tb_cmn_scheme_master', 'tb_sms_claim_master.Scheme_Id_Fk', '=', 'tb_cmn_scheme_master.Pkid')
                ->join('tb_cmn_address', 'tb_cmn_benificiary_master.Address_Id_Fk', '=', 'tb_cmn_address.Pkid')
                ->join('tb_cmn_fund_master', 'tb_cmn_fund_master.Scheme_Id', '=', 'tb_cmn_scheme_master.Pkid')
                ->where('tb_cmn_scheme_master.Pkid', $claimId->Scheme_Id_Fk)
                ->where('tb_cmn_fund_master.Pkid', $claimId->Fund_Id_Fk)
                ->where('tb_sms_claim_master.Audit_Status', [2])
                //->where('tb_sms_claim_master.Claim_Status', [0])
                ->where('tb_sms_claim_master.Claim_Balance_Amount', '>', 0)
                ->where('tb_sms_claim_master.Record_Active_Flag', '1')
                ->whereIn('tb_cmn_benificiary_master.Status_Id', [5])
                ->whereIn('tb_sms_claim_master.Status_Id', [5]);

            $claimBenificiaryList = $query1->get(['tb_sms_claim_master.*', 'tb_cmn_benificiary_master.Benificiary_Name', 'tb_cmn_scheme_master.Scheme_Name', 'tb_cmn_fund_master.Scheme_Id', 'tb_cmn_fund_master.Fund_Balance', 'tb_cmn_fund_master.Pkid AS Fund_Id']);

            $query2 = DB::table('tb_cmn_fund_allocation_master')->join('tb_cmn_fund_allocation_txn', 'tb_cmn_fund_allocation_master.Pkid', '=', 'tb_cmn_fund_allocation_txn.Allocation_Master_Id_Fk')
                ->join('tb_cmn_scheme_master', 'tb_cmn_fund_allocation_txn.Scheme_Id_Fk', '=', 'tb_cmn_scheme_master.Pkid')
                ->join('tb_sms_claim_master', 'tb_cmn_fund_allocation_txn.Claim_Id_Fk', '=', 'tb_sms_claim_master.Pkid')
                ->join('tb_cmn_benificiary_master', 'tb_sms_claim_master.Benificiary_Id_Fk', '=', 'tb_cmn_benificiary_master.Pkid')
                ->join('tb_cmn_address', 'tb_cmn_benificiary_master.Address_Id_Fk', '=', 'tb_cmn_address.Pkid')
                ->join('tb_cmn_fund_master', 'tb_cmn_fund_allocation_master.Fund_Id_Fk', '=', 'tb_cmn_fund_master.Pkid')
                ->where('tb_cmn_scheme_master.Pkid', $claimId->Scheme_Id_Fk)
                ->where('tb_cmn_fund_master.Pkid', $claimId->Fund_Id_Fk)
                ->where('tb_cmn_fund_allocation_txn.Record_Active_Flag', '1')
                ->where('tb_cmn_fund_allocation_master.Record_Active_Flag', '1');

            $allocatedBenificiaryList = $query2->get(['tb_cmn_fund_allocation_master.*', 'tb_cmn_fund_allocation_txn.Pkid AS Allocation_Pk', 'tb_cmn_fund_allocation_txn.Allocated_Amount', 'tb_cmn_benificiary_master.Benificiary_Name', 'tb_cmn_scheme_master.Scheme_Name', 'tb_cmn_fund_master.Scheme_Id', 'tb_cmn_fund_master.Fund_Balance', 'tb_cmn_fund_master.Pkid AS Fund_Id', 'tb_cmn_fund_master.Sanction_Letter', 'tb_cmn_fund_master.Sanction_Amount', 'tb_sms_claim_master.Claim_Amount', 'tb_sms_claim_master.Claim_Id']);

            $html_view = view('fund-management-ui.fund-allocation.after-fund-allocation-list', compact('fundMaster', 'remarks', 'subsidyMaster', 'bankMaster', 'stateMaster', 'claimBenificiaryList', 'allocatedBenificiaryList'))->render();

            $fundAllocation      =       TbCmnFundAllocationMaster::join('tb_cmn_status_master', 'tb_cmn_fund_allocation_master.Status_Id_Fk', '=', 'tb_cmn_status_master.Pkid')
                ->join('tb_cmn_scheme_master', 'tb_cmn_fund_allocation_master.Scheme_Id_Fk', '=', 'tb_cmn_scheme_master.Pkid')
                ->join('tb_cmn_fund_master', 'tb_cmn_fund_allocation_master.Fund_Id_Fk', '=', 'tb_cmn_fund_master.Pkid')
                ->where('tb_cmn_fund_allocation_master.Total_Allocated_Amount', '>', 0)
                ->where('tb_cmn_fund_allocation_master.Record_Active_Flag', '=', 1)
                ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
                ->get(['tb_cmn_fund_allocation_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_fund_master.Sanction_Letter', 'tb_cmn_fund_master.Sanction_Amount', 'tb_cmn_scheme_master.Scheme_Name'])
                ->sortBy('Pkid');
            $html_view1 = view('fund-management-ui.fund-allocation.list-of-claim-allocation', compact('fundAllocation'))->render();

            if ($status == "success") {
                return response()->json(["status" => $status, "message" => "Success! Fund allocation deleted", "body" => $html_view, "body1" => $html_view1]);
            } else {
                return response()->json(["status" => $status, "message" => "Alert! Fund allocation not deleted"]);
            }
        } else {
            $html_view = view('pages.error-pages.access-deny-modal')->render();
            return response()->json(["status" => "access_deny", "body" => $html_view]);
        }
    }

    // -------------- [ Delete post ] ---------------
    public function destroyFundAllocationMaster(Request $request, $allocation_id)
    {
        $accessBoolean = (new CommonController)->checkAccessRightToController('destroyFundAllocation');
        if ($accessBoolean) {
            DB::beginTransaction();
            $status = "success";
            try {

                // Roll back all the data to previous state as master allocation is deleted 
                TbCmnFundAllocationTxn::where("Allocation_Master_Id_Fk", $allocation_id)
                    ->update([
                        'Record_Active_Flag' => '0',
                        'Record_Update_Date' => new \DateTime(),
                        'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'),
                        'Updated_By' => $request->session()->get('id')
                    ]);

                // $claimId = TbCmnFundAllocationTxn::where("Pkid", $allocation_id)->first();
                // $claimMaster = TbSmsClaimMaster::where("Pkid", $claimId->Claim_Id_Fk)->first();
                // 'Claim_Balance_Amount' => (float)($claimMaster->Claim_Balance_Amount) + ($claimId->Allocated_Amount),
                $claimFkList = TbCmnFundAllocationTxn::where("Allocation_Master_Id_Fk", $allocation_id)->get();
                foreach ($claimFkList as $claimFkList) {
                    TbSmsClaimMaster::where("Pkid", $claimFkList->Claim_Id_Fk)
                        ->update([
                            'Claim_Status' => '0',
                            'Record_Update_Date' => new \DateTime(),
                            'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'),
                            'Updated_By' => $request->session()->get('id')
                        ]);
                }
                //rollback fund amoun to its original value.
                $getTotalAllocationAmt = TbCmnFundAllocationMaster::where("Pkid", $allocation_id)->first();
                $getFundBalance = TbCmnFundMaster::where('Pkid', $getTotalAllocationAmt->Fund_Id_Fk)->first();
                TbCmnFundMaster::where('Pkid', $getTotalAllocationAmt->Fund_Id_Fk)
                    ->update([
                        'Fund_Balance' => ($getFundBalance->Fund_Balance + $getTotalAllocationAmt->Total_Allocated_Amount),
                        'Record_Update_Date' => new \DateTime(),
                        'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'),
                        'Updated_By' => $request->session()->get('id')
                    ]);
                //delete tb_cmn_fund_allocation_master
                TbCmnFundAllocationMaster::where("Pkid", $allocation_id)
                    ->update([
                        'Record_Active_Flag' => '0',
                        'Total_Allocated_Amount' => '0.00',
                        'Record_Update_Date' => new \DateTime(),
                        'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'),
                        'Updated_By' => $request->session()->get('id')
                    ]);
                $status = "success";
                DB::commit();
            } catch (\Exception $ex) {
                throw $ex;
                $status = "failed";
                DB::rollback();
            }
            $fundAllocation      =       TbCmnFundAllocationMaster::join('tb_cmn_status_master', 'tb_cmn_fund_allocation_master.Status_Id_Fk', '=', 'tb_cmn_status_master.Pkid')
                ->join('tb_cmn_scheme_master', 'tb_cmn_fund_allocation_master.Scheme_Id_Fk', '=', 'tb_cmn_scheme_master.Pkid')
                ->join('tb_cmn_fund_master', 'tb_cmn_fund_allocation_master.Fund_Id_Fk', '=', 'tb_cmn_fund_master.Pkid')
                ->where('tb_cmn_fund_allocation_master.Total_Allocated_Amount', '>', 0)
                ->where('tb_cmn_fund_allocation_master.Record_Active_Flag', '=', 1)
                ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
                ->get(['tb_cmn_fund_allocation_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_fund_master.Sanction_Letter', 'tb_cmn_fund_master.Sanction_Amount', 'tb_cmn_scheme_master.Scheme_Name'])
                ->sortBy('Pkid');

            $html_view = view('fund-management-ui.fund-allocation.list-of-claim-allocation', compact('fundAllocation'))->render();
            if ($status == "success") {
                return response()->json(["status" => $status, "message" => "Success! Fund allocation deleted", "body" => $html_view]);
            } else {
                return response()->json(["status" => $status, "message" => "Alert! Fund allocation not deleted"]);
            }
        } else {
            $html_view = view('pages.error-pages.access-deny-modal')->render();
            return response()->json(["status" => "access_deny", "body" => $html_view]);
        }
    }

    // Approval record
    public function fundAllocationMaterApproval(Request $request, $id)
    {
        $dataUI = json_decode($request->getContent());
        DB::beginTransaction();
        try {
            $allocationMaster = TbCmnFundAllocationMaster::where('Pkid', $id)->first();

            $tableData = array(
                'Status_Id_Fk' => '2',
                'Record_Update_Date' => new \DateTime(),
                'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'),
                'Updated_By' => $request->session()->get('id')
            );
            $whereCondition = [
                ['Fund_Id_Fk', '=', $allocationMaster->Fund_Id_Fk],
                ['Scheme_Id_Fk', '=',  $allocationMaster->Scheme_Id_Fk],
                ['Record_Active_Flag', '=',  '1'],
                ['Total_Allocated_Amount', '>',  0],
            ];
            $updateTable = TbCmnFundAllocationMaster::where($whereCondition)
                ->whereIn('Status_Id_Fk', [1, 3])
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
        $fundAllocation      =       TbCmnFundAllocationMaster::join('tb_cmn_status_master', 'tb_cmn_fund_allocation_master.Status_Id_Fk', '=', 'tb_cmn_status_master.Pkid')
            ->join('tb_cmn_scheme_master', 'tb_cmn_fund_allocation_master.Scheme_Id_Fk', '=', 'tb_cmn_scheme_master.Pkid')
            ->join('tb_cmn_fund_master', 'tb_cmn_fund_allocation_master.Fund_Id_Fk', '=', 'tb_cmn_fund_master.Pkid')
            ->where('tb_cmn_fund_allocation_master.Total_Allocated_Amount', '>', 0)
            ->where('tb_cmn_fund_allocation_master.Record_Active_Flag', '=', 1)
            ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
            ->get(['tb_cmn_fund_allocation_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_fund_master.Sanction_Letter', 'tb_cmn_fund_master.Sanction_Amount', 'tb_cmn_scheme_master.Scheme_Name'])
            ->sortBy('Pkid');

        $html_view = view('fund-management-ui.fund-allocation.list-of-claim-allocation', compact('fundAllocation'))->render();
        if ($status == "success") {
            return response()->json(["status" => $status, "message" => "Success! Fund allocation submitted for approval", "body" => $html_view]);
        } else {
            return response()->json(["status" => $status, "message" => "Alert! Fund allocation not submitted for approval"]);
        }
    }
}
