<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class BatchList extends Model
{
    public $table = "batch_list";
    protected $primaryKey = 'batch_id';
    public $incrementing = false;
    use HasFactory, HasFactory, Notifiable;
    protected $fillable = [
        'batch_id',
        'branch',
        'S_ID',
    ];
}
