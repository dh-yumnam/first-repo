<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class TbCmnBenificiaryAddressTxn
 * 
 * @property int $Pkid
 * @property int|null $Benificiary_Id_Fk
 * @property int|null $Address_Id_Fk
 * @property int|null $Address_Type_Id_Fk
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
 * @property TbCmnAddress|null $tb_cmn_address
 * @property TbCmnBenificiaryMaster|null $tb_cmn_benificiary_master
 * @property TbCmnAddressTypeMaster|null $tb_cmn_address_type_master
 *
 * @package App\Models
 */
class TbCmnBenificiaryAddressTxn extends Model
{
	protected $table = 'tb_cmn_benificiary_address_txn';
	protected $primaryKey = 'Pkid';
	public $timestamps = false;

	protected $casts = [
		'Benificiary_Id_Fk' => 'int',
		'Address_Id_Fk' => 'int',
		'Address_Type_Id_Fk' => 'int',
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
		'Benificiary_Id_Fk',
		'Address_Id_Fk',
		'Address_Type_Id_Fk',
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

	public function tb_cmn_address()
	{
		return $this->belongsTo(TbCmnAddress::class, 'Address_Id_Fk');
	}

	public function tb_cmn_benificiary_master()
	{
		return $this->belongsTo(TbCmnBenificiaryMaster::class, 'Benificiary_Id_Fk');
	}

	public function tb_cmn_address_type_master()
	{
		return $this->belongsTo(TbCmnAddressTypeMaster::class, 'Address_Type_Id_Fk');
	}
}
