<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class TbSubsidySlcDateTxn
 * 
 * @property int $Pkid
 * @property int|null $Subsidy_Id_fk
 * @property Carbon|null $Slc_Date
 * @property string|null $tb_subsidy_slc_date_txncol
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
 * @package App\Models
 */
class TbSubsidySlcDateTxn extends Model
{
	protected $table = 'tb_subsidy_slc_date_txn';
	protected $primaryKey = 'Pkid';
	public $timestamps = false;

	protected $casts = [
		'Subsidy_Id_fk' => 'int',
		'Record_Active_Flag' => 'int',
		'Branch_Office_Code' => 'int',
		'Company_Code' => 'int',
		'Etl_Id' => 'int',
		'Created_By' => 'int',
		'Updated_By' => 'int'
	];

	protected $dates = [
		'Slc_Date',
		'Record_Insert_Date',
		'Record_Update_Date',
		'Etl_Date'
	];

	protected $fillable = [
		'Subsidy_Id_fk',
		'Slc_Date',
		'tb_subsidy_slc_date_txncol',
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
}
