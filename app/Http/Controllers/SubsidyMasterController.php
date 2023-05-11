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
use App\Models\TbCmnPolicyMaster;
use App\Models\TbCmnSchemeShortName;
use App\Models\TbCmnStatusMaster;

use App\Models\TbCmnApproval;
use App\Models\TbSmsDisbursementDetail;
use Hamcrest\Arrays\IsArray;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Validation\Validator as ValidationValidator;
use Illuminate\Support\Facades\Validator;

class SubsidyMasterController extends Controller
{
    public function subsidyMaterialPanel()
    {
        //return view('benificiary-ui.benificiary-search-result');
        return view('master-ui.panels.subsidy-master-panel');
    }

    public function subsidyMasterModal()
    {
        $govPolicy      =       TbCmnPolicyMaster::where('tb_cmn_policy_master.Record_Active_Flag', '1')
            ->get()->sortBy('Policy_Name');
        $schemShortName      =       TbCmnSchemeShortName::where('tb_cmn_scheme_short_name.Record_Active_Flag', '1')
            ->get()->sortBy('Short_Name');
        $html = view('master-ui.modal.subsidy-master-modal', compact('govPolicy', 'schemShortName'))->render();
        //return view('benificiary-ui.benificiary-search-result');
        return response()->json(['status' => "success", 'body' => $html]);
    }

    public function searchSunsidyMasterResult(Request $request)
    {

        $dataUI = json_decode($request->getContent());
        $scheme_name = $dataUI->scheme_name;
        $status = $dataUI->status_id;
        $query = TbCmnSchemeMaster::join('tb_cmn_status_master', '.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->join('tb_cmn_policy_master', 'tb_cmn_scheme_master.Gov_policy', '=', 'tb_cmn_policy_master.Pkid');

        /* $data = Country::join('state', 'state.country_id', '=', 'country.country_id')
              		->join('city', 'city.state_id', '=', 'state.state_id')
              		->get(['country.country_name', 'state.state_name', 'city.city_name']);
        */

        if (!empty($dataUI->scheme_name)) {
            $query->where('tb_cmn_scheme_master.Scheme_Name', 'LIKE', "%{$scheme_name}%");
        }
        if (!empty($dataUI->status_id)) {
            $query->where('tb_cmn_status_master.Pkid', $dataUI->status_id);
        }
        $schemeMaster = $query
            ->where('tb_cmn_scheme_master.Record_Active_Flag', 1)
            ->get(['tb_cmn_scheme_master.*', 'tb_cmn_status_master.Status_Name']);
        $govPolicy      =       TbCmnPolicyMaster::all()
            ->where('tb_cmn_policy_master.Record_Active_Flag', 1)
            ->sortBy('Policy_Name');
        $html = view('master-ui.master-search.subsidy-master-search', compact('schemeMaster', 'govPolicy'))->render();
        //return view('benificiary-ui.benificiary-search-result');
        return response()->json(['status' => "success", 'body' => $html]);
    }

    // ---------------- [ Update post ] -------------
    /*  public function updateRawMaterialMaster(Request $request)
    {
        $post_id        =       $request->id;
        $post           =       TbCmnRawMaterialMaster::where("id", $post_id)->update($request->all());
        $list      =       TbCmnRawMaterialMaster::latest()->paginate(5);
        $html_view = view("list",compact('list'))->render();
        if ($post == 1) {
            return response()->json(["status" => "success", "message" => "Success! post updated","body"=> $html_view]);
        } else {
            return response()->json(["status" => "failed", "message" => "Alert! post not updated"]);
        }
    }*/

    public function showSubsidyMaster()
    {
        $schemeMaster      =       TbCmnSchemeMaster::join('tb_cmn_status_master', 'tb_cmn_scheme_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->join('tb_cmn_policy_master', 'tb_cmn_scheme_master.Gov_policy', '=', 'tb_cmn_policy_master.Pkid')
            ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
            ->where('tb_cmn_scheme_master.Record_Active_Flag', 1)
            ->get(['tb_cmn_scheme_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_policy_master.Policy_Name'])
            ->sortBy('Pkid');
        $govPolicy      =       TbCmnPolicyMaster::all()->sortBy('Policy_Name');
        $html_view = view("master-ui.list-view.subsidy-master-list-view", compact('schemeMaster', 'govPolicy'))->render();
        return response()->json(["status" => "success", "message" => "Success! Subsidy Master created.", "body" => $html_view]);
    }

    public function showSubsidyMasterApproval()
    {
        $schemeMaster      =      TbCmnSchemeMaster::join('tb_cmn_status_master', 'tb_cmn_scheme_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->join('tb_cmn_policy_master', 'tb_cmn_scheme_master.Gov_policy', '=', 'tb_cmn_policy_master.Pkid')
            ->whereIn('Status_Id', [2])
            ->where('tb_cmn_scheme_master.Record_Active_Flag', 1)
            ->get(['tb_cmn_scheme_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_policy_master.Policy_Name'])->sortBy('Pkid');
        $govPolicy      =       TbCmnPolicyMaster::all()->sortBy('Policy_Name');
        $html_view = view("master-ui.approval-view.subsidy-master-approval-view", compact('schemeMaster', 'govPolicy'))->render();
        return response()->json(["status" => "success", "message" => "Success! Subsidy Master created.", "body" => $html_view]);
    }
    // ------------- [ store post ] -----------------
    public function addSubsidyMaster(Request $request)
    {

        /*'name' => 'required',
        'username' => 'required|min:8',
        'email' => 'required|email|unique:users',
        'contact' => 'required|unique:users'

        $request->validate([
            'Scheme_Name'         =>      'required|unique:tb_cmn_reason_master',
            'Gov_policy'   =>      'required',
        ]);*/

        $validationRules = array(
            'Scheme_Name'         =>      'required|unique:tb_cmn_scheme_master',
            'Gov_policy'   =>      'required',
        );
        $validator = Validator::make($request->all(), $validationRules);
        if ($validator->fails()) {
            return response()->json(["status" => "failed", "message" => "Alert! Scheme Name has already been taken !"]);
        }
        $status = "success";
        try {
            //  $addMaterials            =           TbCmnRawMaterialMaster::create($request->all());
            $addProductMaster = new TbCmnSchemeMaster();
            $addProductMaster->Scheme_Name = $request->Scheme_Name;
            $addProductMaster->Gov_policy = $request->Gov_policy;
            $addProductMaster->Description = $request->Description;
            $addProductMaster->Year = $request->Year;
            $addProductMaster->Scheme_Short_Name_Id_Fk = $request->Short_form;
            $addProductMaster->Status_Id = config("constants.CREATED");
            $result = (new CommonController)->insertDefaultColumns($request, $addProductMaster);
            $addProductMaster->save();
            $status = "success";
        } catch (\Exception $ex) {
            throw $ex;
            $status = "failed";
        }
        $schemeMaster      =       TbCmnSchemeMaster::join('tb_cmn_status_master', 'tb_cmn_scheme_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->join('tb_cmn_policy_master', 'tb_cmn_scheme_master.Gov_policy', '=', 'tb_cmn_policy_master.Pkid')
            ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
            ->where('tb_cmn_scheme_master.Record_Active_Flag', 1)
            ->get(['tb_cmn_scheme_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_policy_master.Policy_Name'])
            ->sortBy('Pkid');
        $govPolicy      =       TbCmnPolicyMaster::all()->sortBy('Policy_Name');
        $schemShortName      =       TbCmnSchemeShortName::where('tb_cmn_scheme_short_name.Record_Active_Flag', '1')
            ->get()->sortBy('Short_Name');
        $html_view = view("master-ui.list-view.subsidy-master-list-view", compact('schemeMaster', 'govPolicy', 'schemShortName'))->render();

        $MODE = 'EDT';
        $schemeMasterUpdate      =       TbCmnSchemeMaster::join('tb_cmn_status_master', 'tb_cmn_scheme_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->join('tb_cmn_policy_master', 'tb_cmn_scheme_master.Gov_policy', '=', 'tb_cmn_policy_master.Pkid')
            ->where('tb_cmn_scheme_master.Pkid', $addProductMaster->Pkid)
            ->where('tb_cmn_scheme_master.Record_Active_Flag', 1)
            ->get(['tb_cmn_scheme_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_policy_master.Policy_Name']);
        $html_view1 = view("master-ui.modal.edit-view-subsidy-master-modal", compact('schemeMasterUpdate', 'MODE', 'govPolicy', 'schemShortName'))->render();


        if (!is_null($schemeMaster)) {
            return response()->json(["status" => "success", "message" => "Success! Subsidy Master created.", "data" => $addProductMaster, "body" => $html_view, "body1" => $html_view1]);
        } else {
            return response()->json(["status" => "failed", "message" => "Alert! Subsidy Master not created"]);
        }
    }

    // -------------- [ Delete post ] ---------------
    public function destroySubsidyMaster(Request $request, $raw_id)
    {
        $deleteProductMaster       =       TbCmnSchemeMaster::where("Pkid", $raw_id)
            ->update([
                'Record_Active_Flag' => '0',
                'Record_Update_Date' => new \DateTime(),
                'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'),
                'Updated_By' => $request->session()->get('id')
            ]);

        $schemeMaster      =       TbCmnSchemeMaster::join('tb_cmn_status_master', 'tb_cmn_scheme_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->join('tb_cmn_policy_master', 'tb_cmn_scheme_master.Gov_policy', '=', 'tb_cmn_policy_master.Pkid')
            ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
            ->where('tb_cmn_scheme_master.Record_Active_Flag', 1)
            ->get(['tb_cmn_scheme_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_policy_master.Policy_Name'])
            ->sortBy('Pkid');
        $govPolicy      =       TbCmnPolicyMaster::all()->sortBy('Policy_Name');
        $html_view = view("master-ui.list-view.subsidy-master-list-view", compact('schemeMaster', 'govPolicy'))->render();
        if ($deleteProductMaster == 1) {
            return response()->json(["status" => "success", "message" => "Success! Subsidy Master deleted", "body" => $html_view]);
        } else {
            return response()->json(["status" => "failed", "message" => "Alert! Subsidy Master not deleted"]);
        }
    }

    public function approveSchemeEntry(Request $request)
    {
        DB::beginTransaction();
        try {
            $dataUI = json_decode($request->getContent());
            $approvalStatus = $dataUI->decision;
            $approvalDate = $dataUI->approval_date;
            $remarks = $dataUI->remarks;
            if (Is_Array($dataUI->check_id)) {
                foreach ($dataUI->check_id as $value) {
                    TbCmnSchemeMaster::where('Pkid', $value)->update(
                        [
                            'Status_Id' => $approvalStatus, 'Record_Update_Date' => new \DateTime(), 'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'),
                            'Updated_By' => $request->session()->get('id')
                        ]
                    );
                    $saveCmnApproval = new TbCmnApproval();
                    $saveCmnApproval->Approval_Date = $approvalDate;
                    $saveCmnApproval->Status_Id_Fk = $approvalStatus;
                    $saveCmnApproval->Remarks = $remarks;
                    $saveCmnApproval->Module = 'Scheme master';
                    $saveCmnApproval->Record_Id_Fk =  $value;
                    $saveCmnApproval->save();
                }
            } else {
                TbCmnSchemeMaster::where('Pkid', $dataUI->check_id)->update(
                    [
                        'Status_Id' => $approvalStatus, 'Record_Update_Date' => new \DateTime(), 'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'),
                        'Updated_By' => $request->session()->get('id')
                    ]
                );
                $saveCmnApproval = new TbCmnApproval();
                $saveCmnApproval->Approval_Date = $approvalDate;
                $saveCmnApproval->Status_Id_Fk = $approvalStatus;
                $saveCmnApproval->Remarks = $remarks;
                $saveCmnApproval->Module = 'Scheme master';
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
        $schemeMaster      =       TbCmnSchemeMaster::join('tb_cmn_status_master', 'tb_cmn_scheme_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->join('tb_cmn_policy_master', 'tb_cmn_scheme_master.Gov_policy', '=', 'tb_cmn_policy_master.Pkid')
            ->whereIn('tb_cmn_status_master.Pkid', [2])
            ->where('tb_cmn_scheme_master.Record_Active_Flag', 1)
            ->get(['tb_cmn_scheme_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_policy_master.Policy_Name'])
            ->sortBy('Pkid');
        $govPolicy      =       TbCmnPolicyMaster::all()->sortBy('Policy_Name');
        $schemShortName      =       TbCmnSchemeShortName::where('tb_cmn_scheme_short_name.Record_Active_Flag', '1')
            ->get()->sortBy('Short_Name');
        $html_view = view("master-ui.approval-view.subsidy-master-approval-view", compact('schemeMaster', 'govPolicy', 'schemShortName'))->render();
        if ($status == 'success') {
            return response()->json(["status" => "success", "message" => "Success! Scheme master " . $decision, "body" => $html_view]);
        } else {
            return response()->json(["status" => "success", "message" => "Alert! Scheme master not " . $decision]);
        }
    }

    public function viewEditSchemeModal($id, $MODE)
    {
        $schemeMasterUpdate      =       TbCmnSchemeMaster::join('tb_cmn_status_master', 'tb_cmn_scheme_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->join('tb_cmn_policy_master', 'tb_cmn_scheme_master.Gov_policy', '=', 'tb_cmn_policy_master.Pkid')
            ->where('tb_cmn_scheme_master.Pkid', $id)
            ->where('tb_cmn_scheme_master.Record_Active_Flag', 1)
            ->get(['tb_cmn_scheme_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_policy_master.Policy_Name']);

        $govPolicy      =       TbCmnPolicyMaster::all()->sortBy('Policy_Name');
        $schemShortName      =       TbCmnSchemeShortName::where('tb_cmn_scheme_short_name.Record_Active_Flag', '1')
            ->get()->sortBy('Short_Name');
        $html_view = view("master-ui.modal.edit-view-subsidy-master-modal", compact('schemeMasterUpdate', 'MODE', 'govPolicy', 'schemShortName'))->render();
        return response()->json(['status' => "success", 'body' => $html_view]);
    }
    // Approval record
    public function schemeMasterApproval(Request $request, $id)
    {
        $dataUI = json_decode($request->getContent());
        DB::beginTransaction();
        try {
            $tableData = array(
                'Status_Id' => '2', 'Record_Update_Date' => new \DateTime(), 'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'), 'Updated_By' => $request->session()->get('id')
            );
            $updateTable = TbCmnSchemeMaster::where('Pkid',  $id)
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
        $schemeMaster      =       TbCmnSchemeMaster::join('tb_cmn_status_master', 'tb_cmn_scheme_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->join('tb_cmn_policy_master', 'tb_cmn_scheme_master.Gov_policy', '=', 'tb_cmn_policy_master.Pkid')
            ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
            ->where('tb_cmn_scheme_master.Record_Active_Flag', 1)
            ->get(['tb_cmn_scheme_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_policy_master.Policy_Name'])
            ->sortBy('Pkid');
        $govPolicy      =       TbCmnPolicyMaster::all()->sortBy('Policy_Name');
        $schemShortName      =       TbCmnSchemeShortName::where('tb_cmn_scheme_short_name.Record_Active_Flag', '1')
            ->get()->sortBy('Short_Name');
        $html_view = view("master-ui.list-view.subsidy-master-list-view", compact('schemeMaster'))->render();
        if ($status == "success") {
            return response()->json(["status" => $status, "message" => "Success! Scheme submitted for approval.", "body" => $html_view]);
        } else {
            return response()->json(["status" => $status, "message" => "Alert! Scheme not submitted for approval"]);
        }
    }

    // ---------------- [ Update post ] -------------
    public function updateSubsidyMaster(Request $request, $id)
    {
        $dataUI = json_decode($request->getContent());
        DB::beginTransaction();
        try {
            $scheme = TbCmnSchemeMaster::where('Pkid', $id)->get(['tb_cmn_scheme_master.*']);
            // $UniqueName = TbCmnBenificiaryMaster::where('Benificiary_Name', $Benificiary_Name)->get();
            $schemeName = null;
            foreach ($scheme as $scheme) {
                $schemeName = $scheme->Scheme_Name;
            }
            if ($schemeName != $dataUI->Scheme_Name) {
                $DuplicateData = TbCmnSchemeMaster::where('Scheme_Name', $dataUI->Scheme_Name)
                    ->where('tb_cmn_scheme_master.Record_Active_Flag', 1)->get();
                if (count($DuplicateData) > 0) {
                    return response()->json(["status" => "failed", "message" => "Alert! Duplicate scheme master !"]);
                }
            }
            $addressData = array(
                'Scheme_Name' => $dataUI->Scheme_Name, 'Description' => $dataUI->Description, 'Year' => $dataUI->Year, 'Gov_policy' => $dataUI->Gov_policy, 'Scheme_Short_Name_Id_Fk' => $dataUI->Short_form,
                'Record_Update_Date' => new \DateTime(), 'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'), 'Updated_By' => $request->session()->get('id')
            );
            $addresBenificiary = TbCmnSchemeMaster::where('Pkid', $id)->update($addressData);
            $status = "success";

            // all good
            DB::commit();
        } catch (\Exception $e) {
            throw $e;
            $status = "failed";
            DB::rollback();
            // something went wrong
        }
        $schemeMaster      =       TbCmnSchemeMaster::join('tb_cmn_status_master', 'tb_cmn_scheme_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->join('tb_cmn_policy_master', 'tb_cmn_scheme_master.Gov_policy', '=', 'tb_cmn_policy_master.Pkid')
            ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
            ->where('tb_cmn_scheme_master.Record_Active_Flag', 1)
            ->get(['tb_cmn_scheme_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_policy_master.Policy_Name'])
            ->sortBy('Pkid');
        $govPolicy      =       TbCmnPolicyMaster::all()->sortBy('Policy_Name');
        $schemShortName      =       TbCmnSchemeShortName::where('tb_cmn_scheme_short_name.Record_Active_Flag', '1')
            ->get()->sortBy('Short_Name');
        $html_view = view("master-ui.list-view.subsidy-master-list-view", compact('schemeMaster', 'govPolicy', 'schemShortName'))->render();

        $MODE = 'EDT';
        $schemeMasterUpdate      =       TbCmnSchemeMaster::join('tb_cmn_status_master', 'tb_cmn_scheme_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->join('tb_cmn_policy_master', 'tb_cmn_scheme_master.Gov_policy', '=', 'tb_cmn_policy_master.Pkid')
            ->where('tb_cmn_scheme_master.Pkid',  $id)
            ->where('tb_cmn_scheme_master.Record_Active_Flag', 1)
            ->get(['tb_cmn_scheme_master.*', 'tb_cmn_status_master.Status_Name', 'tb_cmn_policy_master.Policy_Name']);

        $html_view1 = view("master-ui.modal.edit-view-subsidy-master-modal", compact('schemeMasterUpdate', 'MODE', 'govPolicy', 'schemShortName'))->render();

        if ($status == "success") {
            return response()->json(["status" => $status, "message" => "Success! Scheme master updated.", "body" => $html_view, "body1" => $html_view1]);
        } else {
            return response()->json(["status" => $status, "message" => "Alert! Scheme master not updates"]);
        }
    }
}
