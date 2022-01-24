<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class WeeklyReport extends Model
{
    public $table = "weekly_report";
    use HasApiTokens, HasFactory, Notifiable;
    protected $fillable = [
        'batch_id',
        'day',
        'week',
        'date',
        'remarks',
        'comments',
    ];
}
