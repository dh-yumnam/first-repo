<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class TbCmnAddress
 * 
 * @property int $Pkid
 * @property int|null $Address_Type_Id_Fk
 * @property string|null $Address_Title
 * @property string|null $Address1
 * @property string|null $Address2
 * @property string|null $owner_lease
 * @property Carbon|null $Owner_Lease_From
 * @property Carbon|null $Owner_Lease_To
 * @property int|null $State_Code
 * @property int|null $District_Id
 * @property string|null $City_name
 * @property string|null $Block_Village
 * @property int|null $Pin_Number
 * @property string|null $Police_Station
 * @property string|null $Post_Office
 * @property string|null $gps_latitude
 * @property string|null $gps_logitude
 * @property string|null $landmark
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
 * @property TbCmnAddressTypeMaster|null $tb_cmn_address_type_master
 * @property TbCmnUser|null $tb_cmn_user
 * @property TbCmnDistrictMaster|null $tb_cmn_district_master
 * @property TbCmnStateMaster|null $tb_cmn_state_master
 * @property Collection|TbCmnBenificiaryAddressTxn[] $tb_cmn_benificiary_address_txns
 * @property Collection|TbCmnBenificiaryMaster[] $tb_cmn_benificiary_masters
 *
 * @package App\Models
 */
class TbCmnAddress extends Model
{
	protected $table = 'tb_cmn_address';
	protected $primaryKey = 'Pkid';
	public $timestamps = false;

	protected $casts = [
		'Address_Type_Id_Fk' => 'int',
		'State_Code' => 'int',
		'District_Id' => 'int',
		'Pin_Number' => 'int',
		'Record_Active_Flag' => 'int',
		'Branch_Office_Code' => 'int',
		'Company_Code' => 'int',
		'Etl_Id' => 'int',
		'Created_By' => 'int',
		'Updated_By' => 'int'
	];

	protected $dates = [
		'Owner_Lease_From',
		'Owner_Lease_To',
		'Record_Insert_Date',
		'Record_Update_Date',
		'Etl_Date'
	];

	protected $fillable = [
		'Address_Type_Id_Fk',
		'Address_Title',
		'Address1',
		'Address2',
		'owner_lease',
		'Owner_Lease_From',
		'Owner_Lease_To',
		'State_Code',
		'District_Id',
		'City_name',
		'Block_Village',
		'Pin_Number',
		'Police_Station',
		'Post_Office',
		'gps_latitude',
		'gps_logitude',
		'landmark',
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

	public function tb_cmn_address_type_master()
	{
		return $this->belongsTo(TbCmnAddressTypeMaster::class, 'Address_Type_Id_Fk');
	}

	public function tb_cmn_user()
	{
		return $this->belongsTo(TbCmnUser::class, 'Updated_By');
	}

	public function tb_cmn_district_master()
	{
		return $this->belongsTo(TbCmnDistrictMaster::class, 'District_Id');
	}

	public function tb_cmn_state_master()
	{
		return $this->belongsTo(TbCmnStateMaster::class, 'State_Code');
	}

	public function tb_cmn_benificiary_address_txns()
	{
		return $this->hasMany(TbCmnBenificiaryAddressTxn::class, 'Address_Id_Fk');
	}

	public function tb_cmn_benificiary_masters()
	{
		return $this->hasMany(TbCmnBenificiaryMaster::class, 'Address_Id_Fk');
	}
}
