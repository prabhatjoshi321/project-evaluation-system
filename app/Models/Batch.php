<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class Batch extends Model
{
    public $table = "batch";
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'batch_id',
        'branch',
        'USN'
    ];
}
