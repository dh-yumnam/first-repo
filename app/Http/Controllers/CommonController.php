<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TbCmnFinishGoodsMaster;
use App\Models\TbCmnFundMaster;
use App\Models\TbCmnNedfiBankMaster;
use App\Models\TbCmnReasonMaster;
use App\Models\TbCmnRawMaterialMaster;
use App\Models\TbCmnProductMaster;
use App\Models\TbCmnBenificiaryMaster;
use App\Models\TbCmnDistrictMaster;
use App\Models\TbCmnFundAllocationMaster;
use App\Models\TbCmnModuleMaster;
use App\Models\TbCmnStateMaster;
use App\Models\TbSmsClaimTxn;
use App\Models\TbCmnRoleAccess;
use App\Models\TbCmnRoleMaster;
use App\Models\TbCmnSchemeShortName;
use App\Models\TbCmnSchemeMaster;
use App\Models\TbSmsClaimMaster;
use App\Models\TbSmsDisbursementDetail;
use App\Models\TbChangePasswordHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

use Illuminate\Contracts\Session\Session as SessionSession;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Session\Session as HttpFoundationSessionSession;

class CommonController extends Controller
{
    public function fillDistrictOnStateChange($state_id)
    {
        $districtMaster      =      TbCmnDistrictMaster::where("State_Id_Fk", $state_id)->get();
        $html = view('common-ui.district-master-list', compact('districtMaster'))->render();
        //return view('benificiary-ui.benificiary-search-result');
        return response()->json(['status' => "success", 'body' => $html]);
    }

    public function getShortNameOnSchemChange($scheme_id)
    {
        try {
            $schemeObj      =      TbCmnSchemeMaster::where("tb_cmn_scheme_master.Pkid", $scheme_id)
                ->join('tb_cmn_scheme_short_name', 'tb_cmn_scheme_short_name.Pkid', '=', 'tb_cmn_scheme_master.Scheme_Short_Name_Id_Fk')
                ->get(['tb_cmn_scheme_short_name.Short_Name']);
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
        $shortName = '';
        foreach ($schemeObj as $schemeObj) {
            $shortName = $schemeObj->Short_Name;
        }
        // $html = view('common-ui.district-master-list', compact('shortName'))->render();
        //return view('benificiary-ui.benificiary-search-result');
        return response()->json(['status' => "success", 'body' => $shortName]);
    }


    public function fillAmountOnBankChange($bank_Id)
    {
        $totalAmount      =      TbCmnFundMaster::where("Bank_Account_Id", $bank_Id)->sum('Sanction_Amount');
        $claimedAount = TbCmnFundAllocationMaster::join('tb_cmn_fund_master', 'tb_cmn_fund_allocation_master.Fund_Id_Fk', '=', 'tb_cmn_fund_master.Pkid')
            ->where("tb_cmn_fund_master.Bank_Account_Id", $bank_Id)->sum('tb_cmn_fund_allocation_master.Total_Allocated_Amount');
        $balanceAmount = $totalAmount - $claimedAount;
        $html = view('common-ui.bank-balance-amount', compact('balanceAmount'))->render();
        //return view('benificiary-ui.benificiary-search-result');
        return response()->json(['status' => "success", 'body' => $html]);
    }

    public function cmnSaveSubmitForApproval(Request $request)
    {
        $conn = $this->em->getConnection(); // connection begin here
        $conn->beginTransaction();
        try {
            $dataUI = json_decode($request->getContent());
            $chkValue = array();
            $moduleName = $dataUI->module;
            $action = $dataUI->action;
            $chk = 'chk_' . $moduleName;
            $count1 = 0;
            if (is_array($dataUI->$chk)) {
                $count1 = count($dataUI->$chk);
            } elseif (empty($dataUI->$chk)) {
                $count1 = 0;
            } else {
                $count1 = 1;
            }
            if ($count1 === 1) {
                $chkValue[0] = $dataUI->$chk;
            } else if ($count1 > 1) {
                $chkValue = $dataUI->$chk;
            }

            if ($action == '3') {
                $statusFk =  config("constants.RETURN");
            } else if ($action == '4') {
                $statusFk =  config("constants.REJECTED");
            } else {
                $statusFk =  config("constants.APPROVED");
            }

            for ($countrow = 0; $countrow < $count1; $countrow++) {
                // $distributionDetails = $this->em->getRepository(FAMSConstant::ENTITY_DISTRIBUTION_INWARD_DETAILS)->find($chkValue[$countrow]);
                // $distributionDetails->setStatusFk($statusFk);
                // $distributionDetails->setRecordUpdateDate(new \DateTime('now'));
                // $inwardDetails = $this->em->getRepository(FAMSConstant::ENTITY_INWARD_DETAILS_MASTER)->find($distributionDetails->getFkInwardMaster()->getPkid());
                // $inwardDetails->setStatusFk($statusFk);
                // $inwardDetails->setRecordUpdateDate(new \DateTime('now'));
                // $this->em->flush();
            }
            // $inwardDetails = $this->em->getRepository(FAMSConstant::ENTITY_DISTRIBUTION_INWARD_DETAILS)->findBy(array('statusFk' => DocumentConstant::INWARD_DISTRIBUTIO
            // $this->htmlArr['secondHtml'] = $this->renderView(DocumentConstant::TWIG_DISTRIBUTION_APPROVE_LIST, array('inwardDetails' => $inwardDetails, 'mode' => 'VIW'));
            // $this->htmlArr['thirdHtml'] = $this->renderView(DocumentConstant::TWIG_DISTRIBUTION_APPROVE_FORM, array('tableName' => 'TbInwardDetailsMaster', 'module' => 'ApproveInwardForm'));
            $this->docMessage->setMessage('Approval process completed successfully!');
            if ($action == '2') {
                $this->docMessage->setMessage('Distribution rejected successfully!');
            }
            $this->docMessage->setSuccess(true);
            $conn->commit();
            $this->docMessage->setHtml($this->htmlArr);
        } catch (\Exception $ex) {
            $conn->rollback();
            $this->docMessage->setSuccess(false);
            $this->docMessage->setMessage($ex->getMessage());
            throw new \Exception($ex->getMessage());
        }
        $this->em->close();
        //return new Response($this->get(DocumentConstant::SERVICE_COMMON)->getJsonData($this->docMessage));
    }
    public function insertDefaultColumns($request, $TableName)
    {
        $TableName->Record_Active_Flag = '1';
        $TableName->Record_Insert_Date = new \DateTime();
        $TableName->Application_User_Ip_Address = $request->session()->get('CLIENT_IP');
        $TableName->Created_By = $request->session()->get('id');
        return true;
    }
    public function updateDefaultColumns($request, $TableName)
    {
        $TableName->Record_Update_Date = new \DateTime();
        $TableName->Application_User_Ip_Address = $request->session()->get('CLIENT_IP');
        $TableName->Updated_By = $request->session()->get('id');
        return true;
    }

    public function checkAccessRightToController($uniqueId)
    {
        // $show_session = Session::get('id');
        try {
            $arrayOFRole = TbCmnRoleAccess::join('tb_cmn_role_master', 'tb_cmn_role_master.Pkid', '=', 'tb_cmn_role_access.Role_Id_Fk')
                ->where('User_Id_Fk', Session::get('id'))->get(['tb_cmn_role_master.Controller_Path']);
            if (Session::get('USER_TYPE') == '1') {
                return true;
            } else {
                foreach ($arrayOFRole as $arrayOFRole) {
                    if ($arrayOFRole->Controller_Path == $uniqueId) {
                        return true;
                    }
                }
            }
            return false;
        } catch (\Exception $ex) {
            return false;
        }
    }

    public function fillRoleOnModuleChange($module_id)
    {
        $role      =      TbCmnRoleMaster::where("Module_Id_Fk", $module_id)->get();
        $html = view('common-ui.role-master-list', compact('role'))->render();
        //return view('benificiary-ui.benificiary-search-result');
        return response()->json(['status' => "success", 'body' => $html]);
    }

    public function getDashboardValueOnYearChange($year)
    {
        try {
            $finYear = DB::table('tb_sms_claim_master')->select(DB::raw("year(tb_sms_claim_master.Received_Date) as Received_Date"))->distinct()->get()->sortByDesc('Received_Date');
            $finYear1 = DB::table('tb_sms_claim_master')->select(DB::raw("year(tb_sms_claim_master.Received_Date) as Received_Date"))->distinct()->get()->sortByDesc('Received_Date');
            $totalBenificiary = TbCmnBenificiaryMaster::join('tb_cmn_status_master', 'tb_cmn_benificiary_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
                ->where('tb_cmn_status_master.Pkid', '5')
                ->where(DB::raw("year(tb_cmn_benificiary_master.Production_Date)"), $year)
                ->count('*');
            $totalClaim = TbSmsClaimMaster::join('tb_cmn_status_master', 'tb_sms_claim_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
                ->where('tb_cmn_status_master.Pkid', '5')
                ->where(DB::raw("year(tb_sms_claim_master.Received_Date)"), $year)
                ->sum('tb_sms_claim_master.Claim_Amount');
            $totalAllocation = TbCmnFundAllocationMaster::join('tb_cmn_status_master', 'tb_cmn_fund_allocation_master.Status_Id_Fk', '=', 'tb_cmn_status_master.Pkid')
                ->join('tb_cmn_fund_allocation_txn', 'tb_cmn_fund_allocation_txn.Allocation_Master_Id_Fk', '=', 'tb_cmn_fund_allocation_master.Pkid')
                ->join('tb_sms_claim_master', 'tb_cmn_fund_allocation_txn.Claim_Id_Fk', '=', 'tb_sms_claim_master.Pkid')
                ->where('tb_cmn_fund_allocation_master.Status_Id_Fk', '5')
                ->where('tb_cmn_fund_allocation_txn.Record_Active_Flag', '1')
                ->where(DB::raw("year(tb_sms_claim_master.Received_Date)"), $year)
                ->sum('tb_cmn_fund_allocation_txn.Paid_Amount');
            $totalDisbursement =    TbSmsDisbursementDetail::join('tb_cmn_status_master', 'tb_sms_disbursement_details.Status_Id_Fk', '=', 'tb_cmn_status_master.Pkid')
                ->where('tb_cmn_status_master.Pkid', '5')
                ->where(DB::raw("year(tb_sms_disbursement_details.Disbursement_Date)"), $year)
                ->sum('tb_sms_disbursement_details.Disbursement_Amount');

            $stateWiseClaim = TbSmsClaimMaster::join('tb_cmn_status_master', 'tb_sms_claim_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
                ->join('tb_cmn_benificiary_master', 'tb_cmn_benificiary_master.Pkid', '=', 'tb_sms_claim_master.Benificiary_Id_Fk')
                ->join('tb_cmn_address', 'tb_cmn_address.Pkid', '=', 'tb_cmn_benificiary_master.Address_Id_Fk')
                ->join('tb_cmn_state_master', 'tb_cmn_state_master.Pkid', '=', 'tb_cmn_address.State_Code')
                ->join('tb_cmn_fund_allocation_txn', 'tb_cmn_fund_allocation_txn.Claim_Id_Fk', '=', 'tb_sms_claim_master.Pkid')
                ->select(DB::raw("COALESCE(SUM(tb_cmn_fund_allocation_txn.Claimed_Amount),0) as Claim"), DB::raw("COALESCE(SUM(tb_cmn_fund_allocation_txn.Allocated_Amount),0) as Allocated_Amount"), 'tb_cmn_state_master.State_Name','tb_cmn_state_master.Pkid')
                ->wherein('tb_cmn_status_master.Pkid', [5])
                ->where(DB::raw("year(tb_sms_claim_master.Received_Date)"), $year)
                ->orderBy(DB::raw("tb_cmn_state_master.State_Name"))
                ->groupBy(DB::raw("tb_cmn_state_master.Pkid,tb_cmn_state_master.State_Name"))
                ->get();
            $stateWiseDisbursement = TbSmsClaimMaster::join('tb_cmn_status_master', 'tb_sms_claim_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
                ->join('tb_cmn_benificiary_master', 'tb_cmn_benificiary_master.Pkid', '=', 'tb_sms_claim_master.Benificiary_Id_Fk')
                ->join('tb_cmn_address', 'tb_cmn_address.Pkid', '=', 'tb_cmn_benificiary_master.Address_Id_Fk')
                ->join('tb_cmn_state_master', 'tb_cmn_state_master.Pkid', '=', 'tb_cmn_address.State_Code')
                ->join('tb_cmn_fund_allocation_txn', 'tb_cmn_fund_allocation_txn.Claim_Id_Fk', '=', 'tb_sms_claim_master.Pkid')
                ->leftjoin('tb_sms_disbursement_details', 'tb_sms_disbursement_details.Claim_Id_Fk', '=', 'tb_sms_claim_master.Pkid')
                ->select(DB::raw("COALESCE(SUM(tb_sms_disbursement_details.Disbursement_Amount),0) as Disbursement_Amount"), DB::raw("COALESCE(SUM(tb_cmn_fund_allocation_txn.Claimed_Amount),0) as Claim"), DB::raw("COALESCE(SUM(tb_cmn_fund_allocation_txn.Allocated_Amount),0) as Allocated_Amount"), 'tb_cmn_state_master.State_Name','tb_cmn_state_master.Pkid')
                ->wherein('tb_cmn_status_master.Pkid', [5])
                ->where(DB::raw("year(tb_sms_disbursement_details.Disbursement_Date)"), $year)
                ->orderBy(DB::raw("tb_cmn_state_master.State_Name"))
                ->groupBy(DB::raw("tb_cmn_state_master.Pkid,tb_cmn_state_master.State_Name"))
                ->get();
            $claimDisbursement = TbSmsClaimMaster::leftjoin('tb_sms_disbursement_details', 'tb_sms_claim_master.Pkid', '=', 'tb_sms_disbursement_details.Claim_Id_Fk')->select(
                DB::raw("year(tb_sms_claim_master.Received_Date) as Year"),
                DB::raw("COALESCE(SUM(tb_sms_claim_master.Claim_Amount),0) as Claim"),
                DB::raw("COALESCE(SUM(tb_sms_disbursement_details.Disbursement_Amount), 0) AS Disbursement")
            )
                ->where(DB::raw("YEAR(tb_sms_claim_master.Received_Date)"), $year)
                ->wherein('tb_sms_disbursement_details.Status_Id_Fk', [4])
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
                $res[++$key] = [$val->Year, (int)$claim, (int)$disbursement];
            }
            $claimDisbursement = json_encode($res);
            $html = view('common-ui.state-wise-claim', compact('stateWiseClaim'))->render();
            $html2 = view('common-ui.state-wise-disbursement', compact('stateWiseDisbursement'))->render();
            return response()->json(["status" => "success", "stateWiseDisbursement" => $stateWiseDisbursement, "stateWiseClaim" => $stateWiseClaim, "claimDisbursement" => $claimDisbursement, "totalBenificiary" => $totalBenificiary, "totalClaim" => $totalClaim, "totalAllocation" => $totalAllocation, "totalDisbursement" => $totalDisbursement, "body" => $html, "body1" => $html2]);
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function sendDafaultPass($defaultPass, $user)
    {
        try {
            $oldChangeHistory = TbChangePasswordHistory::where(['Record_Active_Flag' => 1, 'UserId' => $user->Pkid])->get()->first();
            if (!empty($oldChangeHistory)) {
                // foreach ($oldChangeHistory as $oldrecord) {
                $tableData = array(
                    'Record_Update_Date' => new \DateTime(), 'Record_Active_Flag' => 0
                );
                TbChangePasswordHistory::where('UserId',  $user->Pkid)
                    ->update($tableData);
                // }
            }
            $changePasswordHistory = new TbChangePasswordHistory();
            $changePasswordHistory->UserId = $user->Pkid;
            $changePasswordHistory->Is_Default = 1;
            $changePasswordHistory->NewPassword = hash('sha3-512', $defaultPass);
            $changePasswordHistory->OldPassword = $user->User_Password;
            $changePasswordHistory->Username = $user->User_Id;
            $changePasswordHistory->Record_Insert_Date = new \DateTime();
            $changePasswordHistory->Record_Active_Flag = 1;
            $changePasswordHistory->save();
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }
}
