<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class TbCmnStateMaster
 * 
 * @property int $Pkid
 * @property string $State_Name
 * @property string|null $State_Code
 * @property int|null $Record_Active_Flag
 * @property Carbon|null $Record_Insert_Date
 * @property Carbon|null $Record_Update_Date
 * @property string|null $Application_User_Ip_Address
 * @property string|null $Application_User_Id
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
 * @property TbCmnUser|null $tb_cmn_user
 * @property Collection|TbCmnAddress[] $tb_cmn_addresses
 * @property Collection|TbCmnDistrictMaster[] $tb_cmn_district_masters
 * @property Collection|TbCmnFundAllocationTxn[] $tb_cmn_fund_allocation_txns
 *
 * @package App\Models
 */
class TbCmnStateMaster extends Model
{
	protected $table = 'tb_cmn_state_master';
	protected $primaryKey = 'Pkid';
	public $timestamps = false;

	protected $casts = [
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
		'State_Name',
		'State_Code',
		'Record_Active_Flag',
		'Record_Insert_Date',
		'Record_Update_Date',
		'Application_User_Ip_Address',
		'Application_User_Id',
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

	public function tb_cmn_user()
	{
		return $this->belongsTo(TbCmnUser::class, 'Updated_By');
	}

	public function tb_cmn_addresses()
	{
		return $this->hasMany(TbCmnAddress::class, 'State_Code');
	}

	public function tb_cmn_district_masters()
	{
		return $this->hasMany(TbCmnDistrictMaster::class, 'State_Id_Fk');
	}

	public function tb_cmn_fund_allocation_txns()
	{
		return $this->hasMany(TbCmnFundAllocationTxn::class, 'State_Id_Fk');
	}
}
