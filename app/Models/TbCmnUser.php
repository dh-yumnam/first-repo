<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class TbCmnUser
 * 
 * @property int $Pkid
 * @property string|null $User_Id
 * @property int|null $Employee_Id
 * @property string|null $User_Password
 * @property string|null $Confirm_Password
 * @property string|null $User_Designation
 * @property string|null $User_Email
 * @property string|null $Primary_Mobile_No
 * @property int $Approval_Flag
 * @property Carbon|null $Start_Date
 * @property Carbon|null $End_Date
 * @property int|null $Record_Active_Flag
 * @property Carbon|null $Record_Insert_Date
 * @property Carbon|null $Record_Update_Date
 * @property Carbon|null $Last_Password_entry_Date
 * @property string|null $Curr_Sess_Token
 * @property int|null $User_Type
 * @property int|null $Last_Active_Time
 * @property string|null $Application_User_Ip_Address
 * @property int|null $Application_User_Id
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
 * @property TbCmnStatusMaster $tb_cmn_status_master
 * @property Collection|TbChangePasswordHistory[] $tb_change_password_histories
 * @property Collection|TbCmnAddress[] $tb_cmn_addresses
 * @property Collection|TbCmnAddressTypeMaster[] $tb_cmn_address_type_masters
 * @property Collection|TbCmnDistrictMaster[] $tb_cmn_district_masters
 * @property Collection|TbCmnRoleAccess[] $tb_cmn_role_accesses
 * @property Collection|TbCmnStateMaster[] $tb_cmn_state_masters
 * @property Collection|TbCmnUser[] $tb_cmn_users
 * @property Collection|TbLoginTrack[] $tb_login_tracks
 *
 * @package App\Models
 */
class TbCmnUser extends Model
{
	protected $table = 'tb_cmn_user';
	protected $primaryKey = 'Pkid';
	public $timestamps = false;

	protected $casts = [
		'Employee_Id' => 'int',
		'Approval_Flag' => 'int',
		'Record_Active_Flag' => 'int',
		'User_Type' => 'int',
		'Last_Active_Time' => 'int',
		'Application_User_Id' => 'int',
		'Branch_Office_Code' => 'int',
		'Company_Code' => 'int',
		'Etl_Id' => 'int',
		'Created_By' => 'int',
		'Updated_By' => 'int'
	];

	protected $dates = [
		'Start_Date',
		'End_Date',
		'Record_Insert_Date',
		'Record_Update_Date',
		'Last_Password_entry_Date',
		'Etl_Date'
	];

	protected $fillable = [
		'User_Id',
		'Employee_Id',
		'User_Password',
		'Confirm_Password',
		'User_Designation',
		'User_Email',
		'Primary_Mobile_No',
		'Approval_Flag',
		'Start_Date',
		'End_Date',
		'Record_Active_Flag',
		'Record_Insert_Date',
		'Record_Update_Date',
		'Last_Password_entry_Date',
		'Curr_Sess_Token',
		'User_Type',
		'Last_Active_Time',
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

	public function tb_cmn_status_master()
	{
		return $this->belongsTo(TbCmnStatusMaster::class, 'Approval_Flag');
	}

	public function tb_change_password_histories()
	{
		return $this->hasMany(TbChangePasswordHistory::class, 'UserId');
	}

	public function tb_cmn_addresses()
	{
		return $this->hasMany(TbCmnAddress::class, 'Updated_By');
	}

	public function tb_cmn_address_type_masters()
	{
		return $this->hasMany(TbCmnAddressTypeMaster::class, 'Updated_By');
	}

	public function tb_cmn_district_masters()
	{
		return $this->hasMany(TbCmnDistrictMaster::class, 'Updated_By');
	}

	public function tb_cmn_role_accesses()
	{
		return $this->hasMany(TbCmnRoleAccess::class, 'User_Id_Fk');
	}

	public function tb_cmn_state_masters()
	{
		return $this->hasMany(TbCmnStateMaster::class, 'Updated_By');
	}

	public function tb_cmn_users()
	{
		return $this->hasMany(TbCmnUser::class, 'Updated_By');
	}

	public function tb_login_tracks()
	{
		return $this->hasMany(TbLoginTrack::class, 'UserId');
	}
}
