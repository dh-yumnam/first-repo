<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class TbSmsDisbursementTxn
 * 
 * @property int $Pkid
 * @property int|null $Allocation_Id_Fk
 * @property int|null $Claim_Id_Fk
 * @property int|null $Bank_Id_Fk
 * @property int|null $Payment_Mode
 * @property string|null $Instrument_No
 * @property string|null $Instrument_Date
 * @property float|null $Disbursement_Amount
 * @property float|null $Allocated_Amount
 * @property string|null $Narration
 * @property int|null $Status_Id_Fk
 * @property Carbon|null $Disbursement_Date
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
 * @property int|null $Disbursement_Master_Id_Fk
 * 
 * @property TbCmnFundAllocationTxn|null $tb_cmn_fund_allocation_txn
 * @property TbCmnBankMaster|null $tb_cmn_bank_master
 * @property TbSmsClaimMaster|null $tb_sms_claim_master
 * @property TbCmnStatusMaster|null $tb_cmn_status_master
 *
 * @package App\Models
 */
class TbSmsDisbursementTxn extends Model
{
	protected $table = 'tb_sms_disbursement_txn';
	protected $primaryKey = 'Pkid';
	public $timestamps = false;

	protected $casts = [
		'Allocation_Id_Fk' => 'int',
		'Claim_Id_Fk' => 'int',
		'Bank_Id_Fk' => 'int',
		'Payment_Mode' => 'int',
		'Disbursement_Amount' => 'float',
		'Allocated_Amount' => 'float',
		'Status_Id_Fk' => 'int',
		'Record_Active_Flag' => 'int',
		'Branch_Office_Code' => 'int',
		'Company_Code' => 'int',
		'Etl_Id' => 'int',
		'Created_By' => 'int',
		'Updated_By' => 'int',
		'Disbursement_Master_Id_Fk' => 'int'
	];

	protected $dates = [
		'Disbursement_Date',
		'Record_Insert_Date',
		'Record_Update_Date',
		'Etl_Date'
	];

	protected $fillable = [
		'Allocation_Id_Fk',
		'Claim_Id_Fk',
		'Bank_Id_Fk',
		'Payment_Mode',
		'Instrument_No',
		'Instrument_Date',
		'Disbursement_Amount',
		'Allocated_Amount',
		'Narration',
		'Status_Id_Fk',
		'Disbursement_Date',
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
		'Updated_By',
		'Disbursement_Master_Id_Fk'
	];

	public function tb_cmn_fund_allocation_txn()
	{
		return $this->belongsTo(TbCmnFundAllocationTxn::class, 'Allocation_Id_Fk');
	}

	public function tb_cmn_bank_master()
	{
		return $this->belongsTo(TbCmnBankMaster::class, 'Bank_Id_Fk');
	}

	public function tb_sms_claim_master()
	{
		return $this->belongsTo(TbSmsClaimMaster::class, 'Claim_Id_Fk');
	}

	public function tb_cmn_status_master()
	{
		return $this->belongsTo(TbCmnStatusMaster::class, 'Status_Id_Fk');
	}
}
