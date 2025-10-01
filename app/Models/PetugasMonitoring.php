<?php<?php



namespace App\Models;namespace App\Models;



use Illuminate\Database\Eloquent\Factories\HasFactory;use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\Model;



class PetugasMonitoring extends Modelclass PetugasMonitoring extends Model

{{

    use HasFactory;    use HasFactory;

        

    protected $table = 'petugas_monitoring';    protected $table = 'petugas_monitoring';

        

    protected $fillable = [    protected $fillable = [

        'nama_desa',        'nama_desa',

        'nama_kecamatan',         'nama_kecamatan', 

        'nama_petugas',        'nama_petugas',

        'desa_id',        'desa_id',

        'kecamatan_id',        'kecamatan_id',

        'is_active'        'is_active'

    ];    ];

        

    protected $casts = [    protected $casts = [

        'is_active' => 'boolean',        'is_active' => 'boolean',

    ];    ];

        

    /**    /**

     * Get the desa that owns the petugas monitoring.     * Get the desa that owns the petugas monitoring.

     */     */

    public function desa()    public function desa()

    {    {

        return $this->belongsTo(Desa::class);        return $this->belongsTo(Desa::class);

    }    }

        

    /**    /**

     * Get the kecamatan that owns the petugas monitoring.     * Get the kecamatan that owns the petugas monitoring.

     */     */

    public function kecamatan()    public function kecamatan()

    {    {

        return $this->belongsTo(Kecamatan::class);        return $this->belongsTo(Kecamatan::class);

    }    }

        

    /**    /**

     * Get musdesus uploads for this petugas monitoring area     * Get musdesus uploads for this petugas monitoring area

     */     */

    public function musdesusUploads()    public function musdesusUploads()

    {    {

        return $this->hasMany(Musdesus::class, 'desa_id', 'desa_id');        return $this->hasMany(Musdesus::class, 'desa_id', 'desa_id');

    }    }

        

    /**    /**

     * Check if this desa has uploaded musdesus     * Check if this desa has uploaded musdesus

     */     */

    public function hasUploadedMusdesus()    public function hasUploadedMusdesus()

    {    {

        return $this->musdesusUploads()->exists();        return $this->musdesusUploads()->exists();

    }    }

        

    /**    /**

     * Get latest musdesus upload     * Get latest musdesus upload

     */     */

    public function latestMusdesusUpload()    public function latestMusdesusUpload()

    {    {

        return $this->musdesusUploads()->latest()->first();        return $this->musdesusUploads()->latest()->first();

    }    }

}}space App\Models;

use Illuminate\Database\Eloquent\Model;

class PetugasMonitoring extends Model
{
    //
}
