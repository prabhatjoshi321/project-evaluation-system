<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class ProjectEvaluation extends Model
{
    public $table = "project_evaluation";
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'pid',
        'USN',
        'marks'
    ];

}
