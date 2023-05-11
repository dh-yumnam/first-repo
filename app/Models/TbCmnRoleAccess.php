<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class TbCmnRoleAccess
 * 
 * @property int $Pkid
 * @property int|null $User_Id_Fk
 * @property int|null $Module_Id_Fk
 * @property int|null $Role_Id_Fk
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
 * 
 * @property TbCmnModuleMaster|null $tb_cmn_module_master
 * @property TbCmnRoleMaster|null $tb_cmn_role_master
 * @property TbCmnStatusMaster|null $tb_cmn_status_master
 * @property TbCmnUser|null $tb_cmn_user
 *
 * @package App\Models
 */
class TbCmnRoleAccess extends Model
{
	protected $table = 'tb_cmn_role_access';
	protected $primaryKey = 'Pkid';
	public $timestamps = false;

	protected $casts = [
		'User_Id_Fk' => 'int',
		'Module_Id_Fk' => 'int',
		'Role_Id_Fk' => 'int',
		'Status_Id' => 'int',
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
		'User_Id_Fk',
		'Module_Id_Fk',
		'Role_Id_Fk',
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
		'Updated_By'
	];

	public function tb_cmn_module_master()
	{
		return $this->belongsTo(TbCmnModuleMaster::class, 'Module_Id_Fk');
	}

	public function tb_cmn_role_master()
	{
		return $this->belongsTo(TbCmnRoleMaster::class, 'Role_Id_Fk');
	}

	public function tb_cmn_status_master()
	{
		return $this->belongsTo(TbCmnStatusMaster::class, 'Status_Id');
	}

	public function tb_cmn_user()
	{
		return $this->belongsTo(TbCmnUser::class, 'User_Id_Fk');
	}
}
