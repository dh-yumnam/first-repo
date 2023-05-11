<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class TbCmnAddressTypeMaster
 * 
 * @property int $Pkid
 * @property string|null $Address_Type_Name
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
 * @property string|null $Description
 * 
 * @property TbCmnUser|null $tb_cmn_user
 * @property Collection|TbCmnAddress[] $tb_cmn_addresses
 * @property Collection|TbCmnBenificiaryAddressTxn[] $tb_cmn_benificiary_address_txns
 *
 * @package App\Models
 */
class TbCmnAddressTypeMaster extends Model
{
	protected $table = 'tb_cmn_address_type_master';
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
		'Address_Type_Name',
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
		'Description'
	];

	public function tb_cmn_user()
	{
		return $this->belongsTo(TbCmnUser::class, 'Updated_By');
	}

	public function tb_cmn_addresses()
	{
		return $this->hasMany(TbCmnAddress::class, 'Address_Type_Id_Fk');
	}

	public function tb_cmn_benificiary_address_txns()
	{
		return $this->hasMany(TbCmnBenificiaryAddressTxn::class, 'Address_Type_Id_Fk');
	}
}
