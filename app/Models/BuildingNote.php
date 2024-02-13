<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BuildingNote extends Model
{
    use HasFactory;

    protected $tables = "building_notes";
    protected $fillable = ['name', 'building_id', 'amount', 'created_at'];

    public function building(){
        return $this->hasMany(Building::class,'id', 'building_id');
    }

    public function installments(){
        return $this->hasMany(BuildingInstallment::class, 'note_id');
    }


    public function expenses(){
        return $this->hasMany(AdditionalAmount::class, 'note_id');
    }

    public function installmentsTotal(){
        $installment = $this->hasMany(BuildingInstallment::class, 'note_id')->sum('amount');
        return $installment;
    }
}
