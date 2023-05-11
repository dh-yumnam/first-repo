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
use App\Models\TbCmnApproval;
use App\Models\TbSmsDisbursementDetail;
use Hamcrest\Arrays\IsArray;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class FinishGoodsController extends Controller
{

    public function fundMasterPanel()
    {
        //return view('benificiary-ui.benificiary-search-result');
        return view('master-ui.panels.fund-master-panel');
    }
    public function finishGoodsMaterialPanel()
    {
        //return view('benificiary-ui.benificiary-search-result');
        return view('master-ui.panels.finish-goods-panel');
    }
    public function finishGoodsModal()
    {
        $html = view('master-ui.modal.finish-goods-modal')->render();
        //return view('benificiary-ui.benificiary-search-result');
        return response()->json(['status' => "success", 'body' => $html]);
    }
    public function searchFinishGoodsResult(Request $request)
    {
        try {
            $dataUI = json_decode($request->getContent());
            $goods_name = $dataUI->goods_name;
            $status = $dataUI->status_id;
            $query = TbCmnFinishGoodsMaster::join('tb_cmn_status_master', 'tb_cmn_finish_goods_master.Status_Id', '=', 'tb_cmn_status_master.Pkid');

            if (!empty($dataUI->goods_name)) {
                $query->where('Goods_Name', 'LIKE', "%{$goods_name}%");
            }
            if (!empty($dataUI->status_id)) {
                $query->where('Status_Id', $status);
            }

            $finishGoods  = $query
                ->where('tb_cmn_finish_goods_master.Record_Active_Flag', '=', 1)
                ->get(['tb_cmn_finish_goods_master.*', 'tb_cmn_status_master.Status_Name']);
        } catch (\Exception $ex) {
            throw $ex;
        }
        $html = view('master-ui.master-search.finish-goods-search', compact('finishGoods'))->render();
        //return view('benificiary-ui.benificiary-search-result');
        return response()->json(['status' => "success", 'body' => $html]);
    }

    public function showFinishGoodsMasterApproval()
    {
        $finishGoods      =      TbCmnFinishGoodsMaster::join('tb_cmn_status_master', 'tb_cmn_finish_goods_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->whereIn('Status_Id', [2])
            ->where('tb_cmn_finish_goods_master.Record_Active_Flag', '1')
            ->get(['tb_cmn_finish_goods_master.*', 'tb_cmn_status_master.Status_Name'])->sortByDesc('Pkid');
        $html_view = view("master-ui.approval-view.finish-goods-approval-view", compact('finishGoods'))->render();
        return response()->json(["status" => "success", "message" => "Success! Finish Goods created.", "body" => $html_view]);
    }
    public function showFinishGoodsMaster()
    {
        $finishGoods      =        TbCmnFinishGoodsMaster::join('tb_cmn_status_master', 'tb_cmn_finish_goods_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
            ->where('tb_cmn_finish_goods_master.Record_Active_Flag', '1')
            ->get(['tb_cmn_finish_goods_master.*', 'tb_cmn_status_master.Status_Name'])
            ->sortByDesc('Pkid');
        $html_view = view("master-ui.list-view.finish-goods-list-view", compact('finishGoods'))->render();
        return response()->json(["status" => "success", "message" => "Success! Finish Goods created.", "body" => $html_view]);
    }

    // ------------- [ store post ] -----------------
    public function addFinishGoodsMaster(Request $request)
    {
        $validationRules = array(
            'Goods_Name'         =>      'required|unique:tb_cmn_finish_goods_master',
            'Description'   =>      'required',
        );

        $validator = Validator::make($request->all(), $validationRules);
        if ($validator->fails()) {
            return response()->json(["status" => "failed", "message" => "Alert! Goods Name has already been taken !"]);
        }
        $status = "success";
        try {
            //  $addMaterials            =           TbCmnRawMaterialMaster::create($request->all());
            $addFinishGoods = new TbCmnFinishGoodsMaster();
            $addFinishGoods->Goods_Name = $request->Goods_Name;
            $addFinishGoods->Description = $request->Description;
            $addFinishGoods->Status_Id = config("constants.CREATED");
            $result = (new CommonController)->insertDefaultColumns($request, $addFinishGoods);
            $addFinishGoods->save();
            $status = "success";
        } catch (\Exception $ex) {
            throw $ex;
            $status = "failed";
        }

        $finishGoods      =       TbCmnFinishGoodsMaster::join('tb_cmn_status_master', 'tb_cmn_finish_goods_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
            ->where('tb_cmn_finish_goods_master.Record_Active_Flag', '1')
            ->get(['tb_cmn_finish_goods_master.*', 'tb_cmn_status_master.Status_Name'])->sortByDesc('Pkid');
        $html_view = view("master-ui.list-view.finish-goods-list-view", compact('finishGoods'))->render();

        $MODE = 'EDT';
        $finishGoodsUpdate      =        TbCmnFinishGoodsMaster::join('tb_cmn_status_master', 'tb_cmn_finish_goods_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->where('tb_cmn_finish_goods_master.Pkid', $addFinishGoods->Pkid)
            ->where('tb_cmn_finish_goods_master.Record_Active_Flag', '1')
            ->get(['tb_cmn_finish_goods_master.*', 'tb_cmn_status_master.Status_Name']);
        $html_view1 = view("master-ui.modal.edit-view-finish-goods-modal", compact('finishGoodsUpdate', 'MODE'))->render();

        if ($status == "success") {
            return response()->json(["status" => "success", "message" => "Success! Finish Goods created.", "data" => $addFinishGoods, "body" => $html_view, "body1" => $html_view1]);
        } else {
            return response()->json(["status" => "failed", "message" => "Alert! Finish Goods not created"]);
        }
    }

    // -------------- [ Delete post ] ---------------
    public function destroyFinishGoodsMaster(Request $request, $raw_id)
    {
        $deleteFinishGoods       =       TbCmnFinishGoodsMaster::where("Pkid", $raw_id)
            ->update([
                'Record_Active_Flag' => '0',
                'Record_Update_Date' => new \DateTime(),
                'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'),
                'Updated_By' => $request->session()->get('id')
            ]);
        $finishGoods     =       TbCmnFinishGoodsMaster::join('tb_cmn_status_master', 'tb_cmn_finish_goods_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
            ->where('tb_cmn_finish_goods_master.Record_Active_Flag', '1')
            ->get(['tb_cmn_finish_goods_master.*', 'tb_cmn_status_master.Status_Name'])->sortByDesc('Pkid');
        $html_view = view("master-ui.list-view.finish-goods-list-view", compact('finishGoods'))->render();
        if ($deleteFinishGoods == 1) {
            return response()->json(["status" => "success", "message" => "Success! Raw Material deleted", "body" => $html_view]);
        } else {
            return response()->json(["status" => "failed", "message" => "Alert! Raw Material not deleted"]);
        }
    }


    public function approveGoodsEntry(Request $request)
    {
        DB::beginTransaction();
        try {
            $dataUI = json_decode($request->getContent());
            $approvalStatus = $dataUI->decision;
            $approvalDate = $dataUI->approval_date;
            $remarks = $dataUI->remarks;
            if (Is_Array($dataUI->check_id)) {
                foreach ($dataUI->check_id as $value) {
                    TbCmnFinishGoodsMaster::where('Pkid', $value)->update(
                        [
                            'Status_Id' => $approvalStatus, 'Record_Update_Date' => new \DateTime(), 'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'),
                            'Updated_By' => $request->session()->get('id')
                        ]
                    );
                    $saveCmnApproval = new TbCmnApproval();
                    $saveCmnApproval->Approval_Date = $approvalDate;
                    $saveCmnApproval->Status_Id_Fk = $approvalStatus;
                    $saveCmnApproval->Remarks = $remarks;
                    $saveCmnApproval->Module = 'Goods master';
                    $saveCmnApproval->Record_Id_Fk =  $value;
                    $saveCmnApproval->save();
                }
            } else {
                TbCmnFinishGoodsMaster::where('Pkid', $dataUI->check_id)->update(
                    [
                        'Status_Id' => $approvalStatus, 'Record_Update_Date' => new \DateTime(), 'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'),
                        'Updated_By' => $request->session()->get('id')
                    ]
                );
                $saveCmnApproval = new TbCmnApproval();
                $saveCmnApproval->Approval_Date = $approvalDate;
                $saveCmnApproval->Status_Id_Fk = $approvalStatus;
                $saveCmnApproval->Remarks = $remarks;
                $saveCmnApproval->Module = 'Goods master';
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
        $finishGoods      =      TbCmnFinishGoodsMaster::join('tb_cmn_status_master', 'tb_cmn_finish_goods_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->whereIn('Status_Id', [2])
            ->where('tb_cmn_finish_goods_master.Record_Active_Flag', '1')
            ->get(['tb_cmn_finish_goods_master.*', 'tb_cmn_status_master.Status_Name'])->sortByDesc('Pkid');
        $html_view = view("master-ui.approval-view.finish-goods-approval-view", compact('finishGoods'))->render();
        if ($status == 'success') {
            return response()->json(["status" => "success", "message" => "Success! Goods master approved", "body" => $html_view]);
        } else {
            return response()->json(["status" => "success", "message" => "Alert! Goods master not approved"]);
        }
    }
    public function viewEditGoodsModal($id, $MODE)
    {
        $finishGoodsUpdate      =        TbCmnFinishGoodsMaster::join('tb_cmn_status_master', 'tb_cmn_finish_goods_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->where('tb_cmn_finish_goods_master.Pkid', $id)
            ->where('tb_cmn_finish_goods_master.Record_Active_Flag', '1')
            ->get(['tb_cmn_finish_goods_master.*', 'tb_cmn_status_master.Status_Name']);
        $html_view = view("master-ui.modal.edit-view-finish-goods-modal", compact('finishGoodsUpdate', 'MODE'))->render();
        //return view('benificiary-ui.benificiary-search-result');
        return response()->json(['status' => "success", 'body' => $html_view]);
    }
    // Approval record
    public function goodsMasterApproval(Request $request, $id)
    {
        $dataUI = json_decode($request->getContent());
        DB::beginTransaction();
        try {
            $tableData = array(
                'Status_Id' => '2', 'Record_Update_Date' => new \DateTime(), 'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'), 'Updated_By' => $request->session()->get('id')
            );
            $updateTable = TbCmnFinishGoodsMaster::where('Pkid',  $id)
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
        $finishGoods      =        TbCmnFinishGoodsMaster::join('tb_cmn_status_master', 'tb_cmn_finish_goods_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
            ->where('tb_cmn_finish_goods_master.Record_Active_Flag', '1')
            ->get(['tb_cmn_finish_goods_master.*', 'tb_cmn_status_master.Status_Name'])
            ->sortBy('Pkid');
        $html_view = view("master-ui.list-view.finish-goods-list-view", compact('finishGoods'))->render();
        if ($status == "success") {
            return response()->json(["status" => $status, "message" => "Success! Finish goods submitted for approval.", "body" => $html_view]);
        } else {
            return response()->json(["status" => $status, "message" => "Alert! Finish goods not submitted for approval"]);
        }
    }

    // ---------------- [ Update post ] -------------
    public function updateFinshGoodsMaster(Request $request, $id)
    {
        $dataUI = json_decode($request->getContent());
        DB::beginTransaction();
        try {
            $goods = TbCmnFinishGoodsMaster::where('Pkid', $id)->get(['tb_cmn_finish_goods_master.*']);
            // $UniqueName = TbCmnBenificiaryMaster::where('Benificiary_Name', $Benificiary_Name)->get();
            $goodsName = null;
            foreach ($goods as $goods) {
                $goodsName = $goods->Goods_Name;
            }
            if ($goodsName != $dataUI->Goods_Name) {
                $DuplicateData = TbCmnFinishGoodsMaster::where('Goods_Name', $dataUI->Goods_Name)
                    ->where('tb_cmn_finish_goods_master.Record_Active_Flag', '1')
                    ->get();
                if (count($DuplicateData) > 0) {
                    return response()->json(["status" => "failed", "message" => "Alert! Duplicate finish goods !"]);
                }
            }
            $addressData = array(
                'Goods_Name' => $dataUI->Goods_Name, 'Description' => $dataUI->Description,
                'Record_Update_Date' => new \DateTime(), 'Application_User_Ip_Address' => $request->session()->get('CLIENT_IP'), 'Updated_By' => $request->session()->get('id')
            );
            $addresBenificiary = TbCmnFinishGoodsMaster::where('Pkid', $id)->update($addressData);
            $status = "success";

            // all good
            DB::commit();
        } catch (\Exception $e) {
            throw $e;
            $status = "failed";
            DB::rollback();
            // something went wrong
        }
        $finishGoods      =        TbCmnFinishGoodsMaster::join('tb_cmn_status_master', 'tb_cmn_finish_goods_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->whereIn('tb_cmn_status_master.Pkid', [1, 3])
            ->where('tb_cmn_finish_goods_master.Record_Active_Flag', '1')
            ->get(['tb_cmn_finish_goods_master.*', 'tb_cmn_status_master.Status_Name'])
            ->sortBy('Pkid');
        $html_view = view("master-ui.list-view.finish-goods-list-view", compact('finishGoods'))->render();
        $MODE = 'EDT';
        $finishGoodsUpdate      =        TbCmnFinishGoodsMaster::join('tb_cmn_status_master', 'tb_cmn_finish_goods_master.Status_Id', '=', 'tb_cmn_status_master.Pkid')
            ->where('tb_cmn_finish_goods_master.Pkid', $id)
            ->where('tb_cmn_finish_goods_master.Record_Active_Flag', '1')
            ->get(['tb_cmn_finish_goods_master.*', 'tb_cmn_status_master.Status_Name']);
        $html_view1 = view("master-ui.modal.edit-view-finish-goods-modal", compact('finishGoodsUpdate', 'MODE'))->render();
        if ($status == "success") {
            return response()->json(["status" => $status, "message" => "Success! Finish goods updated.", "body" => $html_view, "body1" => $html_view1]);
        } else {
            return response()->json(["status" => $status, "message" => "Alert! Finish goods not updates"]);
        }
    }
}
