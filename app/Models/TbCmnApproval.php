<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class TbCmnApproval
 * 
 * @property int $Pkid
 * @property string|null $Remarks
 * @property Carbon|null $Approval_Date
 * @property int|null $Status_Id_Fk
 * @property string|null $Module
 * @property int|null $Record_Id_Fk
 *
 * @package App\Models
 */
class TbCmnApproval extends Model
{
	protected $table = 'tb_cmn_approval';
	protected $primaryKey = 'Pkid';
	public $timestamps = false;

	protected $casts = [
		'Status_Id_Fk' => 'int',
		'Record_Id_Fk' => 'int'
	];

	protected $dates = [
		'Approval_Date'
	];

	protected $fillable = [
		'Remarks',
		'Approval_Date',
		'Status_Id_Fk',
		'Module',
		'Record_Id_Fk'
	];
}
