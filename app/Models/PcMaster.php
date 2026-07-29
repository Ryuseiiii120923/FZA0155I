<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class PcMaster extends Model
{
    protected $table = 'PCMaster';
     protected $connection = 'sqlsrv';
    protected $primaryKey = 'RecNo';
    public $incrementing = false;
    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'HostName',
        'UserName',
        'PlantDesgination',
    ];
}