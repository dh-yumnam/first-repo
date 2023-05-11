<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TbCmnStatusMaster;
use App\Models\TbSmsClaimMaster;
use App\Models\TbCmnFundAllocationMaster;
use App\Models\TbSmsDisbursementDetail;
use App\Models\TbCmnBenificiaryMaster;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\WildlifePopulation;


class DashboardController extends Controller
{
    public function index()
    {
        return view('pages.user-pages.login')->render();
        // $html_view = view('pages.user-pages.login')->render();
        //return response()->json(["status" => "success", "body" => $html_view]);
    }
    public function sessionExpire()
    {
        return view('pages.user-pages.session-expire')->render();
    }
    public function Dashboard()
    {
        try {

            $finYear = DB::table('tb_sms_claim_master')->select(DB::raw("year(tb_sms_claim_master.Received_Date) as Received_Date"))->distinct()->get()->sortByDesc('Received_Date');
            $finYear1 = DB::table('tb_sms_claim_master')->select(DB::raw("year(tb_sms_claim_master.Received_Date) as Received_Date"))->distinct()->get()->sortByDesc('Received_Date');
            $totalBenificiary = TbCmnBenificiaryMaster::join('tb_cmn_status_master', 'tb_cmn_benificiary_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
                ->where('tb_cmn_status_master.Pkid', '5')->count('*');
            $totalClaim = TbSmsClaimMaster::join('tb_cmn_status_master', 'tb_sms_claim_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
                ->where('tb_cmn_status_master.Pkid', '5')->sum('tb_sms_claim_master.Claim_Amount');
            $totalAllocation = TbCmnFundAllocationMaster::join('tb_cmn_status_master', 'tb_cmn_fund_allocation_master.Status_Id_Fk', '=', 'tb_cmn_status_master.Pkid')
                ->where('tb_cmn_status_master.Pkid', '5')->sum('tb_cmn_fund_allocation_master.Total_Allocated_Amount');
            $totalDisbursement =    TbSmsDisbursementDetail::join('tb_cmn_status_master', 'tb_sms_disbursement_details.Status_Id_Fk', '=', 'tb_cmn_status_master.Pkid')
                ->where('tb_cmn_status_master.Pkid', '5')->sum('tb_sms_disbursement_details.Disbursement_Amount');

            $stateWiseClaim = TbSmsClaimMaster::join('tb_cmn_status_master', 'tb_sms_claim_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
                ->join('tb_cmn_benificiary_master', 'tb_cmn_benificiary_master.Pkid', '=', 'tb_sms_claim_master.Benificiary_Id_Fk')
                ->join('tb_cmn_address', 'tb_cmn_address.Pkid', '=', 'tb_cmn_benificiary_master.Address_Id_Fk')
                ->join('tb_cmn_state_master', 'tb_cmn_state_master.Pkid', '=', 'tb_cmn_address.State_Code')
                ->leftjoin('tb_cmn_fund_allocation_txn', 'tb_cmn_fund_allocation_txn.Claim_Id_Fk', '=', 'tb_sms_claim_master.Pkid')
                ->select(DB::raw("COALESCE(SUM(tb_cmn_fund_allocation_txn.Claimed_Amount),0) as Claim"), DB::raw("COALESCE(SUM(tb_cmn_fund_allocation_txn.Allocated_Amount),0) as Allocated_Amount"), 'tb_cmn_state_master.State_Name','tb_cmn_state_master.Pkid')
                ->wherein('tb_cmn_status_master.Pkid', [5])
                ->orderBy(DB::raw("tb_cmn_state_master.State_Name"))
                ->groupBy(DB::raw("tb_cmn_state_master.Pkid,tb_cmn_state_master.State_Name"))
                ->get();

            $stateWiseDisbursement = TbSmsClaimMaster::join('tb_cmn_status_master', 'tb_sms_claim_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
                ->join('tb_cmn_benificiary_master', 'tb_cmn_benificiary_master.Pkid', '=', 'tb_sms_claim_master.Benificiary_Id_Fk')
                ->join('tb_cmn_address', 'tb_cmn_address.Pkid', '=', 'tb_cmn_benificiary_master.Address_Id_Fk')
                ->join('tb_cmn_state_master', 'tb_cmn_state_master.Pkid', '=', 'tb_cmn_address.State_Code')
                ->leftjoin('tb_sms_disbursement_details', 'tb_sms_disbursement_details.Claim_Id_Fk', '=', 'tb_sms_claim_master.Pkid')
                ->select(DB::raw("COALESCE(SUM(tb_sms_disbursement_details.Disbursement_Amount),0) as Disbursement_Amount"), DB::raw("COALESCE(SUM(tb_sms_disbursement_details.Allocated_Amount),0) as Allocated_Amount"), 'tb_cmn_state_master.State_Name','tb_cmn_state_master.Pkid')
                ->wherein('tb_cmn_status_master.Pkid', [5])
                ->orderBy(DB::raw("tb_cmn_state_master.State_Name"))
                ->groupBy(DB::raw("tb_cmn_state_master.Pkid,tb_cmn_state_master.State_Name"))
                ->get();

            $claimDisbursement = TbSmsClaimMaster::leftjoin('tb_sms_disbursement_details', 'tb_sms_claim_master.Pkid', '=', 'tb_sms_disbursement_details.Claim_Id_Fk')->select(
                DB::raw("year(tb_sms_claim_master.Received_Date) as Year"),
                DB::raw("COALESCE(SUM(tb_sms_claim_master.Claim_Amount),0) as Claim"),
                DB::raw("COALESCE(SUM(tb_sms_disbursement_details.Disbursement_Amount), 0) AS Disbursement")
            )
                ->wherein('tb_sms_disbursement_details.Status_Id_Fk', [5])
                ->orderBy(DB::raw("YEAR(tb_sms_claim_master.Received_Date)"))
                ->groupBy(DB::raw("YEAR(tb_sms_claim_master.Received_Date)"))
                // ->groupBy(DB::raw("tb_sms_claim_master.Pkid"))
                ->get();
            $res[] = ['Year', 'Claim', 'Disbursement'];
            foreach ($claimDisbursement as $key => $val) {
                $disbursement = 0;
                $claim = 0;
                if ((int)$val->Disbursement > 0) {
                    $disbursement = $val->Disbursement;
                } else {
                    $disbursement = (int) 0;
                }
                if ((int)$val->Claim > 0) {
                    $claim = $val->Claim;
                } else {
                    $claim = 0;
                }
                $res[++$key] = [(int)$val->Year, (int)$claim, (int)$disbursement];
            }

            // $population = WildlifePopulation::select(
            //     DB::raw("year(created_at) as year"),
            //     DB::raw("SUM(Claim) as Claim"),
            //     DB::raw("SUM(Disbursement) as Disbursement")
            // )
            //     ->orderBy(DB::raw("YEAR(created_at)"))
            //     ->groupBy(DB::raw("YEAR(created_at)"))
            //     ->get();

            // $res[] = ['Year', 'Disbursement', 'Claim'];
            // foreach ($population as $key => $val) {
            //     $res[++$key] = [$val->year, (int)$val->Disbursement, (int)$val->Claim];
            // }
            $claimDisbursement = json_encode($res);
            return view('dashboard', compact('stateWiseDisbursement', 'stateWiseClaim', 'claimDisbursement', 'finYear', 'finYear1', 'totalBenificiary', 'totalClaim', 'totalAllocation', 'totalDisbursement'))->render();
            // return response()->json(["status" => "dashboard", "body" => $html_view]);
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
    public function DashboardAction()
    {
        try {
            $finYear = DB::table('tb_sms_claim_master')->select(DB::raw("year(tb_sms_claim_master.Received_Date) as Received_Date"))->distinct()->get()->sortByDesc('Received_Date');
            $finYear1 = DB::table('tb_sms_claim_master')->select(DB::raw("year(tb_sms_claim_master.Received_Date) as Received_Date"))->distinct()->get()->sortByDesc('Received_Date');
            $totalBenificiary = TbCmnBenificiaryMaster::join('tb_cmn_status_master', 'tb_cmn_benificiary_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
                ->where('tb_cmn_status_master.Pkid', '5')->count('*');
            $totalClaim = TbSmsClaimMaster::join('tb_cmn_status_master', 'tb_sms_claim_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
                ->where('tb_cmn_status_master.Pkid', '5')->sum('tb_sms_claim_master.Claim_Amount');
            $totalAllocation = TbCmnFundAllocationMaster::join('tb_cmn_status_master', 'tb_cmn_fund_allocation_master.Status_Id_Fk', '=', 'tb_cmn_status_master.Pkid')
                ->where('tb_cmn_status_master.Pkid', '5')->sum('tb_cmn_fund_allocation_master.Total_Allocated_Amount');
            $totalDisbursement =    TbSmsDisbursementDetail::join('tb_cmn_status_master', 'tb_sms_disbursement_details.Status_Id_Fk', '=', 'tb_cmn_status_master.Pkid')
                ->where('tb_cmn_status_master.Pkid', '5')->sum('tb_sms_disbursement_details.Disbursement_Amount');

            $stateWiseClaim = TbSmsClaimMaster::join('tb_cmn_status_master', 'tb_sms_claim_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
                ->join('tb_cmn_benificiary_master', 'tb_cmn_benificiary_master.Pkid', '=', 'tb_sms_claim_master.Benificiary_Id_Fk')
                ->join('tb_cmn_address', 'tb_cmn_address.Pkid', '=', 'tb_cmn_benificiary_master.Address_Id_Fk')
                ->join('tb_cmn_state_master', 'tb_cmn_state_master.Pkid', '=', 'tb_cmn_address.State_Code')
                ->join('tb_cmn_fund_allocation_txn', 'tb_cmn_fund_allocation_txn.Claim_Id_Fk', '=', 'tb_sms_claim_master.Pkid')
                ->select(DB::raw("COALESCE(SUM(tb_cmn_fund_allocation_txn.Claimed_Amount),0) as Claim"), DB::raw("COALESCE(SUM(tb_cmn_fund_allocation_txn.Allocated_Amount),0) as Allocated_Amount"), 'tb_cmn_state_master.State_Name')
                ->wherein('tb_cmn_status_master.Pkid', [5])
                ->orderBy(DB::raw("tb_cmn_state_master.State_Name"))
                ->groupBy(DB::raw("tb_cmn_state_master.Pkid,tb_cmn_state_master.State_Name"))
                ->get();

            $stateWiseDisbursement = TbSmsClaimMaster::join('tb_cmn_status_master', 'tb_sms_claim_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
                ->join('tb_cmn_benificiary_master', 'tb_cmn_benificiary_master.Pkid', '=', 'tb_sms_claim_master.Benificiary_Id_Fk')
                ->join('tb_cmn_address', 'tb_cmn_address.Pkid', '=', 'tb_cmn_benificiary_master.Address_Id_Fk')
                ->join('tb_cmn_state_master', 'tb_cmn_state_master.Pkid', '=', 'tb_cmn_address.State_Code')
                ->leftjoin('tb_sms_disbursement_details', 'tb_sms_disbursement_details.Claim_Id_Fk', '=', 'tb_sms_claim_master.Pkid')
                ->select(DB::raw("COALESCE(SUM(tb_sms_disbursement_details.Disbursement_Amount),0) as Disbursement_Amount"), DB::raw("COALESCE(SUM(tb_sms_disbursement_details.Allocated_Amount),0) as Allocated_Amount"), 'tb_cmn_state_master.State_Name')
                ->wherein('tb_cmn_status_master.Pkid', [5])
                ->orderBy(DB::raw("tb_cmn_state_master.State_Name"))
                ->groupBy(DB::raw("tb_cmn_state_master.Pkid,tb_cmn_state_master.State_Name"))
                ->get();

            $claimDisbursement = TbSmsClaimMaster::leftjoin('tb_sms_disbursement_details', 'tb_sms_claim_master.Pkid', '=', 'tb_sms_disbursement_details.Claim_Id_Fk')->select(
                DB::raw("year(tb_sms_claim_master.Received_Date) as Year"),
                DB::raw("COALESCE(SUM(tb_sms_claim_master.Claim_Amount),0) as Claim"),
                DB::raw("COALESCE(SUM(tb_sms_disbursement_details.Disbursement_Amount), 0) AS Disbursement")
            )
                ->wherein('tb_sms_disbursement_details.Status_Id_Fk', [5])
                ->orderBy(DB::raw("YEAR(tb_sms_claim_master.Received_Date)"))
                ->groupBy(DB::raw("YEAR(tb_sms_claim_master.Received_Date)"))
                // ->groupBy(DB::raw("tb_sms_claim_master.Pkid"))
                ->get();


            $res[] = ['Year', 'Claim', 'Disbursement'];
            foreach ($claimDisbursement as $key => $val) {
                $disbursement = 0;
                $claim = 0;
                if ((int)$val->Disbursement > 0) {
                    $disbursement = $val->Disbursement;
                } else {
                    $disbursement = (int) 0;
                }
                if ((int)$val->Claim > 0) {
                    $claim = $val->Claim;
                } else {
                    $claim = 0;
                }
                $res[++$key] = [(int)$val->Year, (int)$claim, (int)$disbursement];
            }
            // print_r($res);
            // exit;
            // $population = WildlifePopulation::select(
            //     DB::raw("year(created_at) as year"),
            //     DB::raw("SUM(Claim) as Claim"),
            //     DB::raw("SUM(Disbursement) as Disbursement")
            // )
            //     ->orderBy(DB::raw("YEAR(created_at)"))
            //     ->groupBy(DB::raw("YEAR(created_at)"))
            //     ->get();

            // $res[] = ['Year', 'Disbursement', 'Claim'];
            // foreach ($population as $key => $val) {
            //     $res[++$key] = [$val->year, (int)$val->Disbursement, (int)$val->Claim];
            // }
            $claimDisbursement = json_encode($res);
            $html_view = view('dashboard-page', compact('stateWiseDisbursement', 'stateWiseClaim', 'claimDisbursement', 'finYear', 'finYear1', 'totalBenificiary', 'totalClaim', 'totalAllocation', 'totalDisbursement'))->render();
            $html = view('common-ui.state-wise-claim', compact('stateWiseClaim'))->render();
            $html2 = view('common-ui.state-wise-disbursement', compact('stateWiseDisbursement'))->render();
            return response()->json(["status" => "success", "body" => $html_view]);
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}
