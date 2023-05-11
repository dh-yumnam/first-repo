<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class TbCmnFundAllocationMaster
 * 
 * @property int $Pkid
 * @property int|null $Scheme_Id_Fk
 * @property float|null $Total_Allocated_Amount
 * @property int|null $Status_Id_Fk
 * @property int|null $Fund_Id_Fk
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
 * @property TbCmnFundMaster|null $tb_cmn_fund_master
 * @property TbCmnSchemeMaster|null $tb_cmn_scheme_master
 * @property TbCmnStatusMaster|null $tb_cmn_status_master
 * @property Collection|TbCmnFundAllocationTxn[] $tb_cmn_fund_allocation_txns
 *
 * @package App\Models
 */
class TbCmnFundAllocationMaster extends Model
{
	protected $table = 'tb_cmn_fund_allocation_master';
	protected $primaryKey = 'Pkid';
	public $timestamps = false;

	protected $casts = [
		'Scheme_Id_Fk' => 'int',
		'Total_Allocated_Amount' => 'float',
		'Status_Id_Fk' => 'int',
		'Fund_Id_Fk' => 'int',
		'Record_Active_Flag' => 'int',
		'Branch_Office_Code' => 'int',
		'Company_Code' => 'int',
		'Etl_Id' => 'int',
		'Created_By' => 'int',
		'Updated_By' => 'int'
	];

	protected $dates = [
		'Record_Insert_Date',
		'Record_Update_Date',
		'Etl_Date'
	];

	protected $fillable = [
		'Scheme_Id_Fk',
		'Total_Allocated_Amount',
		'Status_Id_Fk',
		'Fund_Id_Fk',
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

	public function tb_cmn_fund_master()
	{
		return $this->belongsTo(TbCmnFundMaster::class, 'Fund_Id_Fk');
	}

	public function tb_cmn_scheme_master()
	{
		return $this->belongsTo(TbCmnSchemeMaster::class, 'Scheme_Id_Fk');
	}

	public function tb_cmn_status_master()
	{
		return $this->belongsTo(TbCmnStatusMaster::class, 'Status_Id_Fk');
	}

	public function tb_cmn_fund_allocation_txns()
	{
		return $this->hasMany(TbCmnFundAllocationTxn::class, 'Allocation_Master_Id_Fk');
	}
}
