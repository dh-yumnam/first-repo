<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class TbChangePasswordHistory
 * 
 * @property int $Pkid
 * @property string|null $Username
 * @property string|null $OldPassword
 * @property string|null $NewPassword
 * @property int|null $UserId
 * @property int|null $Is_Default
 * @property Carbon|null $Record_Insert_Time
 * @property Carbon|null $Record_Insert_Date
 * @property Carbon|null $Record_Update_Date
 * @property int|null $Record_Active_Flag
 * @property int|null $Application_User_Id
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
 * @property TbCmnUser|null $tb_cmn_user
 *
 * @package App\Models
 */
class TbChangePasswordHistory extends Model
{
	protected $table = 'tb_change_password_history';
	protected $primaryKey = 'Pkid';
	public $timestamps = false;

	protected $casts = [
		'UserId' => 'int',
		'Is_Default' => 'int',
		'Record_Active_Flag' => 'int',
		'Application_User_Id' => 'int',
		'Branch_Office_Code' => 'int',
		'Company_Code' => 'int',
		'Etl_Id' => 'int',
		'Created_By' => 'int',
		'Updated_By' => 'int'
	];

	protected $dates = [
		'Record_Insert_Time',
		'Record_Insert_Date',
		'Record_Update_Date',
		'Etl_Date'
	];

	protected $fillable = [
		'Username',
		'OldPassword',
		'NewPassword',
		'UserId',
		'Is_Default',
		'Record_Insert_Time',
		'Record_Insert_Date',
		'Record_Update_Date',
		'Record_Active_Flag',
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

	public function tb_cmn_user()
	{
		return $this->belongsTo(TbCmnUser::class, 'UserId');
	}
}
