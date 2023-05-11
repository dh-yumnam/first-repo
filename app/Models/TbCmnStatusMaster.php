<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class TbCmnStatusMaster
 * 
 * @property int $Pkid
 * @property string|null $Status_Name
 * @property string|null $Description
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
 * @property Collection|TbCmnBenificiaryMaster[] $tb_cmn_benificiary_masters
 * @property Collection|TbCmnFinishGoodsMaster[] $tb_cmn_finish_goods_masters
 * @property Collection|TbCmnFundAllocationMaster[] $tb_cmn_fund_allocation_masters
 * @property Collection|TbCmnFundMaster[] $tb_cmn_fund_masters
 * @property Collection|TbCmnModuleMaster[] $tb_cmn_module_masters
 * @property Collection|TbCmnNedfiBankMaster[] $tb_cmn_nedfi_bank_masters
 * @property Collection|TbCmnRawMaterialMaster[] $tb_cmn_raw_material_masters
 * @property Collection|TbCmnReasonMaster[] $tb_cmn_reason_masters
 * @property Collection|TbCmnRoleAccess[] $tb_cmn_role_accesses
 * @property Collection|TbCmnRoleMaster[] $tb_cmn_role_masters
 * @property Collection|TbCmnSchemeMaster[] $tb_cmn_scheme_masters
 * @property Collection|TbCmnUnitMaster[] $tb_cmn_unit_masters
 * @property Collection|TbCmnUser[] $tb_cmn_users
 * @property Collection|TbSmsClaimMaster[] $tb_sms_claim_masters
 * @property Collection|TbSmsDisbursementDetail[] $tb_sms_disbursement_details
 *
 * @package App\Models
 */
class TbCmnStatusMaster extends Model
{
	protected $table = 'tb_cmn_status_master';
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
		'Status_Name',
		'Description',
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

	public function tb_cmn_benificiary_masters()
	{
		return $this->hasMany(TbCmnBenificiaryMaster::class, 'Status_Id');
	}

	public function tb_cmn_finish_goods_masters()
	{
		return $this->hasMany(TbCmnFinishGoodsMaster::class, 'Status_Id');
	}

	public function tb_cmn_fund_allocation_masters()
	{
		return $this->hasMany(TbCmnFundAllocationMaster::class, 'Status_Id_Fk');
	}

	public function tb_cmn_fund_masters()
	{
		return $this->hasMany(TbCmnFundMaster::class, 'Status_Id');
	}

	public function tb_cmn_module_masters()
	{
		return $this->hasMany(TbCmnModuleMaster::class, 'Status_Id');
	}

	public function tb_cmn_nedfi_bank_masters()
	{
		return $this->hasMany(TbCmnNedfiBankMaster::class, 'Status_Id');
	}

	public function tb_cmn_raw_material_masters()
	{
		return $this->hasMany(TbCmnRawMaterialMaster::class, 'Status_Id');
	}

	public function tb_cmn_reason_masters()
	{
		return $this->hasMany(TbCmnReasonMaster::class, 'Status_Id');
	}

	public function tb_cmn_role_accesses()
	{
		return $this->hasMany(TbCmnRoleAccess::class, 'Status_Id');
	}

	public function tb_cmn_role_masters()
	{
		return $this->hasMany(TbCmnRoleMaster::class, 'Status_Id');
	}

	public function tb_cmn_scheme_masters()
	{
		return $this->hasMany(TbCmnSchemeMaster::class, 'Status_Id');
	}

	public function tb_cmn_unit_masters()
	{
		return $this->hasMany(TbCmnUnitMaster::class, 'Status_Id');
	}

	public function tb_cmn_users()
	{
		return $this->hasMany(TbCmnUser::class, 'Approval_Flag');
	}

	public function tb_sms_claim_masters()
	{
		return $this->hasMany(TbSmsClaimMaster::class, 'Status_Id');
	}

	public function tb_sms_disbursement_details()
	{
		return $this->hasMany(TbSmsDisbursementDetail::class, 'Status_Id_Fk');
	}
}
