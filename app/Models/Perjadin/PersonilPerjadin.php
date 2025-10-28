<?php

namespace App\Models\Perjadin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Perjadin\Bidang;

class PersonilPerjadin extends Model
{
    use HasFactory;
    
    protected $table = 'personil';
    protected $primaryKey = 'id_personil';
    
    protected $fillable = [
        'id_bidang',
        'nama_personil'
    ];
    
    public function bidang()
    {
        return $this->belongsTo(Bidang::class, 'id_bidang', 'id');
    }
}
