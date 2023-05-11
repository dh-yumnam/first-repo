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
use App\Models\TbCmnStateMaster;
use App\Models\TbCmnPolicyMaster;
use App\Models\TbCmnSchemeMaster;
use App\Models\TbCmnStatusMaster;
use App\Models\TbSmsClaimMaster;
use App\Models\TbSmsClaimTxn;
use App\Models\TbCmnApproval;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class FundMasterController extends Controller
{

    public function fundMasterModal()
    {
        echo 'her';
        exit;
        $bankMaster      =       TbCmnNedfiBankMaster::where('tb_cmn_nedfi_bank_master.Record_Active_Flag', 1)
            ->get(['tb_cmn_nedfi_bank_master.*'])
            ->sortByDesc('Bank_Name');
        $html = view('master-ui.modal.fund-master-modal', compact('bankMaster'))->render();
        //return view('benificiary-ui.benificiary-search-result');
        return response()->json(['status' => "success", 'body' => $html]);
    }

    public function searchFundMasterResult(Request $request)
    {
        $dataUI = json_decode($request->getContent());
        $fund_name = $dataUI->fund_name;
        $status = $dataUI->status_id;
        $query = TbCmnFundMaster::join('tb_cmn_status_master', 'tb_cmn_fund_master.Status_Id', '=', 'tb_cmn_status_master.Pkid');

        if (!empty($dataUI->fund_name)) {
            $query->where('Fund_Name', 'LIKE', "%{$fund_name}%");
        }
        if (!empty($dataUI->status_id)) {
            $query->where('Status_Id', $status);
        }

        $fundMaster = $query
            ->where('tb_cmn_fund_master.Record_Active_Flag', 1)
            ->get();

        $html = view('master-ui.master-search.fund-master-search', compact('fundMaster'))->render();
        //return view('benificiary-ui.benificiary-search-result');
        return response()->json(['status' => "success", 'body' => $html]);
    }

    public function showFundMaster()
    {
        $fundMaster      =       TbCmnFundMaster::join('tb_cmn_status_master', 'tb_cmn_fund_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
            ->where('tb_cmn_fund_master.Record_Active_Flag', 1)
            ->get(['tb_cmn_fund_master.*', 'tb_cmn_status_master.Status_Name'])
            ->sortByDesc('Pkid');
        $html_view = view("master-ui.list-view.fund-master-list-view", compact('fundMaster'))->render();
        return response()->json(["status" => "success", "message" => "Success! Fund Master created.", 'body' => $html_view]);
    }

    public function showFundMasterApproval()
    {
        $approvalStatusMaster = TbCmnStatusMaster::whereIn('Pkid', [3, 4, 5])->get();
        $fundMaster      =     TbCmnFundMaster::join('tb_cmn_status_master', 'tb_cmn_fund_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->whereIn('Status_Id', [2])
            ->where('tb_cmn_fund_master.Record_Active_Flag', 1)
            ->get(['tb_cmn_fund_master.*', 'tb_cmn_status_master.Status_Name'])->sortByDesc('Pkid');
        $html_view = view("master-ui.approval-view.fund-master-approval-view", compact('fundMaster', 'approvalStatusMaster'))->render();
        return response()->json(["status" => "success", "message" => "Success! Fund Master created.", 'body' => $html_view]);
    }
    // ------------- [ store post ] -----------------
    public function addFundMaster(Request $request)
    {
        /* $request->validate([
            'Fund_Name'         =>      'required|unique:tb_cmn_fund_master',
            'Sanction_Letter'   =>      'required',
            'Sanction_Date'   =>      'required',
            'Sanction_Amount'   =>      'required',
            'Bank_Master_Id'   =>      'required',
        ]); */

        $validationRules = array(
            'Sanction_Letter' => 'required|unique:tb_cmn_fund_master',
            'Sanction_Date'    => 'required|date',
            'Sanction_Amount'    => 'required|numeric',
            'Bank_Account_Id'   =>      'required',
            'Scheme_Id'   =>      'required',
        );
        $validator = Validator::make($request->all(), $validationRules);
        if ($validator->fails()) {
            return response()->json(["status" => "failed", "message" => "Alert! Sanction Letter has already been entered !"]);
        }

        $status = "success";
        try {
            //  $addMaterials            =           TbCmnRawMaterialMaster::create($request->all());
            $addFundMaster = new TbCmnFundMaster();
            $addFundMaster->Fund_Name = $request->Fund_Name;
            $addFundMaster->Sanction_Letter = $request->Sanction_Letter;
            $addFundMaster->Sanction_Date = $request->Sanction_Date;
            $addFundMaster->Sanction_Amount = $request->Sanction_Amount;
            $addFundMaster->Bank_Account_Id = $request->Bank_Account_Id;
            $addFundMaster->Scheme_Id = $request->Scheme_Id;
            $addFundMaster->Status_Id = '1';
            $addFundMaster->Record_Active_Flag = '1';
            $addFundMaster->Record_Insert_Date = new \DateTime();
            $addFundMaster->save();
            $status = "success";
        } catch (\Exception $ex) {
            throw $ex;
            $status = "failed";
        }

        $fundMaster      =       TbCmnFundMaster::join('tb_cmn_status_master', 'tb_cmn_fund_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->join('tb_cmn_scheme_master', 'tb_cmn_fund_master.Scheme_Id', '=', 'tb_cmn_scheme_master.Pkid')
            ->where('tb_cmn_fund_master.Record_Active_Flag', 1)
            ->get(['tb_cmn_fund_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_scheme_master.Scheme_Name'])
            ->sortBy('Pkid');
        $html_view = view("master-ui.list-view.fund-master-list-view", compact('fundMaster'))->render();
        if ($status == "success") {
            return response()->json(["status" => "success", "message" => "Success! Fund Master created.", 'data' => $addFundMaster, 'body' => $html_view]);
        } else {
            return response()->json(["status" => "failed", "message" => "Alert! Fund Master not created"]);
        }
    }

    // -------------- [ Delete post ] ---------------
    public function destroyFundMaster(Request $request, $raw_id)
    {
        $deleteFundMaster       =       TbCmnFundMaster::where("Pkid", $raw_id)
            ->update([
                'Record_Active_Flag' => '0',
                'Record_Update_Date' => new \DateTime(),
                'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'),
                'Updated_By' => $request->session()->get('id')
            ]);
        $fundMaster     =      TbCmnFundMaster::join('tb_cmn_status_master', 'tb_cmn_fund_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->where('tb_cmn_fund_master.Record_Active_Flag',  1)
            ->get(['tb_cmn_fund_master.*', 'tb_cmn_status_master.Status_Name'])->sortBy('Pkid');
        $html_view = view("master-ui.list-view.fund-master-list-view", compact('fundMaster'))->render();
        if ($deleteFundMaster == 1) {
            return response()->json(["status" => "success", "message" => "Success! Fund Master deleted", "body" => $html_view]);
        } else {
            return response()->json(["status" => "failed", "message" => "Alert! Fund Master not deleted"]);
        }
    }

    // Subsidy fund module-------------------------
    public function addSubsidyFund()
    {

        $subsidyMaster      =       TbCmnSchemeMaster::where('tb_cmn_scheme_master.Record_Active_Flag', 1)
            ->get(['tb_cmn_scheme_master.*'])->sortByDesc('Scheme_Name');
        $govPolicy      =       TbCmnPolicyMaster::where('tb_cmn_policy_master.Record_Active_Flag', 1)
            ->get(['tb_cmn_policy_master.*'])->sortByDesc('Policy_Name');
        $statusMaster      =       TbCmnStatusMaster::where('tb_cmn_status_master.Record_Active_Flag', 1)
            ->get(['tb_cmn_status_master.*'])->sortByDesc('Pkid');

        $subsidyFund      =       TbCmnFundMaster::join('tb_cmn_status_master', 'tb_cmn_fund_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->join('tb_cmn_scheme_master', 'tb_cmn_fund_master.Scheme_Id', '=', 'tb_cmn_scheme_master.Pkid')
            ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
            ->where('tb_cmn_fund_master.Record_Active_Flag', 1)
            ->get(['tb_cmn_fund_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_status_master.Pkid AS Status_Id', 'tb_cmn_scheme_master.Scheme_Name'])
            ->sortBy('Pkid');
        $html = view('fund-management-ui.subsidy-fund.create-subsidy-fund', compact('subsidyMaster', 'govPolicy', 'statusMaster', 'subsidyFund'))->render();
        return response()->json(['status' => "success", 'body' => $html]);
    }
    public function approveSubsidyFundForm()
    {
        $accessBoolean = (new CommonController)->checkAccessRightToController('approveSubsidyFundForm');
        if ($accessBoolean) {
            $approvalStatusMaster = TbCmnStatusMaster::whereIn('Pkid', [3, 4, 5])->get();
            $subsidyFund      =       TbCmnFundMaster::join('tb_cmn_status_master', 'tb_cmn_fund_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
                ->join('tb_cmn_scheme_master', 'tb_cmn_fund_master.Scheme_Id', '=', 'tb_cmn_scheme_master.Pkid')
                ->whereIn('tb_cmn_status_master.Pkid', [2])
                ->where('tb_cmn_fund_master.Record_Active_Flag', 1)
                ->get(['tb_cmn_fund_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_scheme_master.Scheme_Name'])
                ->sortBy('Pkid');
            $html = view('fund-management-ui.subsidy-fund.approve-subsidy-fund', compact('subsidyFund', 'approvalStatusMaster'))->render();
            return response()->json(['status' => "success", 'body' => $html]);
        } else {
            $html_view = view('pages.error-pages.access-deny-modal')->render();
            return response()->json(["status" => "success", "body" => $html_view]);
        }
    }
    public function searchSubsidyFund(Request $request)
    {
        $dataUI = json_decode($request->getContent());
        try {
            $query = DB::table('tb_cmn_fund_master')->leftjoin('tb_cmn_scheme_master', 'tb_cmn_fund_master.Scheme_Id', '=', 'tb_cmn_scheme_master.Pkid')
                ->join('tb_cmn_status_master', 'tb_cmn_fund_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
                ->whereIn('tb_cmn_status_master.Pkid', [1, 2, 3, 4, 5])
                ->where('tb_cmn_fund_master.Record_Active_Flag', 1);

            if (!empty($dataUI->sanction_letter)) {
                $query->where('tb_cmn_fund_master.Sanction_Letter', 'LIKE', "%{$dataUI->sanction_letter}%");
            }
            if (!empty($dataUI->scheme)) {
                $query->where('tb_cmn_scheme_master.Pkid', $dataUI->scheme);
            }
            if (!empty($dataUI->allcation_From_Date)) {
                $query->where('tb_cmn_fund_master.Sanction_Date', $dataUI->sanction_From_Date);
            }
            if (!empty($dataUI->allcation_To_Date)) {
                $query->where('tb_cmn_fund_master.Sanction_Date', $dataUI->sanction_To_Date);
            }
            if (!empty($dataUI->status_id)) {
                $query->where('tb_cmn_fund_master.Status_Id', $dataUI->status_id);
            }

            $subsidyFund = $query->get(['tb_cmn_fund_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_status_master.Pkid AS Status_Id', 'tb_cmn_scheme_master.Scheme_Name'])->sortBy('Pkid');
        } catch (\Exception $ex) {
            throw $ex;
        }

        $html = view('fund-management-ui.subsidy-fund.search-subsidy-fund-result', compact('subsidyFund'))->render();
        //return view('benificiary-ui.benificiary-search-result');
        return response()->json(['status' => "success", 'body' => $html]);
    }

    public function addSubsidyFundModal()
    {
        try {
            $accessBoolean = (new CommonController)->checkAccessRightToController('addSubsidyFund');
            if ($accessBoolean) {
                $subsidyMaster      =       TbCmnSchemeMaster::where('tb_cmn_scheme_master.Record_Active_Flag', 1)
                    ->get(['tb_cmn_scheme_master.*'])
                    ->sortByDesc('Scheme_Name');
                $bankMaster      =       TbCmnNedfiBankMaster::where('tb_cmn_nedfi_bank_master.Record_Active_Flag', 1)
                    ->get(['tb_cmn_nedfi_bank_master.*'])
                    ->sortByDesc('Bank_Name');
                $approvalStatusMaster = TbCmnStatusMaster::whereIn('Pkid', [3, 4, 5])->get();
                $html = view('fund-management-ui.subsidy-fund.create-subsidy-fund-modal', compact('subsidyMaster', 'bankMaster'))->render();
                //return view('benificiary-ui.benificiary-search-result');
                return response()->json(['status' => "success", 'body' => $html]);
            } else {
                $html_view = view('pages.error-pages.access-deny-modal')->render();
                return response()->json(["status" => "success", "body" => $html_view]);
            }
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }
    // ------------- [ store Subsidy Fun ] -----------------
    public function saveSubsidyFund(Request $request)
    {
        $whereCondition = [
            ['Sanction_Letter', '=', $request->Sanction_Letter],
        ];
        $UniqueName = TbCmnFundMaster::where($whereCondition)
            ->where('tb_cmn_fund_master.Record_Active_Flag', 1)
            ->get(['tb_cmn_fund_master.*'])
            ->sortBy('Pkid');
        if (count($UniqueName) > 0) {
            return response()->json(["status" => "failed", "message" => "Alert! Sanction Letter has already been entered !"]);
        }
        $status = "success";
        try {
            //  $addMaterials            =           TbCmnRawMaterialMaster::create($request->all());
            $addFundMaster = new TbCmnFundMaster();
            $addFundMaster->Sanction_Letter = $request->Sanction_Letter;
            $addFundMaster->Sanction_Date = $request->Sanction_Date;
            $addFundMaster->Sanction_Amount = $request->Sanction_Amount;
            $addFundMaster->Fund_Balance = $request->Sanction_Amount;
            $addFundMaster->Bank_Account_Id = $request->Bank_Account_Id;
            $addFundMaster->Scheme_Id = $request->Scheme_Id;
            $addFundMaster->Status_Id = config("constants.CREATED");
            $result = (new CommonController)->insertDefaultColumns($request, $addFundMaster);
            $addFundMaster->save();
            $status = "success";
        } catch (\Exception $ex) {
            throw $ex;
            $status = "failed";
        }

        $subsidyFund      =       TbCmnFundMaster::join('tb_cmn_status_master', 'tb_cmn_fund_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->join('tb_cmn_scheme_master', 'tb_cmn_fund_master.Scheme_Id', '=', 'tb_cmn_scheme_master.Pkid')
            ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
            ->where('tb_cmn_fund_master.Record_Active_Flag', 1)
            ->get(['tb_cmn_fund_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_scheme_master.Scheme_Name'])
            ->sortBy('Pkid');
        $html_view = view("fund-management-ui.subsidy-fund.search-subsidy-fund", compact('subsidyFund'))->render();

        $MODE = 'EDT';
        $subsidyMaster      =       TbCmnSchemeMaster::where('tb_cmn_scheme_master.Record_Active_Flag', 1)
            ->get(['tb_cmn_scheme_master.*'])
            ->sortByDesc('Scheme_Name');
        $bankMaster      =       TbCmnNedfiBankMaster::where('tb_cmn_nedfi_bank_master.Record_Active_Flag', 1)
            ->get(['tb_cmn_nedfi_bank_master.*'])
            ->sortByDesc('Bank_Name');
        $subsidyFundUpdate      =       TbCmnFundMaster::join('tb_cmn_status_master', 'tb_cmn_fund_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->join('tb_cmn_scheme_master', 'tb_cmn_fund_master.Scheme_Id', '=', 'tb_cmn_scheme_master.Pkid')
            ->join('tb_cmn_nedfi_bank_master', 'tb_cmn_fund_master.Bank_Account_Id', '=', 'tb_cmn_nedfi_bank_master.Pkid')
            ->where('tb_cmn_fund_master.Pkid', $addFundMaster->Pkid)
            ->where('tb_cmn_fund_master.Record_Active_Flag', 1)
            ->get(['tb_cmn_fund_master.*', 'tb_cmn_nedfi_bank_master.Bank_Name', 'tb_cmn_status_master.Status_Name', 'tb_cmn_scheme_master.Scheme_Name'])
            ->sortBy('Pkid');
        $html_view1 = view("fund-management-ui.subsidy-fund.view-edit-subsidy-fund", compact('subsidyFundUpdate', 'MODE', 'subsidyMaster', 'bankMaster'))->render();

        if ($status == "success") {
            return response()->json(["status" => "success", "message" => "Success! Subsidy Fund added.", 'data' => $addFundMaster, 'body' => $html_view, "body1" => $html_view1]);
        } else {
            return response()->json(["status" => "failed", "message" => "Alert! Subsidy Fund not added"]);
        }
    }

    // -------------- [ Delete post ] ---------------
    public function destroySubsidyFund(Request $request, $raw_id)
    {
        $accessBoolean = (new CommonController)->checkAccessRightToController('destroySubsidyFund');
        if ($accessBoolean) {
            $deleteFundMaster       =       TbCmnFundMaster::where("Pkid", $raw_id)
                ->update([
                    'Record_Active_Flag' => '0',
                    'Record_Update_Date' => new \DateTime(),
                    'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'),
                    'Updated_By' => $request->session()->get('id')
                ]);
            $subsidyFund      =       TbCmnFundMaster::join('tb_cmn_status_master', 'tb_cmn_fund_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
                ->join('tb_cmn_scheme_master', 'tb_cmn_fund_master.Scheme_Id', '=', 'tb_cmn_scheme_master.Pkid')
                ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
                ->where('tb_cmn_fund_master.Record_Active_Flag', 1)
                ->get(['tb_cmn_fund_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_scheme_master.Scheme_Name'])
                ->sortBy('Pkid');
            $html_view = view("fund-management-ui.subsidy-fund.search-subsidy-fund", compact('subsidyFund'))->render();
            if ($deleteFundMaster == 1) {
                return response()->json(["status" => "success", "message" => "Success! Subsidy Fund deleted", "body" => $html_view]);
            } else {
                return response()->json(["status" => "failed", "message" => "Alert! Subsidy Fund not deleted"]);
            }
        } else {
            $html_view = view('pages.error-pages.access-deny-modal')->render();
            return response()->json(["status" => "access_deny", "body" => $html_view]);
        }
    }

    // save approve subsidy claim

    public function approveSubsidyFundEntry(Request $request)
    {
        DB::beginTransaction();
        try {
            $dataUI = json_decode($request->getContent());
            $approvalStatus = $dataUI->decision;
            $approvalDate = $dataUI->approval_date;
            $remarks = $dataUI->remarks;
            if (Is_Array($dataUI->check_id)) {
                foreach ($dataUI->check_id as $value) {
                    TbCmnFundMaster::where('Pkid', $value)->update(
                        [
                            'Status_Id' => $approvalStatus, 'Record_Update_Date' => new \DateTime(), 'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'),
                            'Updated_By' => $request->session()->get('id')
                        ]
                    );
                    $saveCmnApproval = new TbCmnApproval();
                    $saveCmnApproval->Approval_Date = $approvalDate;
                    $saveCmnApproval->Status_Id_Fk = $approvalStatus;
                    $saveCmnApproval->Remarks = $remarks;
                    $saveCmnApproval->Module = 'Fund Master';
                    $saveCmnApproval->Record_Id_Fk =  $value;
                    $saveCmnApproval->save();
                }
            } else {
                TbCmnFundMaster::where('Pkid', $dataUI->check_id)->update(
                    [
                        'Status_Id' => $approvalStatus, 'Record_Update_Date' => new \DateTime(), 'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'),
                        'Updated_By' => $request->session()->get('id')
                    ]
                );
                $saveCmnApproval = new TbCmnApproval();
                $saveCmnApproval->Approval_Date = $approvalDate;
                $saveCmnApproval->Status_Id_Fk = $approvalStatus;
                $saveCmnApproval->Remarks = $remarks;
                $saveCmnApproval->Module = 'Fund Master';
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
        $subsidyFund      =       TbCmnFundMaster::join('tb_cmn_status_master', 'tb_cmn_fund_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->join('tb_cmn_scheme_master', 'tb_cmn_fund_master.Scheme_Id', '=', 'tb_cmn_scheme_master.Pkid')
            ->whereIn('tb_cmn_status_master.Pkid', [2])
            ->where('tb_cmn_fund_master.Record_Active_Flag', 1)
            ->get(['tb_cmn_fund_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_status_master.Pkid', 'tb_cmn_scheme_master.Scheme_Name'])
            ->sortBy('Pkid');

        $html_view = view('fund-management-ui.subsidy-fund.after-approve-subsidy-fund-list', compact('subsidyFund'))->render();
        if ($status == 'success') {
            return response()->json(["status" => "success", "message" => "Success! Subsidy Fund " . $decision, "body" => $html_view]);
        } else {
            return response()->json(["status" => "success", "message" => "Alert!  Subsidy Fund not " . $decision]);
        }
    }
    public function viewEditSubsidyFund($fund_Id, $MODE)
    {
        $subsidyMaster      =       TbCmnSchemeMaster::where('tb_cmn_scheme_master.Record_Active_Flag', 1)
            ->get(['tb_cmn_scheme_master.*'])
            ->sortByDesc('Scheme_Name');
        $bankMaster      =       TbCmnNedfiBankMaster::where('tb_cmn_nedfi_bank_master.Record_Active_Flag', 1)
            ->get(['tb_cmn_nedfi_bank_master.*'])
            ->sortByDesc('Bank_Name');
        $subsidyFundUpdate      =       TbCmnFundMaster::join('tb_cmn_status_master', 'tb_cmn_fund_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->join('tb_cmn_scheme_master', 'tb_cmn_fund_master.Scheme_Id', '=', 'tb_cmn_scheme_master.Pkid')
            ->leftjoin('tb_cmn_nedfi_bank_master', 'tb_cmn_fund_master.Bank_Account_Id', '=', 'tb_cmn_nedfi_bank_master.Pkid')
            ->where('tb_cmn_fund_master.Pkid', $fund_Id)
            ->where('tb_cmn_fund_master.Record_Active_Flag', 1)
            ->get(['tb_cmn_fund_master.*', 'tb_cmn_nedfi_bank_master.Bank_Name', 'tb_cmn_status_master.Status_Name', 'tb_cmn_scheme_master.Scheme_Name'])
            ->sortBy('Pkid');
        if ($MODE == 'EDT') {
            $accessBoolean = (new CommonController)->checkAccessRightToController('viewEditSubsidyFund');
            if ($accessBoolean) {
                $html_view = view("fund-management-ui.subsidy-fund.view-edit-subsidy-fund", compact('subsidyFundUpdate', 'MODE', 'subsidyMaster', 'bankMaster'))->render();
                return response()->json(["status" => "success", 'body' => $html_view]);
            } else {
                $html_view = view('pages.error-pages.access-deny-modal')->render();
                return response()->json(["status" => "access_deny", "body" => $html_view]);
            }
        } else {
            $html_view = view("fund-management-ui.subsidy-fund.view-edit-subsidy-fund", compact('subsidyFundUpdate', 'MODE', 'subsidyMaster', 'bankMaster'))->render();
            return response()->json(["status" => "success", 'body' => $html_view]);
        }
    }
    public function updateSubsidyFund(Request $request, $id)
    {
        $dataUI = json_decode($request->getContent());
        DB::beginTransaction();
        try {
            $validator = TbCmnFundMaster::where('Pkid', $id)->get(['Sanction_Letter']);
            foreach ($validator as $validator) {
                if ($validator->Sanction_Letter != $dataUI->Sanction_Letter) {
                    $count = TbCmnFundMaster::where('Sanction_Letter', $dataUI->Sanction_Letter)->get();
                    if ($count > 0) {
                        return response()->json(["status" => "failed", "message" => "Alert! Sanction Letter has already been entered !"]);
                    }
                }
            }
            $fundBalance = TbCmnFundMaster::where('Pkid', $id)->get()->first();
            $fundMasterDate = array(
                'Sanction_Letter' => $dataUI->Sanction_Letter, "Sanction_Date" => $dataUI->Sanction_Date, 'Sanction_Amount' => $dataUI->Sanction_Amount,
                'Bank_Account_Id' => $dataUI->Bank_Account_Id, 'Scheme_Id' => $dataUI->Scheme_Id, 'Fund_Balance' => $fundBalance->Fund_Balance,
                'Record_Update_Date' => new \DateTime(), 'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'), 'Updated_By' => $request->session()->get('id')
            );
            $updateFundMaster = TbCmnFundMaster::where('Pkid', $id)->update($fundMasterDate);
            $status = "success";
            // all good
            DB::commit();
        } catch (\Exception $e) {
            throw $e;
            $status = "failed";
            DB::rollback();
            // something went wrong
        }
        $subsidyFund      =       TbCmnFundMaster::join('tb_cmn_status_master', 'tb_cmn_fund_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->join('tb_cmn_scheme_master', 'tb_cmn_fund_master.Scheme_Id', '=', 'tb_cmn_scheme_master.Pkid')
            ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
            ->where('tb_cmn_fund_master.Record_Active_Flag', 1)
            ->get(['tb_cmn_fund_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_scheme_master.Scheme_Name'])
            ->sortBy('Pkid');
        $html_view = view("fund-management-ui.subsidy-fund.search-subsidy-fund", compact('subsidyFund'))->render();

        $MODE = 'EDT';
        $subsidyMaster      =       TbCmnSchemeMaster::where('tb_cmn_scheme_master.Record_Active_Flag', 1)
            ->get(['tb_cmn_scheme_master.*'])
            ->sortByDesc('Scheme_Name');
        $bankMaster      =       TbCmnNedfiBankMaster::where('tb_cmn_nedfi_bank_master.Record_Active_Flag', 1)
            ->get(['tb_cmn_nedfi_bank_master.*'])
            ->sortByDesc('Bank_Name');
        $subsidyFundUpdate      =       TbCmnFundMaster::join('tb_cmn_status_master', 'tb_cmn_fund_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->join('tb_cmn_scheme_master', 'tb_cmn_fund_master.Scheme_Id', '=', 'tb_cmn_scheme_master.Pkid')
            ->join('tb_cmn_nedfi_bank_master', 'tb_cmn_fund_master.Bank_Account_Id', '=', 'tb_cmn_nedfi_bank_master.Pkid')
            ->where('tb_cmn_fund_master.Pkid', $id)
            ->where('tb_cmn_fund_master.Record_Active_Flag', 1)
            ->get(['tb_cmn_fund_master.*', 'tb_cmn_nedfi_bank_master.Bank_Name', 'tb_cmn_status_master.Status_Name', 'tb_cmn_scheme_master.Scheme_Name'])
            ->sortBy('Pkid');
        $html_view1 = view("fund-management-ui.subsidy-fund.view-edit-subsidy-fund", compact('subsidyFundUpdate', 'MODE', 'subsidyMaster', 'bankMaster'))->render();
        if ($status == "success") {
            return response()->json(["status" => "success", "message" => "Success! Subsidy Fund updated.", "body" => $html_view, "body1" => $html_view1]);
        } else {
            return response()->json(["status" => "failed", "message" => "Alert! Subsidy Fund not updated"]);
        }
    }

    // Approval record
    public function fundMaterApproval(Request $request, $id)
    {
        $dataUI = json_decode($request->getContent());
        DB::beginTransaction();
        try {
            $tableData = array(
                'Status_Id' => '2', 'Record_Insert_Date' => new \DateTime()
            );
            $updateTable = TbCmnFundMaster::where('Pkid',  $id)
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
        $subsidyFund      =       TbCmnFundMaster::join('tb_cmn_status_master', 'tb_cmn_fund_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->join('tb_cmn_scheme_master', 'tb_cmn_fund_master.Scheme_Id', '=', 'tb_cmn_scheme_master.Pkid')
            ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
            ->where('tb_cmn_fund_master.Record_Active_Flag', 1)
            ->get(['tb_cmn_fund_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_scheme_master.Scheme_Name'])
            ->sortBy('Pkid');
        $html_view = view("fund-management-ui.subsidy-fund.search-subsidy-fund", compact('subsidyFund'))->render();
        if ($status == "success") {
            return response()->json(["status" => "success", "message" => "Success! Subsidy Fund Submitted for approval.", 'body' => $html_view]);
        } else {
            return response()->json(["status" => "failed", "message" => "Alert! Subsidy Fund not Submitted for approval"]);
        }
    }
}
