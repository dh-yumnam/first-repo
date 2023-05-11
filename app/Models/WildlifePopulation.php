<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class WildlifePopulation
 * 
 * @property int $id
 * @property int $Claim
 * @property int $Disbursement
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models
 */
class WildlifePopulation extends Model
{
	protected $table = 'wildlife_populations';

	protected $casts = [
		'Claim' => 'int',
		'Disbursement' => 'int'
	];

	protected $fillable = [
		'Claim',
		'Disbursement'
	];
}
