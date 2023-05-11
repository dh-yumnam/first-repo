<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class TbLoginTrack
 * 
 * @property int $Pkid
 * @property int|null $Record_Active_Flag_User
 * @property int|null $Record_Active_Flag_Ip
 * @property int|null $Count_Attempt_From_Ip
 * @property int|null $Count_Attempt_From_User
 * @property int|null $UserId
 * @property string|null $UserIp_Address
 * @property int|null $Check_Flag
 * @property Carbon|null $Record_Insert_Date
 * @property Carbon|null $Record_Insert_Time
 * @property Carbon|null $Record_Update_Date
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
 *
 * @package App\Models
 */
class TbLoginTrack extends Model
{
	protected $table = 'tb_login_track';
	protected $primaryKey = 'Pkid';
	public $timestamps = false;

	protected $casts = [
		'Record_Active_Flag_User' => 'int',
		'Record_Active_Flag_Ip' => 'int',
		'Count_Attempt_From_Ip' => 'int',
		'Count_Attempt_From_User' => 'int',
		'UserId' => 'int',
		'Check_Flag' => 'int',
		'Application_User_Id' => 'int',
		'Branch_Office_Code' => 'int',
		'Company_Code' => 'int',
		'Etl_Id' => 'int',
		'Created_By' => 'int',
		'Updated_By' => 'int'
	];

	protected $dates = [
		'Record_Insert_Date',
		'Record_Insert_Time',
		'Record_Update_Date',
		'Etl_Date'
	];

	protected $fillable = [
		'Record_Active_Flag_User',
		'Record_Active_Flag_Ip',
		'Count_Attempt_From_Ip',
		'Count_Attempt_From_User',
		'UserId',
		'UserIp_Address',
		'Check_Flag',
		'Record_Insert_Date',
		'Record_Insert_Time',
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
		return $this->belongsTo(TbCmnUser::class, 'UserId');
	}
}
