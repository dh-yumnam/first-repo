<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class TbCmnSchemeMaster
 * 
 * @property int $Pkid
 * @property string|null $Scheme_Name
 * @property int|null $Gov_policy
 * @property string|null $Description
 * @property int|null $Year
 * @property int|null $Status_Id
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
 * @property int|null $Scheme_Short_Name_Id_Fk
 * 
 * @property TbCmnPolicyMaster|null $tb_cmn_policy_master
 * @property TbCmnStatusMaster|null $tb_cmn_status_master
 * @property Collection|TbBenificiarySchemeTxn[] $tb_benificiary_scheme_txns
 * @property Collection|TbCmnFundAllocationMaster[] $tb_cmn_fund_allocation_masters
 * @property Collection|TbCmnFundAllocationTxn[] $tb_cmn_fund_allocation_txns
 * @property Collection|TbCmnFundMaster[] $tb_cmn_fund_masters
 * @property Collection|TbCmnNedfiBankMaster[] $tb_cmn_nedfi_bank_masters
 *
 * @package App\Models
 */
class TbCmnSchemeMaster extends Model
{
	protected $table = 'tb_cmn_scheme_master';
	protected $primaryKey = 'Pkid';
	public $timestamps = false;

	protected $casts = [
		'Gov_policy' => 'int',
		'Year' => 'int',
		'Status_Id' => 'int',
		'Record_Active_Flag' => 'int',
		'Branch_Office_Code' => 'int',
		'Company_Code' => 'int',
		'Etl_Id' => 'int',
		'Created_By' => 'int',
		'Updated_By' => 'int',
		'Scheme_Short_Name_Id_Fk' => 'int'
	];

	protected $dates = [
		'Record_Insert_Date',
		'Record_Update_Date',
		'Etl_Date'
	];

	protected $fillable = [
		'Scheme_Name',
		'Gov_policy',
		'Description',
		'Year',
		'Status_Id',
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
		'Scheme_Short_Name_Id_Fk'
	];

	public function tb_cmn_policy_master()
	{
		return $this->belongsTo(TbCmnPolicyMaster::class, 'Gov_policy');
	}

	public function tb_cmn_status_master()
	{
		return $this->belongsTo(TbCmnStatusMaster::class, 'Status_Id');
	}

	public function tb_benificiary_scheme_txns()
	{
		return $this->hasMany(TbBenificiarySchemeTxn::class, 'Scheme_Id_Fk');
	}

	public function tb_cmn_fund_allocation_masters()
	{
		return $this->hasMany(TbCmnFundAllocationMaster::class, 'Scheme_Id_Fk');
	}

	public function tb_cmn_fund_allocation_txns()
	{
		return $this->hasMany(TbCmnFundAllocationTxn::class, 'Scheme_Id_Fk');
	}

	public function tb_cmn_fund_masters()
	{
		return $this->hasMany(TbCmnFundMaster::class, 'Scheme_Id');
	}

	public function tb_cmn_nedfi_bank_masters()
	{
		return $this->hasMany(TbCmnNedfiBankMaster::class, 'Scheme_Id_Fk');
	}
}
