<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class TbCmnBenificiaryMaster
 * 
 * @property int $Pkid
 * @property string $Benificiary_Name
 * @property string|null $Pan_No
 * @property string|null $Aadhaar_no
 * @property int|null $Sector_Id_Fk
 * @property int|null $Raw_Materials_Id_Fk
 * @property int|null $Finish_Goods_Id_Fk
 * @property string|null $Industry_Regn_No
 * @property Carbon|null $Industry_Regn_Date
 * @property int|null $Gov_Policy_Id
 * @property string|null $GST_No
 * @property Carbon|null $Production_Date
 * @property string|null $Proposal_For
 * @property string|null $Distance
 * @property string|null $Unit_Status
 * @property string|null $Activity
 * @property string|null $Prob_License_No
 * @property Carbon|null $Prob_License_Date
 * @property string|null $PMT_License_No
 * @property Carbon|null $PMT_License_Date
 * @property string|null $Subsidy_Regn_No
 * @property Carbon|null $Subsidy_Regn_Date
 * @property string|null $Production_Capacity
 * @property int|null $Unit_Id_Fk
 * @property int|null $Bank_Id_Fk
 * @property string|null $Bank_Acc_no
 * @property int|null $Emp_Generation_No
 * @property int|null $Status_Id
 * @property int|null $Address_Id_Fk
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
 * @property TbCmnBankMaster|null $tb_cmn_bank_master
 * @property TbCmnSectorMaster|null $tb_cmn_sector_master
 * @property TbCmnStatusMaster|null $tb_cmn_status_master
 * @property TbCmnAddress|null $tb_cmn_address
 * @property Collection|TbBenificiarySchemeTxn[] $tb_benificiary_scheme_txns
 * @property Collection|TbCmnBenificiaryAddressTxn[] $tb_cmn_benificiary_address_txns
 *
 * @package App\Models
 */
class TbCmnBenificiaryMaster extends Model
{
	protected $table = 'tb_cmn_benificiary_master';
	protected $primaryKey = 'Pkid';
	public $timestamps = false;

	protected $casts = [
		'Sector_Id_Fk' => 'int',
		'Raw_Materials_Id_Fk' => 'int',
		'Finish_Goods_Id_Fk' => 'int',
		'Gov_Policy_Id' => 'int',
		'Unit_Id_Fk' => 'int',
		'Bank_Id_Fk' => 'int',
		'Emp_Generation_No' => 'int',
		'Status_Id' => 'int',
		'Address_Id_Fk' => 'int',
		'Record_Active_Flag' => 'int',
		'Branch_Office_Code' => 'int',
		'Company_Code' => 'int',
		'Etl_Id' => 'int',
		'Created_By' => 'int',
		'Updated_By' => 'int'
	];

	protected $dates = [
		'Industry_Regn_Date',
		'Production_Date',
		'Prob_License_Date',
		'PMT_License_Date',
		'Subsidy_Regn_Date',
		'Record_Insert_Date',
		'Record_Update_Date',
		'Etl_Date'
	];

	protected $fillable = [
		'Benificiary_Name',
		'Pan_No',
		'Aadhaar_no',
		'Sector_Id_Fk',
		'Raw_Materials_Id_Fk',
		'Finish_Goods_Id_Fk',
		'Industry_Regn_No',
		'Industry_Regn_Date',
		'Gov_Policy_Id',
		'GST_No',
		'Production_Date',
		'Proposal_For',
		'Distance',
		'Unit_Status',
		'Activity',
		'Prob_License_No',
		'Prob_License_Date',
		'PMT_License_No',
		'PMT_License_Date',
		'Subsidy_Regn_No',
		'Subsidy_Regn_Date',
		'Production_Capacity',
		'Unit_Id_Fk',
		'Bank_Id_Fk',
		'Bank_Acc_no',
		'Emp_Generation_No',
		'Status_Id',
		'Address_Id_Fk',
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

	public function tb_cmn_bank_master()
	{
		return $this->belongsTo(TbCmnBankMaster::class, 'Bank_Id_Fk');
	}

	public function tb_cmn_sector_master()
	{
		return $this->belongsTo(TbCmnSectorMaster::class, 'Sector_Id_Fk');
	}

	public function tb_cmn_status_master()
	{
		return $this->belongsTo(TbCmnStatusMaster::class, 'Status_Id');
	}

	public function tb_cmn_address()
	{
		return $this->belongsTo(TbCmnAddress::class, 'Address_Id_Fk');
	}

	public function tb_benificiary_scheme_txns()
	{
		return $this->hasMany(TbBenificiarySchemeTxn::class, 'Benificiary_Id_Fk');
	}

	public function tb_cmn_benificiary_address_txns()
	{
		return $this->hasMany(TbCmnBenificiaryAddressTxn::class, 'Benificiary_Id_Fk');
	}
}
