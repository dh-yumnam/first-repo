<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class TbSmsClaimTxn
 * 
 * @property int $Pkid
 * @property string|null $Claim_No
 * @property int|null $Audit_Status
 * @property Carbon|null $Claim_Receive_Date
 * @property int|null $Remarks_Id
 * @property string|null $Account_No
 * @property string|null $File_Volume_No
 * @property Carbon|null $Claim_From_Date
 * @property Carbon|null $Claim_To_Date
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
 * @property bool|null $Premium_Eligible_For_Reimbursement
 * @property Carbon|null $Premium_Commencement_Date
 * @property string|null $Insurance_Policy_No
 * @property string|null $Endorsement_Policy_No
 * @property float|null $Subsidy_Claim_Amount
 * @property bool|null $Under_EC_CC
 * @property Carbon|null $EC_CC _Date
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
 * @package App\Models
 */
class TbSmsClaimTxn extends Model
{
	protected $table = 'tb_sms_claim_txn';
	protected $primaryKey = 'Pkid';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'Pkid' => 'int',
		'Audit_Status' => 'int',
		'Remarks_Id' => 'int',
		'Raw_Materials_Quantity' => 'int',
		'Finish_Goods_Quantity' => 'int',
		'Sum_Insured' => 'float',
		'Insured_Stock' => 'int',
		'Value_Covered_Insurance' => 'float',
		'Premium_Actually_Paid' => 'float',
		'Refund' => 'float',
		'Premium_Eligible_For_Reimbursement' => 'bool',
		'Subsidy_Claim_Amount' => 'float',
		'Under_EC_CC' => 'bool',
		'Record_Active_Flag' => 'int',
		'Branch_Office_Code' => 'int',
		'Company_Code' => 'int',
		'Etl_Id' => 'int',
		'Created_By' => 'int',
		'Updated_By' => 'int'
	];

	protected $dates = [
		'Claim_Receive_Date',
		'Claim_From_Date',
		'Claim_To_Date',
		'Premium_Commencement_Date',
		'EC_CC _Date',
		'Record_Insert_Date',
		'Record_Update_Date',
		'Etl_Date'
	];

	protected $fillable = [
		'Claim_No',
		'Audit_Status',
		'Claim_Receive_Date',
		'Remarks_Id',
		'Account_No',
		'File_Volume_No',
		'Claim_From_Date',
		'Claim_To_Date',
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
		'Under_EC_CC',
		'EC_CC _Date',
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
}
