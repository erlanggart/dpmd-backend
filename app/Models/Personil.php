<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Personil extends Model
{
    use HasFactory;

    protected $table = 'personil';
    protected $fillable = ['id_bidang', 'nama_personil'];

    public function bidang()
    {
        return $this->belongsTo(Bidang::class, 'id_bidang');
    }
}
