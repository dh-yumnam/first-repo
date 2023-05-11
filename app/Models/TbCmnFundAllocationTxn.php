<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class TbCmnFundAllocationTxn
 * 
 * @property int $Pkid
 * @property int|null $Allocation_Master_Id_Fk
 * @property int|null $Claim_Id_Fk
 * @property float|null $Claimed_Amount
 * @property float|null $Allocated_Amount
 * @property float|null $Paid_Amount
 * @property Carbon|null $From_Date
 * @property Carbon|null $To_Date
 * @property int|null $Scheme_Id_Fk
 * @property int|null $Fund_Id_Fk
 * @property int|null $State_Id_Fk
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
 * @property TbSmsClaimMaster|null $tb_sms_claim_master
 * @property TbCmnFundMaster|null $tb_cmn_fund_master
 * @property TbCmnFundAllocationMaster|null $tb_cmn_fund_allocation_master
 * @property TbCmnSchemeMaster|null $tb_cmn_scheme_master
 * @property TbCmnStateMaster|null $tb_cmn_state_master
 * @property Collection|TbSmsDisbursementDetail[] $tb_sms_disbursement_details
 *
 * @package App\Models
 */
class TbCmnFundAllocationTxn extends Model
{
	protected $table = 'tb_cmn_fund_allocation_txn';
	protected $primaryKey = 'Pkid';
	public $timestamps = false;

	protected $casts = [
		'Allocation_Master_Id_Fk' => 'int',
		'Claim_Id_Fk' => 'int',
		'Claimed_Amount' => 'float',
		'Allocated_Amount' => 'float',
		'Paid_Amount' => 'float',
		'Scheme_Id_Fk' => 'int',
		'Fund_Id_Fk' => 'int',
		'State_Id_Fk' => 'int',
		'Record_Active_Flag' => 'int',
		'Branch_Office_Code' => 'int',
		'Company_Code' => 'int',
		'Etl_Id' => 'int',
		'Created_By' => 'int',
		'Updated_By' => 'int'
	];

	protected $dates = [
		'From_Date',
		'To_Date',
		'Record_Insert_Date',
		'Record_Update_Date',
		'Etl_Date'
	];

	protected $fillable = [
		'Allocation_Master_Id_Fk',
		'Claim_Id_Fk',
		'Claimed_Amount',
		'Allocated_Amount',
		'Paid_Amount',
		'From_Date',
		'To_Date',
		'Scheme_Id_Fk',
		'Fund_Id_Fk',
		'State_Id_Fk',
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

	public function tb_sms_claim_master()
	{
		return $this->belongsTo(TbSmsClaimMaster::class, 'Claim_Id_Fk');
	}

	public function tb_cmn_fund_master()
	{
		return $this->belongsTo(TbCmnFundMaster::class, 'Fund_Id_Fk');
	}

	public function tb_cmn_fund_allocation_master()
	{
		return $this->belongsTo(TbCmnFundAllocationMaster::class, 'Allocation_Master_Id_Fk');
	}

	public function tb_cmn_scheme_master()
	{
		return $this->belongsTo(TbCmnSchemeMaster::class, 'Scheme_Id_Fk');
	}

	public function tb_cmn_state_master()
	{
		return $this->belongsTo(TbCmnStateMaster::class, 'State_Id_Fk');
	}

	public function tb_sms_disbursement_details()
	{
		return $this->hasMany(TbSmsDisbursementDetail::class, 'Allocation_Id_Fk');
	}
}
