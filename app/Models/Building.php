<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Building extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'isSold', 'amount', 'created_at'];
    public function installment(){

    }

    // public function expenses(){
    //     return $this->hasMany(AdditionalAmount::class, 'building_id');
        
    // }

    // public function installments(){
    //     return $this->hasMany(BuildingInstallment::class, 'building_id');
    // }

    // public function refunds(){
    //     return $this->hasMany(BuildingRefund::class, 'building_id');
    // }
}
