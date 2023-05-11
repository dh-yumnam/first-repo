<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class TbSmsClaimMaster
 * 
 * @property int $Pkid
 * @property string|null $Claim_Id
 * @property int|null $Benificiary_Id_Fk
 * @property int|null $Scheme_Id_Fk
 * @property float|null $Claim_Amount
 * @property Carbon|null $Received_Date
 * @property Carbon|null $Claim_From_Date
 * @property Carbon|null $Claim_To_Date
 * @property string|null $Remarks
 * @property string|null $Claim_Status
 * @property int|null $Status_Id
 * @property string|null $Audit_Status
 * @property string|null $Account_No
 * @property string|null $File_Volume_No
 * @property int|null $Raw_Materials_Quantity
 * @property int|null $Finish_Goods_Quantity
 * @property string|null $Approved_Raw_Materials
 * @property string|null $Approved_Finish_Goods
 * @property string|null $Investment_On_Plant_Machinery
 * @property string|null $Approved_On_Plant_Machinery
 * @property string|null $Approved_Interest_Subsidy
 * @property float|null $Sum_Insured
 * @property int|null $Insured_Stock
 * @property float|null $Value_Covered_Insurance
 * @property float|null $Premium_Actually_Paid
 * @property float|null $Refund
 * @property float|null $Premium_Eligible_For_Reimbursement
 * @property Carbon|null $Premium_Commencement_Date
 * @property string|null $Insurance_Policy_No
 * @property string|null $Endorsement_Policy_No
 * @property float|null $Subsidy_Claim_Amount
 * @property float|null $Investment_On_Building
 * @property float|null $Claim_Balance_Amount
 * @property int|null $Under_EC_CC
 * @property Carbon|null $EC_CC_Date
 * @property string|null $Claim_Year
 * @property int|null $Record_Active_Flag
 * @property Carbon|null $Record_Insert_Date
 * @property Carbon|null $Record_Update_Date
 * @property string|null $Application_User_Id
 * @property string|null $Application_User_Ip_Address
 * @property int|null $Branch_Office_Code
 * @property int|null $Company_Code
 * @property string|null $Custom1
 * @property string|null $Custom2
 * @property string|null $Source_Key
 * @property Carbon|null $Etl_Date
 * @property int|null $Etl_Id
 * @property int|null $Created_By
 * @property int|null $Updated_By
 * 
 * @property TbCmnStatusMaster|null $tb_cmn_status_master
 * @property Collection|TbCmnFundAllocationTxn[] $tb_cmn_fund_allocation_txns
 * @property Collection|TbSmsDisbursementDetail[] $tb_sms_disbursement_details
 *
 * @package App\Models
 */
class TbSmsClaimMaster extends Model
{
	protected $table = 'tb_sms_claim_master';
	protected $primaryKey = 'Pkid';
	public $timestamps = false;

	protected $casts = [
		'Benificiary_Id_Fk' => 'int',
		'Scheme_Id_Fk' => 'int',
		'Claim_Amount' => 'float',
		'Status_Id' => 'int',
		'Raw_Materials_Quantity' => 'int',
		'Finish_Goods_Quantity' => 'int',
		'Sum_Insured' => 'float',
		'Insured_Stock' => 'int',
		'Value_Covered_Insurance' => 'float',
		'Premium_Actually_Paid' => 'float',
		'Refund' => 'float',
		'Premium_Eligible_For_Reimbursement' => 'float',
		'Subsidy_Claim_Amount' => 'float',
		'Investment_On_Building' => 'float',
		'Claim_Balance_Amount' => 'float',
		'Under_EC_CC' => 'int',
		'Record_Active_Flag' => 'int',
		'Branch_Office_Code' => 'int',
		'Company_Code' => 'int',
		'Etl_Id' => 'int',
		'Created_By' => 'int',
		'Updated_By' => 'int'
	];

	protected $dates = [
		'Received_Date',
		'Claim_From_Date',
		'Claim_To_Date',
		'Premium_Commencement_Date',
		'EC_CC_Date',
		'Record_Insert_Date',
		'Record_Update_Date',
		'Etl_Date'
	];

	protected $fillable = [
		'Claim_Id',
		'Benificiary_Id_Fk',
		'Scheme_Id_Fk',
		'Claim_Amount',
		'Received_Date',
		'Claim_From_Date',
		'Claim_To_Date',
		'Remarks',
		'Claim_Status',
		'Status_Id',
		'Audit_Status',
		'Account_No',
		'File_Volume_No',
		'Raw_Materials_Quantity',
		'Finish_Goods_Quantity',
		'Approved_Raw_Materials',
		'Approved_Finish_Goods',
		'Investment_On_Plant_Machinery',
		'Approved_On_Plant_Machinery',
		'Approved_Interest_Subsidy',
		'Sum_Insured',
		'Insured_Stock',
		'Value_Covered_Insurance',
		'Premium_Actually_Paid',
		'Refund',
		'Premium_Eligible_For_Reimbursement',
		'Premium_Commencement_Date',
		'Insurance_Policy_No',
		'Endorsement_Policy_No',
		'Subsidy_Claim_Amount',
		'Investment_On_Building',
		'Claim_Balance_Amount',
		'Under_EC_CC',
		'EC_CC_Date',
		'Claim_Year',
		'Record_Active_Flag',
		'Record_Insert_Date',
		'Record_Update_Date',
		'Application_User_Id',
		'Application_User_Ip_Address',
		'Branch_Office_Code',
		'Company_Code',
		'Custom1',
		'Custom2',
		'Source_Key',
		'Etl_Date',
		'Etl_Id',
		'Created_By',
		'Updated_By'
	];

	public function tb_cmn_status_master()
	{
		return $this->belongsTo(TbCmnStatusMaster::class, 'Status_Id');
	}

	public function tb_cmn_fund_allocation_txns()
	{
		return $this->hasMany(TbCmnFundAllocationTxn::class, 'Claim_Id_Fk');
	}

	public function tb_sms_disbursement_details()
	{
		return $this->hasMany(TbSmsDisbursementDetail::class, 'Claim_Id_Fk');
	}
}
