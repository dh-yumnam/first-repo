<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class TbCmnFundMaster
 * 
 * @property int $Pkid
 * @property string|null $Sanction_Letter
 * @property Carbon|null $Sanction_Date
 * @property float|null $Sanction_Amount
 * @property int|null $Bank_Account_Id
 * @property string|null $Description
 * @property int|null $Status_Id
 * @property int|null $Scheme_Id
 * @property float|null $Fund_Balance
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
 * @property TbCmnNedfiBankMaster|null $tb_cmn_nedfi_bank_master
 * @property TbCmnStatusMaster|null $tb_cmn_status_master
 * @property TbCmnSchemeMaster|null $tb_cmn_scheme_master
 * @property Collection|TbCmnFundAllocationMaster[] $tb_cmn_fund_allocation_masters
 * @property Collection|TbCmnFundAllocationTxn[] $tb_cmn_fund_allocation_txns
 *
 * @package App\Models
 */
class TbCmnFundMaster extends Model
{
	protected $table = 'tb_cmn_fund_master';
	protected $primaryKey = 'Pkid';
	public $timestamps = false;

	protected $casts = [
		'Sanction_Amount' => 'float',
		'Bank_Account_Id' => 'int',
		'Status_Id' => 'int',
		'Scheme_Id' => 'int',
		'Fund_Balance' => 'float',
		'Record_Active_Flag' => 'int',
		'Branch_Office_Code' => 'int',
		'Company_Code' => 'int',
		'Etl_Id' => 'int',
		'Created_By' => 'int',
		'Updated_By' => 'int'
	];

	protected $dates = [
		'Sanction_Date',
		'Record_Insert_Date',
		'Record_Update_Date',
		'Etl_Date'
	];

	protected $fillable = [
		'Sanction_Letter',
		'Sanction_Date',
		'Sanction_Amount',
		'Bank_Account_Id',
		'Description',
		'Status_Id',
		'Scheme_Id',
		'Fund_Balance',
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

	public function tb_cmn_nedfi_bank_master()
	{
		return $this->belongsTo(TbCmnNedfiBankMaster::class, 'Bank_Account_Id');
	}

	public function tb_cmn_status_master()
	{
		return $this->belongsTo(TbCmnStatusMaster::class, 'Status_Id');
	}

	public function tb_cmn_scheme_master()
	{
		return $this->belongsTo(TbCmnSchemeMaster::class, 'Scheme_Id');
	}

	public function tb_cmn_fund_allocation_masters()
	{
		return $this->hasMany(TbCmnFundAllocationMaster::class, 'Fund_Id_Fk');
	}

	public function tb_cmn_fund_allocation_txns()
	{
		return $this->hasMany(TbCmnFundAllocationTxn::class, 'Fund_Id_Fk');
	}
}
