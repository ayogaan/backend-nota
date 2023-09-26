<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    use HasFactory;
    protected $fillable = ['supplier_id', 'project_id', 'is_pay_later'];
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'id_notes')
        ->join('goods', 'transactions.id_good', '=', 'goods.id')
        ->select('transactions.*', 'goods.name as good_name');
    }

    public function installments()
    {
        return $this->hasMany(InstallmentTransaction::class, 'note_id');
    }


    
}
