<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class Project extends Model
{
    public $table = "project";
    protected $primaryKey = 'pid';
    use HasApiTokens, HasFactory, Notifiable;
    protected $fillable = [
        'project_name',
        'batch_id',
        'S_ID',
        'file_link',
    ];
}
